<?php
session_start();
include '../databse/connect.php';
if(!isset($_SESSION['username'])){
    header('location: http://localhost/mini%20project/login/login.php');
}

// Get user's favorite farms with all details
$user_id = $_SESSION['userid'];
$favorites_query = "SELECT 
    f.farm_id,
    f.farm_name,
    f.location,
    f.description,
    f.status,
    COUNT(p.product_id) as product_count,
    MIN(fi.path) as farm_image,
    fav.created_at as favorited_at
FROM tbl_favorites fav
JOIN tbl_farms f ON fav.farm_id = f.farm_id
LEFT JOIN tbl_products p ON f.farm_id = p.farm_id
LEFT JOIN tbl_farm_image fi ON f.farm_id = fi.farm_id
WHERE fav.user_id = ? AND f.status = 'active'
GROUP BY f.farm_id
ORDER BY fav.created_at DESC";

$stmt = mysqli_prepare($conn, $favorites_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$favorites_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favorite Farms - Farmfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Add the JavaScript here -->
    <script>
    async function removeFavorite(button, farmId) {
        try {
            // Add loading state
            const card = button.closest('.farm-card');
            button.classList.add('loading');
            card.classList.add('removing');
            
            const response = await fetch('remove_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `farm_id=${farmId}`
            });

            const data = await response.json();
            
            if (data.success) {
                // Animate and remove the card
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    card.remove();
                    
                    // Check if there are any cards left
                    const remainingCards = document.querySelectorAll('.farm-card');
                    if (remainingCards.length === 0) {
                        // Show the no favorites message
                        const favoritesGrid = document.querySelector('.favorites-grid');
                        favoritesGrid.innerHTML = `
                            <div class="no-favorites">
                                <i class="fas fa-heart-broken"></i>
                                <p>You haven't added any farms to your favorites yet.</p>
                                <a href="browse.php" class="browse-farms-btn">
                                    <i class="fas fa-search"></i> Browse Farms
                                </a>
                            </div>
                        `;
                    }
                }, 300);
            } else {
                throw new Error(data.message || 'Error removing from favorites');
            }
        } catch (error) {
            // Remove loading state
            button.classList.remove('loading');
            card.classList.remove('removing');
            
            console.error('Error:', error);
            alert(error.message || 'An error occurred while removing from favorites');
        }
    }
    </script>
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
            background-color: #f0f2f5;
        }

        .sidebar {
            width: 250px;
            background: #1a4d2e;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: width 0.3s ease;
        }

        .sidebar .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar .sidebar-header h2 {
            transition: opacity 0.3s ease, width 0.3s ease;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar-menu a:hover {
            background: #2d6a4f;
        }

        .sidebar-menu i {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .active {
            background: #2d6a4f;
            font-weight: 500;
        }
        
        .pro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-menu-container {
            position: relative;
        }

        .profile-icon {
            width: 40px;
            height: 40px;
            background-color: #1a4d2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-icon i {
            color: white;
            font-size: 1.2rem;
        }

        .profile-icon:hover {
            background-color: #2d6a4f;
        }

        .profile-popup {
            position: absolute;
            top: 120%;
            right: 0;
            width: 220px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .profile-popup.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-info {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-name {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .profile-email {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .popup-logout-btn {
            width: 100%;
            padding: 12px 15px;
            text-align: left;
            background: none;
            border: none;
            color: #dc2626;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .popup-logout-btn:hover {
            background-color: #f3f4f6;
        }

        .popup-logout-btn i {
            font-size: 0.9rem;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            transition: margin-left 0.3s ease;
            padding: 20px;
            background-color: #f0f2f5;
        }

        .popup-logout-btn {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 15px 20px;
            border-radius: 0 0 15px 15px;
            transition: all 0.3s ease;
        }

        .popup-logout-btn:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }

            .main-content {
                margin-left: 60px;
            }
        }

        .dashboard-header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #1a4d2e;
            font-size: 1.8rem;
        }

        .footer {
            background: white;
            color: #4b5563;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
            border-radius: 12px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .logout-btn {
            padding: 10px 20px;
            background: linear-gradient(to right, #dc2626, #ef4444);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 10px rgba(220,38,38,0.2);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
        }

        /* removing favorite */
        .remove-favorite {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 2;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.remove-favorite i {
    color: #e63946;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.remove-favorite:hover {
    transform: scale(1.1);
    background: #fff;
}

.remove-favorite:hover i {
    transform: scale(1.1);
}

.farm-card.removing {
    opacity: 0.5;
    pointer-events: none;
}

/* Add loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.remove-favorite.loading i {
    animation: spin 1s linear infinite;
}
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 20px;
        }

        .farm-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .farm-card:hover {
            transform: translateY(-5px);
        }

        .farm-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .farm-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #9ca3af;
        }

        .farm-status {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1;
        }

        .farm-status.active {
            background-color: #dcfce7;
            color: #166534;
        }

        .farm-status.inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .farm-status.pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .farm-details {
            padding: 20px;
        }

        .farm-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a4d2e;
            margin-bottom: 10px;
        }

        .farm-location {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .farm-description {
            color: #4b5563;
            font-size: 0.95rem;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .farm-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .view-farm {
            display: block;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .view-farm:hover {
            background: #2d6a4f;
        }

        .no-favorites {
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
        }

        .no-favorites i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #d1d5db;
        }

        .no-favorites p {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .browse-farms-btn {
            display: inline-block;
            background: #1a4d2e;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .browse-farms-btn:hover {
            background: #2d6a4f;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="userindex.php" ><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse.php" ><i class="fas fa-store"></i><span>Browse Farms</span></a></li>
            <li><a href="cart.php" ><i class="fas fa-shopping-cart"></i><span>My Cart</span></a></li>
            <li><a href="orders.php" ><i class="fas fa-truck"></i><span>My Orders</span></a></li>
            <li><a href="favorite.php" class="active" ><i class="fas fa-heart"></i><span>Favorite Farms</span></a></li>
            <li><a href="events.php" ><i class="fas fa-calendar"></i><span>Farm Events</span></a></li>
            <li><a href="profile.php" ><i class="fas fa-user"></i><span>Profile</span></a></li>
            <!-- <li><a href="settings.php" ><i class="fas fa-cog"></i><span>Settings</span></a></li> -->
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="container">
            <div class="user-section">
                <div class="profile-menu-container">
                    <div class="pro">
                        <div class="head">
                            <h2>FarmFolio</h2>
                        </div>
                        <div class="profile-icon" id="profileIcon">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <div class="profile-popup" id="profilePopup">
                        <div class="profile-info">
                        <p class="profile-name"><?php echo $_SESSION['username'];?></p>
                        <p class="profile-email"><?php echo $_SESSION['email'];?></p>
                        </div>
                        <button class="popup-logout-btn"  onclick="window.location.href='http://localhost/mini%20project/logout/logout.php'">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </div>
            </div>

            <h1>My Favorite Farms</h1>

            <?php if(mysqli_num_rows($favorites_result) > 0): ?>
                <div class="favorites-grid">
                    <?php while($farm = mysqli_fetch_assoc($favorites_result)): ?>
                        <div class="farm-card">
                            <span class="farm-status <?php echo strtolower($farm['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($farm['status'])); ?>
                            </span>
                            <button class="remove-favorite" 
                                    onclick="removeFavorite(this, <?php echo $farm['farm_id']; ?>)"
                                    title="Remove from favorites">
                                <i class="fas fa-heart"></i>
                            </button>
                            <div class="farm-image">
                                <?php if($farm['farm_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($farm['farm_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($farm['farm_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-farm"></i>
                                        <p>No Image Available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="farm-details">
                                <h3 class="farm-name"><?php echo htmlspecialchars($farm['farm_name']); ?></h3>
                                <p class="farm-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($farm['location']); ?>
                                </p>
                                <p class="farm-description">
                                    <?php echo htmlspecialchars($farm['description']); ?>
                                </p>
                                <div class="farm-meta">
                                    <span>
                                        <i class="fas fa-box"></i> 
                                        <?php echo $farm['product_count']; ?> Products
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        Added <?php echo date('M d, Y', strtotime($farm['favorited_at'])); ?>
                                    </span>
                                </div>
                                <a href="farm_details.php?id=<?php echo $farm['farm_id']; ?>" class="view-farm">
                                    View Farm
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-favorites">
                    <i class="fas fa-heart-broken"></i>
                    <p>You haven't added any farms to your favorites yet.</p>
                    <a href="browse.php" class="browse-farms-btn">
                        <i class="fas fa-search"></i> Browse Farms
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; 2024 Farmfolio. All rights reserved.</p>
        </div>
    </div>

    <script>
         document.addEventListener('DOMContentLoaded', function() {
            const profileIcon = document.getElementById('profileIcon');
            const profilePopup = document.getElementById('profilePopup');
            let timeoutId;

            function showPopup() {
                profilePopup.classList.add('show');
            }

            function hidePopup() {
                profilePopup.classList.remove('show');
            }

            profileIcon.addEventListener('mouseenter', () => {
                clearTimeout(timeoutId);
                showPopup();
            });

            profileIcon.addEventListener('mouseleave', () => {
                timeoutId = setTimeout(() => {
                    if (!profilePopup.matches(':hover')) {
                        hidePopup();
                    }
                }, 300);
            });

            profilePopup.addEventListener('mouseenter', () => {
                console.log("Mouse entered icon");
                clearTimeout(timeoutId);
                showPopup(); 
            });

            profilePopup.addEventListener('mouseleave', () => {
                
                timeoutId = setTimeout(hidePopup, 300);
            });

            profileIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                profilePopup.classList.toggle('show');
            });

            document.addEventListener('click', (e) => {
                if (!profileIcon.contains(e.target) && !profilePopup.contains(e.target)) {
                    hidePopup();
                }
            });
        })

           </script>
</body>
</html>