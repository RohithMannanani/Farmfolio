<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['userid'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Check farm status
$is_farm_active = false;
if(isset($_SESSION['userid'])){
    $userid = $_SESSION['userid'];
    $farm = "SELECT * FROM tbl_farms WHERE user_id=$userid";
    $result = mysqli_query($conn, $farm);
    $row = $result->fetch_assoc();
    $_SESSION['farm_id']=$row['farm_id'];
    if($row && $row['status'] == 'active') {
        $is_farm_active = true;
        $farm_id = $row['farm_id'];
    }
}
 
// Only proceed with other queries if farm is active
if($is_farm_active) {
    // Fetch categories and subcategories for the current farm
    $category_query = "SELECT c.category_id, c.category, c.sub 
                      FROM tbl_category c 
                      INNER JOIN tbl_fc ON c.category_id = tbl_fc.category_id 
                      WHERE tbl_fc.farm_id = $farm_id";
    $category_result = mysqli_query($conn, $category_query);
    $farm_subcategories = array();
    
    while($category = mysqli_fetch_assoc($category_result)) {
        $farm_subcategories[] = array(
            'id' => $category['category_id'],
            'category' => $category['category'],
            'sub' => $category['sub']
        );
    }

    // Fetch products for the current farm
    $products_query = "SELECT p.*, c.category 
                      FROM tbl_products p 
                      JOIN tbl_category c ON p.category_id = c.category_id 
                      WHERE p.farm_id = $farm_id 
                      ORDER BY p.created_at DESC";
    $products_result = mysqli_query($conn, $products_query);
}

// Add this function after the database connection

function checkProductExists($conn, $product_name, $category_id, $farm_id, $product_id = null) {
    $sql = "SELECT product_id FROM tbl_products 
            WHERE LOWER(product_name) = LOWER(?) 
            AND category_id = ? 
            AND farm_id = ?";
    
    // Exclude current product when updating
    if ($product_id) {
        $sql .= " AND product_id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($product_id) {
        $stmt->bind_param("siii", $product_name, $category_id, $farm_id, $product_id);
    } else {
        $stmt->bind_param("sii", $product_name, $category_id, $farm_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Modify the add product section
if(isset($_POST['submit'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['productName']);
    $price = mysqli_real_escape_string($conn, $_POST['productPrice']);
    $stock = mysqli_real_escape_string($conn, $_POST['productStock']);
    $description = mysqli_real_escape_string($conn, $_POST['productDescription']);
    $category_id = mysqli_real_escape_string($conn, $_POST['productCategory']);
    $unit = mysqli_real_escape_string($conn, $_POST['productUnit']);

    // Check if product exists
    if (checkProductExists($conn, $product_name, $category_id, $farm_id)) {
        echo "<script>alert('A product with this name already exists in the selected category!');</script>";
    } else {
        $insert_query = "INSERT INTO tbl_products (farm_id, product_name, price, stock, description, category_id, unit) 
                     VALUES ('$farm_id', '$product_name', '$price', '$stock', '$description', '$category_id', '$unit')";

        if(mysqli_query($conn, $insert_query)) {
            echo "<script>alert('Product added successfully!');</script>";
            echo "<script>window.location.href='product.php';</script>";
        } else {
            echo "<script>alert('Error adding product: " . mysqli_error($conn) . "');</script>";
        }
    }
}

// Modify the edit product section
if(isset($_POST['edit_submit'])) {
    $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['productName']);
    $price = mysqli_real_escape_string($conn, $_POST['productPrice']);
    $stock = mysqli_real_escape_string($conn, $_POST['productStock']);
    $description = mysqli_real_escape_string($conn, $_POST['productDescription']);
    $category_id = mysqli_real_escape_string($conn, $_POST['productCategory']);
    $unit = mysqli_real_escape_string($conn, $_POST['productUnit']);

    // Check if product exists (excluding current product)
    if (checkProductExists($conn, $product_name, $category_id, $farm_id, $product_id)) {
        echo "<script>alert('A product with this name already exists in the selected category!');</script>";
    } else {
        $update_query = "UPDATE tbl_products 
                    SET product_name = '$product_name',
                        price = '$price',
                        stock = '$stock',
                        description = '$description',
                        category_id = '$category_id',
                        unit = '$unit'
                    WHERE product_id = '$product_id' AND farm_id = '$farm_id'";

        if(mysqli_query($conn, $update_query)) {
            echo "<script>alert('Product updated successfully!');</script>";
            echo "<script>window.location.href='product.php';</script>";
        } else {
            echo "<script>alert('Error updating product: " . mysqli_error($conn) . "');</script>";
        }
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
// Handle activate product request
if (isset($_GET['activate_product']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Update the status to '1' to activate the product
    $update_query = "UPDATE tbl_products SET status = '0' WHERE product_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Product activated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error activating product: " . $stmt->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
    }
    exit();
}
// Handle deactivate product request
if (isset($_GET['deactivate_product']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Update the status to '0' to deactivate the product
    $update_query = "UPDATE tbl_products SET status = '1' WHERE product_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Product deactivated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error deactivating product: " . $stmt->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
    }
    exit();
}

// Add this to your existing PHP code
if (isset($_POST['check_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $product_id = isset($_POST['product_id']) ? mysqli_real_escape_string($conn, $_POST['product_id']) : null;

    $exists = checkProductExists($conn, $product_name, $category_id, $farm_id, $product_id);
    echo json_encode(['exists' => $exists]);
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
        .error-message {
    color: red;
    font-size: 0.9em;
    margin-top: 5px;
    display: block;
}
.deactivate-btn {
    background-color: #dc2626; /* Red for deactivating */
    color: white;
}

.activate-btn {
    background-color: #1a4d2e; /* Green for activating */
    color: white;
}

.deactivate-btn:hover {
    background-color: #c62828; /* Darker red on hover */
}

.activate-btn:hover {
    background-color: #155724; /* Darker green on hover */
}
.inactive-product {
        opacity: 0.7;
        position: relative;
        background: #f8f8f8;
        border: 1px solid #ddd;
    }

    .inactive-label {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #dc2626;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: bold;
    }

    .inactive-product .product-details {
        position: relative;
    }

    .inactive-product::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        pointer-events: none;
    }

    .activate-btn {
        background-color: #1a4d2e;
        color: white;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .deactivate-btn {
        background-color: #dc2626;
        color: white;
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .activate-btn:hover {
        background-color: #155724;
    }

    .deactivate-btn:hover {
        background-color: #c62828;
    }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>Farmfolio</h2>
           
        </div>
        <ul class="sidebar-menu">
            <li><a href="farm.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="product.php" class="active"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
            <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
            <li><a href="review.php"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
            <!-- <li><a href="#"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
        </ul>
    </nav>

    <div class="main-content">
    <div class="dashboard-header">
                <?php if(isset($row['farm_name'])&&isset($_SESSION['username'])){?>
                <h1><?php echo $row['farm_name'];?></h1>
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
    <!-- Error message will be inserted here -->
</div>
                        <div class="form-group">
        <label for="productCategory">Category*</label>
        <select id="productCategory" name="productCategory" required>
            <option value="">Select Category</option>
            <?php 
            if (!empty($farm_subcategories)): 
                foreach($farm_subcategories as $category): 
            ?>
                <option value="<?php echo htmlspecialchars($category['id']); ?>">
                    <?php echo htmlspecialchars(ucfirst($category['sub'])); ?>
                </option>
            <?php 
                endforeach; 
            endif;
            ?>
        </select>
        <div class="error-message"></div>
        <?php if (empty($farm_subcategories)): ?>
            <div class="error-message">No categories available. Please contact administrator.</div>
        <?php endif; ?>
    </div>
<div class="form-group">
    <label for="productDescription">Description</label>
    <textarea id="productDescription" name="productDescription" rows="3" required></textarea>
    <!-- Error message will be inserted here -->
</div>
<div class="form-group">
    <label for="productPrice">Price (₹)</label>
    <input type="number" id="productPrice" name="productPrice" step="0.01" required>
    <!-- Error message will be inserted here -->
</div>

<div class="form-group">
    <label for="productStock">Stock Quantity</label>
    <input type="number" id="productStock" name="productStock" required>
    <!-- Error message will be inserted here -->
</div>
<div class="form-group">
    <label for="productUnit">Unit</label>
    <select id="productUnit" name="productUnit" required>
        <option value="">Select Unit</option>
        <option value="kg">Kilogram (kg)</option>
        <option value="g">Gram (g)</option>
        <option value="l">Liter (l)</option>
        <option value="ml">Milliliter (ml)</option>
    </select>
    <!-- Error message will be inserted here -->
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
                    $statusClass = $product['status'] == '1' ? 'inactive-product' : '';
                    $statusLabel = $product['status'] == '1' ? '<div class="inactive-label">Inactive</div>' : '';
                    
                    // Determine which button to show based on status
                   // Determine which button to show based on status
$actionButton = $product['status'] == '0' 
? "<button class='deactivate-btn' onclick='deactivateProduct(" . $product['product_id'] . ")'><i class='fas fa-times'></i> Deactivate</button>"
: "<button class='activate-btn' onclick='activateProduct(" . $product['product_id'] . ")'><i class='fas fa-check'></i> Activate</button>";
                    
                    echo "
                    <div class='product-card {$statusClass}'>
                        <div class='product-details'>
                            {$statusLabel}
                            <div class='product-title'>" . htmlspecialchars($product['product_name']) . "</div>
                            <div class='product-price'>₹" . number_format($product['price'], 2) . "</div>
                            <div class='product-stock'>In Stock: " . htmlspecialchars($product['stock']) . " " . htmlspecialchars(strtoupper($product['unit'])) . "</div>
                            <div class='product-category'>Category: " . htmlspecialchars($product['category']) . "</div>
                            <div class='product-actions'>
                                <button class='edit-btn' onclick='editProduct(" . $product['product_id'] . ")'>
                                    <i class='fas fa-edit'></i> Edit
                                </button>
                                {$actionButton}
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
            document.getElementById('productUnit').value = product.unit;
            
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

     
        async function activateProduct(productId) {
            if (confirm("Are you sure you want to activate this product?")) {
                const response = await fetch(`product.php?activate_product=1&id=${productId}`);
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload(); // Reload the page to update the product list
                } else {
                    alert("Error: " + result.message);
                }
            }
        }

        async function deactivateProduct(productId) {
            if (confirm("Are you sure you want to deactivate this product?")) {
                const response = await fetch(`product.php?deactivate_product=1&id=${productId}`);
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload(); // Reload the page to update the product list
                } else {
                    alert("Error: " + result.message);
                }
            }
        }

    </script>
    <?php endif; ?>
<script>
 document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("productForm");
    const submitBtn = document.getElementById("submitBtn");
    const editBtn = document.getElementById("editBtn");

    // Function to validate the form
    async function validateForm() {
        let isValid = true;

        const productName = document.getElementById("productName");
        const productDescription = document.getElementById("productDescription");
        const productPrice = document.getElementById("productPrice");
        const productStock = document.getElementById("productStock");
        const productCategory = document.getElementById("productCategory");
        const productUnit = document.getElementById("productUnit");

        const namePattern = /^[a-zA-Z-_ ]{4,50}$/;
        const descriptionPattern = /^[a-zA-Z0-9.,'"\-_\s]{10,}$/;

        // Function to show error messages
        function showError(input, message) {
            let errorDiv = input.nextElementSibling;
            
            // Check if the error message element already exists
            if (!errorDiv || !errorDiv.classList.contains("error-message")) {
                errorDiv = document.createElement("span");
                errorDiv.className = "error-message";
                errorDiv.style.color = "red";
                errorDiv.style.fontSize = "0.9em";
                input.parentNode.appendChild(errorDiv);
            }

            errorDiv.textContent = message; // Set the error message
            isValid = false; // Mark the form as invalid
        }

        // Function to clear error messages
        function clearError(input) {
            let errorDiv = input.nextElementSibling;
            if (errorDiv && errorDiv.classList.contains("error-message")) {
                errorDiv.textContent = ""; // Clear the error message
            }
        }

        // Validate Product Name
        if (productName.value.trim() === "") {
            showError(productName, "Product name is required.");
        } else if (!namePattern.test(productName.value.trim())) {
            showError(productName, "Name must be 3-50 characters long and contain only letters, numbers, - or _.");
        } else {
            clearError(productName);
        }

        // Validate Description
        if (productDescription.value.trim() === "") {
            showError(productDescription, "Description is required.");
        } else if (!descriptionPattern.test(productDescription.value.trim())) {
            showError(productDescription, "Description must be at least 10 characters long.");
        } else {
            clearError(productDescription);
        }

        // Validate Price
        if (productPrice.value.trim() === "" || isNaN(productPrice.value) || productPrice.value <= 0) {
            showError(productPrice, "Enter a valid price.");
        } else {
            clearError(productPrice);
        }

        // Validate Stock
        if (productStock.value.trim() === "" || isNaN(productStock.value) || productStock.value < 0) {
            showError(productStock, "Enter a valid stock quantity.");
        } else {
            clearError(productStock);
        }

        // Validate Category
        if (productCategory.value === "") {
            showError(productCategory, "Please select a category.");
        } else {
            clearError(productCategory);
        }

        // Validate Unit
        if (productUnit.value === "") {
            showError(productUnit, "Please select a unit.");
        } else {
            clearError(productUnit);
        }

        // Check for duplicate product
        const exists = await checkProductExists(productName.value, productCategory.value, document.getElementById("product_id").value);
        if (exists) {
            showError(productName, "A product with this name already exists in the selected category.");
            isValid = false;
        }

        // Enable/disable buttons based on form validity
        submitBtn.disabled = !isValid;
        editBtn.disabled = !isValid;

        console.log("Form validation completed. Form is valid:", isValid); // Debugging
        return isValid;
    }

    // Add event listeners for live validation
    form.addEventListener("input", function (e) {
        validateForm();
    });

    form.addEventListener("change", function (e) {
        validateForm();
    });

    // Update form submission to be async
    form.addEventListener("submit", async function(e) {
        e.preventDefault();
        if (await validateForm()) {
            this.submit();
        }
    });

    // Initial validation on page load
    validateForm();
});

// Add this to your existing JavaScript code
async function checkProductExists(productName, categoryId, productId = null) {
    try {
        const formData = new FormData();
        formData.append('check_product', '1');
        formData.append('product_name', productName);
        formData.append('category_id', categoryId);
        if (productId) {
            formData.append('product_id', productId);
        }

        const response = await fetch('product.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        return result.exists;
    } catch (error) {
        console.error('Error checking product:', error);
        return false;
    }
}

</script>


</body>
</html>