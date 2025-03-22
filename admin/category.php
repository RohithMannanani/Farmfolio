<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['type'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Handle form submission for adding category
if(isset($_POST['add_category'])) {
    $category = strtolower(trim($_POST['category']));
    $subcategory = strtolower(trim($_POST['subcategory']));
    
    
    // Validate inputs
    $errors = [];
    if(empty($category)) {
        $errors[] = "Category name is required";
    }
    if(empty($subcategory)) {
        $errors[] = "Subcategory name is required";
    }
    
    if(empty($errors)) {
        // Check if category-subcategory combination already exists
        $check_query = "SELECT * FROM tbl_category WHERE category = ? AND sub = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $category, $subcategory);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $message = "This category and subcategory combination already exists!";
            $message_class = "error";
        } else {
            // Insert new category
            $insert_query = "INSERT INTO tbl_category (category, sub) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ss", $category, $subcategory);
            
            if($stmt->execute()) {
                $message = "Category added successfully!";
                $message_class = "success";
            } else {
                $message = "Error adding category: " . $conn->error;
                $message_class = "error";
            }
        }
    }
}

// Handle category status toggle
if(isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    $current_status = $_GET['status'];
    
    // Toggle the status ('1' to '0' or '0' to '1')
    $new_status = ($current_status == '1') ? '0' : '1';
    
    $update_query = "UPDATE tbl_category SET status = ? WHERE category_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if($stmt) {
        $stmt->bind_param("si", $new_status, $category_id);
        if($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Category " . ($new_status == '1' ? "activated" : "deactivated") . " successfully."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Error updating status: " . $stmt->error
            ]);
        }
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Error preparing statement: " . $conn->error
        ]);
    }
    exit();
}

// Handle category edit
if(isset($_POST['edit_category'])) {
    $category_id = intval($_POST['category_id']);
    $category = strtolower(trim($_POST['category']));
    $subcategory = strtolower(trim($_POST['subcategory']));
    
    $update_query = "UPDATE tbl_category SET category = ?, sub = ? WHERE category_id = ?";
    $stmt = $conn->prepare($update_query);
    
    if($stmt) {
        $stmt->bind_param("ssi", $category, $subcategory, $category_id);
        if($stmt->execute()) {
            $message = "Category updated successfully!";
            $message_class = "success";
        } else {
            $message = "Error updating category: " . $conn->error;
            $message_class = "error";
        }
    }
}

