<?php
session_start();
include '../databse/connect.php';

// Check if user is logged in and has an active farm
if(!isset($_SESSION['userid'])) {
    header('location: http://localhost/mini%20project/login/login.php');
    exit();
}

// Get farm details
$userid = $_SESSION['userid'];
$farm_query = "SELECT * FROM tbl_farms WHERE user_id = $userid";
$farm_result = mysqli_query($conn, $farm_query);
$farm = mysqli_fetch_assoc($farm_result);
$farm_id = $farm['farm_id'];

// Fetch farm categories
$farm_categories_query = "SELECT c.* FROM tbl_category c 
                         INNER JOIN tbl_fc fc ON c.category_id = fc.category_id 
                         WHERE fc.farm_id = $farm_id";
$farm_categories = mysqli_query($conn, $farm_categories_query);

// Handle image upload
if(isset($_POST['upload'])) {
    $target_dir = "../uploads/farm_images/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file = $_FILES['farm_image'];
    $file_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $file_name;
    $image_path = 'uploads/farm_images/' . $file_name;

    if(move_uploaded_file($file['tmp_name'], $target_file)) {
        $insert_query = "INSERT INTO tbl_farm_image (farm_id, path) VALUES ($farm_id, '$image_path')";
        mysqli_query($conn, $insert_query);
    
    // Redirect after processing
    header("Location: image.php");
    exit();
}}

