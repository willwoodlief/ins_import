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
    $string = $raw_data['fname'] . ' ' . $raw_data['mname'] . ' ' . $raw_data['cubed_id'];
    return md5($string);
}

/**
 * @param array $data
 * @param integer $raw_id
 * @throws SQLException
 */
function save_exported_data($data,$raw_id) {
    global $mydb;
    $data['raw_user_data_id'] = $raw_id;
    $mydb->insert('exported_user_data',$data);
}

/**
 * @param $exported_data_id
 * @param $response
 * @throws SQLException
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
    $mydb->execSQL($sql,['si',$member_id,$exported_data_id],'@sey@add_member_id');
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
function create_export_log($response,$http_code,$exported_data_row,$error_log_id,$notes=null) {
    global $mydb;
    //determine if success or not
    $b_success = 1;
    //here are all the things that can make it not successful
    if ($error_log_id) {
        $b_success = 0;
    }
    if ($http_code == 400) {
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
        MYDB::RESULT_SET,
                [
                  'iiiiss',
                    $export_data_id,
                    $error_log_id,
                    $http_code,
                    $b_success,
                    $response,
                    $notes


                ],
                '@sey@create_export_log:insert_export_log'
        );
    return $export_log_id;

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
        $error_log_id = null;
        $response = null;
        $response = insert_member($exported_data,$http_code);
        $b_good_call = check_for_response_success($response) ;
        if ($b_good_call) {
            add_member_id($response,$exported_data_row->id);
        }

    } catch (Exception $e) {
        $error_log_id = ErrorLogger::saveException($e);
    }


    //make the export log
    $export_log_id = create_export_log($response,$http_code,$exported_data_row,$error_log_id);
    return $export_log_id;



}

function convert_export_to_to_call_data($data) {
    $ret = $data;
    unset($ret['id']);
    unset($ret['raw_user_data_id']);
    unset($ret['created_at']);
    unset($ret['updated_at']);
    unset($ret['member_id']);
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
    $ret['sourcedetail'] = "Agent Cubed Id: " . $raw_object->cubed_id;

    $raws = get_object_vars($raw_object);
    if (!$raws) {
        throw new ExportException("Cannot get get properties from ". print_r($raw_object,true));
    }

    /**
     * cubed_id,member_id,Corpid,Agent,UniqueID,source,sourcedetail
     * Firstname,Middlename,Lastname,Gender,DOB,email,Phone1,Phone2,ADDRESS1,ADDRESS2,CITY,STATE,ZIPCODE,
     */
    foreach ($raws as $raw) {
        if (empty($raw_object->$raw) || empty(trim($raw_object->$raw))) {
            continue;
        }
        switch ($raw) {
            case 'fname': {
                $ret['Firstname'] = $raw_object->$raw;
                break;
            }
            case 'mname':{
                $ret['Middlename'] = $raw_object->$raw;
                break;
            }
            case 'lname':{
                $ret['Lastname'] = $raw_object->$raw;
                break;
            }
            case 'gender':{
                $ret['Gender'] = $raw_object->$raw;
                break;
            }
            case 'dob':{
                //same format (MM/DD/YY)
                $ret['DOB'] = $raw_object->$raw;
                break;
            }
            case 'age':{
                //age is not a field in the import
                break;
            }
            case 'email':{
                $ret['Email'] = $raw_object->$raw;
                break;
            }
            case 'spouse_dob':{
                //not entering spouses at this point
                break;
            }
            case 'lead_phone':{
                $ret['Phone1'] = preg_replace("/[^0-9]/", "",$raw_object->$raw);
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
                $ret['Phone2'] = preg_replace("/[^0-9]/", "",$raw_object->$raw);
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
                $ret['ADDRESS1'] = $raw_object->$raw;
                break;
            }
            case 'address2':{
                $ret['ADDRESS2'] = $raw_object->$raw;
                break;
            }
            case 'city':{
                $ret['CITY'] = $raw_object->$raw;
                break;
            }
            case 'state':{
                $ret['STATE'] = $raw_object->$raw;
                break;
            }
            case 'postal':{
                $ret['ZIPCODE'] = $raw_object->$raw;
                break;
            }
            case 'lead_created_by':{
                //no way to transfer this right now
                break;
            }
            case 'do_not_call':{
                //no way to make note right now
                break;
            }
            case 'do_not_email':{
                //no way to make note right now
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
    return $ret;

}