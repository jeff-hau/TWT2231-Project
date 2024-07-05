<?php
// auth_log.php

function log_auth_event($username, $event, $additional_info = '') {
    $log_file = __DIR__ . '/auth.log';
    $timestamp = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')); // Using Kuala Lumpur timezone
    $formatted_timestamp = $timestamp->format('Y-m-d H:i:s T');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $log_entry = "$formatted_timestamp - $username - $event - IP: $ip_address $additional_info\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}


?>