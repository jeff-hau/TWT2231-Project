<?php
include '../config.php';

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get product ID from query parameters
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product info including category, type, and supplier
$product_info_sql = "SELECT p.product_name, p.quantity, p.upc, p.description, p.price, 
                            p.manufacturing_date, p.expired_date, p.measurement, 
                            p.requires_prescription, mc.category_name, 
                            mt.type_name, s.supplier_name
                     FROM product p
                     LEFT JOIN medicine_category mc ON p.category_id = mc.id
                     LEFT JOIN medicine_type mt ON p.type_id = mt.id
                     LEFT JOIN supplier s ON p.supplier_id = s.id
                     WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $product_info_sql);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $product_info = mysqli_fetch_assoc($result);
    
    // Convert boolean to 'Yes' or 'No'
    $product_info['requires_prescription'] = $product_info['requires_prescription'] ? 'Yes' : 'No';
    
    // Format dates
    $product_info['manufacturing_date'] = date('Y-m-d', strtotime($product_info['manufacturing_date']));
    $product_info['expired_date'] = date('Y-m-d', strtotime($product_info['expired_date']));
    
    // Return product info as JSON
    header('Content-Type: application/json');
    echo json_encode($product_info);
} else {
    // Return error message as JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Product not found']);
}

// Close statement and connection
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>