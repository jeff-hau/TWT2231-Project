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

// Handle search
$search_term = isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '';
$search_results = [];
$all_products = [];

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

// Fetch all products
$all_products_sql = "SELECT id, upc, product_name, quantity FROM product";
$result = mysqli_query($conn, $all_products_sql);
while ($row = mysqli_fetch_assoc($result)) {
    $all_products[] = $row;
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
        .search-container {
            display: flex;
            margin-bottom: 20px;
            align-items: center;
        }
        .search-bar {
            flex-grow: 1;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
        }
        .search-button img {
            width: 20px;
            height: 20px;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        #sort {
            font-size: 16px;
            padding: 5px;
        }
        .label {
            font-size: 16px;
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
        }
        th {
            background-color: #f2f2f2;
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
            justify-items: center;
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

        .edit-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .edit-button:hover {
            background-color: #0056b3;
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
            text-align: left;
            color: black;
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
            max-width: 2000px;
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

        @keyframes popup-animation {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        .product-info {
            cursor: pointer;
        }
        #product-info-popup {
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

        #product-info-popup h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5em;
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
        .path {
            stroke-dasharray: 1000;
            stroke-dashoffset: 0;
            &.circle {
                -webkit-animation: dash 0.9s ease-in-out;
                animation: dash 0.9s ease-in-out;
            }
            &.line {
                stroke-dashoffset: 1000;
                -webkit-animation: dash 0.9s 0.35s ease-in-out forwards;
                animation: dash 0.9s 0.35s ease-in-out forwards;
            }
            &.check {
                stroke-dashoffset: -100;
                -webkit-animation: dash-check 0.9s 0.35s ease-in-out forwards;
                animation: dash-check 0.9s 0.35s ease-in-out forwards;
            }
        }
        @-webkit-keyframes dash {
            0% {
                stroke-dashoffset: 1000;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        @keyframes dash {
            0% {
                stroke-dashoffset: 1000;
            }
            100% {
                stroke-dashoffset: 0;
            }
        }
        @-webkit-keyframes dash-check {
            0% {
                stroke-dashoffset: -100;
            }
            100% {
                stroke-dashoffset: 900;
            }
        }
        @keyframes dash-check {
            0% {
                stroke-dashoffset: -100;
            }
            100% {
                stroke-dashoffset: 900;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include '../components/sidebar.php'; ?>
        <div class="main-content">
            <h1>Inventory</h1>
            
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
                                <tr>
                                    <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['upc']); ?></td>
                                    <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td class="quantity-control">
                                        <button onclick="updateQuantity(<?php echo $product['id']; ?>, -1)">-</button>
                                        <input type="text" value="<?php echo htmlspecialchars($product['quantity']); ?>" readonly>
                                        <button onclick="updateQuantity(<?php echo $product['id']; ?>, 1)">+</button>
                                    </td>
                                    <td>
                                        <button class="edit-button" onclick="openEditPopup(<?php echo $product['id']; ?>)">Edit</button>
                                        <button class="delete-button" onclick="confirmDelete(<?php echo $product['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p>No product found.</p>
                <?php } ?>
            <?php } else { ?>
                <p>Perform a search to get results.</p>
            <?php } ?>

            <h2>All Products</h2>
            <div>
                <label for="sort">Sort by:</label>
                <select id="sort" onchange="sortTable()">
                    <option value="product_name_asc">Medicine Name (Ascending)</option>
                    <option value="product_name_desc">Medicine Name (Descending)</option>
                    <option value="quantity_asc">Quantity (Ascending)</option>
                    <option value="quantity_desc">Quantity (Descending)</option>
                </select>
            </div>
            <table id="all-products-table">
                <thead>
                    <tr>
                        <th>UPC</th>
                        <th>Medicine Name</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($all_products as $product) { ?>
                    <tr data-product-id="<?php echo $product['id']; ?>">
                        <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['upc']); ?></td>
                        <td class="product-info" onclick="showProductInfo(<?php echo $product['id']; ?>)"><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td class="quantity-control">
                            <button onclick="updateQuantity(<?php echo $product['id']; ?>, -1)">-</button>
                            <input type="text" value="<?php echo htmlspecialchars($product['quantity']); ?>" readonly>
                            <button onclick="updateQuantity(<?php echo $product['id']; ?>, 1)">+</button>
                        </td>
                        <td>
                            <button class="edit-button" onclick="openEditPopup(<?php echo $product['id']; ?>)">Edit</button>
                            <button class="delete-button" onclick="confirmDelete(<?php echo $product['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
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

    <div id="edit-popup" class="popup">
        <div class="popup-content" id="edit-popup-content">
            <!-- Edit form will be loaded here dynamically -->
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
            console.log("Updating quantity for product ID:", productId, "with change:", change);
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
                        console.log("Quantity updated successfully:", response.newQuantity);
                        // Update the quantity in the UI
                        var quantityInput = $(`tr[data-product-id="${productId}"] input`);
                        quantityInput.val(response.newQuantity);
                    } else {
                        console.error("Error updating quantity:", response.error);
                        alert("Failed to update quantity. Please try again.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
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
                            // Refresh the page after closing the popup
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

        function closeDeletePopup() {
            document.getElementById('delete-popup').style.display = 'none';
            document.getElementById('delete-popup-result').innerHTML = '';
        }

        function sortTable() {
            console.log("Sorting table");
            var table = document.getElementById('all-products-table');
            var tbody = table.querySelector('tbody');
            var rows = Array.from(tbody.rows);
            var sortBy = document.getElementById('sort').value;

            rows.sort(function(a, b) {
                var aVal, bVal;

                switch (sortBy) {
                    case 'product_name_asc':
                    case 'product_name_desc':
                        aVal = a.cells[1].innerText.toLowerCase();
                        bVal = b.cells[1].innerText.toLowerCase();
                        break;
                    case 'quantity_asc':
                    case 'quantity_desc':
                        aVal = parseInt(a.cells[2].querySelector('input').value);
                        bVal = parseInt(b.cells[2].querySelector('input').value);
                        break;
                }

                if (sortBy.endsWith('_asc')) {
                    return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
                } else {
                    return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
                }
            });

            // Clear the table body
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }

            // Append sorted rows
            rows.forEach(row => tbody.appendChild(row));
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

        function openEditPopup(productId) {
            $.ajax({
                url: 'edit_product.php',
                type: 'GET',
                data: { id: productId },
                success: function(response) {
                    console.log("Edit form loaded successfully");
                    $('#edit-popup-content').html(response);
                    $('#edit-popup').show();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    alert("Failed to load edit form. Please try again.");
                }
            });
        }

        function closeEditPopup() {
            $('#edit-popup').hide();
        }
    </script>
</body>
</html>
