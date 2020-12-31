<?php
include_once("helper.php");

$currentDir = getcwd();

$dirs = getBuildDirs();
foreach($dirs as $dir){
    $fullPath = $currentDir . '/build/' . $dir;
    chdir($fullPath);
    $splits = explode("_", $dir);

    exec_async("docker-compose -f docker-compose.{$splits[1]}.yml -f docker-compose.yml down");
}
