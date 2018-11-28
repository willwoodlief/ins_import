<?php
set_time_limit(0); //long running program

require_once 'includes/head.php';
global $mydb;
$longopts  = array(
	"start::",     // Required value
	"end::",
	"count::"
);
$options = getopt('', $longopts);
$count = $start = $end = 0;

if (array_key_exists('count',$options)) {
	$count = intval($options['count']);
	$limit = 0;
	print "Doing $count members\n";
} else {
	if (!array_key_exists('start',$options)) {
		die("need --start [number] --end [number");
	}

	if (!array_key_exists('end',$options)) {
		die("need --start [number] --end [number");
	}
	$start = intval($options['start']);
	$end =   intval($options['end']);

	if ($start > $end) {
		die("start needs to be less than end");
	}

	$limit = 5000;

}






try {
	if ($start && $end) {
		$sql = "select count(*) as c from raw_user_data r
            left join exported_user_data d ON d.raw_user_data_id = r.id
            left join export_log e ON e.exported_data_id = d.id
            left join export_log e2 ON e2.exported_data_id = d.id AND e2.is_success > 0
            WHERE ((e.id IS NULL) OR (e2.id IS NULL)) and (r.id between $start and $end) ;";

		$res = $mydb->execSQL($sql, null, MYDB::RESULT_SET);
		$count = $res[0]->c;
		print "Doing $count members\n";
	}




    $number_batches = floor($count / 100) + 1;
	$extra_where = '';
	if ($start && $end) {
		$extra_where = "and (r.id between $start and $end)";
	}

    $sql = "select DISTINCT r.id from raw_user_data r
            left join exported_user_data d ON d.raw_user_data_id = r.id
            left join export_log e ON e.exported_data_id = d.id
            left join export_log e2 ON e2.exported_data_id = d.id AND e2.is_success > 0
            WHERE ((e.id IS NULL) OR (e2.id IS NULL)) $extra_where
            limit ?,?
            ;";
    $total_count = 0;
    for ($i = 0; $i < $number_batches; $i++) {
	    $calc_count = 100;
	    if ($i == $number_batches - 1) {
		    $calc_count = $count - $total_count;
	    }

        $res = $mydb->execSQL($sql, ['ii', $i * 100,$calc_count], MYDB::RESULT_SET, '@sey@raw_batch_to_try');
        if (!$res) {
        	print "Batch [$i] is Empty, going to next batch\n";
            break;
        }
        $sub_count = 0;
        foreach ($res as $row) {
          //  $first_name = $row->fname;
            $id = $row->id;
            $export_log_id = do_one_export($id);

            $ts = udate('Y-m-d H:i:s.u T');
            print "$ts]] batch $i, sub batch $sub_count, total $total_count --> Export Log ID is $export_log_id, raw is $id \n";
	        $sub_count ++;
            $total_count++;
            if ($limit) {
                if ($total_count >= $limit ) {
                    break;
                }
            }
        }

        if ($total_count >= $limit) {
            break;
        }
    }
} catch (Exception $e) {
    ErrorLogger::saveException($e);
    print (string) $e;
}