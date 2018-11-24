<?php


require_once realpath(dirname(__FILE__)) . '/mydb.php';
require_once realpath(dirname(__FILE__)) . '/JsonHelperHelper.php';


class DBSelector {


    /**
     * @var array of string , these are the allowed values to pass to
     * @see DBSelector::getConnection()
     */
    protected static $db_names = [
    	'ins', # use the wordpress db
    ];
    protected static $cache = [];

    /**
     * @return array
     * @throws SQLException if a connection is tried and it does not work
     */
    public static function getAllConnections() {
        $ret = [];
        foreach(self::$db_names as $name ) {
            $ret[$name] = self::getConnection($name);
        }
        return $ret;
    }
    //returns stored connection, may created it first

    /**
     * Gets the database connection for the connection
     * @param string $what <p>
     *   @see MYDB::getMySqliDatabase() for details of keys in the array used in the code
     * @uses DBSelector::$db_names
     *</p>
     * @return object|MYDB
     * @throws SQLException
     */
    public static function getConnection($what) {
        if (isset(self::$cache[$what])) {
            $mysqli =  self::$cache[$what]->getDBHandle();
            return new MYDB($mysqli); //smart pointer, db will only go out of scope when the static class def does
        }

        $mydb = null;
        if (in_array($what, self::$db_names)) {
            switch ($what) {
                case 'ins':
                    $dbstuff = ['username'=>DB_USER,'password'=>DB_PASSWORD,
                        'database_name'=>DB_NAME,'host'=>DB_HOST,
                        'character_set'=>DB_CHARSET];
                    $mydb = new MYDB(null,$dbstuff,true);
                    break;

                default:
                    throw new SQLException("Cannot create new db connection from name of [$what]");
            }
        } else {
            throw new SQLException("Cannot create new db connection from name of [$what]");
        }

        self::$cache[$what] = $mydb;
        return $mydb;
    }




}