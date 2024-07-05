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

// Count expired products
$expired_sql = "SELECT COUNT(*) as count FROM product WHERE expired_date < '$current_date'";
$expired_result = mysqli_query($conn, $expired_sql);
$expired_count = mysqli_fetch_assoc($expired_result)['count'];

// Count products expiring in 30 days
$expiring_sql = "SELECT COUNT(*) as count FROM product WHERE expired_date BETWEEN '$current_date' AND DATE_ADD('$current_date', INTERVAL 30 DAY)";
$expiring_result = mysqli_query($conn, $expiring_sql);
$expiring_count = mysqli_fetch_assoc($expiring_result)['count'];

// Handle search
$search_term = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
$search_results = [];

if (!empty($search_term)) {
    $search_sql = "SELECT id, upc, product_name, quantity FROM product WHERE upc LIKE ? OR product_name LIKE ? LIMIT 10";
    $stmt = mysqli_prepare($conn, $search_sql);
    $search_param = "%$search_term%";
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $search_results[] = $row;
    }
    mysqli_stmt_close($stmt);
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
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            outline: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .container {
            display: flex;
            width: 100%;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
            background: linear-gradient(-45deg, #3f51b1 0%, #5a55ae 20%, #7b5fac 40%, #8f6aae 60%, #a86aa4 80%);
            background-size: 400% 400%;
            animation: animate 15s ease-in-out infinite;
        }

        @keyframes animate {
            0% { background-position: 0 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }

        h1 {
            font-size: 3em;
            text-align: center;
            color: #fff;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
            padding: 10px;
            border-radius: 10px;
        }

        .widgets-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 40px;
        }

        .widget {
            background-color: rgba(242, 242, 242, 0.8);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            width: 45%;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .widget:hover {
            background-color: rgba(224, 224, 224, 0.8);
        }

        .widget-number {
            font-size: 48px;
            font-weight: bold;
            color: #007bff;
        }

        .widget-label {
            font-size: 18px;
            color: #333;
        }

        .expired-bg {
            background-color: rgba(242, 242, 242, 0.8);
        }

        .expired-bg[data-count]:not([data-count="0"]) {
            background-color: rgba(255, 200, 200, 0.8);
        }

        .expiring-bg {
            background-color: rgba(242, 242, 242, 0.8);
        }

        .expiring-bg[data-count]:not([data-count="0"]) {
            background-color: rgba(255, 229, 180, 0.8);
        }
        
        .search-title {
            color: #fff;
            font-size: 36px;
            margin-bottom: 10px;
            text-align: left;
        }

        .search-container {
            display: flex;
            margin-bottom: 20px;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 5px;
        }

        .search-bar {
            flex-grow: 1;
            padding: 10px 15px;
            font-size: 1rem;
            border: none;
            background: transparent;
            color: #fff;
        }

        .search-bar::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-button {
            background: rgba(255, 255, 255, 0.3);
            border: none;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .search-button:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .search-button img {
            width: 20px;
            height: 20px;
            filter: invert(1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: rgba(242, 242, 242, 0.8);
            color: #333;
        }

        .search-message {
            text-align: left;
            color: #fff;
            font-size: 18px;
            margin-top: 20px;
            padding: 10px;
            border-radius: 10px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
        }

        .quantity-control button {
            background-color: #ddd;
            border: none;
            padding: 5px;
            cursor: pointer;
        }

        .quantity-control input {
            font-size: 18px;
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            margin-top: 4px;
            margin-bottom: 3px;
        }

        .delete-button {
            background-color: #ff0000;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .popup {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .popup-content {
            position: fixed;
            text-align: center;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: popup-animation 0.3s ease-out forwards;
            width: 80%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #333;
        }

        #delete-popup-message h2 {
            margin-top: 10px;
            color: #333;
            font-size: 1.5em;
        }

        #delete-popup-message p {
            color: #666;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        #delete-popup-message .btn-container {
            display: flex;
            justify-content: space-between;
        }

        @keyframes popup-animation {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
        .product-info {
            cursor: pointer;
        }

        #product-info-popup {
            display: none;
            position: absolute;
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

        #product-info-popup h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5em;
            color: black;
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

        #product-info-popup .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        #product-info-popup .btn-ok {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        #product-info-popup .btn-ok:hover {
            background-color: #0056b3;
        }

        #delete-popup {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .popup-content {
            position: fixed;
            text-align: center;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: popup-animation 0.3s ease-out forwards;
            width: 80%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        @keyframes popup-animation {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        .close-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
            transition: color 0.3s ease;
        }

        .close-btn:hover {
            color: #333;
        }

        #delete-popup-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        #confirm-delete-btn {
            background-color: #007bff;
            color: white;
        }

        #confirm-delete-btn:hover {
            background-color: #0056b3;
        }

        .cancel-btn {
            background-color: #ddd;
            margin-left: 10px;
        }

        .cancel-btn:hover {
            background-color: #bbb;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-content">
            <h1>Pharmacy Inventory System</h1>
            <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
            <div class="widgets-container">
                <div class="widget expired-bg" onclick="location.href='expired_list.php'" data-count="<?php echo $expired_count; ?>">
                    <div class="widget-number"><?php echo $expired_count; ?></div>
                    <div class="widget-label">Expired Products</div>
                </div>
                <div class="widget expiring-bg" onclick="location.href='expired_list.php'" data-count="<?php echo $expiring_count; ?>">
                    <div class="widget-number"><?php echo $expiring_count; ?></div>
                    <div class="widget-label">Products Expiring Soon</div>
                </div>
            </div>
            <div>
            <h2 class="search-title">Search</h2>
            <form action="" method="GET" class="search-container">
                <input type="text" id="search-input" name="search" class="search-bar" placeholder="Search by UPC or Medicine Name" value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-button">
                    <img src="../assets/search.svg" alt="Search">
                </button>
            </form>
            <?php if (!empty($search_term)) { ?>
                <h2>Search Results</h2>
                <?php if (!empty($search_results)) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>UPC</th>
                                <th>Medicine Name</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $product) { ?>
                                <tr data-product-id="<?php echo $product['id']; ?>">
                                    <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['upc']); ?></td>
                                    <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td class="quantity-control">
                                        <button onclick="updateQuantity(<?php echo $product['id']; ?>, -1)">-</button>
                                        <input type="text" value="<?php echo htmlspecialchars($product['quantity']); ?>" readonly>
                                        <button onclick="updateQuantity(<?php echo $product['id']; ?>, 1)">+</button>
                                    </td>
                                    <td><button class="delete-button" onclick="confirmDelete(<?php echo $product['id']; ?>)">Delete</button></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="search-message">No product found.</p>
                <?php } ?>
            <?php } else { ?>
                <p class="search-message">Perform a search to get results.</p>
            <?php } ?>
        </div>
    </div>

    <div id="product-info-popup">
        <button class="close-btn" onclick="closeProductInfoPopup()">&times;</button>
        <div id="product-info-content"></div>
    </div>

    <div id="delete-popup" class="popup">
        <div class="popup-content">
            <span class="close-btn" onclick="closeDeletePopup()">&times;</span>
            <div id="delete-popup-message">
                <h2>Confirm Deletion</h2>
                <p>Are you sure you want to delete this product?</p>
            </div>
            <div id="delete-popup-buttons">
                <button id="confirm-delete-btn" onclick="deleteProduct()">Delete</button>
                <button class="cancel-btn" onclick="closeDeletePopup()">Cancel</button>
            </div>
            <div id="delete-popup-result"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $(document).ready(function() {
            $("#search-input").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "search_products.php",
                        type: "GET",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.product_name + ' (UPC: ' + item.upc + ')',
                                    value: item.product_name
                                };
                            }));
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#search-input").val(ui.item.value);
                    return false;
                }
            });
        });

        function updateQuantity(productId, change) {
            $.ajax({
                url: "update_quantity.php",
                type: "POST",
                dataType: "json",
                data: {
                    id: productId,
                    change: change
                },
                success: function(response) {
                    if (response.success) {
                        var quantityInput = $(`tr[data-product-id="${productId}"] input`);
                        quantityInput.val(response.newQuantity);
                    } else {
                        alert("Failed to update quantity. Please try again.");
                    }
                },
                error: function() {
                    alert("Failed to update quantity. Please try again.");
                }
            });
        }

        let productIdToDelete;

        function confirmDelete(productId) {
            productIdToDelete = productId;
            document.getElementById('delete-popup').style.display = 'block';
        }

        function closeDeletePopup() {
            document.getElementById('delete-popup').style.display = 'none';
            document.getElementById('delete-popup-result').innerHTML = '';
        }

        function deleteProduct() {
            $.ajax({
                url: "delete_product.php",
                type: "POST",
                dataType: "json",
                data: { id: productIdToDelete },
                success: function(response) {
                    if (response.success) {
                        showDeleteStatus(true);
                        setTimeout(() => {
                            closeDeletePopup();
                            location.reload();
                        }, 2000);
                    } else {
                        showDeleteStatus(false);
                    }
                },
                error: function() {
                    showDeleteStatus(false);
                }
            });
        }

        function showDeleteStatus(success) {
            const resultDiv = document.getElementById('delete-popup-result');
            const buttonDiv = document.getElementById('delete-popup-buttons');
            const messageDiv = document.getElementById('delete-popup-message');
            
            buttonDiv.style.display = 'none';
            messageDiv.style.display = 'none';

            if (success) {
                resultDiv.innerHTML = `
                    <div class="success-checkmark">
                        <div class="check-icon">
                            <span class="icon-line line-tip"></span>
                            <span class="icon-line line-long"></span>
                            <div class="icon-circle"></div>
                            <div class="icon-fix"></div>
                        </div>
                    </div>
                    <p>Product deleted successfully!</p>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="error-cross">
                        <div class="cross-icon"></div>
                    </div>
                    <p>Failed to delete product. Please try again.</p>
                `;
            }
        }

        function showProductInfo(productId) {
            console.log("Showing product info for ID:", productId);
            $.ajax({
                url: "product_info.php",
                type: "GET",
                dataType: "json",
                data: { id: productId },
                success: function(productDetails) {
                    if (productDetails.error) {
                        alert(productDetails.error);
                    } else {
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
                            <button class="btn-ok" onclick="closeProductInfoPopup()">Close</button>
                        `;
                        $('#product-info-content').html(popupContent);
                        $('#product-info-popup').show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    alert("Failed to retrieve product information. Please try again.");
                }
            });
        }

        function closeProductInfoPopup() {
            $('#product-info-popup').hide();
        }
    </script>
</body>
</html>
