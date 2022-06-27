<?php

include_once("helper.php");

const PHP_VERSIONS = ["7.3", "7.4"];

const DATABASES = ["mysql", "mariadb", "sqlsrv"];

const IMGORE_FILES = [
    "mysql" => [
        'docker-compose.sqlsrv.yml',
        'docker-compose.mariadb.yml',
    ],
    "mariadb" => [
        'docker-compose.sqlsrv.yml',
        'docker-compose.mysql.yml',
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
    "mariadb" => [
        'sqlsrv',
    ],
    "sqlsrv" => [
        'mysql',
    ],
];

const REPLACES = [
    'php_version' => 'replacePhpVersion',
    'apt_get_extend' => 'replaceAptGetExtend',
    'php_test_path' => 'replacePhpTestPath',
    'php_test_args' => 'replacePhpTestArgs',
    'php_composer_for_test' => 'replaceComposerForTest',
    'php_remove_env' => 'replaceRemoveEnv',
    'composer_require_exment' => 'replaceComposerRequireExment',
    'package_provider_name' => 'replacePackageProviderName',
];


$buildBasePath = path_join(dirname(__FILE__), 'build');
if(!file_exists($buildBasePath)){
    mkdir($buildBasePath);
}
else{
    $buildFiles = scandir('build');
    foreach($buildFiles as $buildFile){
        if(in_array($buildFile, ['.', '..'])){
            continue;
        }
    
        rmdirAll($buildBasePath . '/' . $buildFile);
    }
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
                    $fileContent = str_replace('$#{' . $replaceKey . '}', $replaceFunc($phpVersion, $database), $fileContent);
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
        $result = <<<EOT
# Append ODBC Driver
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
  && curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
  && apt-get update \
  && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools \
  && apt-get install -y unixodbc-dev libgssapi-krb5-2


# install driver

EOT;

$result .= $phpVersion == '7.2' ? 'RUN pecl install sqlsrv-5.8.1 && pecl install pdo_sqlsrv-5.8.1' : 'RUN pecl install sqlsrv && pecl install pdo_sqlsrv';
$result .= ' && docker-php-ext-enable sqlsrv && docker-php-ext-enable pdo_sqlsrv';
        return $result;
    }

    return 'RUN apt-get install -y default-mysql-client && docker-php-ext-install pdo_mysql';
}


function replaceComposerForTest($phpVersion, $database)
{
    $argvs = getArgvs();
    if(!isset($argvs['test']) || !boolval($argvs['test'])){
        return null;
    }

    return <<<EOT
# Execute for test
ARG DB_CONNECTION
ARG DB_HOST
ARG DB_PORT
ARG DB_DATABASE
ARG DB_USERNAME
ARG DB_PASSWORD
ARG APP_URL

ENV APP_URL=\${APP_URL} DB_CONNECTION=\${DB_CONNECTION} DB_HOST=\${DB_HOST} DB_PORT=\${DB_PORT} DB_DATABASE=\${DB_DATABASE} DB_USERNAME=\${DB_USERNAME} DB_PASSWORD=\${DB_PASSWORD}
# Now only support ja and Tokyo
ENV APP_LOCALE=ja APP_TIMEZONE=Asia/Tokyo

RUN composer require symfony/css-selector=~4.2 && composer require laravel/browser-kit-testing=~5.2 && composer require dms/phpunit-arraysubset-asserts=~0.1 && composer require laravel/socialite=~5.1 && php artisan passport:keys
RUN composer require --dev nunomaduro/larastan=^0.5 && composer require pragmarx/google2fa && composer require simplesoftwareio/simple-qrcode=^2.0.0 && composer require aacotroneo/laravel-saml2 && composer require league/flysystem-sftp=~1.0 && composer require league/flysystem-aws-s3-v3=~1.0 && composer require league/flysystem-azure-blob-storage=~0.1.6

COPY ./volumes/test.sh /var/www/exment
RUN chmod -R +x /var/www/exment/test.sh
EOT;
}


function replaceRemoveEnv($phpVersion, $database)
{
    $argvs = getArgvs();
    if(isset($argvs['test']) && boolval($argvs['test'])){
        return null;
    }

    return <<<EOT
RUN rm /var/www/exment/.env
EOT;
}

function replaceComposerRequireExment($phpVersion, $database)
{
    $argvs = getArgvs();
    if(!isset($argvs['test']) || !boolval($argvs['test'])){
        return 'RUN COMPOSER_MEMORY_LIMIT=-1 composer require exceedone/exment';
    }

    $provider = replacePackageProviderName($phpVersion, $database);
    return 'RUN COMPOSER_MEMORY_LIMIT=-1 composer require ' . $provider . '/exment=${EXMENT_VERSION}';
}

function replacePackageProviderName($phpVersion, $database)
{
    $argvs = getArgvs();
    if(!isset($argvs['provider'])){
        return 'exceedone';
    }
    else{
        return $argvs['provider'];
    }
}



function replacePhpTestPath($phpVersion, $database)
{
    $argvs = getArgvs();

    if(!isset($argvs['test']) || !boolval($argvs['test'])){
        return '- ./php/volumes/.env:/var/www/exment/.env';
    }
    return null;
}


function replacePhpTestArgs($phpVersion, $database)
{
    $argvs = getArgvs();

    if(!isset($argvs['test']) || !boolval($argvs['test'])){
        return null;
    }

    if ($database == 'sqlsrv') {
    return <<<EOT
        - DB_CONNECTION=$database
        - DB_HOST=$database
        - DB_PORT=1433
        - DB_DATABASE=exment_database
        - DB_USERNAME=sa
        - DB_PASSWORD=\${EXMENT_DOCKER_SQLSRV_ROOT_PASSWORD}
        - APP_URL=http://localhost:\${EXMENT_DOCKER_HTTP_PORTS}
EOT;
    }
    return <<<EOT
        - DB_CONNECTION=$database
        - DB_HOST=$database
        - DB_PORT=3306
        - DB_DATABASE=\${EXMENT_DOCKER_MYSQL_DATABASE}
        - DB_USERNAME=\${EXMENT_DOCKER_MYSQL_USER}
        - DB_PASSWORD=\${EXMENT_DOCKER_MYSQL_PASSWORD}
        - APP_URL=http://localhost:\${EXMENT_DOCKER_HTTP_PORTS}
EOT;
}





// common funcs ----------------------------------------------------

function getArgvs(){
    global $argv;

    $result = [];
    foreach($argv as $a){
        if(strpos($a, '--') === 0){
            $split = explode('=', str_replace('--', '', $a));
            $result[$split[0]] = count($split) > 1 ? $split[1] : true;
        }
    }

    return $result;
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
