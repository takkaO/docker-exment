<?php

const PHP_VERSIONS = ["7.2", "7.3", "7.4"];

const DATABASES = ["mysql", "sqlsrv"];

const IMGORE_FILES = [
    "mysql" => [
        'docker-compose.sqlsrv.yml',
    ],
    "sqlsrv" => [
        'docker-compose.mariadb.yml',
        'docker-compose.mysql.yml',
    ],
];

const IMGORE_DIRS = [
    "mysql" => [
        'sqlsrv',
    ],
    "sqlsrv" => [
        'mysql',
    ],
];

const REPLACES = [
    'php_version' => 'replacePhpVersion',
    'apt_get_extend' => 'replaceAptGetExtend',
];


$buildBasePath = path_join(dirname(__FILE__), 'build');
if(!file_exists($buildBasePath)){
    mkdir($buildBasePath);
}


foreach(PHP_VERSIONS as $phpVersion){
    foreach(DATABASES as $database){
        // create directory
        $dirName = 'php' . str_replace('.', '', $phpVersion) . '_' . $database;
        $buildDirFullPath = path_join($buildBasePath, $dirName);
        $srcDirFullPath = dirname(__FILE__) . '/src/';

        copyFile($srcDirFullPath, $buildDirFullPath, $phpVersion, $database);
    }
}


function copyFile($srcDirFullPath, $buildDirFullPath, $phpVersion, $database){
    if(!file_exists($buildDirFullPath)){
        mkdir($buildDirFullPath);
    }

    // copy files
    $files = scandir($srcDirFullPath); 
    foreach($files as $file)
    {
        if(in_array($file, ['.', '..'])){
            continue;
        }
        
        $srcFileFullPath = path_join($srcDirFullPath, $file);
        $buildFileFullPath = path_join($buildDirFullPath, $file);

        if (is_file($srcFileFullPath))
        {
            if(in_array($file, IMGORE_FILES[$database])){
                continue;
            }

            // replace value if stub
            $pathinfo = pathinfo($srcFileFullPath);
            if(array_key_exists('extension', $pathinfo) && $pathinfo['extension'] == 'stub'){
                $fileContent = file_get_contents($srcFileFullPath);
                foreach(REPLACES as $replaceKey => $replaceFunc)
                {
                    $fileContent = str_replace('${' . $replaceKey . '}', $replaceFunc($phpVersion, $database), $fileContent);
                }
                file_put_contents(path_join($buildDirFullPath, $pathinfo['filename']), $fileContent);
            }

            // else, copy
            else{
                copy($srcFileFullPath, $buildFileFullPath);
            }
        }
        else
        {
            if(in_array($file, IMGORE_DIRS[$database])){
                continue;
            }

            copyFile($srcFileFullPath, $buildFileFullPath, $phpVersion, $database);
        }
    }
}

function replacePhpVersion($phpVersion, $database){
    return $phpVersion;
}


function replaceAptGetExtend($phpVersion, $database)
{
    if ($database == 'sqlsrv') {
        return <<<EOT
# Append ODBC Driver
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
  && curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools \
  && apt-get install -y unixodbc-dev libgssapi-krb5-2


# install driver
RUN pecl install sqlsrv && pecl install pdo_sqlsrv \
  && docker-php-ext-enable sqlsrv \
  && docker-php-ext-enable pdo_sqlsrv
EOT;
    }

    return 'RUN apt-get install -y default-mysql-client && docker-php-ext-install pdo_mysql';
}



/**
 * Join FilePath.
 */
function path_join(...$pass_array)
{
    return join_paths('/', $pass_array);
}


/**
 * Join path using trim_str.
 */
function join_paths($trim_str, $pass_array)
{
    $ret_pass   =   "";

    foreach ($pass_array as $value) {
        if (empty($value)) {
            continue;
        }
        
        if (is_array($value)) {
            $ret_pass = $ret_pass.$trim_str.join_paths($trim_str, $value);
        } elseif ($ret_pass == "") {
            $ret_pass   =   $value;
        } else {
            $ret_pass   =   rtrim($ret_pass, $trim_str);
            $value      =   ltrim($value, $trim_str);
            $ret_pass   =   $ret_pass.$trim_str.$value;
        }
    }
    return $ret_pass;
}
