<?php
session_start();
include '../databse/connect.php';

// Check if user is logged in and has an active farm
if(!isset($_SESSION['userid'])) {
    header('location: ../login/login.php');
    exit();
}

// Get farm details
$userid = $_SESSION['userid'];
$farm_query = "SELECT * FROM tbl_farms WHERE user_id = $userid";
$farm_result = mysqli_query($conn, $farm_query);
$farm = mysqli_fetch_assoc($farm_result);
$farm_id = $farm['farm_id'];

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


// Fetch existing images
$images_query = "SELECT * FROM tbl_farm_image WHERE farm_id = $farm_id";
$images_result = mysqli_query($conn, $images_query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Farm Images</title>
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
            <h1><?php echo $farm['farm_name']; ?> - Images</h1>
            <div class="user-section">
                <span>Welcome, <?php echo $_SESSION['username']; ?></span>
                <a href="../logout/logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
            </div>
        </div>

        <?php if($farm['status'] == 'active'): ?>
            <div class="upload-container">
                <form class="upload-form" method="POST" enctype="multipart/form-data" onsubmit="return validateImage(document.getElementById('farmImage'))">
                    <div class="upload-section">
                        <div class="preview-container">
                            <img id="imagePreview" src="#" alt="Preview" style="display: none;">
                        </div>
                        <div class="input-container">
                            <input type="file" 
                                   id="farmImage" 
                                   name="farm_image" 
                                   class="file-input" 
                                   accept="image/*" 
                                   required 
                                   onchange="validateImage(this)">
                            <div id="imageError" class="error-message"></div>
                        </div>
                        <button type="submit" name="upload" class="upload-btn">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>
                    </div>
                </form>
            </div>

            <div class="image-grid">
                <?php while($image = mysqli_fetch_assoc($images_result)): ?>
                    <div class="image-card">
                        <div class="image-container">
                            <img src="../<?php echo $image['path']; ?>" alt="Farm Image">
                        </div>
                        <div class="image-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                <button type="submit" name="delete" class="delete-btn">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="inactive-message">
                <i class="fas fa-store-slash" style="font-size: 48px; color: #1a4d2e; margin-bottom: 20px;"></i>
                <h2>Farm Not Active</h2>
                <p>Your farm is currently inactive. Please contact the administrator to activate your farm account.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>