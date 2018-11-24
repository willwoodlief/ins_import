<?php
global $mydb;
if (empty($mydb)) {
    $mydb = null;
}

require_once __DIR__. '/../constants.php';
require_once ROOT_PATH . '/vendor/autoload.php';
require_once LIB_PATH . '/CurlHelper.php';
require_once LIB_PATH . '/DBSelector.php';
require_once LIB_PATH . '/ErrorLogger.php';
require_once LIB_PATH . '/JsonHelperHelper.php';
require_once LIB_PATH . '/mydb.php';
require_once INCLUDE_PATH . '/functions.php';

use Symfony\Component\Yaml\Exception\ParseException;

use Symfony\Component\Yaml\Yaml;

try {
    $file_path = ROOT_PATH . '/config.yaml';
    if (! is_readable($file_path)) {
        throw new Exception("Cannot find config.yaml at $file_path");
    }
    $configs = Yaml::parseFile($file_path);
    define('DB_USER',$configs['database_user']);
    define('DB_PASSWORD',$configs['database_pw']);
    define('DB_NAME',$configs['database_name']);
    define('DB_HOST',$configs['database_host']);
    define('DB_CHARSET',$configs['database_charset']);
    define('DEFAULT_DB_NICKNAME','ins');
    $mydb = DBSelector::getConnection(DEFAULT_DB_NICKNAME);

    define('TARGET_USERNAME',$configs['target_username']);
    define('TARGET_PASSWORD',$configs['target_password']);
    define('TARGET_BROKER_ID',$configs['target_broker_id']);

    define('TARGET_CORP_ID',$configs['target_corp_id']);
    define('TARGET_AGENT_ID',$configs['target_agent_id']);




} catch (ParseException $exception) {
    ErrorLogger::saveException($exception);
}
catch (Exception $e) {
    ErrorLogger::saveException($e);
}