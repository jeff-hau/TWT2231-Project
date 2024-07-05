<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/pharmacy.png" class="sidebar-logo" alt="Pharmacy Logo">
        <span class="sidebar-title">Pharmacy Inventory System</span>
    </div>

    <a href="../pages/home.php" class="nav-item <?php echo ($current_page == 'home.php') ? 'active' : ''; ?>">
        <img src="../assets/home.svg" class="nav-icon" alt="Home">
        <span class="nav-text">Home</span>
    </a>
    <a href="../pages/inventory.php" class="nav-item <?php echo ($current_page == 'inventory.php') ? 'active' : ''; ?>">
        <img src="../assets/inventory.svg" class="nav-icon" alt="Inventory">
        <span class="nav-text">Inventory</span>
    </a>
    <a href="../pages/medicine_category.php" class="nav-item <?php echo ($current_page == 'medicine_category.php') ? 'active' : ''; ?>">
        <img src="../assets/category.svg" class="nav-icon" alt="Category">
        <span class="nav-text">Medicine Category</span>
    </a>
    <a href="../pages/medicine_type.php" class="nav-item <?php echo ($current_page == 'medicine_type.php') ? 'active' : ''; ?>">
        <img src="../assets/type.svg" class="nav-icon" alt="Type">
        <span class="nav-text">Medicine Type</span>
    </a>
    <a href="../pages/medicine_list.php" class="nav-item <?php echo ($current_page == 'medicine_list.php') ? 'active' : ''; ?>">
        <img src="../assets/medicine.svg" class="nav-icon" alt="Add Medicine">
        <span class="nav-text">Add Medicine</span>
    </a>
    <a href="../pages/expired_list.php" class="nav-item <?php echo ($current_page == 'expired_list.php') ? 'active' : ''; ?>">
        <img src="../assets/expired.svg" class="nav-icon" alt="Expired">
        <span class="nav-text">Expired List</span>
    </a>
    <a href="../pages/supplier_list.php" class="nav-item <?php echo ($current_page == 'supplier_list.php') ? 'active' : ''; ?>">
        <img src="../assets/supplier.svg" class="nav-icon" alt="Supplier">
        <span class="nav-text">Supplier List</span>
    </a>
    <a href="../pages/users.php" class="nav-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
        <img src="../assets/users.svg" class="nav-icon" alt="Users">
        <span class="nav-text">Users</span>
    </a>
    
    <div class="sidebar-footer">
        <a href="../pages/logout.php" class="nav-item logout">
            <img src="../assets/logout.svg" class="nav-icon" alt="Logout">
            <span class="nav-text">Logout</span>
        </a>
    </div>
</div>


<link rel="stylesheet" href="../styles/sidebar.css">

