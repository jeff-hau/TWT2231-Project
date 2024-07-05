<?php
include '../config.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: ../index.php');
    exit;
}

// Create connection to MySQL server
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get current date
$current_date = date('Y-m-d');

// Fetch expired products
$expired_sql = "SELECT id, upc, product_name, quantity, expired_date FROM product WHERE expired_date < '$current_date' ORDER BY expired_date";
$expired_result = mysqli_query($conn, $expired_sql);

// Fetch products expiring in 30 days
$expiring_sql = "SELECT id, upc, product_name, quantity, expired_date FROM product WHERE expired_date BETWEEN '$current_date' AND DATE_ADD('$current_date', INTERVAL 30 DAY) ORDER BY expired_date";
$expiring_result = mysqli_query($conn, $expiring_sql);

// Fetch all products
$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC';
$all_products_sql = "SELECT id, upc, product_name, quantity, expired_date FROM product ORDER BY expired_date $sort_order";
$all_products_result = mysqli_query($conn, $all_products_sql);

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory System</title>
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <style>
        .container {
            display: flex;
            width: 100%;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: #fff;
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        h2 {
            text-align: left;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
        }
        th {
            background-color: #f2f2f2;
        }
        .expired {
            background-color: #ffcccb;
        }
        .expiring {
            background-color: #ffdab9;
        }
        .popup {
            display: none;
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 60%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .popup .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .product-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 1.1em;
        }
        .product-info-item {
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }
        .product-info-item strong {
            color: #007bff;
            font-size: 1.1em;
        }
        .btn-ok {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn-ok:hover {
            background-color: #0056b3;
        }
        .sort-container {
            margin-bottom: 10px;
        }
        .sort-container select {
            padding: 5px;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-content">
            <h1>Expired List</h1>

            <h2>Expired Products</h2>
            <table>
                <thead>
                    <tr>
                        <th>UPC</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                        <th>Expired Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($expired_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($expired_result)) { ?>
                            <tr class="expired" onclick="showProductInfo(<?php echo $row['id']; ?>)">
                                <td><?php echo $row['upc']; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo $row['expired_date']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No expired products</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Products Expiring in 30 Days</h2>
            <table>
                <thead>
                    <tr>
                        <th>UPC</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($expiring_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($expiring_result)) { ?>
                            <tr class="expiring" onclick="showProductInfo(<?php echo $row['id']; ?>)">
                                <td><?php echo $row['upc']; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo $row['expired_date']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No expiring products</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>All Products</h2>
            <div class="sort-container">
                <label for="sort-select">Sort by expiry date:</label>
                <select id="sort-select" onchange="changeSort(this.value)">
                    <option value="asc" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    <option value="desc" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                </select>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>UPC</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($all_products_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($all_products_result)) { ?>
                            <tr onclick="showProductInfo(<?php echo $row['id']; ?>)">
                                <td><?php echo $row['upc']; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo $row['expired_date']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No products found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="popup" id="product-info-popup">
        <button class="close-btn" onclick="document.getElementById('product-info-popup').style.display='none'">&times;</button>
        <div id="product-info-content"></div>
    </div>

    <script>
        function showProductInfo(productId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'product_info.php?id=' + productId, true);
            xhr.onload = function () {
                if (xhr.status === 200) {
                    var productDetails = JSON.parse(xhr.responseText);
                    var popupContent = `
                    <h2>Product Info</h2>
                        <div class="product-info-grid">
                            <div class="product-info-item"><strong>UPC:</strong> ${productDetails.upc}</div>
                            <div class="product-info-item"><strong>Medicine Name:</strong> ${productDetails.product_name}</div>
                            <div class="product-info-item"><strong>Category:</strong> ${productDetails.category_name || 'N/A'}</div>
                            <div class="product-info-item"><strong>Type:</strong> ${productDetails.type_name || 'N/A'}</div>
                            <div class="product-info-item"><strong>Price:</strong> ${productDetails.price}</div>
                            <div class="product-info-item"><strong>Quantity:</strong> ${productDetails.quantity}</div>
                            <div class="product-info-item"><strong>Manufacturing Date:</strong> ${productDetails.manufacturing_date}</div>
                            <div class="product-info-item"><strong>Expiry Date:</strong> ${productDetails.expired_date}</div>
                            <div class="product-info-item"><strong>Measurement:</strong> ${productDetails.measurement}</div>
                            <div class="product-info-item"><strong>Requires Prescription:</strong> ${productDetails.requires_prescription}</div>
                            <div class="product-info-item" style="grid-column: 1 / -1;"><strong>Supplier:</strong> ${productDetails.supplier_name || 'N/A'}</div>
                            <div class="product-info-item" style="grid-column: 1 / -1;"><strong>Description:</strong> ${productDetails.description}</div>
                        </div>
                        <button class="btn-ok" onclick="document.getElementById('product-info-popup').style.display='none'">Close</button>
                    `;
                    document.getElementById('product-info-content').innerHTML = popupContent;
                    document.getElementById('product-info-popup').style.display = 'block';
                }
            };
            xhr.send();
        }

        function changeSort(sortOrder) {
            window.location.href = 'expired_list.php?sort=' + sortOrder;
        }
    </script>
</body>
</html>