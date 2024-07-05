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

// Store the selected category
$selected_category_id = isset($_GET['category_id']) ? $_GET['category_id'] : null;
$selected_category_name = "Select Category";

// Fetch categories
$category_sql = "SELECT id, category_name FROM medicine_category";
$category_result = mysqli_query($conn, $category_sql);

// Create an array to store categories
$categories = array();
while ($row = mysqli_fetch_assoc($category_result)) {
    $categories[] = $row;
    if ($row['id'] == $selected_category_id) {
        $selected_category_name = $row['category_name'];
    }
}

// Handle form submission for adding category
$add_category_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_category_name'])) {
    $new_category_name = $_POST['new_category_name'];

    $insert_category_sql = "INSERT INTO medicine_category (category_name) VALUES ('$new_category_name')";

    try {
        if (mysqli_query($conn, $insert_category_sql)) {
            $add_category_message = "success";
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $add_category_message = "duplicate";
        } else {
            $add_category_message = "error";
        }
    }
}

// Fetch medicines for a selected category
$medicines = [];
if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];
    $medicines_sql = "SELECT id, product_name AS medicine_name, quantity, upc FROM product WHERE category_id = $category_id";
    $medicines_result = mysqli_query($conn, $medicines_sql);
    while ($row = mysqli_fetch_assoc($medicines_result)) {
        $medicines[] = $row;
    }
}

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
        .add-category-button {
            padding: 15px;
            font-size: 1.2rem;
            border: none;
            border-radius: 8px;
            background-color: #28a745;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
        }
        .add-category-button:hover {
            background-color: #218838;
        }
        .dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .dropdown input {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 100%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }
        .dropdown-content button {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            width: 100%;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
        }
        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            animation: popup-animation 1s ease-out forwards;
            width: 80%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .popup .form-group {
            margin-bottom: 20px;
        }
        .popup label {
            font-weight: bold;
        }
        .popup input[type="text"] {
            padding: 8px;
            margin-top: 5px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        .popup button {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .popup .btn-ok {
            background-color: #007bff;
            color: white;
        }
        .popup .btn-ok:hover {
            background-color: #0056b3;
        }
        .popup .btn-cancel {
            background-color: #ddd;
            margin-left: 10px;
        }
        .popup .btn-cancel:hover {
            background-color: #bbb;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
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
        @keyframes popup-animation {
            0% {
                transform: translate(-50%, -50%) scale(0.5);
                opacity: 0;
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        .popup svg {
            width: 100px;
            display: block;
            margin: 40px auto 0;
        }
        .popup .path {
            stroke-dasharray: 1000;
            stroke-dashoffset: 0;
        }
        .popup .path.circle {
            animation: dash 0.9s ease-in-out;
        }
        .popup .path.line {
            stroke-dashoffset: 1000;
            animation: dash 0.9s 0.35s ease-in-out forwards;
        }
        .popup .path.check {
            stroke-dashoffset: -100;
            animation: dash-check 0.9s 0.35s ease-in-out forwards;
        }
        .popup p {
            text-align: center;
            margin: 20px 0 60px;
            font-size: 1.25em;
        }
        .popup p.success {
            color: #73AF55;
        }
        .popup p.error {
            color: #D06079;
        }
        @keyframes dash {
            0% { stroke-dashoffset: 1000; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes dash-check {
            0% { stroke-dashoffset: -100; }
            100% { stroke-dashoffset: 900; }
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .product-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 5px;
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

        #product-info-popup {
            width: 60%;
            max-width: 800px;
        }

        #product-info-popup h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
    </style>
</head>
<body>
<div class="container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-content">
            <h1>Medicine Category</h1>

            <button class="add-category-button" id="add-category-button">Add Category</button>
            
            <div class="dropdown">
            <input type="text" id="category-input" onkeyup="filterCategories()" onclick="showDropdown()" placeholder="Select category" value="<?php echo htmlspecialchars($selected_category_name); ?>">
            <div id="category-dropdown" class="dropdown-content">
                <button onclick="selectCategory(null)">Select Category</button>
                <?php foreach ($categories as $category) { ?>
                    <button onclick="selectCategory(<?php echo $category['id']; ?>)"><?php echo htmlspecialchars($category['category_name']); ?></button>
                <?php } ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>UPC</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)) { ?>
                        <tr>
                            <td colspan="3">No products found</td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($medicines as $medicine) { ?>
                            <tr onclick="showProductInfo(<?php echo $medicine['id']; ?>)">
                                <td><?php echo $medicine['upc']; ?></td>
                                <td><?php echo $medicine['medicine_name']; ?></td>
                                <td><?php echo $medicine['quantity']; ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="popup" id="add-category-popup">
        <form id="add-category-form" method="post" action="medicine_category.php">
            <div class="form-group">
                <label for="new_category_name">New Category Name:</label>
                <input type="text" id="new_category_name" name="new_category_name" required>
            </div>
            <button type="submit" class="btn-ok">Add</button>
            <button type="button" class="btn-cancel" onclick="closePopup('add-category-popup')">Cancel</button>
        </form>
    </div>

    <div class="popup" id="category-popup">
        <?php if ($add_category_message === 'success') { ?>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                <circle class="path circle" fill="none" stroke="#73AF55" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <polyline class="path check" fill="none" stroke="#73AF55" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 "/>
            </svg>
            <p class="success">Successful add into database!</p>
        <?php } elseif ($add_category_message === 'duplicate' || $add_category_message === 'error') { ?>
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                <circle class="path circle" fill="none" stroke="#D06079" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="34.4" y1="37.9" x2="95.8" y2="92.3"/>
                <line class="path line" fill="none" stroke="#D06079" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" x1="95.8" y1="38" x2="34.4" y2="92.2"/>
            </svg>
            <p class="error">Unsuccessful add into database!</p>
        <?php } ?>
        <button type="button" class="btn-ok" onclick="closePopup('category-popup')">Close</button>
    </div>

    <div class="popup" id="product-info-popup">
        <button class="close-btn" onclick="document.getElementById('product-info-popup').style.display='none'">&times;</button>
        <div id="product-info-content"></div>
    </div>

    <script>
         function filterCategories() {
            var input, filter, div, buttons, i;
            input = document.getElementById("category-input");
            filter = input.value.toUpperCase();
            div = document.getElementById("category-dropdown");
            buttons = div.getElementsByTagName("button");
            for (i = 0; i < buttons.length; i++) {
                if (buttons[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    buttons[i].style.display = "";
                } else {
                    buttons[i].style.display = "none";
                }
            }
        }

        function showDropdown() {
            document.getElementById("category-dropdown").style.display = "block";
        }

        function selectCategory(categoryId) {
            window.location.href = 'medicine_category.php?category_id=' + categoryId;
        }

        document.addEventListener('click', function(event) {
            var isClickInside = document.getElementById('category-input').contains(event.target);
            var isDropdown = document.getElementById('category-dropdown').contains(event.target);
            if (!isClickInside && !isDropdown) {
                document.getElementById('category-dropdown').style.display = 'none';
            }
        });

        document.getElementById('add-category-button').addEventListener('click', function() {
            document.getElementById('add-category-popup').style.display = 'block';
        });

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
                    <button class="btn-ok" onclick="document.getElementById('product-info-popup').style.display='none'" style="margin-top: 20px;">Close</button>
                `;
                document.getElementById('product-info-content').innerHTML = popupContent;
                document.getElementById('product-info-popup').style.display = 'block';
            }
        };
        xhr.send();
    }


        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
            if (popupId === 'category-popup') {
                location.reload();
            }
        }

        document.getElementById('add-category-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            fetch('medicine_category.php', {
                method: 'POST',
                body: formData
            }).then(response => response.text())
              .then(html => {
                  document.body.innerHTML = html;
                  closePopup('add-category-popup');
                  document.getElementById('category-popup').style.display = 'block';
              });
        });

        <?php if ($add_category_message) { ?>
            document.getElementById('category-popup').style.display = 'block';
        <?php } ?>
    </script>
</body>
</html>