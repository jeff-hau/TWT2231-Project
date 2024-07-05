<?php
session_start();
require_once '../auth_log.php'; 

$username = $_SESSION['username'] ?? 'Unknown';
$login_time = $_SESSION['login_time'] ?? time();
$session_duration = time() - $login_time;

$hours = floor($session_duration / 3600);
$minutes = floor(($session_duration % 3600) / 60);
$seconds = $session_duration % 60;

$duration_string = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

log_auth_event($username, 'LOGOUT', "- Session duration: $duration_string");

session_destroy();

header('Location: ../index.php');
exit;
?>