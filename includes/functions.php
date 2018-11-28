<?php

class ExportException extends Exception {}
/**
 * @param string $message
 * @param mixed|null $other
 * @throws Exception
 */
function export_log( $message, $other = null ) {
    global $module_path;

    $stamp =  date("Y-m-d H:i:s");
    $message = $stamp . ' ' . $message;
    if ( $other !== null ) {
        $message .= ':' . print_r( $other, true );
    }
    $log_file = $module_path . "process_log.txt";

    $fp = fopen( $log_file, "a+" );
    if ( ! $fp ) {
        throw new Exception( "could not open log file [$log_file]" );
    }
    $getLock = flock( $fp, LOCK_EX );

    if ( $getLock ) {  // acquire an exclusive lock

        fwrite( $fp, $message . "\n" );
        fflush( $fp );            // flush output before releasing the lock
        flock( $fp, LOCK_UN );    // release the lock
    } else {
        throw new Exception( "could not get lock for log file" );
    }

    fclose( $fp );

}

/**
 * @param $member_id
 * @param $data
 * @param $http_code
 * @return array|int|string|null
 * @throws CurlHelperException
 * @throws JsonHelperException
 */
function update_member($member_id,$data,&$http_code) {
    $curl = new CurlHelper();
    //$basic_auth = 'Basic bWFqZWVtQGhlYWx0aHloYWxvLmNvbTpSYXZpbmdmYW5zMTIzKSg=';
    $user = TARGET_USERNAME;
    $password = TARGET_PASSWORD;
    $headers = [
        'Authorization: Basic '. base64_encode("$user:$password"),
        'Content-Type: application/json'
    ];

    $payload_json = JsonHelper::toStringAgnostic($data);
    $broker_id = TARGET_BROKER_ID;
    $url = "https://api.1administration.com/v1/$broker_id/member/$member_id.json";
    $response = $curl->curl_helper($url,$http_code,$payload_json,true,'json',false,false,$headers);
    return $response;
}

/**
 * @param $member_id
 * @param $data
 * @param $http_code
 * @return array
 * @throws CurlHelperException
 */
function update_member_with_api($member_id,$data,&$http_code) {
	$curl = new CurlHelper();
	//$curl->b_debug = true;
	//$basic_auth = 'Basic bWFqZWVtQGhlYWx0aHloYWxvLmNvbTpSYXZpbmdmYW5zMTIzKSg=';
	$user = TARGET_USERNAME;
	$password = TARGET_PASSWORD;
	$headers = [
		'Authorization: Basic '. base64_encode("$user:$password"),
		'Content-Type: application/x-www-form-urlencoded'
	];

	$required = [
		'CORP_ID' => TARGET_CORP_ID ,
		'AGENT_ID' => TARGET_AGENT_ID,
		'UNIQUE_ID' => $member_id,
	];



	//make into form post string
	$payload = array_merge($required,$data);
	$hkeys = [];
	foreach ($payload as $key => $value) {
		$hkeys[] = "$key=$value";
	}
	//$payload_json = JsonHelper::toStringAgnostic($payload);
	$payload_string = implode('&',$hkeys);
	$url = "https://www.enrollment123.com/gateway/member.cfm";
	$response = $curl->curl_helper($url,$http_code,$payload_string,true,'json',false,false,$headers);
	return $response;
}

/**
 * @param $data
 * @param integer $http_code
 * @return array|int|string|null
 * @throws CurlHelperException
 * @throws JsonHelperException
 */
function insert_member($data,&$http_code) {
    $curl = new CurlHelper();
    $user = TARGET_USERNAME;
    $password = TARGET_PASSWORD;
    $headers = [
        'Authorization: Basic '. base64_encode("$user:$password"),
        'Content-Type: application/json'
    ];

    $payload_json = JsonHelper::toStringAgnostic($data);
    $broker_id = TARGET_BROKER_ID;
    $url = "https://api.1administration.com/v1/$broker_id/member/0.json";
    $response = $curl->curl_helper($url,$http_code,$payload_json,true,'json',false,false,$headers);
    return $response;
}


/**
 * @param int $raw_id
 * @return bool|integer - false or the id of the export log
 * @throws SQLException
 */
function has_raw_been_done_successfully($raw_id) {
    global $mydb;

    $sql = "select e.id from export_log e
            inner join exported_user_data d ON d.id = e.exported_data_id
            where d.raw_user_data_id = ? AND e.is_success > 0;";

    $res = $mydb->execSQL($sql,['i',$raw_id],MYDB::RESULT_SET,'@sey@has_raw_been_done_successfully');
    if (empty($res)) {
        return false;
    }
    return $res[0]->id;
}

