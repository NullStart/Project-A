<?php

require_once __DIR__ . '/../includes/db.php';


//read monster drops

function getMonsterDrops($conn, $monster_id)
{
    $sql = "SELECT item_id, drop_chance,
                   min_quantity, max_quantity
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

//load zone monster drops

function loadZoneMonsterDrops($conn, $monsters)
{
    $monster_drops = [];

    foreach ($monsters as $monster) {

        $monster_id = $monster['id'];

        $monster_drops[$monster_id] =
            getMonsterDrops($conn, $monster_id);
    }

    return $monster_drops;
}

//Calculate EXP reward

function getExpReward($monster)
{
    return (int) $monster['exp_reward'];
}

//Calculate gold reward

function rollGoldReward($monster)
{
    return mt_rand(
        $monster['gold_min'],
        $monster['gold_max']
    );
}

//roll drop chance

function rollDrop($drop_chance)
{
    $random = mt_rand(1, 100000000);

    return $random <= $drop_chance * 100000000;
}

//Generate monster loot

function rollMonsterDrops($drops)
{
    $loot = [];

    foreach ($drops as $drop) {

        if (rollDrop($drop['drop_chance'])) {

            $item_id = $drop['item_id'];

            $quantity = mt_rand(
                $drop['min_quantity'],
                $drop['max_quantity']
            );

            $loot[$item_id] = $quantity;
        }
    }

    return $loot;
}

//Initialize rewards

function createRewards()
{
    return [
        'exp' => 0,
        'gold' => 0,
        'items' => [],
        'kills' => 0
    ];
}

//Calculate monster rewards

function getMonsterRewards($monster, $monster_drops)
{
    $monster_id = $monster['id'];

    $drops = $monster_drops[$monster_id] ?? [];

    return [
        'exp' => getExpReward($monster),
        'gold' => rollGoldReward($monster),
        'items' => rollMonsterDrops($drops),
        'kills' => 1
    ];
}

//total rewards

function mergeRewards($total, $reward)
{
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

//Calculate expected gold

function getExpectedGold($monster)
{
    return (
        $monster['gold_min'] +
        $monster['gold_max']
    ) / 2;
}

//Calculate expected drops

function getExpectedDrops($drops)
{
    $loot = [];

    foreach ($drops as $drop) {

        $item_id = $drop['item_id'];

        $average_quantity = (
            $drop['min_quantity'] +
            $drop['max_quantity']
        ) / 2;

        $loot[$item_id] =
            $drop['drop_chance'] * $average_quantity;
    }

    return $loot;
}

//Calculate expected monster rewards

function getExpectedMonsterRewards(
    $monster, $monster_drops
)
{
    $monster_id = $monster['id'];

    $drops = $monster_drops[$monster_id] ?? [];

    return [
        'exp' => getExpReward($monster),
        'gold' => getExpectedGold($monster),
        'items' => getExpectedDrops($drops),
        'kills' => 1
    ];
}