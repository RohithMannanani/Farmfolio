<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['type'])){
    header('location: ../login/login.php');
}

// Fetch all products with farm and category details
$products_query = "SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.stock,
    p.description,
    p.unit,
    p.status,
    p.created_at,
    f.farm_name,
    c.category as category_name
FROM tbl_products p
JOIN tbl_farms f ON p.farm_id = f.farm_id
JOIN tbl_category c ON p.category_id = c.category_id
ORDER BY p.created_at DESC";

$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Copy your existing admin panel styles here */
        
        /* Add these new styles */
        .products-container {
            margin: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .products-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th,
        .products-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .products-table th {
            background-color: #f8f9fa;
            color: #1a4d2e;
            font-weight: 600;
        }

        .products-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-active {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin: 0 2px;
        }

        .edit-btn {
            background-color: #1a4d2e;
            color: white;
        }

        .delete-btn {
            background-color: #dc2626;
            color: white;
        }

        .export-btn {
            background-color: #1a4d2e;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .search-box {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
            color: #334155;
        }


    /* Header Styles */
    .header {
        position: fixed;
        top: 0;
        right: 0;
        left: 250px;
        height: 70px;
        background: #ffffff;
        padding: 0 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        z-index: 100;
    }

    .head h1 {
        color: #1a4d2e;
        font-size: 24px;
        font-weight: 600;
    }

    .admin-controls {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .admin-controls h2 {
        font-size: 16px;
        color: #64748b;
    }

    .logout-btn {
        background-color: #d9534f;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s ease-in-out;
    }

    .logout-btn:hover {
        background-color: #c9302c;
        transform: translateY(-2px);
    }

    /* Sidebar Styles */
    .sidebar {
        width: 250px;
        background: #1a4d2e;
        color: white;
        padding: 10px;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        z-index: 10;
    }

    .sidebar-menu {
        list-style: none;
        margin-top: 20px;
    }

    .sidebar-menu a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin-bottom: 4px;
    }

    .sidebar-menu a:hover {
        background: #2d6a4f;
        transform: translateX(4px);
    }

    .sidebar-menu i {
        margin-right: 12px;
        width: 20px;
    }

    .sidebar-menu .active {
        background: #2d6a4f;
        font-weight: 500;
    }

    /* Main Content Styles */
    .main-content {
        margin-left: 250px;
        flex: 1;
        padding: 90px 24px 24px;
    }

    /* Products Container Styles */
    .products-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .products-header {
        padding: 24px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .products-header h2 {
        color: #1a4d2e;
        font-size: 1.5em;
    }

    .header-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .search-box {
        padding: 10px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        width: 250px;
        font-size: 0.9em;
        transition: border-color 0.2s;
    }

    .search-box:focus {
        outline: none;
        border-color: #1a4d2e;
    }

    .export-btn {
        background: #1a4d2e;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    .export-btn:hover {
        background: #2d6a4f;
        transform: translateY(-2px);
    }

    /* Table Styles */
    .table-container {
        overflow-x: auto;
        padding: 0 24px 24px;
    }

    .products-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .products-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        color: #1a4d2e;
        font-weight: 600;
        border-bottom: 2px solid #e5e7eb;
    }

    .products-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .products-table tr:hover {
        background: #f8f9fa;
    }

    /* Status Badge Styles */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 500;
        display: inline-block;
    }

    .status-active {
        background: #dcfce7;
        color: #166534;
    }

    .status-inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    /* Action Button Styles */
    .action-btn {
        padding: 8px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        margin: 0 4px;
    }

    .edit-btn {
        background: #1a4d2e;
        color: white;
    }

    .edit-btn:hover {
        background: #2d6a4f;
        transform: translateY(-2px);
    }

    .delete-btn {
        background: #dc2626;
        color: white;
    }

    .delete-btn:hover {
        background: #b91c1c;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .header {
            left: 200px;
        }
        .sidebar {
            width: 200px;
        }
        .main-content {
            margin-left: 200px;
        }
    }

    @media (max-width: 768px) {
        .header {
            left: 0;
        }
        .sidebar {
            transform: translateX(-100%);
        }
        .main-content {
            margin-left: 0;
        }
        .products-header {
            flex-direction: column;
            gap: 12px;
        }
        .header-actions {
            width: 100%;
        }
        .search-box {
            flex: 1;
        }
    }
    </style>
</head>
<body>
    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="user.php"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="farm.php"><i class="fas fa-store"></i><span>Farms</span></a></li>
            <li><a href="product.php" class="active"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="category.php"><i class="fas fa-th-large"></i><span>Category</span></a></li>
            
        </ul>
    </nav>

    <header class="header">
        <div class="head"><h1>🌱 FarmFolio</h1></div>
        <div class="admin-controls">
            <h2>Welcome, Admin</h2>
            <button class="logout-btn" onclick="window.location.href='../logout/logout.php'">Logout</button>
        </div>
    </header>

    <main class="main-content">
        <div class="products-container">
            <div class="products-header">
                <h2>Products List</h2>
                <div class="header-actions">
                    <input type="text" id="searchInput" class="search-box" placeholder="Search products...">
                    <!-- <button class="export-btn" onclick="exportToCSV()">
                        <i class="fas fa-download"></i> Export
                    </button> -->
                </div>
            </div>
            
            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Farm</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Product added Date</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['farm_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['unit']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $product['status'] == '0' ? 'active' : 'inactive'; ?>">
                                        <?php echo $product['status'] == '0' ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($product['created_at'])); ?></td>
                                <!-- <td>
                                    <button class="action-btn edit-btn" onclick="editProduct(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td> -->
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('.products-table tbody tr');
            
            tableRows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Export to CSV
        function exportToCSV() {
            let table = document.querySelector('.products-table');
            let rows = table.querySelectorAll('tr');
            let csv = [];
            
            for(let row of rows) {
                let rowData = [];
                let cols = row.querySelectorAll('td, th');
                
                for(let col of cols) {
                    rowData.push(col.textContent.trim());
                }
                csv.push(rowData.join(','));
            }
            
            let csvContent = csv.join('\n');
            let blob = new Blob([csvContent], { type: 'text/csv' });
            let url = window.URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'products.csv';
            a.click();
        }

        function editProduct(productId) {
            // Implement edit functionality
            console.log('Edit product:', productId);
        }

        function deleteProduct(productId) {
            if(confirm('Are you sure you want to delete this product?')) {
                // Implement delete functionality
                console.log('Delete product:', productId);
            }
        }
    </script>
</body>
</html>