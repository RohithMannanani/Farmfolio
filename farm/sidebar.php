<nav class="sidebar">
    <div class="sidebar-header">
        <h2>Farmfolio</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="farm.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
        <li><a href="product.php"><i class="fas fa-box"></i><span>Products</span></a></li>
        <li><a href="image.php"><i class="fas fa-image"></i><span>Farm Images</span></a></li>
        <li><a href="event.php"><i class="fas fa-calendar"></i><span>Events</span></a></li>
        <li><a href="review.php"><i class="fas fa-star"></i><span>Reviews</span></a></li>
        <li><a href="orders.php"><i class="fas fa-truck"></i><span>Orders</span></a></li>
         <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i><span>Farm Details </span></a></li>
         <!--<li><a href="about.php"><i class="fas fa-info-circle"></i><span>About</span></a></li> -->

    </ul>
</nav>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #1a4d2e;
    color: white;
    padding: 20px 0;
    z-index: 1000;
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

.sidebar-menu span {
    font-size: 16px;
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }

    .sidebar-header h2,
    .sidebar-menu span {
        display: none;
    }

    .sidebar-menu a {
        justify-content: center;
        padding: 15px;
    }

    .sidebar-menu i {
        margin: 0;
        font-size: 20px;
    }

    .main-content {
        margin-left: 70px;
    }
}
</style> 