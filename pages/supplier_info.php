<?php
include '../config.php';

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get supplier ID from query parameters
$supplier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch supplier info
$supplier_info_sql = "SELECT supplier_name, supplier_contact, supplier_address FROM supplier WHERE id = ?";
$stmt = mysqli_prepare($conn, $supplier_info_sql);
mysqli_stmt_bind_param($stmt, "i", $supplier_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$supplier_info = mysqli_fetch_assoc($result);

// Close connection
mysqli_close($conn);

// Return supplier info as JSON
header('Content-Type: application/json');
echo json_encode($supplier_info);
?>