/**
 * @param integer $raw_id
 * @return string|null
 * @throws SQLException
 */
function get_unique_id($raw_id) {
    global $mydb;

    $sql = "select d.UniqueID from exported_user_data d
            where d.raw_user_data_id = ? ";

    $res = $mydb->execSQL($sql,['i',$raw_id],MYDB::RESULT_SET,'@sey@get_unique_id');
    if (empty($res)) {
        return null;
    }
    return $res[0]->UniqueID;
}


/**
 * @param integer $raw_id
 * @return object|null
 * @throws SQLException
 */
function get_exported_row_from_raw($raw_id) {
    global $mydb;

    $sql = "select * from exported_user_data d
            where d.raw_user_data_id = ? ";

    $res = $mydb->execSQL($sql,['i',$raw_id],MYDB::RESULT_SET,'@sey@get_exported_row_from_raw');
    if (empty($res)) {
        return null;
    }
    return $res[0];
}

/**
 * @param integer $raw_id
 * @return object
 * @throws ExportException
 * @throws SQLException
 */
function get_raw_data($raw_id) {
    global $mydb;
    $sql = "SELECT * from raw_user_data where id = ?";
    $res = $mydb->execSQL($sql,['i',$raw_id],MYDB::RESULT_SET,'@sey@do_one_export::get_raw_data');
    if (empty($res)) {
        throw new ExportException("Could not find the raw data id of [$raw_id]");
    }
    return $res[0];
}

function generate_unique_id($raw_data) {
    $string = $raw_data->fname . ' ' . $raw_data->mname . ' ' . $raw_data->cubed_id;
    return md5($string);
}

/**
 * @param array $data
 * @param integer $raw_id
 * @return integer
 * @throws SQLException
 */
function save_exported_data($data,$raw_id) {
    global $mydb;
    $data['raw_user_data_id'] = $raw_id;
    unset($data['UseInternalIDAsMemberID']); //this is not in the db
    return $mydb->insert('exported_user_data',$data);
}

/**
 * @param $exported_data_id
 * @param $response
 * @throws SQLException
 * @return string
 * @throws ExportException
 */
function add_member_id($exported_data_id,$response) {
    global $mydb;
    if (array_key_exists('MEMBER',$response)) {
        if (array_key_exists('ID',$response['MEMBER']) ) {
            $member_id = $response['MEMBER']['ID'];
        } else {
            throw new ExportException("Cannot find MEMBER ID in the response: ". print_r($response));
        }
    } else {
        throw new ExportException("Cannot find MEMBER  in the response: ". print_r($response));
    }
    $sql = "update exported_user_data SET member_id = ? WHERE id = ?;";
    $mydb->execSQL($sql,['si',$member_id,$exported_data_id],MYDB::LAST_ID,'@sey@add_member_id');
	return $member_id;
}


/**
 * creates the api log after getting success and error message from response
 * returns the api log id
 * @param $export_log_id
 * @param $http_code
 * @param $response
 * @param $error_log_id
 * @return integer
 * @throws SQLException
 */
function create_api_log($export_log_id,$http_code,$response,$error_log_id) {
	global $mydb;


	$error_message = null;
	$response_success = check_for_extra_response_success($response,$http_code,$error_message);
	if ($response_success) {
		$response_success = 1;
	} else {
		$response_success = 0;
	}


	$sql = "insert into 
              api_update_log(export_log_id,is_success,http_code,error_message,response,error_log_id)
            VALUES (?,?,?,?,?,?);";

	$api_log_id = $mydb->execSQL(
		$sql,
		[
			'iiissi',
			$export_log_id,
			$response_success,
			$http_code,
			$error_message,
			$response,
			$error_log_id
		],
		MYDB::LAST_ID,

		'@sey@create_export_log:create_api_log'
	);
	return $api_log_id;
}
/**
 * @param $response
 * @param $http_code
 * @param $exported_data_row
 * @param $error_log_id
 * @param null $notes
 * @return integer
 * @throws SQLException
 */
function create_export_log($response,$http_code,$exported_data_row,$error_log_id, $notes=null) {

    global $mydb;
    //determine if success or not
    $b_success = 1;
    //here are all the things that can make it not successful
    if ($error_log_id) {
        $b_success = 0;
    }
    if ($http_code >= 400) {
        $b_success = 0;
    }

	if ($http_code == 0) {
		$b_success = 0;
	}


    $is_successful = check_for_response_success($response);
    if (!$is_successful) {
        $b_success = 0;
    }


    $export_data_id = $exported_data_row->id;

    $sql = "insert into 
              export_log(exported_data_id,error_log_id,http_response_code,is_success,json_response,notes)
            VALUES (?,?,?,?,?,?);";

    $export_log_id = $mydb->execSQL(
                $sql,
			    [
				    'iiiiss',
				    $export_data_id,
				    $error_log_id,
				    $http_code,
				    $b_success,
				    $response,
				    $notes


			    ],
        MYDB::LAST_ID,

                '@sey@create_export_log:insert_export_log'
        );
    return $export_log_id;

}

