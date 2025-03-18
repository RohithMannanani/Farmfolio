<?php  
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}
// After successfully inserting farm data and getting the farm_id
if (isset($_POST['selected_categories']) && is_array($_POST['selected_categories'])) {
    $farm_id = $conn->insert_id; // Assuming this is after your farm insert query
    
    $insert_category = "INSERT INTO tbl_fc (farm_id, category_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_category);
    
    foreach ($_POST['selected_categories'] as $category_id) {
        $stmt->bind_param("ii", $farm_id, $category_id);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farm Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="farm.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0px; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        textarea { height: 100px; resize: vertical; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; display: none; }
        button { background-color: rgb(16, 159, 61); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
        button:hover { background-color: rgb(4, 158, 63); }
        .success-message { color: #28a745; text-align: center; margin-top: 20px; display: <?= isset($_SESSION['success_message']) ? 'block' : 'none'; ?>; }
        .main-content {
    margin-left: 250px;
    flex: 1;
    flex-direction: column;
    min-height: 100vh;
}
.category-selection {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.category-select {
    flex: 1;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#addCategoryBtn {
    padding: 8px 15px;
    background-color: #1a4d2e;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

#addCategoryBtn:disabled {
    background-color: #cccccc;
    cursor: not-allowed;
}

.selected-categories {
    margin-top: 10px;
}

.category-tag {
    display: inline-flex;
    align-items: center;
    background-color: #e2e8f0;
    padding: 5px 10px;
    border-radius: 15px;
    margin: 5px;
}

.remove-category {
    margin-left: 8px;
    cursor: pointer;
    color: #ef4444;
}

.hidden {
    display: none;
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
            <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
             <!--<li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->
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
            </div>
    <div class="container">
    
        <b><p style="color: red; font-size:20px; margin-bottom:25px;">*kindly please complete this form before accesing this site</p></b>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <form id="farmForm" action="" method="POST">
            <div class="form-group">
                <label for="farm_name">Farm Name*</label>
                <input type="text" id="farm_name" name="farm_name" required>
                <div class="error" id="farm_name_error"></div>
            </div>

            <div class="form-group">
                <label for="location">Location*</label>
                <input type="text" id="location" name="location" required>
                <div class="error" id="location_error"></div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
                <div class="error" id="description_error"></div>
            </div>

            <div class="form-group">
                <label for="categories">Select Farm Categories*</label>
                <div class="category-selection">
                    <select id="mainCategory" class="category-select">
                        <option value="">Select Farm Type</option>
                        <?php
                        $cat_query = "SELECT DISTINCT category FROM tbl_category ORDER BY category";
                        $cat_result = mysqli_query($conn, $cat_query);
                        while($cat_row = mysqli_fetch_assoc($cat_result)) {
                            echo "<option value='" . htmlspecialchars($cat_row['category']) . "'>" . 
                                 ucfirst(htmlspecialchars($cat_row['category'])) . "</option>";
                        }
                        ?>
                    </select>

                    <select id="subCategory" class="category-select" disabled>
                        <option value="">Select Farm Products</option>
                    </select>

                    <button type="button" id="addCategoryBtn" disabled>Add Category</button>
                </div>

                <div id="selectedCategories" class="selected-categories">
                    <!-- Selected categories will appear here -->
                </div>
                <div class="error" id="categories_error"></div>
            </div>

            <button type="submit">Register Farm</button>
        </form>
    </div>


        <footer class="footer">
            <p>© 2025 Farmfolio. All rights reserved.</p>
            <p style="margin-top: 5px; font-size: 0.9em;">Connecting Farms to Communities</p>
        </footer>
    </div>

    <script>
        document.getElementById('farmForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault(); // Prevent form submission if validation fails
            }
        });

        function validateForm() {
            let isValid = true;

            // Farm Name validation
            const farmName = document.getElementById('farm_name').value.trim();
            if (farmName.length === 0) {
                showError('farm_name_error', 'Farm name is required');
                isValid = false;
            } else if (farmName.length > 255) {
                showError('farm_name_error', 'Farm name must be less than 255 characters');
                isValid = false;
            } else {
                hideError('farm_name_error');
            }

            // Location validation
            const location = document.getElementById('location').value.trim();
            if (location.length === 0) {
                showError('location_error', 'Location is required');
                isValid = false;
            } else if (location.length > 255) {
                showError('location_error', 'Location must be less than 255 characters');
                isValid = false;
            } else {
                hideError('location_error');
            }

            return isValid;
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }

        function hideError(elementId) {
            document.getElementById(elementId).style.display = 'none';
        }

        document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("farmForm");
    const sidebarLinks = document.querySelectorAll(".sidebar-menu a");

    // Function to disable all sidebar links
    function disableSidebarLinks() {
        sidebarLinks.forEach(link => {
            link.classList.add("disabled");
            link.addEventListener("click", function (e) {
                e.preventDefault(); // Prevent navigation
                alert("Please complete and submit the form before accessing other options.");
            });
        });
    }

    // Function to enable sidebar links after form submission
    function enableSidebarLinks() {
        sidebarLinks.forEach(link => {
            link.classList.remove("disabled");
            link.onclick = null; // Remove the preventDefault behavior
        });
    }

    // Initially disable sidebar links
    disableSidebarLinks();

    // Check if form is submitted
    form.addEventListener("submit", function (e) {
        if (validateForm()) {
            enableSidebarLinks(); // Enable navigation upon successful form submission
        } else {
            e.preventDefault(); // Prevent form submission if validation fails
        }
    });

    function validateForm() {
        let isValid = true;

        // Farm Name validation
        const farmName = document.getElementById("farm_name").value.trim();
        if (farmName.length === 0) {
            showError("farm_name_error", "Farm name is required");
            isValid = false;
        } else {
            hideError("farm_name_error");
        }

        // Location validation
        const location = document.getElementById("location").value.trim();
        if (location.length === 0) {
            showError("location_error", "Location is required");
            isValid = false;
        } else {
            hideError("location_error");
        }

        return isValid;
    }
        });

    </script>
    <script src="farm.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const mainCategory = document.getElementById('mainCategory');
    const subCategory = document.getElementById('subCategory');
    const addButton = document.getElementById('addCategoryBtn');
    const selectedContainer = document.getElementById('selectedCategories');
    const selectedCategories = new Set();

    mainCategory.addEventListener('change', function() {
        const category = this.value;
        subCategory.disabled = !category;
        subCategory.innerHTML = '<option value="">Select Farm Products</option>';
        addButton.disabled = true;

        if (category) {
            // Fetch subcategories using AJAX
            fetch(`get_subcategories.php?category=${encodeURIComponent(category)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.category_id;
                        option.textContent = sub.sub;
                        subCategory.appendChild(option);
                    });
                });
        }
    });

    subCategory.addEventListener('change', function() {
        addButton.disabled = !this.value;
    });

    addButton.addEventListener('click', function() {
        const categoryId = subCategory.value;
        const categoryText = `${mainCategory.options[mainCategory.selectedIndex].text} - ${subCategory.options[subCategory.selectedIndex].text}`;

        if (!selectedCategories.has(categoryId)) {
            selectedCategories.add(categoryId);
            
            const tag = document.createElement('div');
            tag.className = 'category-tag';
            tag.innerHTML = `
                ${categoryText}
                <input type="hidden" name="selected_categories[]" value="${categoryId}">
                <span class="remove-category" data-id="${categoryId}">&times;</span>
            `;
            
            selectedContainer.appendChild(tag);
        }

        // Reset selections
        mainCategory.value = '';
        subCategory.innerHTML = '<option value="">Select Farm Products</option>';
        subCategory.disabled = true;
        addButton.disabled = true;
    });

    selectedContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-category')) {
            const categoryId = e.target.dataset.id;
            selectedCategories.delete(categoryId);
            e.target.parentElement.remove();
        }
    });

    // Modify your form submission to include validation
    document.getElementById('farmForm').addEventListener('submit', function(e) {
        if (selectedCategories.size === 0) {
            e.preventDefault();
            document.getElementById('categories_error').textContent = 'Please select at least one category';
        }
    });
});
</script>
</body>
</html>
