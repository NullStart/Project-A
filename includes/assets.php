<?php

/**
 * handles image paths for game assets.
 * uses a placeholder when an image is missing.
 */

function getAssetImage($folder, $name){
    if (empty($name)){
        return 'assets/other/null.png';
    }
    $path = 'assets/' . $folder . '/' . $name . '.png';
    if (!is_file(__DIR__ . '/../' . $path)){
        return 'assets/other/null.png';
    }
    return $path;
}