<?php
/**
 * handles requests to start an adventure.
 * checks character status and zone requirements before creating
 * and saving a new adventure state.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/adventure_request.php';

$zone_id = filter_input(
    INPUT_POST, 'zone_id', FILTER_VALIDATE_INT
);

if (!$zone_id || $zone_id < 1) {
    exit('Invalid zone.');
}

mysqli_begin_transaction($conn);

try {
    lockCharacter($conn, $character_id, $user_id);

    $character = getCharacter($conn, $character_id, $user_id);
    $adventure = getAdventureState($conn, $character_id);
    // Prevent the occurrence of overwriting an ongoing adventure
    if ($adventure && $adventure['status'] === 'active') {

        mysqli_commit($conn);

        header('Location: ../adventure.php?character_id='. $character_id);
        exit();
    }

    if ($character['current_hp'] <= 0) {
        throw new RuntimeException('Character is dead.');
    }

    $zone = getZone($conn, $zone_id);

    if (!$zone) {
        throw new RuntimeException('Invalid zone');
    }

    if ($character['level'] < $zone['required_level']) {
        throw new RuntimeException(
            'Character level is too low!'
        );
    }

    $monsters = getZoneMonsters($conn, $zone_id);

    if (empty($monsters)) {
        throw new RuntimeException(
            'No monsters in this zone！'
        );
    }

    $stats = getCharacterStats($conn, $character);
    $player = createPlayerBattleState($character, $stats);
    // simulation time starts at zero for each new adventure
    $state = createAdventureState($player, 0);
    $state = startEncounter($state, $zone, $monsters, 0);
    
    // use UTC with millisecond precision for offline calculations
    $result = mysqli_query(
        $conn,
        "SELECT UTC_TIMESTAMP(3) AS server_time"
    );

    $row = mysqli_fetch_assoc($result);
    $current_time = $row['server_time'];
    mysqli_free_result($result);
    $state_data = json_encode($state, JSON_THROW_ON_ERROR);
    // replace the previous adventure state when starting a new one
    $sql = "INSERT INTO adventure_state(
                character_id, zone_id, status,
                started_at, last_update_at,
                next_encounter_at, total_kills,
                total_battles, state_data)
            VALUES (?, ?, 'active', ?, ?,NULL, 0, 0, ?)
            ON DUPLICATE KEY UPDATE
                zone_id = VALUES(zone_id),
                status = 'active',
                started_at = VALUES(started_at),
                last_update_at = VALUES(last_update_at),
                next_encounter_at = NULL,
                total_kills = 0,
                total_battles = 0,
                state_data = VALUES(state_data)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "iisss",
        $character_id,
        $zone_id,
        $current_time,
        $current_time,
        $state_data
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    echo htmlspecialchars($e->getMessage());
    echo '<a href="../adventure.php?character_id=' . $character_id . '">Go Back</a>';
    exit();
}
mysqli_close($conn);

header('Location: ../adventure.php?character_id='
       . $character_id);
exit();