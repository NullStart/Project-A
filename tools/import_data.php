<?php

/**
* generate a database seed file from the CSV game data
* and verify the CSV records and convert them into SQL INSERT statements
*/

$root = dirname(__DIR__);
$data_dir = $root . '/data';
$output = $root . '/database/seed.sql';


// Table import order

$tables = [
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

$text_columns = [
    'name',
    'description',
    'equip_slot',
    'effect_type',
    'target_type'
];

$nullable_columns = [
    'description',
    'equip_slot'
];


/**
 * Converts a CSV value into a valid SQL value.
 *
 * @param string $value CSV field value
 * @param string $column Column name
 * @param array $text_columns Text column names
 * @param array $nullable_columns Nullable column names
 * @return string Formatted SQL value
 * @throws RuntimeException Invalid or missing value
 */
function sqlValue($value, $column, $text_columns, $nullable_columns){
    $value = trim($value);
    if ($value === '') {
        if (in_array($column, $nullable_columns, true)) {
            return 'NULL';
        }
        throw new RuntimeException("Empty value in $column");
    }

    if (in_array($column, $text_columns, true)) {
        if (strpos($value, "\\") !== false) {
            return "CONVERT(X'" . bin2hex($value) . "' USING utf8mb4)";
        }
        return "'" . str_replace("'", "''", $value) . "'";
    }

    if (!preg_match('/^-?\d+(\.\d+)?$/D', $value)) {
        throw new RuntimeException(
            "Invalid number in $column: $value"
        );
    }
    return $value;
}


// SQL initialization

$sql = "USE project_demo;\n\n";
$sql .= "-- Generated from data/*.csv. Do not edit manually.\n\n";
$sql .= "SET NAMES utf8mb4;\n\n";
$sql .= "START TRANSACTION;\n\n";

try {
    // process CSV files
    foreach ($tables as $index => $table) {

        $path = $data_dir . '/' . $table . '.csv';
        $file = fopen($path, 'r');
        if ($file === false) {
            throw new RuntimeException("Cannot open $path");
        }
        try {
            $header = fgetcsv($file, 0, ',', '"', '');
            if ($header === false || $header === [null]) {
                throw new RuntimeException(
                    "Missing header in $table.csv"
                );
            }
            // remove UTF-8 BOM
            $header[0] = preg_replace('/^\xEF\xBB\xBF/','', $header[0]);
            $header = array_map('trim', $header);
            // validate column names
            foreach ($header as $column) {
                if (!preg_match('/^[a-z_][a-z0-9_]*$/D', $column)) {
                    throw new RuntimeException(
                        "Invalid column in $table.csv: $column"
                    );
                }
            }
            // check duplicate columns
            if (count(array_unique($header)) !== count($header)) {
                throw new RuntimeException(
                    "Duplicate columns in $table.csv"
                );
            }
            $rows = [];
            $record = 1;
            // read CSV records
            while (($row = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $record++;
                // skip empty records
                if ($row === [null]) {
                    continue;
                }
                // validate column count
                if (count($row) !== count($header)) {
                    throw new RuntimeException(
                        "Wrong column count in $table.csv, record $record"
                    );
                }
                $values = [];
                // convert CSV values
                foreach ($header as $i => $column) {
                    try {
                        $values[] = sqlValue(
                            $row[$i],
                            $column,
                            $text_columns,
                            $nullable_columns
                        );
                    } catch (RuntimeException $e) {
                        throw new RuntimeException(
                            "$table.csv, record $record: "
                            . $e->getMessage()
                        );
                    }
                }
                $rows[] = '(' . implode(', ', $values) . ')';
            }

        } finally {
            fclose($file);
        }
        // generate table heading
        $sql .= sprintf(
            "-- %02d %s\n\n",
            $index + 1,
            $table
        );
        // generate INSERT statement
        if (count($rows) > 0) {
            $columns = implode('`, `', $header);
            $sql .= "INSERT INTO `$table` (`$columns`)\n";
            $sql .= "VALUES\n";
            $sql .= implode(",\n", $rows) . ";\n\n";
        }
        echo "$table: " . count($rows) . " rows\n";
    }
    $sql .= "COMMIT;\n";
    // save generated SQL file
    if (file_put_contents($output, $sql) === false) {
        throw new RuntimeException("Cannot write seed.sql");
    }
    echo "\nseed.sql generated successfully.\n";
} catch (RuntimeException $e) {
    // report generation errors
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}