/**
 * return true, and sets error message to null, if success
 * returns false and puts error message if fail
 * @param $response
 * @param $http_code
 * @param $error_message
 * @return bool
 */
function check_for_extra_response_success($response,$http_code,&$error_message) {
	$b_success = true;
	$error_message = null;
	if (empty($response)) {
		$b_success = false;
		$error_message = "empty response";
		return $b_success;
	}

	if ($http_code >= 400) {
		$b_success = false;
		$error_message = "http code >= 400";
	}
	// 1|674266968|||  ok
	// 0|CORP ID not found

	if (is_string($response)) {
		$whats = explode('|',$response);
		if (count($whats) < 2) {
			$b_success = false;
			$error_message = "could not parse the response of:$response ";
		} else {
			$con = intval($whats[0]);
			if (!$con) {
				$b_success = false;
				$error_message = $whats[1];
			}
		}
	}
	else {
		if ($b_success) {
			$error_message = "response is not a string";
			$b_success = false;
		}

	}

	return $b_success;
}

function check_for_response_success($response) {
    $b_success = true;
    if (is_array($response) ) {
        if (!array_key_exists('SUCCESS',$response)) {
            $b_success = false;
        }

        if (! $response['SUCCESS']) {
            $b_success = false;
        }

    } else {
        $b_success = false;
    }

    return $b_success;
}

/**
 *
 * @param integer $raw_id
 * @return int
 * @throws ExportException
 * @throws SQLException
 */
function do_one_export($raw_id) {

    //check to make sure raw id not already in table
    $b_already_done = has_raw_been_done_successfully($raw_id);
    if ($b_already_done) {
        throw new ExportException("Already did Export for this raw[$raw_id] export log [$b_already_done]");
    }

    //see if there is already an export row
    $exported_data_row = get_exported_row_from_raw($raw_id);

    if ($exported_data_row) {
        //already have the structure,
        $exported_data = convert_export_to_to_call_data($exported_data_row);
    } else {
        //get the raw data
        $raw_data = get_raw_data($raw_id);
        $unique_id = generate_unique_id($raw_data);
        $exported_data = build_export_data($raw_data,$unique_id);
        save_exported_data($exported_data,$raw_id);
        $exported_data_row = get_exported_row_from_raw($raw_id);
    }

    // do the request
    try {
	    $member_id = null;
	    $http_code = 0;
	    $extra_http_code = 0;
        $error_log_id = null;
        $response = null;
	    $extra_response = null;
	    $extra_error_log_id = null;

        $response = insert_member($exported_data,$http_code);
        $b_good_call = check_for_response_success($response) ;
        if ($b_good_call) {
            $member_id = add_member_id($exported_data_row->id,$response );
        }
		print "m-$member_id\n";
        if ($member_id) {
        	try {
		        $extra_response = add_extra_data( $member_id, $exported_data, $extra_http_code );
	        } catch (Exception $f) {
		        $error_log_info = ErrorLogger::saveException($f);
		        $extra_error_log_id = $error_log_info['id'];
	        }
        }


    } catch (Exception $e) {
        $error_log_info = ErrorLogger::saveException($e);
	    $error_log_id = $error_log_info['id'];
    }


    //make the export log
    $export_log_id = create_export_log($response,$http_code,$exported_data_row,$error_log_id);
    create_api_log($export_log_id,$extra_http_code,$extra_response,$extra_error_log_id);
    return $export_log_id;



}


function extract_extra_data($exported_data) {
	if (is_object($exported_data)) {
		$exported_data = (array) $exported_data;
	}
	$payload = [];
	$keys_to_check = ['DONOTCALL','EMAILOPTOUT','NOTE'];
	foreach ($keys_to_check as $index => $key) {
		if (!array_key_exists($key,$exported_data)) {
			//throw new ExportException("Extra data key [$key] not in $exported_data");
			continue;
		}
		if (empty($exported_data[$key])) {continue;}
		$payload[$key] = $exported_data[$key];
	}
	return $payload;
}

/**
 * @param $member_id
 * @param $exported_data
 * @param integer $http_code
 * @return array|null
 * @throws CurlHelperException
 */
function add_extra_data($member_id,$exported_data,&$http_code) {

	$payload = extract_extra_data($exported_data);
	if (empty($payload)) {return null;}
	return update_member_with_api($member_id,$payload,$http_code);
}

