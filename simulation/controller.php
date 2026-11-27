<?php
/**
 * manages adventure simulation and database updates.
 * selects simulation algorithms based on elapsed time and saves
 * character progress, item rewards, and adventure states.
 * uses database transactions and row locks to prevent duplicate rewards.
 *
 * main functions:
 * - runSimulation()
 * - settleAdventure()
 * - updateAdventure()
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_once __DIR__ . '/exact.php';
require_once __DIR__ . '/event_jump.php';
require_once __DIR__ . '/statistical.php';

// thresholds in seconds: 60s and 30min
function selectAlgorithm($elapsed_time){
    if ($elapsed_time < 60) {
        return 'exact';
    }

    if ($elapsed_time < 1800) {
        return 'event_jump';
    }

    return 'statistical';
}

/**
 * @param string $last_update_at previous settlement timestamp
 * @param string $current_time current server timestamp
 * @return float elapsed time in seconds
 */
function getElapsedTime($last_update_at, $current_time){
    $last = new DateTimeImmutable($last_update_at);

    $current = new DateTimeImmutable($current_time);

    // unix timestamps with fractional seconds
    $elapsed = (float) $current->format('U.u') - (float) $last->format('U.u');
    // prevent negative elapsed time and keep millisecond precision
    return round(max(0, $elapsed), 3);
}

// lock the adventure row until the transaction ends
function getAdventureState($conn, $character_id){
    $sql = "SELECT *
            FROM adventure_state
            WHERE character_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "i", $character_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $adventure = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $adventure;
}

/**
 * dispatches adventure simulation to the selected algorithm.
 *
 * @param mysqli $conn database connection
 * @param array $state current adventure state
 * @param array $zone zone settings
 * @param array $monsters available monsters
 * @param array $monster_drops drop tables indexed by monster id
 * @param float $elapsed_time elapsed time in seconds
 * @return array simulation results and performance counters
 */
function runSimulation($conn, $state, $zone, $monsters, $monster_drops, $elapsed_time){
    $algorithm = selectAlgorithm($elapsed_time);

    if ($algorithm === 'exact') {

        return simulateExact(
            $conn,
            $state,
            $zone,
            $monsters,
            $monster_drops,
            $elapsed_time
        );
    } elseif ($algorithm === 'event_jump') {
        return simulateEventJump(
            $conn,
            $state,
            $zone,
            $monsters,
            $monster_drops,
            $elapsed_time
        );
    } else {
        return simulateStatistical(
            $conn,
            $state,
            $zone,
            $monsters,
            $monster_drops,
            $elapsed_time
        );
    }
}

function saveCharacter($conn, $player){
    $sql = "UPDATE characters
            SET level = ?,
                exp = ?,
                gold = ?,
                current_hp = ?,
                current_mp = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "iiiiii",
        $player['level'],
        $player['exp'],
        $player['gold'],
        $player['current_hp'],
        $player['current_mp'],
        $player['id']
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

function getItemType($conn, $item_id){
    $sql = "SELECT type_id
            FROM items
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $item_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $item = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$item) {
        return null;
    }

    return (int) $item['type_id'];
}



