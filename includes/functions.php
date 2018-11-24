<?php

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
 * @return array|int|string|null
 * @throws CurlHelperException
 * @throws JsonHelperException
 */
function update_member($member_id,$data) {
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
 * @return array|int|string|null
 * @throws CurlHelperException
 * @throws JsonHelperException
 */
function insert_member($data) {
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