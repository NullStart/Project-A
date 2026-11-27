<?php
/**
 * handles experience, gold, and item rewards from monster kills.
 * provides random rewards for regular combat and expected rewards
 * for statistical simulation.
 */
require_once __DIR__ . '/../includes/db.php';



function getMonsterDrops($conn, $monster_id){
    $sql = "SELECT item_id, drop_chance,min_quantity, max_quantity
            FROM monster_drops
            WHERE monster_id = ?
            ORDER BY item_id";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $monster_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $drops = [];
    while ($drop = mysqli_fetch_assoc($result)) {
        $drops[] = $drop;
    }
    mysqli_stmt_close($stmt);
    return $drops;
}


function loadZoneMonsterDrops($conn, $monsters){
    $monster_drops = [];
    foreach ($monsters as $monster) {
        $monster_id = $monster['id'];
        $monster_drops[$monster_id] = getMonsterDrops($conn, $monster_id);
    }
    return $monster_drops;
}



function getExpReward($monster){
    return (int) $monster['exp_reward'];
}



function rollGoldReward($monster){
    return mt_rand($monster['gold_min'], $monster['gold_max']);
}

// probability precision of 8 decimal places

function rollDrop($drop_chance){
    $random = mt_rand(1, 100000000);
    return $random <= $drop_chance * 100000000;
}

// independent drop roll for each item

function rollMonsterDrops($drops){
    $loot = [];
    foreach ($drops as $drop) {
        if (rollDrop($drop['drop_chance'])) {
            $item_id = $drop['item_id'];
            $quantity = mt_rand($drop['min_quantity'],$drop['max_quantity']);
            $loot[$item_id] = $quantity;
        }
    }
    return $loot;
}



function createRewards(){
    return ['exp' => 0,'gold' => 0, 'items' => [], 'kills' => 0];
}

/**
 * @param array $monster defeated monster data
 * @param array $monster_drops drop tables indexed by monster id
 * @return array exp, gold, items, and kill count
 */

function getMonsterRewards($monster, $monster_drops){
    $monster_id = $monster['id'];
    $drops = $monster_drops[$monster_id] ?? [];
    return [
        'exp' => getExpReward($monster),
        'gold' => rollGoldReward($monster),
        'items' => rollMonsterDrops($drops),
        'kills' => 1
    ];
}

/**
 * combines rewards from multiple monster kills.
 *
 * @param array $total accumulated rewards
 * @param array $reward newly obtained rewards
 * @return array combined rewards
 */

function mergeRewards($total, $reward){
    $total['exp'] += $reward['exp'];
    $total['gold'] += $reward['gold'];
    $total['kills'] += $reward['kills'];

    foreach ($reward['items'] as $item_id => $quantity) {
        if (!isset($total['items'][$item_id])) {
            $total['items'][$item_id] = 0;
        }
        $total['items'][$item_id] += $quantity;
    }
    return $total;
}

//uniform gold distribution

function getExpectedGold($monster){
    return ($monster['gold_min'] + $monster['gold_max']) / 2;
}



function getExpectedDrops($drops){
    $loot = [];
    foreach ($drops as $drop) {
        $item_id = $drop['item_id'];
        $average_quantity = ($drop['min_quantity'] + $drop['max_quantity']) / 2;
        // expected quantity = drop probability * average quantity
        $loot[$item_id] =$drop['drop_chance'] * $average_quantity;
    }
    return $loot;
}

/**
 * calculates expected rewards for statistical simulation.
 *
 * @param array $monster monster data
 * @param array $monster_drops drop tables indexed by monster id
 * @return array expected rewards with possible fractional quantities
 */
function getExpectedMonsterRewards($monster, $monster_drops){
    $monster_id = $monster['id'];
    $drops = $monster_drops[$monster_id] ?? [];
    return [
        'exp' => getExpReward($monster),
        'gold' => getExpectedGold($monster),
        'items' => getExpectedDrops($drops),
        'kills' => 1
    ];
}