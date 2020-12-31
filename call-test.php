<?php
include_once("helper.php");

$currentDir = getcwd();

$dirs = getBuildDirs();
foreach($dirs as $dir){
    $fullPath = $currentDir . '/build/' . $dir;
    chdir($fullPath);
    $splits = explode("_", $dir);

    // remove log file
    $logfiles = glob($fullPath.'/php/volumes/logs/{*,.[!.]*,..?*}', GLOB_BRACE);
    foreach($logfiles as $logfile){
        unlink($logfile);
    }

    exec("docker-compose -f docker-compose.{$splits[1]}.yml -f docker-compose.yml up -d");

    sleep(10);

    // get container list
    $output = [];
    exec("docker ps -f \"name={$dir}_php\" -q", $output);

    exec_async("docker exec {$output[0]} /var/www/exment/test.sh");
}
