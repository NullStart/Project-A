<?php
/**
 * handles requests to stop an adventure.
 * settles remaining battle progress before stopping the adventure
 * and preserves rewards already earned by the character.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/adventure_request.php';

mysqli_begin_transaction($conn);

try {

    lockCharacter($conn, $character_id, $user_id);
    // settle pending rewards before stopping
    settleAdventure($conn, $character_id);

    $adventure = getAdventureState(
        $conn, $character_id
    );
    // preserve dead status if the character dies during settlement
    if ($adventure && $adventure['status'] === 'active') {

        $state = json_decode(
            $adventure['state_data'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $state['status'] = 'idle';
        // discard the unfinished encounter
        $state['battle'] = null;
        $state['next_encounter_at'] = null;
        $state['player']['next_action_at'] = null;

        saveAdventureState(
            $conn,
            $character_id,
            $state,
            $adventure['last_update_at'],
            $adventure['started_at']
        );
    }
    mysqli_commit($conn);

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log($e->getMessage());
    exit('Unable to stop adventure.');
}
mysqli_close($conn);
header('Location: ../adventure.php?character_id='. $character_id);

exit();