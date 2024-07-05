<?php
include '../config.php';

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$term = isset($_GET['term']) ? $_GET['term'] : '';

$sql = "SELECT product_name, upc FROM product WHERE product_name LIKE ? OR upc LIKE ? LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
$search_term = "%$term%";
mysqli_stmt_bind_param($stmt, "ss", $search_term, $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode($products);
?>