<!DOCTYPE html>
<html>
<head>
    <title>Farmfolio Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f3f4f6;
        }

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background: #ffffff;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .admin-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-controls .icon-btn {
            background: none;
            border: none;
            font-size: 1.2em;
            cursor: pointer;
            color: #666;
        }

        /* Sidebar Styles */
        /* Sidebar Toggle Styles */
.menu-toggle {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    font-size: 1.2em;
    color: white;
    cursor: pointer;
}

.sidebar.shrunk {
    width: 60px;
}

.sidebar.shrunk .sidebar-menu span {
    display: none;
}

.sidebar.shrunk .sidebar-menu a {
    justify-content: center;
}

.sidebar.shrunk .menu-toggle {
    right: 10px;
}

.header.shrunk {
    left: 60px;
}

.main-content.shrunk {
    margin-left: 60px;
}

        .sidebar {
            width: 250px;
            background: #1a4d2e;
            color: white;
            padding: 80px 20px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .sidebar-menu a:hover {
            background: #2d6a4f;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .active {
            background: #2d6a4f;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 80px 20px 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8em;
            font-weight: bold;
            color: #1a4d2e;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .recent-activity {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
        }

        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Footer Styles */
        .footer {
            background: #1a4d2e;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 80px 10px 20px;
            }

            .sidebar-menu span {
                display: none;
            }

            .header {
                left: 60px;
            }

            .main-content {
                margin-left: 60px;
            }

            .search-bar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="search-bar">
            <input type="text" placeholder="Search...">
        </div>
        <div class="admin-controls">
            <button class="icon-btn"><i class="fas fa-bell"></i></button>
            <button class="icon-btn"><i class="fas fa-envelope"></i></button>
            <button class="icon-btn"><i class="fas fa-user-circle"></i></button>
        </div>
    </header>

    <nav class="sidebar">
        <button class="menu-toggle"><i class="fas fa-bars"></i></button>
        <ul class="sidebar-menu">
            <li><a href="#" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-users"></i><span>Users</span></a></li>
            <li><a href="#"><i class="fas fa-store"></i><span>Farms</span></a></li>
            <li><a href="#"><i class="fas fa-box"></i><span>Products</span></a></li>
            <li><a href="#"><i class="fas fa-truck"></i><span>Deliveries</span></a></li>
            <li><a href="#"><i class="fas fa-star"></i><span>Reviews</span></a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i><span>Analytics</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i><span>Settings</span></a></li>
        </ul>
    </nav>
    

    <main class="main-content">
        <h1 style="margin-bottom: 20px">Admin Dashboard</h1>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value">1,234</div>
                <small>+12% this month</small>
            </div>
            <div class="stat-card">
                <h3>Active Farms</h3>
                <div class="value">156</div>
                <small>8 pending approval</small>
            </div>
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value">2,845</div>
                <small>142 categories</small>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="value">$45,678</div>
                <small>This month</small>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h2>Revenue Overview</h2>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="chart-card">
                <h2>User Growth</h2>
                <canvas id="userChart"></canvas>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Farm Applications</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Farm Name</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Green Valley Farms</td>
                            <td>John Smith</td>
                            <td>California, USA</td>
                            <td><span class="status status-pending">Pending</span></td>
                            <td>
                                <button class="icon-btn"><i class="fas fa-check"></i></button>
                                <button class="icon-btn"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Sunrise Organics</td>
                            <td>Mary Johnson</td>
                            <td>Texas, USA</td>
                            <td><span class="status status-active">Active</span></td>
                            <td>
                                <button class="icon-btn"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>Fresh Fields</td>
                            <td>Robert Davis</td>
                            <td>Florida, USA</td>
                            <td><span class="status status-inactive">Inactive</span></td>
                            <td>
                                <button class="icon-btn"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Farmfolio Admin Panel. All rights reserved.</p>
        </footer>
    </main>

    <script>
        // Toggle Sidebar
const menuToggle = document.querySelector('.menu-toggle');
const sidebar = document.querySelector('.sidebar');
const header = document.querySelector('.header');
const mainContent = document.querySelector('.main-content');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('shrunk');
    header.classList.toggle('shrunk');
    mainContent.classList.toggle('shrunk');
});

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue',
                    data: [30000, 35000, 32000, 40000, 45000, 45678],
                    borderColor: '#1a4d2e',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // User Growth Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'New Users',
                    data: [120, 150, 180, 190, 210, 250],
                    backgroundColor: '#2d6a4f'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Menu toggle functionality
        document.querySelectorAll('.sidebar-menu a').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector('.active').classList.remove('active');
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>