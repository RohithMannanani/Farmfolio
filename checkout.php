<?php
session_start();
include '../databse/connect.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit();
}

// Check if the cart is not empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    echo "Your cart is empty.";
    exit();
}

// Retrieve cart items
$cartItems = $_SESSION['cart'];
$totalPrice = 0;

// Prepare an array to hold product IDs and quantities
$productIds = [];
$quantities = [];

// Calculate total price and prepare data for the order
foreach ($cartItems as $productId => $quantity) {
    $productId = intval($productId);
    $quantity = intval($quantity);
    
    // Fetch product details from the database
    $query = "SELECT price, stock FROM tbl_products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $totalPrice += $product['price'] * $quantity;
        
        // Check stock availability
        if ($product['stock'] < $quantity) {
            echo "Not enough stock for product ID: $productId.";
            exit();
        }
        
        // Store product IDs and quantities for order processing
        $productIds[] = $productId;
        $quantities[] = $quantity;
    }
}

// Process payment (this is a placeholder, integrate with a payment gateway)
// For example, you might use Stripe or PayPal here
// Assuming payment is successful, proceed to create the order

// Create an order record
$userId = $_SESSION['user_id'];
$orderQuery = "INSERT INTO tbl_orders (user_id, total_price, created_at) VALUES (?, ?, NOW())";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("id", $userId, $totalPrice);
$orderStmt->execute();
$orderId = $conn->insert_id; // Get the last inserted order ID

// Insert order items into the order details table
foreach ($productIds as $index => $productId) {
    $orderItemQuery = "INSERT INTO tbl_order_items (order_id, product_id, quantity) VALUES (?, ?, ?)";
    $orderItemStmt = $conn->prepare($orderItemQuery);
    $orderItemStmt->bind_param("iii", $orderId, $productId, $quantities[$index]);
    $orderItemStmt->execute();
    
    // Update the stock in the products table
    $updateStockQuery = "UPDATE tbl_products SET stock = stock - ? WHERE product_id = ?";
    $updateStockStmt = $conn->prepare($updateStockQuery);
    $updateStockStmt->bind_param("ii", $quantities[$index], $productId);
    $updateStockStmt->execute();
}

// Clear the cart
unset($_SESSION['cart']);

// Redirect to a success page or display a success message
echo "Checkout successful! Your order ID is: " . $orderId;
?>





2. **tbl_order_items**: To store items in each order.
   ```sql
   CREATE TABLE tbl_order_items (
       order_item_id INT AUTO_INCREMENT PRIMARY KEY,
       order_id INT,
       product_id INT,
       quantity INT,
       FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id),
       FOREIGN KEY (product_id) REFERENCES tbl_products(product_id)
   );
   ```


   
2. **tbl_order_items**: To store items in each order.
   ```sql
   CREATE TABLE tbl_order_items (
       order_item_id INT AUTO_INCREMENT PRIMARY KEY,
       order_id INT,
       product_id INT,
       quantity INT,
       FOREIGN KEY (order_id) REFERENCES tbl_orders(order_id),
       FOREIGN KEY (product_id) REFERENCES tbl_products(product_id)
   );
   ```
