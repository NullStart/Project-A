<?php
/**
 * handles character revival after death.
 * restores HP and MP and removes the previous adventure state
 * while preserving character progress and inventory.
 */

 //TODO: may be changed to automatic resurrection?
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/adventure_request.php';

mysqli_begin_transaction($conn);

try {

    lockCharacter($conn, $character_id, $user_id);

    $character = getCharacter(
        $conn, $character_id, $user_id
    );

    $adventure = getAdventureState(
        $conn, $character_id
    );
    // only revive dead characters outside active adventures
    if ($character['current_hp'] <= 0 && (!$adventure || $adventure['status'] !== 'active')) {

        $stats = getCharacterStats($conn, $character);

        $character['current_hp'] = $stats['max_hp'];
        $character['current_mp'] = $stats['max_mp'];

        saveCharacter($conn, $character);
        // clear previous battle state and adventure counters
        $sql = "DELETE FROM adventure_state
                WHERE character_id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt, "i", $character_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

} catch (Throwable $e) {

    mysqli_rollback($conn);

    error_log($e->getMessage());

    exit('Unable to revive character.');
}

mysqli_close($conn);

header('Location: ../adventure.php?character_id='
       . $character_id);

exit();