function convert_export_to_to_call_data($data) {
    $ret = clone $data;
    unset($ret->id);
    unset($ret->raw_user_data_id);
    unset($ret->created_at);
    unset($ret->updated_at);
    unset($ret->member_id);
    return $ret;
}

/**
 * @param object $raw_object
 * @param string $unique_id
 * @throws ExportException
 * @return array
 */
function build_export_data($raw_object,$unique_id) {
    $ret = [];
    $ret['Corpid'] = TARGET_CORP_ID;
    $ret['Agent'] = TARGET_AGENT_ID;
    $ret['UniqueID'] = $unique_id;
    $ret['UseInternalIDAsMemberID'] = 'N';
    $ret['source'] = 'AgentCubed';


    $raws = get_object_vars($raw_object);
    if (!$raws) {
        throw new ExportException("Cannot get get properties from ". print_r($raw_object,true));
    }

    /**
     * cubed_id,member_id,Corpid,Agent,UniqueID,source,sourcedetail
     * Firstname,Middlename,Lastname,Gender,DOB,email,Phone1,Phone2,ADDRESS1,ADDRESS2,CITY,STATE,ZIPCODE,
     */
    foreach ($raws as $prop => $raw) {
        if (empty($raw) || empty(trim($raw))) {
            continue;
        }
        switch ($prop) {
	        case 'id': {
	        	//do not process the automatic id pk
	        	break;
	        }
	        case 'record_count': {
	        	//do not process record count
	        	break;
	        }
	        case 'cubed_id' : {
		        $ret['sourcedetail'] = "Agent Cubed Id: " . $raw;
		        break;
	        }
            case 'fname': {
                $ret['Firstname'] = $raw;
                break;
            }
            case 'mname':{
                $ret['Middlename'] = $raw;
                break;
            }
            case 'lname':{
                $ret['Lastname'] = $raw;
                break;
            }
            case 'gender':{
                $ret['Gender'] = $raw;
                break;
            }
            case 'dob':{
                //same format (MM/DD/YY)
                $ret['DOB'] = $raw;
                break;
            }
            case 'age':{
                //age is not a field in the import
                break;
            }
            case 'email':{
                $ret['Email'] = $raw;
                break;
            }
            case 'spouse_dob':{
                //not entering spouses at this point
                break;
            }
            case 'lead_phone':{
                $ret['Phone1'] = preg_replace("/[^0-9]/", "",$raw);
                break;
            }
            case 'lead_phone_ext':{
                //no place for ext
                break;
            }
            case 'lead_phone_type':{
                //no place for type
                break;
            }
            case 'secondary_phone':{
                $ret['Phone2'] = preg_replace("/[^0-9]/", "",$raw);
                break;
            }
            case 'secondary_phone_ext':{
                //no place for ext
                break;
            }
            case 'secondary_phone_type':{
                //no place for type
                break;
            }
            case 'address1':{
                $ret['ADDRESS1'] = $raw;
                break;
            }
            case 'address2':{
                $ret['ADDRESS2'] = $raw;
                break;
            }
            case 'city':{
                $ret['CITY'] = $raw;
                break;
            }
            case 'state':{
                $ret['STATE'] = $raw;
                break;
            }
            case 'postal':{
                $ret['ZIPCODE'] = $raw;
                break;
            }
            case 'lead_created_by':{
                //no way to transfer this right now
                break;
            }
            case 'do_not_call':{
	            if ($raw == 'True') {
		            $ret['DONOTCALL'] = 'Y';
	            }
                break;
            }
            case 'do_not_email':{
            	if ($raw == 'True') {
		            $ret['EMAILOPTOUT'] = 'Y';
	            }
                break;
            }
            case 'do_not_mail':{
                //no way to make note right now
                break;
            }
            case 'age_precision':{
                break;
            }
            case 'mailing_city':{
                //no way to set at this point
                break;
            }
            case 'mailing_state':{
                //no way to set at this point
                break;
            }
            case 'mailing_postal':{
                //no way to set at this point
                break;
            }
            default : {
                throw new ExportException("Switch does not have this field: " . $raw);
            }
        }
    }

    if (!array_key_exists('Lastname',$ret)) {
        $ret['Lastname'] = 'unknown';
    }

	if (!array_key_exists('NOTE',$ret)) {
		$ret['NOTE'] = 'Customer Imported from AgentCubed';
	}
    return $ret;

}

function udate($format = 'u', $utimestamp = null) {
	if (is_null($utimestamp))
		$utimestamp = microtime(true);

	$timestamp = floor($utimestamp);
	$milliseconds = round(($utimestamp - $timestamp) * 1000000);

	return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}