// Fetch all categories for display
$categories_query = "SELECT * FROM tbl_category ORDER BY category, sub";
$categories_result = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Category Management - Farmfolio Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
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

        .icon-btn {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            color: #64748b;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .icon-btn:hover {
            background: #f1f5f9;
            color: #1a4d2e;
        }

         /* logout */
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

        .sidebar {
            width: 200px;
            background: #1a4d2e;
            color: white;
            padding: 10px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 10;
            overflow: hidden;
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

        .active {
            background: #2d6a4f;
            font-weight: 500;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 90px 24px 24px;
        }

        .category-container {
            display: flex;
            gap: 20px;
            margin: 20px;
        }

        .category-form {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .category-list {
            flex: 2;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .submit-btn {
            background-color: #1a4d2e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .submit-btn:hover {
            background-color: #2d6a4f;
        }

        .category-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .category-table th,
        .category-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .category-table th {
            background-color: #1a4d2e;
            color: white;
        }

        .category-table tr:hover {
            background-color: #f5f5f5;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .edit-btn {
            background-color: #ffc107;
            color: #000;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }

        .inactive-row {
            opacity: 0.7;
            background-color: #f8f9fa;
        }

        .activate-btn {
            background-color: #28a745;
            color: white;
        }

        .deactivate-btn {
            background-color: #dc3545;
            color: white;
        }

        /* Add modal styles */
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
    </style>
</head>
<body>
    <header class="header">
        <div class="head"><h1>🌱 FarmFolio</h1></div>
        <div class="admin-controls">
            <h2>Welcome, Admin</h2>
            <button class="icon-btn" data-tooltip="Notifications"><i class="fas fa-bell"></i></button>
            <button class="icon-btn" data-tooltip="Messages"><i class="fas fa-envelope"></i></button>
            <button class="icon-btn" data-tooltip="Profile"><i class="fas fa-user-circle"></i></button>
            <button class="logout-btn" onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">Logout</button>
        </div>
    </header>

    <nav class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="admin.php"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="user.php" ><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="farm.php"><i class="fas fa-store"></i><span>Farms</span></a></li>
            <li><a href="product.php"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="category.php"class="active"><i class="fas fa-th-large"></i><span>category</span></a></li>
            <!-- <li><a href="#"><i class="fas fa-box"></i><span>Products</span></a></li> -->
            <!-- <li><a href="delivery.php"><i class="fas fa-truck"></i><span>Deliveries</span></a></li>     -->
            <!-- <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i><span>Analytics</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li> -->
        </ul>
    </nav>

    <main class="main-content">
        <div class="category-container">
            <div class="category-form">
                <h2>Add New Category</h2>
                <?php if(isset($message)): ?>
                    <div class="message <?php echo $message_class; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="categoryForm">
                    <div class="form-group">
                        <label for="category">Farm Type</label>
                        <input type="text" id="category" name="category" required>
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="subcategory">Farm Products</label>
                        <input type="text" id="subcategory" name="subcategory" required>
                        <div class="error-message"></div>
                    </div>

                    <button type="submit" name="add_category" class="submit-btn">Add Category</button>
                </form>
            </div>

            <div class="category-list">
                <h2>Categories</h2>
                <table class="category-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($categories_result)): ?>
                        <tr class="<?php echo $row['status'] == '0' ? 'inactive-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo htmlspecialchars($row['sub']); ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="editCategory(<?php echo $row['category_id']; ?>, '<?php echo htmlspecialchars($row['category']); ?>', '<?php echo htmlspecialchars($row['sub']); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn <?php echo $row['status'] == '1' ? 'deactivate-btn' : 'activate-btn'; ?>" 
                                        onclick="toggleCategoryStatus(<?php echo $row['category_id']; ?>, '<?php echo $row['status']; ?>')">
                                    <i class="fas <?php echo $row['status'] == '1' ? 'fa-times' : 'fa-check'; ?>"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- <footer class="footer">
        <p>© 2025 Farmfolio Admin Panel. All rights reserved.</p>
    </footer> -->

    <!-- Edit Category Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>Edit Category</h2>
            <form method="POST" action="" id="editForm">
                <input type="hidden" id="edit_category_id" name="category_id">
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <input type="text" id="edit_category" name="category" required>
                </div>
                <div class="form-group">
                    <label for="edit_subcategory">Subcategory</label>
                    <input type="text" id="edit_subcategory" name="subcategory" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_category" class="submit-btn">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Live validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            let hasError = false;
            const category = document.getElementById('category');
            const subcategory = document.getElementById('subcategory');

            // Validate category
            if (!/^[A-Za-z\s]{2,}$/.test(category.value.trim())) {
                showError(category, 'Category must contain only letters and spaces, minimum 2 characters');
                hasError = true;
            } else {
                hideError(category);
            }

            // Validate subcategory
            if (!/^[A-Za-z\s]{2,}$/.test(subcategory.value.trim())) {
                showError(subcategory, 'Subcategory must contain only letters and spaces, minimum 2 characters');
                hasError = true;
            } else {
                hideError(subcategory);
            }

            if (hasError) {
                e.preventDefault();
            }
        });

        function showError(input, message) {
            const errorDiv = input.nextElementSibling;
            errorDiv.textContent = message;
            errorDiv.style.color = 'red';
            errorDiv.style.fontSize = '12px';
            input.style.borderColor = 'red';
        }

        function hideError(input) {
            const errorDiv = input.nextElementSibling;
            errorDiv.textContent = '';
            input.style.borderColor = '#ddd';
        }

        // Add live validation on input
        document.getElementById('category').addEventListener('input', function() {
            if (!/^[A-Za-z\s]{2,}$/.test(this.value.trim())) {
                showError(this, 'Category must contain only letters and spaces, minimum 2 characters');
            } else {
                hideError(this);
            }
        });

        document.getElementById('subcategory').addEventListener('input', function() {
            if (!/^[A-Za-z\s]{2,}$/.test(this.value.trim())) {
                showError(this, 'Subcategory must contain only letters and spaces, minimum 2 characters');
            } else {
                hideError(this);
            }
        });

        // Edit category function
        function editCategory(categoryId, category, subcategory) {
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_subcategory').value = subcategory;
            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
            document.getElementById('editForm').reset();
        }

        async function toggleCategoryStatus(categoryId, currentStatus) {
            const action = currentStatus == '1' ? 'deactivate' : 'activate';
            if (confirm(`Are you sure you want to ${action} this category?`)) {
                try {
                    const response = await fetch(`category.php?toggle_status=1&id=${categoryId}&status=${currentStatus}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message);
                        window.location.reload();
                    } else {
                        alert("Error: " + result.message);
                    }
                } catch (error) {
                    alert("Error: " + error.message);
                }
            }
        }
    </script>
</body>
</html>
