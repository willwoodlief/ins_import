<?php

require_once 'includes/head.php';
global $mydb;

try {
    $sql = "select count(*) as c from raw_user_data where lname = 'unknown';";

    $res = $mydb->execSQL($sql, null, MYDB::RESULT_SET);
    $count = $res[0]->c;
    $number_batches = floor($count / 100);
    $sql = "select id,fname from raw_user_data where lname = 'unknown' order by id asc limit ?,100;";
    for ($i = 0; $i < $number_batches; $i++) {
        $res = $mydb->execSQL($sql, ['i', $i * 100], MYDB::RESULT_SET, '@sey@name_batch');
        if (!$res) {
            break;
        }
        foreach ($res as $row) {
            $first_name = $row->fname;
            $id = $row->id;
            $words = explode(' ', $first_name);
            if (count($words) < 2) {continue;}

            $last_name = $words[count($words) - 1];
            if (strlen($last_name) <= 3) { continue;}


            print "[$id/$i] $first_name -> $last_name\n";
        }
    }
} catch (Exception $e) {
    ErrorLogger::saveException($e);
    print (string) $e;
}