// Handle image deletion
if(isset($_POST['delete'])) {
    $image_id = mysqli_real_escape_string($conn, $_POST['image_id']);
    
    // Get the image path first
    $path_query = "SELECT path FROM tbl_farm_image WHERE image_id = '$image_id' AND farm_id = '$farm_id'";
    $path_result = mysqli_query($conn, $path_query);
    
    if($path_result && mysqli_num_rows($path_result) > 0) {
        $path_data = mysqli_fetch_assoc($path_result);
        $image_path = "../" . $path_data['path'];
        
        // Check if file exists before trying to delete
        if(file_exists($image_path) && is_file($image_path)) {
            // Delete the file
            unlink($image_path);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM tbl_farm_image WHERE image_id = '$image_id' AND farm_id = '$farm_id'";
        mysqli_query($conn, $delete_query);
    
    // Redirect after processing
    header("Location: image.php");
    exit();
}}

// Replace the existing category addition handler
if(isset($_POST['add_category'])) {
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $sub_category = mysqli_real_escape_string($conn, trim($_POST['sub_category']));
    
    // Check if category exists in database (case insensitive)
    $check_existing = "SELECT c.* FROM tbl_category c 
                      WHERE LOWER(c.category) = LOWER('$category') 
                      AND LOWER(c.sub) = LOWER('$sub_category')";
    $existing_result = mysqli_query($conn, $check_existing);
    
    if(mysqli_num_rows($existing_result) > 0) {
        // Category exists, check if it's already linked to this farm
        $existing_category = mysqli_fetch_assoc($existing_result);
        $category_id = $existing_category['category_id'];
        
        $check_farm_category = "SELECT * FROM tbl_fc 
                              WHERE farm_id = $farm_id 
                              AND category_id = $category_id";
        $farm_category_result = mysqli_query($conn, $check_farm_category);
        
        if(mysqli_num_rows($farm_category_result) > 0) {
            echo "<script>alert('This category is already added to your farm!');</script>";
            echo "<script>window.location.href='about.php';</script>";
            exit();
        } else {
            // Category exists but not linked to farm, link it
            $insert_fc = "INSERT INTO tbl_fc (farm_id, category_id) 
                         VALUES ($farm_id, $category_id)";
            if(mysqli_query($conn, $insert_fc)) {
                echo "<script>alert('Category added to farm successfully!');</script>";
                echo "<script>window.location.href='about.php';</script>";
            }
        }
    } else {
        // Category doesn't exist, create new and link
        $insert_category = "INSERT INTO tbl_category (category, sub, status) 
                           VALUES ('$category', '$sub_category', '1')";
        
        if(mysqli_query($conn, $insert_category)) {
            $new_category_id = mysqli_insert_id($conn);
            
            $insert_fc = "INSERT INTO tbl_fc (farm_id, category_id) 
                         VALUES ($farm_id, $new_category_id)";
            
            if(mysqli_query($conn, $insert_fc)) {
                echo "<script>alert('New category created and added successfully!');</script>";
                echo "<script>window.location.href='about.php';</script>";
            }
        }
    }
}

// Handle category deletion
if(isset($_POST['delete_category'])) {
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    
    // First delete from tbl_fc
    $delete_fc = "DELETE FROM tbl_fc WHERE farm_id = $farm_id AND category_id = $category_id";
    if(mysqli_query($conn, $delete_fc)) {
        echo "<script>alert('Category removed from farm successfully!');</script>";
        echo "<script>window.location.href='about.php';</script>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Farm About</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="farm.css">
    <style>
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .image-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .image-container {
            position: relative;
            padding-top: 75%; /* 4:3 Aspect Ratio */
        }

        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-actions {
            padding: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .delete-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .upload-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .upload-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .file-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .upload-btn {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .inactive-message {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .upload-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .upload-form {
            width: 100%;
        }

        .upload-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .preview-container {
            width: 200px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        #imagePreview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .input-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .file-input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
        }

        .upload-btn {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .upload-btn:hover:not(:disabled) {
            background: #2d6a4f;
        }

        .upload-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .preview-container {
                width: 150px;
                height: 150px;
            }
        }

        .farm-details {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .details-section {
            margin-bottom: 30px;
        }

        .details-section h2 {
            color: #1a4d2e;
            margin-bottom: 15px;
            border-bottom: 2px solid #1a4d2e;
            padding-bottom: 10px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .category-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-info h3 {
            margin: 0;
            color: #1a4d2e;
        }

        .category-info p {
            margin: 5px 0 0;
            color: #666;
            font-size: 0.9em;
        }

        .add-category-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .add-category-btn {
            background: #1a4d2e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        .delete-category-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
    </style>
    <script>
    function validateImage(input) {
    const file = input.files[0];
    const errorDisplay = document.getElementById('imageError');
    const submitBtn = document.querySelector('.upload-btn');
    const preview = document.getElementById('imagePreview');
    
    // Reset error message and preview
    errorDisplay.textContent = '';
    submitBtn.disabled = false;
    preview.style.display = 'none';
    preview.src = '#';

    // Check if a file is selected
    if (!file) {
        errorDisplay.textContent = 'Please select an image.';
        submitBtn.disabled = true;
        return false;
    }

    // Check file type
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    if (!validTypes.includes(file.type)) {
        errorDisplay.textContent = 'Please select a valid image file (JPEG, PNG, GIF).';
        submitBtn.disabled = true;
        return false;
    }

    // Check file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        errorDisplay.textContent = 'Image size should be less than 5MB.';
        submitBtn.disabled = true;
        return false;
    }

    // Show image preview
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    }
    reader.readAsDataURL(file);

    return true;
}

    </script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1><?php echo $farm['farm_name']; ?> - About</h1>
            <div class="user-section">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
            </div>
        </div>

        <?php if($farm['status'] == 'active'): ?>
            <div class="farm-details">
                <div class="details-section">
                    <h2>Farm Information</h2>
                    <p><strong>Farm Name:</strong> <?php echo htmlspecialchars($farm['farm_name']); ?></p>
                    <!-- Add other farm details as needed -->
                </div>

                <div class="details-section">
                    <h2>Product Categories</h2>
                    <div class="category-grid">
                        <?php while($category = mysqli_fetch_assoc($farm_categories)): ?>
                            <div class="category-card">
                                <div class="category-info">
                                    <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                                    <p><?php echo htmlspecialchars($category['sub']); ?></p>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                    <button type="submit" name="delete_category" class="delete-category-btn" 
                                            onclick="return confirm('Are you sure you want to remove this category?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="add-category-form">
                        <h3>Add New Category</h3>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">Main Category*</label>
                                    <input type="text" id="category" name="category" required 
                                           placeholder="e.g., Vegetables, Fruits">
                                </div>
                                <div class="form-group">
                                    <label for="sub_category">Sub Category*</label>
                                    <input type="text" id="sub_category" name="sub_category" required 
                                           placeholder="e.g., Organic Vegetables">
                                </div>
                            </div>
                            <button type="submit" name="add_category" class="add-category-btn">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash" style="font-size: 48px; color: #1a4d2e; margin-bottom: 20px;"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add form validation
        const categoryForm = document.querySelector('.add-category-form form');
        if(categoryForm) {
            categoryForm.addEventListener('submit', function(e) {
                const category = document.getElementById('category').value.trim();
                const subCategory = document.getElementById('sub_category').value.trim();
                
                if(category.length < 3 || subCategory.length < 3) {
                    e.preventDefault();
                    alert('Category and sub-category names must be at least 3 characters long.');
                    return false;
                }
                
                if(!/^[a-zA-Z\s]+$/.test(category) || !/^[a-zA-Z\s]+$/.test(subCategory)) {
                    e.preventDefault();
                    alert('Category and sub-category names should only contain letters and spaces.');
                    return false;
                }
            });
        }
        if(categoryForm) {
            const categoryInput = document.getElementById('category');
            const subCategoryInput = document.getElementById('sub_category');
            
            async function checkCategoryExists() {
                const category = categoryInput.value.trim();
                const subCategory = subCategoryInput.value.trim();
                
                try {
                    const response = await fetch('check_category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `category=${encodeURIComponent(category)}&sub_category=${encodeURIComponent(subCategory)}&farm_id=<?php echo $farm_id; ?>`
                    });
                    
                    const data = await response.json();
                    return data.exists;
                } catch (error) {
                    console.error('Error checking category:', error);
                    return false;
                }
            }
            
            categoryForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const category = categoryInput.value.trim();
                const subCategory = subCategoryInput.value.trim();
                
                if(category.length < 3 || subCategory.length < 3) {
                    alert('Category and sub-category names must be at least 3 characters long.');
                    return;
                }
                
                if(!/^[a-zA-Z\s]+$/.test(category) || !/^[a-zA-Z\s]+$/.test(subCategory)) {
                    alert('Category and sub-category names should only contain letters and spaces.');
                    return;
                }
                
                const exists = await checkCategoryExists();
                if(exists) {
                    alert('This category is already added to your farm!');
                    return;
                }
                
                this.submit();
            });
        }
    });
    </script>
</body>
</html>