function saveStackableItem($conn, $character_id, $item_id, $quantity){
    // increase quantity if the item already exists
    $sql = "INSERT INTO inventory_stacks
                (character_id, item_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = quantity + ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "iiii",
        $character_id,
        $item_id,
        $quantity,
        $quantity
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

function saveEquipment($conn, $character_id, $item_id, $quantity){

    $sql = "INSERT INTO equipment_instances
                (character_id, item_id)
            VALUES (?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "ii",
        $character_id,
        $item_id
    );
    // create a separate instance for each equipment item
    for ($i = 0; $i < $quantity; $i++) {

        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}

function saveItemRewards($conn, $character_id, $items){
    foreach ($items as $item_id => $quantity) {

        if ($quantity <= 0) {
            continue;
        }
        // 1 = equipment, 2 = consumable, 3 = material
        $type = getItemType($conn, $item_id);

        if ($type === 1) {
            saveEquipment(
                $conn,
                $character_id,
                $item_id,
                $quantity
            );
        } elseif ($type === 2 || $type === 3) {
            saveStackableItem(
                $conn,
                $character_id,
                $item_id,
                $quantity
            );
        } else {
            throw new RuntimeException(
                'Invalid item type.'
            );
        }
    }
}

/**
 * saves adventure progress and the current simulation state.
 *
 * @param mysqli $conn database connection
 * @param int $character_id character id
 * @param array $state current adventure state
 * @param string $current_time settlement timestamp
 * @param string $started_at adventure start timestamp
 * @return void
 */
function saveAdventureState($conn, $character_id, $state,$current_time, $started_at){
    $status = $state['status'];

    $total_kills = $state['total_kills'];

    $total_battles = $state['total_battles'];
    // preserve unfinished battles for later simulation
    $state_data = json_encode($state, JSON_THROW_ON_ERROR);

    $next_encounter_at = null;
    // convert relative simulation time to a database timestamp
    if ($state['next_encounter_at'] !== null) {

        $start = new DateTimeImmutable($started_at);

        $milliseconds = (int) round($state['next_encounter_at'] * 1000);

        $next = $start->modify('+' . $milliseconds . ' milliseconds');

        $next_encounter_at = $next->format('Y-m-d H:i:s.v');
    }

    $sql = "UPDATE adventure_state
            SET status = ?,
                last_update_at = ?,
                next_encounter_at = ?,
                total_kills = ?,
                total_battles = ?,
                state_data = ?
            WHERE character_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "sssiisi",
        $status,
        $current_time,
        $next_encounter_at,
        $total_kills,
        $total_battles,
        $state_data,
        $character_id
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);
}

/**
 * validates session ownership and locks user and character rows.
 * must be called inside a transaction.
 *
 * @param mysqli $conn database connection
 * @param int $character_id character id
 * @param int $user_id owner id
 * @return void
 * @throws RuntimeException invalid session or character
 */
function lockCharacter($conn, $character_id, $user_id){
    // lock order: user, character, adventure
    $sql = "SELECT id
            FROM users
            WHERE id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$user ||
        getUserId() != $user_id ||
        !validateSession($conn)) {

        throw new RuntimeException('Invalid session.');
    }

    $sql = "SELECT id
            FROM characters
            WHERE id = ? 
            AND user_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $character_id, $user_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $character = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$character) {
        throw new RuntimeException('Invalid character.');
    }
}

/**
 * calculates adventure progress since the previous settlement.
 * saves character progress, rewards, and adventure state.
 * requires an active transaction and a locked character.
 *
 * @param mysqli $conn database connection
 * @param int $character_id character id
 * @return array|null simulation results or no pending settlement
 */
function settleAdventure($conn, $character_id){

    $adventure = getAdventureState($conn, $character_id);

    if (!$adventure || $adventure['status'] !== 'active') {

        return null;
    }
    // use server time instead of client time
    $result = mysqli_query($conn,"SELECT UTC_TIMESTAMP(3) AS server_time");

    $row = mysqli_fetch_assoc($result);

    $current_time = $row['server_time'];

    mysqli_free_result($result);

    $elapsed_time = getElapsedTime($adventure['last_update_at'],$current_time);

    if ($elapsed_time <= 0) {
        return null;
    }

    $state = json_decode(
        $adventure['state_data'],
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $zone = getZone($conn, $adventure['zone_id']);

    $monsters = getZoneMonsters($conn, $adventure['zone_id']);

    if (!$zone || empty($monsters)) {

        throw new RuntimeException(
            'Invalid adventure zone.'
        );
    }

    $monster_drops = loadZoneMonsterDrops($conn, $monsters);

    $result = runSimulation(
        $conn,
        $state,
        $zone,
        $monsters,
        $monster_drops,
        $elapsed_time
    );

    $state = $result['state'];

    $rewards = $result['rewards'];
    // exp and gold already applied during simulation
    saveCharacter($conn, $state['player']);

    saveItemRewards(
        $conn,
        $character_id,
        $rewards['items']
    );

    saveAdventureState(
        $conn,
        $character_id,
        $state,
        $current_time,
        $adventure['started_at']
    );

    return $result;
}

/**
 * settles adventure progress within a single database transaction.
 *
 * @param mysqli $conn database connection
 * @param int $character_id character id
 * @param int $user_id owner id
 * @return array|null simulation results
 * @throws Throwable settlement failure
 */
function updateAdventure($conn, $character_id, $user_id){

    mysqli_begin_transaction($conn);

    try {
        lockCharacter($conn, $character_id, $user_id);
        $result = settleAdventure($conn, $character_id);
        mysqli_commit($conn);
        return $result;
    } catch (Throwable $e) {
        // rollback all changes if settlement fails
        mysqli_rollback($conn);

        throw $e;
    }
}