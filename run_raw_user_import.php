<?php

require_once 'includes/head.php';
global $mydb;
$limit = 2;
try {
    $sql = "select count(*) as c from raw_user_data r
            left join exported_user_data d ON d.raw_user_data_id = r.id
            left join export_log e ON e.exported_data_id = d.id
            left join export_log e2 ON e2.exported_data_id = d.id AND e2.is_success > 0
            WHERE (e.id IS NULL) OR (e2.id IS NULL);";

    $res = $mydb->execSQL($sql, null, MYDB::RESULT_SET);
    $count = $res[0]->c;
    $number_batches = floor($count / 100);
    $sql = "select r.id from raw_user_data r
            left join exported_user_data d ON d.raw_user_data_id = r.id
            left join export_log e ON e.exported_data_id = d.id
            left join export_log e2 ON e2.exported_data_id = d.id AND e2.is_success > 0
            WHERE (e.id IS NULL) OR (e2.id IS NULL)
            limit ?,100
            ;";
    $total_count = 0;
    for ($i = 0; $i < $number_batches; $i++) {
        $res = $mydb->execSQL($sql, ['i', $i * 100], MYDB::RESULT_SET, '@sey@raw_batch_to_try');
        if (!$res) {
            break;
        }
        $count = 0;
        foreach ($res as $row) {
            $first_name = $row->fname;
            $id = $row->id;
            $export_log_id = do_one_export($id);

            print "batch $i, sub batch $count, total $total_count --> Export Log ID is $export_log_id \n";
            $count ++;
            $total_count++;
            if ($limit) {
                if ($limit >= $total_count) {
                    break;
                }
            }
        }

        if ($limit >= $total_count) {
            break;
        }
    }
} catch (Exception $e) {
    ErrorLogger::saveException($e);
    print (string) $e;
}