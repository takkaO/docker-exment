<?php
include_once("helper.php");

$currentDir = getcwd();

$resultLogPath = $currentDir . '/logs';
if(!file_exists($resultLogPath)){
    mkdir($resultLogPath);
}

$nowLogPath = $resultLogPath . '/' . date('YmdHis');
if(!file_exists($nowLogPath)){
    mkdir($nowLogPath);
}


$dirs = getBuildDirs();
foreach($dirs as $dir){
    $fullPath = $currentDir . '/build/' . $dir;
    chdir($fullPath);

    // copy log file
    $logfiles = glob($fullPath.'/php/volumes/logs/{*,.[!.]*,..?*}', GLOB_BRACE);
    foreach($logfiles as $logfile){
        $filename = pathinfo($logfile, PATHINFO_FILENAME);
        $extension = pathinfo($logfile, PATHINFO_EXTENSION);
        $copyFilePath = "{$nowLogPath}/{$filename}_{$dir}.{$extension}";
        copy($logfile, $copyFilePath);
    }
}