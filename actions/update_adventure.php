<?php
/**
 * handles manual adventure updates.
 * settles battle progress since the previous update
 * and redirects the player back to the adventure page.
 */

 // TODO: Automatic battle update
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/adventure_request.php';

try {


    updateAdventure(
        $conn,
        $character_id,
        $user_id
    );

} catch (Throwable $e) {
    error_log($e->getMessage());
    exit('Unable to update adventure.');
}
mysqli_close($conn);
header('Location: ../adventure.php?character_id='
       . $character_id);

exit();