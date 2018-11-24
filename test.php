<?php

require_once 'includes/head.php';

$member_id = 674251946;
$data = [
    "CORPID" => TARGET_CORP_ID,
    "AGENT" => TARGET_AGENT_ID,
    "LASTNAME" => "Smith",
    "ADDRESS1" => "5050 Sam Houston",
    "ADDRESS2" => "Apt 514",
    "CITY" => "Huntsville",
    "STATE" => "TX",
    "ZIPCODE" => 77340
];

try {
    $res = update_member($member_id,$data);
    print_r($res);
} catch (Exception $e) {
    print (string) $e;
}
