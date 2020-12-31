<?php

function exec_async($command) {
	if (PHP_OS !== 'WIN32' && PHP_OS !== 'WINNT') {
		exec($command . ' >/dev/null 2>&1 &');
	} else {
		$fp = popen('start "" ' . $command, 'r');
		pclose($fp);
	}
}



function rmdirAll($dir) {
	$res = glob($dir.'/{*,.[!.]*,..?*}', GLOB_BRACE);
 
	foreach ($res as $f) {
		if (is_file($f)) {
			unlink($f);
		} else {
			rmdirAll($f);
		}
	}
	rmdir($dir);
}


/**
 * Get build directories
 *
 * @return array
 */
function getBuildDirs() {
    $currentDir = getcwd();
    $dirs = scandir('build');
    
    $result = [];
    foreach($dirs as $dir){
        if(in_array($dir, ['.', '..'])){
            continue;
        }
        $result[] = $dir;
    }

    return $result;
}
