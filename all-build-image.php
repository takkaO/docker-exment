<?php
include_once("helper.php");

const EXMENT_DOCKER_HTTP_PORTS=[8081, 8089];
const EXMENT_DOCKER_MYSQL_PORTS=[13307, 13326];
const EXMENT_DOCKER_SQLSRV_PORTS=[11433, 11452];

/**
 * Replace env port
 *
 * @return void
 */
function replaceEnvPort($dbName, $dirPath, $index){
    if($dbName == 'sqlsrv'){
        $key = 'EXMENT_DOCKER_SQLSRV_PORT=';
        $dbPorts = range(EXMENT_DOCKER_SQLSRV_PORTS[0], EXMENT_DOCKER_SQLSRV_PORTS[1]);
    }else{
        $key = 'EXMENT_DOCKER_MYSQL_PORT=';
        $dbPorts = range(EXMENT_DOCKER_MYSQL_PORTS[0], EXMENT_DOCKER_MYSQL_PORTS[1]);
    }
    $webPorts = range(EXMENT_DOCKER_HTTP_PORTS[0], EXMENT_DOCKER_HTTP_PORTS[1]);
    
    $envfile = glob($dirPath.'/.env')[0];
    $text = file_get_contents($envfile);

    $text = preg_replace('/' . $key . '\d+/iu', $key . $dbPorts[$index], $text);
    $text = preg_replace('/EXMENT_DOCKER_HTTP_PORTS=\d+/iu', 'EXMENT_DOCKER_HTTP_PORTS=' . $webPorts[$index], $text);

    file_put_contents($envfile, $text);
}

$currentDir = getcwd();
$dirs = getBuildDirs();
foreach($dirs as $index => $dir){
    $fullPath = $currentDir . '/build/' . $dir;
    chdir($fullPath);
    $splits = explode("_", $dir);
    $dbName = $splits[1];

    replaceEnvPort($dbName, $fullPath, $index);

    // call docker-compose
    exec_async("docker-compose -f docker-compose.{$dbName}.yml -f docker-compose.yml build --no-cache");
}

