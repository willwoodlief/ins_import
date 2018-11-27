<?php

require_once 'includes/head.php';

try {
	global $mydb;

	//get all the things that do not have the api call done
	$sql = "select e.id as export_log_id,d.raw_user_data_id as raw_id, d.member_id, d.UniqueID from export_log e
                   inner join exported_user_data d ON d.id = e.exported_data_id
                   LEFT JOIN api_update_log a ON a.export_log_id = e.id  AND a.is_success > 0
					where a.id IS NULL AND e.is_success > 0 order by e.id;";

	$res = $mydb->execSQL($sql);
	if (empty($res)) {
		die("Nothing to process\n");
	}

	foreach ($res as $row) {
		$raw_id = $row->raw_id;
		$member_id = $row->member_id;
		$export_log_id = $row->export_log_id;
		$unique_id = $row->UniqueID;
		$extra_error_log_id = null;
		$raw_data = get_raw_data($raw_id);
		$exported_data = build_export_data($raw_data,'80a97ab4c572065e40d6e1080c4fa655');

		$extra_http_code = 0;
		try {
			$extra_response = add_extra_data( $member_id, $exported_data, $extra_http_code );
		} catch (Exception $f) {
			$error_log_info = ErrorLogger::saveException($f);
			$extra_error_log_id = $error_log_info['id'];
		}
		$api_log_id = create_api_log($export_log_id,$extra_http_code,$extra_response,$extra_error_log_id);
		print "api log id is $api_log_id\n";
	}

	print "finished\n";


} catch (Exception $e) {
	ErrorLogger::saveException($e);
    print (string) $e;
}
