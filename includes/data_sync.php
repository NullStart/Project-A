<?php
/**
 * handles synchronization between database records and CSV game data.
 * only public game definitions are included in the synchronization process.
 */

function getGameDataTables(){
    return [
        'classes',
        'items',
        'zones',
        'monsters',
        'zone_monsters',
        'monster_drops',
        'skills',
        'class_skills',
        'monster_skills'
    ];
}

/**
 * exports public game definitions from the database to CSV files.
 *
 * @param mysqli $conn database connection
 * @return array number of exported records for each table
 */
function exportGameData($conn){
    $data_dir = __DIR__ . '/../data';
    $tables = getGameDataTables();
    $counts = [];

    foreach ($tables as $table){
        $path = $data_dir . '/' . $table . '.csv';

        $sql = "SELECT *
                FROM `$table`
                ORDER BY 1, 2";

        $result = mysqli_query($conn, $sql);
        $fields = mysqli_fetch_fields($result);

        $header = [];

        foreach ($fields as $field){
            $header[] = $field->name;
        }

        $file = fopen($path . '.tmp', 'w');

        if ($file === false){
            throw new RuntimeException("Cannot write $table.csv");
        }

        try {
            if (fputcsv($file, $header, ',', '"', '') === false){
                throw new RuntimeException("Cannot write CSV header");
            }

            $count = 0;

            while ($row = mysqli_fetch_row($result)){
                if (fputcsv($file, $row, ',', '"', '') === false){
                    throw new RuntimeException("Cannot write CSV record");
                }

                $count++;
            }
        }
        finally {
            fclose($file);
        }

        if (!rename($path . '.tmp', $path)){
            throw new RuntimeException("Cannot replace $table.csv");
        }

        mysqli_free_result($result);

        $counts[$table] = $count;
    }

    return $counts;
}