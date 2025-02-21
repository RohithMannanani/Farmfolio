// Initialize Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            datasets: [{
                label: 'Sales',
                data: [4000, 3000, 5000, 4500, 6000],
                borderColor: '#2563eb',
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

    // Notifications
    const notifications = [
        { message: 'New order received', time: '5m ago' },
        { message: 'Product review posted', time: '1h ago' },
        { message: 'Upcoming farm event', time: '2h ago' }
    ];

    const notificationsList = document.getElementById('notificationsList');
    notifications.forEach(notification => {
        const li = document.createElement('li');
        li.className = 'notification-item';
        li.innerHTML = `
            <div class="notification-message">${notification.message}</div>
            <div class="notification-time">${notification.time}</div>
        `;
        notificationsList.appendChild(li);
    });

