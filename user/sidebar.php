<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>FarmFolio</h2>
        <button id="toggleSidebar" class="toggle-btn">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="userindex.php" class="<?php echo $current_page == 'userindex.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="browse.php" class="<?php echo $current_page == 'browse.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                <span>Browse Farms</span>
            </a>
        </li>
        <li>
            <a href="cart.php" class="<?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>My Cart</span>
                <?php
                // Get cart count
                $cart_query = "SELECT COUNT(*) as count FROM tbl_cart WHERE user_id = " . $_SESSION['userid'];
                $cart_result = mysqli_query($conn, $cart_query);
                $cart_count = mysqli_fetch_assoc($cart_result)['count'];
                if($cart_count > 0):
                ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                <span>My Orders</span>
            </a>
        </li>
        <li>
            <a href="favorite.php" class="<?php echo $current_page == 'favorite.php' ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i>
                <span>Favorite Farms</span>
                <?php
                // Get favorites count
                $fav_query = "SELECT COUNT(*) as count FROM tbl_favorites WHERE user_id = " . $_SESSION['userid'];
                $fav_result = mysqli_query($conn, $fav_query);
                $fav_count = mysqli_fetch_assoc($fav_result)['count'];
                if($fav_count > 0):
                ?>
                    <span class="badge"><?php echo $fav_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="events.php" class="<?php echo $current_page == 'events.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i>
                <span>Farm Events</span>
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <!-- <li>
            <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li> -->
    </ul>
    <div class="sidebar-footer">
        <a href="../logout/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background: #1a4d2e;
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        padding: 20px 0;
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .sidebar.collapsed {
        width: 70px;
    }

    .sidebar-header {
        padding: 0 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sidebar-header h2 {
        font-size: 1.5rem;
        margin: 0;
    }

    .toggle-btn {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 5px;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }

    .sidebar-menu li {
        margin: 5px 0;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
    }

    .sidebar-menu a:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar-menu a.active {
        background: rgba(255, 255, 255, 0.2);
    }

    .sidebar-menu a i {
        width: 20px;
        margin-right: 10px;
        text-align: center;
    }

    .sidebar-menu a span {
        transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .sidebar-menu a span {
        opacity: 0;
        width: 0;
    }

    .badge {
        background: #e63946;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 0.8rem;
        margin-left: auto;
    }

    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .logout-btn {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        padding: 10px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }

    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .logout-btn i {
        margin-right: 10px;
    }

    .main-content {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s ease;
    }

    .sidebar.collapsed + .main-content {
        margin-left: 70px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }

        .sidebar .sidebar-menu a span,
        .sidebar .sidebar-header h2,
        .sidebar .logout-btn span {
            display: none;
        }

        .main-content {
            margin-left: 70px;
        }

        .toggle-btn {
            display: none;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    const mainContent = document.querySelector('.main-content');

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if(sidebar.classList.contains('collapsed')) {
            mainContent.style.marginLeft = '70px';
        } else {
            mainContent.style.marginLeft = '250px';
        }
    });
});
</script> 