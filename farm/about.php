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

// Fetch farm categories
$farm_categories_query = "SELECT c.* FROM tbl_category c 
                         INNER JOIN tbl_fc fc ON c.category_id = fc.category_id 
                         WHERE fc.farm_id = $farm_id";
$farm_categories = mysqli_query($conn, $farm_categories_query);

// Get all available categories that aren't already linked to this farm
$available_categories_query = "SELECT c.* FROM tbl_category c 
                             WHERE c.status = '1' 
                             AND NOT EXISTS (
                                 SELECT 1 FROM tbl_fc fc 
                                 WHERE fc.category_id = c.category_id 
                                 AND fc.farm_id = $farm_id
                             )
                             ORDER BY c.category, c.sub";
$available_categories = mysqli_query($conn, $available_categories_query);

// Get ALL categories for the new dropdown
$all_categories_query = "SELECT c.*, 
                        (SELECT COUNT(*) FROM tbl_fc fc 
                         WHERE fc.category_id = c.category_id 
                         AND fc.farm_id = $farm_id) as is_assigned 
                        FROM tbl_category c 
                        WHERE c.status = '1'
                        ORDER BY c.category, c.sub";
$all_categories = mysqli_query($conn, $all_categories_query);

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

// Handle adding existing category
if(isset($_POST['add_existing_category'])) {
    if(empty($_POST['category_id'])) {
        echo "<script>alert('Please select a category!');</script>";
        echo "<script>window.location.href='about.php';</script>";
        exit();
    }
    
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    
    // Check if this category is already associated with the farm in tbl_fc - with error handling
    $check_existing = "SELECT * FROM tbl_fc 
                      WHERE farm_id = $farm_id 
                      AND category_id = $category_id";
    $existing_result = mysqli_query($conn, $check_existing);
    
    if(!$existing_result) {
        echo "<script>alert('Database error: " . mysqli_error($conn) . "');</script>";
        echo "<script>window.location.href='about.php';</script>";
        exit();
    }
    
    if(mysqli_num_rows($existing_result) > 0) {
        // Category already exists for this farm
        echo "<script>alert('This category is already added to your farm!');</script>";
        echo "<script>window.location.href='about.php';</script>";
        exit();
    } 
    
    // Link the category to the farm
    $insert_fc = "INSERT INTO tbl_fc (farm_id, category_id) 
                VALUES ($farm_id, $category_id)";
    
    if(mysqli_query($conn, $insert_fc)) {
        echo "<script>alert('Category added to farm successfully!');</script>";
        echo "<script>window.location.href='about.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error adding category: " . mysqli_error($conn) . "');</script>";
        echo "<script>window.location.href='about.php';</script>";
        exit();
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
        :root {
            --primary: #1a4d2e;
            --primary-light: #2d6a4f;
            --primary-dark: #0c3820;
            --primary-transparent: rgba(26, 77, 46, 0.05);
            --secondary: #f59e0b;
            --accent: #0ea5e9;
            --success: #10b981;
            --danger: #dc2626;
            --light: #f3f4f6;
            --dark: #1f2937;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-sm: 4px;
            --radius: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s ease;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            padding: 24px;
        }

        .image-card {
            background: var(--white);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 1px solid var(--gray-light);
        }

        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
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
            padding: 12px;
            display: flex;
            justify-content: flex-end;
        }

        .delete-btn {
            background: linear-gradient(to right, var(--danger), #ef4444);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .upload-container {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 24px;
            margin: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-light);
        }

        .upload-form {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .file-input {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .file-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.2);
            outline: none;
        }

        .upload-btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .upload-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .upload-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        .inactive-message {
            text-align: center;
            padding: 48px;
            color: var(--gray);
            background: var(--white);
            border-radius: var(--radius-md);
            margin: 24px;
            box-shadow: var(--shadow-md);
        }

        .inactive-message h2 {
            color: var(--primary);
            margin-bottom: 16px;
            font-size: 1.5rem;
        }

        .upload-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .preview-container {
            width: 200px;
            height: 200px;
            border: 2px dashed var(--gray-light);
            border-radius: var(--radius);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: var(--transition);
        }

        .preview-container:hover {
            border-color: var(--primary-light);
        }

        #imagePreview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .input-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            margin-top: 6px;
        }

        /* Farm details section */
        .farm-details {
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 32px;
            margin: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-light);
        }

        .details-section {
            margin-bottom: 36px;
        }

        .details-section h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 12px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .details-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .details-section p {
            color: var(--dark);
            line-height: 1.6;
        }

        .details-section p strong {
            color: var(--primary-dark);
        }

        /* Category grid styles */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 24px;
        }

        .category-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-light);
            transition: var(--transition);
        }

        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .category-info h3 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 1.1rem;
        }

        .category-info p {
            margin: 6px 0 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .add-category-form {
            background: var(--white);
            padding: 24px;
            border-radius: var(--radius);
            margin-top: 24px;
            border: 1px solid var(--gray-light);
            box-shadow: var(--shadow);
        }

        .add-category-form h3 {
            color: var(--primary-dark);
            margin-bottom: 16px;
            font-size: 1.2rem;
            position: relative;
            display: inline-block;
        }

        .add-category-form h3::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--primary);
            border-radius: 2px;
        }

        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(26, 77, 46, 0.2);
            outline: none;
        }

        .form-group select {
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M12 15l-4.243-4.243 1.415-1.414L12 12.172l2.828-2.829 1.415 1.414z" fill="rgba(107,114,128,1)"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 40px;
        }

        .add-category-btn {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .add-category-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .delete-category-btn {
            background: linear-gradient(to right, var(--danger), #ef4444);
            color: var(--white);
            border: none;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .delete-category-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 12px;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .farm-details {
                padding: 20px;
                margin: 16px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .category-card {
            animation: fadeIn 0.4s ease-out forwards;
        }

        .category-card:nth-child(2) { animation-delay: 0.05s; }
        .category-card:nth-child(3) { animation-delay: 0.1s; }
        .category-card:nth-child(4) { animation-delay: 0.15s; }
        .category-card:nth-child(5) { animation-delay: 0.2s; }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid var(--gray);
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            background-color: var(--light);
            border-radius: var(--radius);
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Additional select styling */
        select.form-control {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            appearance: none;
            background-color: var(--white);
            font-size: 1rem;
            color: var(--dark);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        select.form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(26, 77, 46, 0.2);
            outline: none;
        }
        
        select.form-control:hover {
            border-color: var(--primary-light);
        }
        
        /* Styles for assigned categories */
        .assigned-category {
            background-color: #f3f4f6;
            color: #9ca3af;
            font-style: italic;
        }
        
        option:disabled {
            background-color: #f3f4f6;
            color: #9ca3af;
        }
        
        /* Hint text styling */
        .hint-text {
            color: var(--gray);
            font-size: 0.85rem;
            margin-top: 8px;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .hint-text i {
            color: var(--primary);
            font-size: 0.9rem;
        }

        /* Styles for category selection grid */
        .category-select-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 15px;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        
        .category-select-card {
            display: block;
            background: var(--white);
            border: 2px solid var(--gray-light);
            border-radius: var(--radius);
            padding: 15px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .category-select-card:hover:not(.disabled) {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }
        
        .category-select-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .category-select-card input[type="radio"]:checked + .card-content {
            background-color: rgba(26, 77, 46, 0.05);
        }
        
        .category-select-card input[type="radio"]:checked + .card-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
        }
        
        .category-select-card.selected {
            border-color: var(--primary);
            border-width: 2px;
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }
        
        .category-select-card.selected .card-content {
            background-color: rgba(26, 77, 46, 0.05);
        }
        
        .card-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            transition: var(--transition);
        }
        
        .category-select-info h4 {
            margin: 0;
            color: var(--primary-dark);
            font-size: 1rem;
            font-weight: 600;
        }
        
        .category-select-info p {
            margin: 5px 0 0;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .already-added {
            color: var(--gray);
            font-size: 1.2rem;
        }
        
        .category-select-card.disabled {
            opacity: 0.7;
            background-color: var(--light);
            cursor: not-allowed;
            border-color: var(--gray-light);
        }
        
        .category-select-card.disabled .category-select-info h4,
        .category-select-card.disabled .category-select-info p {
            color: var(--gray);
        }
        
        /* Animation for the cards */
        .category-select-card {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        .category-select-card:nth-child(2n) { animation-delay: 0.05s; }
        .category-select-card:nth-child(3n) { animation-delay: 0.1s; }
        .category-select-card:nth-child(4n) { animation-delay: 0.15s; }
        
        /* Form actions styling */
        .form-actions {
            display: flex;
            justify-content: flex-end;
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

    document.addEventListener('DOMContentLoaded', function() {
        // Add form validation for category selection
        const categoryForm = document.querySelector('.add-category-form form');
        const categoryRadios = document.querySelectorAll('input[name="category_id"]');
        
        if(categoryForm) {
            // Add visual feedback when a card is selected
            categoryRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Remove selection highlight from all cards
                    document.querySelectorAll('.category-select-card').forEach(card => {
                        card.classList.remove('selected');
                    });
                    
                    // Add selection highlight to selected card
                    if(this.checked && !this.disabled) {
                        this.closest('.category-select-card').classList.add('selected');
                    }
                });
            });
            
            // Form submission validation
            categoryForm.addEventListener('submit', function(e) {
                // Check if any category is selected
                const isAnySelected = Array.from(categoryRadios).some(radio => radio.checked);
                
                if(!isAnySelected) {
                    e.preventDefault();
                    alert('Please select a category to add to your farm.');
                    return false;
                }
            });
        }
    });
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
                        <h3 style="margin-top: 30px;">Add Category to Farm</h3>
                        
                        <form method="POST">
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label class="form-label">All Available Categories</label>
                                
                                <div class="category-select-grid">
                                    <?php 
                                    // Use all_categories
                                    mysqli_data_seek($all_categories, 0);
                                    $available_count = 0;
                                    while($cat = mysqli_fetch_assoc($all_categories)): 
                                        $isAssigned = $cat['is_assigned'] > 0;
                                        if(!$isAssigned) {
                                            $available_count++;
                                        }
                                        $cardClass = $isAssigned ? 'category-select-card disabled' : 'category-select-card';
                                    ?>
                                        <label class="<?php echo $cardClass; ?>">
                                            <input type="radio" name="category_id" value="<?php echo $cat['category_id']; ?>" 
                                                  <?php echo $isAssigned ? 'disabled' : ''; ?> required>
                                            <div class="card-content">
                                                <div class="category-select-info">
                                                    <h4><?php echo htmlspecialchars($cat['category']); ?></h4>
                                                    <p><?php echo htmlspecialchars($cat['sub']); ?></p>
                                                </div>
                                                <?php if($isAssigned): ?>
                                                    <div class="already-added">
                                                        <i class="fas fa-check-circle"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endwhile; ?>
                                </div>
                                
                                <?php if($available_count == 0): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-list-ul" style="font-size: 2.5rem; color: var(--gray-light); margin-bottom: 1rem;"></i>
                                        <p style="color: var(--gray); font-style: italic;">All categories have been added to your farm.</p>
                                    </div>
                                <?php else: ?>
                                    <p class="hint-text"><i class="fas fa-info-circle"></i> Click a card to select that category. Gray cards are already added to your farm.</p>
                                    <div class="form-actions" style="margin-top: 20px;">
                                        <button type="submit" name="add_existing_category" class="add-category-btn">
                                            <i class="fas fa-plus"></i> Add Selected Category
                                        </button>
                                </div>
                                <?php endif; ?>
                            </div>
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
</body>
</html>