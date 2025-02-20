<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Check farm status
$is_farm_active = false;
if(isset($_SESSION['userid'])){
    $userid = $_SESSION['userid'];
    $farm = "SELECT * FROM tbl_farms WHERE user_id=$userid";
    $result = mysqli_query($conn, $farm);
    $row = $result->fetch_assoc();
    
    if($row && $row['status'] == 'active') {
        $is_farm_active = true;
        $farm_id = $row['farm_id'];
    }
}

// Only proceed with other queries if farm is active
if($is_farm_active) {
    // Fetch categories from database
    $category_query = "SELECT * FROM tbl_category";
    $category_result = mysqli_query($conn, $category_query);

    // Fetch products for the current farm
    $products_query = "SELECT p.*, c.category 
                      FROM tbl_products p 
                      JOIN tbl_category c ON p.category_id = c.category_id 
                      WHERE p.farm_id = $farm_id 
                      ORDER BY p.created_at DESC";
    $products_result = mysqli_query($conn, $products_query);
}

// Add product form submission handling
// Add product form submission handling
if(isset($_POST['submit'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['productName']);
    $price = mysqli_real_escape_string($conn, $_POST['productPrice']);
    $stock = mysqli_real_escape_string($conn, $_POST['productStock']);
    $description = mysqli_real_escape_string($conn, $_POST['productDescription']);
    $category_id = mysqli_real_escape_string($conn, $_POST['productCategory']); 

    // Insert into tbl_products
    $insert_query = "INSERT INTO tbl_products (farm_id, product_name, price, stock, description, created_at, category_id) 
                     VALUES ('$farm_id', '$product_name', '$price', '$stock', '$description', NOW(), '$category_id')";

    if(mysqli_query($conn, $insert_query)) {
        // Redirect to prevent form resubmission on page refresh
        header("Location: product.php");
        exit(); // Make sure to exit after redirection
    } else {
        echo "<script>alert('Error adding product: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle edit product submission
if(isset($_POST['edit_submit'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['productName']);
    $price = mysqli_real_escape_string($conn, $_POST['productPrice']);
    $stock = mysqli_real_escape_string($conn, $_POST['productStock']);
    $description = mysqli_real_escape_string($conn, $_POST['productDescription']);
    $category_id = mysqli_real_escape_string($conn, $_POST['productCategory']);

    $update_query = "UPDATE tbl_products 
                    SET product_name = '$product_name',
                        price = '$price',
                        stock = '$stock',
                        description = '$description',
                        category_id = '$category_id'
                    WHERE product_id = '$product_id' AND farm_id = '$farm_id'";

    if(mysqli_query($conn, $update_query)) {
        echo "<script>alert('Product updated successfully!');</script>";
        header("Location: product.php");
        exit();
    } else {
        echo "<script>alert('Error updating product: " . mysqli_error($conn) . "');</script>";
    }
}

// Add API endpoint to get product details
if(isset($_GET['get_product']) && isset($_GET['id'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM tbl_products WHERE product_id = '$product_id' AND farm_id = '$farm_id'";
    $result = mysqli_query($conn, $query);
    $product = mysqli_fetch_assoc($result);
    echo json_encode($product);
    exit();
}
// Handle delete product request
if(isset($_GET['delete_product']) && isset($_GET['id'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Ensure the product belongs to the current farm
    $delete_query = "DELETE FROM tbl_products WHERE product_id = '$product_id' AND farm_id = '$farm_id'";
    
    if(mysqli_query($conn, $delete_query)) {
        echo json_encode(["success" => true, "message" => "Product deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error deleting product: " . mysqli_error($conn)]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Products</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .product-container {
            padding: 20px;
            max-width: 1200px;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            padding: 10px 20px;
            background: #1a4d2e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-product-btn:hover {
            background: #2d6a4f;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-details {
            padding: 15px;
        }

        .product-title {
            font-size: 1.1em;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-price {
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .product-stock {
            color: #666;
            font-size: 0.9em;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-btn {
            background: #1a4d2e;
            color: white;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background: #1a4d2e;
            color: white;
        }

        .cancel-btn {
            background: #666;
            color: white;
        }

        .inactive-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 70vh;
            text-align: center;
            color: #666;
        }

        .inactive-message h2 {
            font-size: 24px;
            margin-bottom: 16px;
            color: #1a4d2e;
        }

        .inactive-message p {
            font-size: 16px;
            max-width: 600px;
            line-height: 1.6;
        }

        .inactive-icon {
            font-size: 48px;
            color: #1a4d2e;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
            <button id="sidebarToggle" class="menu-icon">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li><a href="farm.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="product.php" class="active"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="#"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
            <li><a href="#"><i class="fas fa-info-circle"></i><span>About</span></a></li>
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?>Farm</h1>
                <div class="user-section">
                    <span>Welcome, <?php echo $_SESSION['username'];?></span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div>
                <?php }else{?>
                    <h1>Farm Dashboard</h1>
                <div class="user-section">
                    <span>Welcome,</span>
                    <a href="http://localhost/mini%20project/logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
                </div><?php }?>
            </div>s
        <?php if($is_farm_active): ?>
            <div class="product-container">
                <div class="product-header">
                    <h1>Products</h1>
                    <button class="add-product-btn" onclick="openModal()">
                        <i class="fas fa-plus"></i>
                        Add New Product
                    </button>
                </div>

                <div class="product-grid">
                    <!-- Product cards will be dynamically added here -->
                </div>
            </div>

            <!-- Add/Edit Product Modal -->
            <div class="modal" id="productModal">
                <div class="modal-content">
                    <h2 id="modalTitle">Add New Product</h2>
                    <form id="productForm" method="POST">
                        <input type="hidden" id="product_id" name="product_id">
                        <div class="form-group">
                            <label for="productName">Product Name</label>
                            <input type="text" id="productName" name="productName" required>
                        </div>
                        <div class="form-group">
                            <label for="productDescription">Description</label>
                            <textarea id="productDescription" name="productDescription" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="productPrice">Price (₹)</label>
                            <input type="number" id="productPrice" name="productPrice" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="productCategory">Category</label>
                            <select id="productCategory" name="productCategory" required>
                                <option value="">Select Category</option>
                                <?php
                                // Reset the category result pointer
                                mysqli_data_seek($category_result, 0);
                                while($category = mysqli_fetch_assoc($category_result)) {
                                    echo "<option value='" . $category['category_id'] . "'>" . htmlspecialchars($category['category']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="productStock">Stock Quantity</label>
                            <input type="number" id="productStock" name="productStock" required>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                            <button type="submit" name="submit" class="save-btn" id="submitBtn">Save Product</button>
                            <button type="submit" name="edit_submit" class="save-btn" id="editBtn" style="display: none;">Update Product</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash inactive-icon"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account before managing products.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if($is_farm_active): ?>
    <script>
        // Function to render products
        function renderProducts() {
            const grid = document.querySelector('.product-grid');
            grid.innerHTML = `
                <?php
                while($product = mysqli_fetch_assoc($products_result)) {
                    echo "
                    <div class='product-card'>
                       
                        <div class='product-details'>
                            <div class='product-title'>" . htmlspecialchars($product['product_name']) . "</div>
                            <div class='product-price'>₹" . number_format($product['price'], 2) . "</div>
                            <div class='product-stock'>In Stock: " . htmlspecialchars($product['stock']) . "</div>
                            <div class='product-category'>Category: " . htmlspecialchars($product['category']) . "</div>
                            <div class='product-actions'>
                                <button class='edit-btn' onclick='editProduct(" . $product['product_id'] . ")'>
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                <button class='delete-btn' onclick='deleteProduct(" . $product['product_id'] . ")'>
                                    <i class='fas fa-trash'></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    ";
                }
                ?>
            `;
        }

        // Function to open modal for editing
        async function editProduct(productId) {
            const response = await fetch(`product.php?get_product=1&id=${productId}`);
            const product = await response.json();
            
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('product_id').value = product.product_id;
            document.getElementById('productName').value = product.product_name;
            document.getElementById('productDescription').value = product.description;
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productCategory').value = product.category_id;
            document.getElementById('productStock').value = product.stock;
            
            // Show edit button, hide submit button
            document.getElementById('submitBtn').style.display = 'none';
            document.getElementById('editBtn').style.display = 'block';
            
            openModal();
        }

        // Function to open modal for adding new product
        function openModal() {
            if (!document.getElementById('product_id').value) {
                // Clear form if it's a new product
                document.getElementById('modalTitle').textContent = 'Add New Product';
                document.getElementById('productForm').reset();
                document.getElementById('submitBtn').style.display = 'block';
                document.getElementById('editBtn').style.display = 'none';
            }
            document.getElementById('productModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
            document.getElementById('productForm').reset();
        }

        // Update form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            // Remove the default e.preventDefault() to allow form submission
            // Add any additional client-side validation if needed
        });

        // Initialize the page
        renderProducts();

        // Function to delete a product
async function deleteProduct(productId) {
    if (confirm("Are you sure you want to delete this product?")) {
        const response = await fetch(`product.php?delete_product=1&id=${productId}`);
        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload(); // Reload page to update product list
        } else {
            alert("Error: " + result.message);
        }
    }
}

    </script>
    <?php endif; ?>
</body>
</html>