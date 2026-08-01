<?php
// api.php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'read_products';

switch ($action) {

    case 'check_auth':
        $isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        echo json_encode(['authenticated' => $isLoggedIn, 'username' => $_SESSION['admin_username'] ?? 'Admin']);
        break;

    case 'read_products':
        try {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            $products = $stmt->fetchAll();

            $analyticsStmt = $pdo->query("
                SELECT 
                    COUNT(*) AS total_products,
                    SUM(CASE WHEN condition_type = 'Brand New' THEN 1 ELSE 0 END) AS brand_new_count,
                    SUM(CASE WHEN condition_type = 'Secondhand' THEN 1 ELSE 0 END) AS secondhand_count,
                    COALESCE(SUM(stock), 0) AS total_inventory_units
                FROM products
            ");
            $analytics = $analyticsStmt->fetch();

            $ordersStmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10");
            $orders = $ordersStmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => $products,
                'orders' => $orders,
                'analytics' => $analytics
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'checkout':
        $customer_name    = $_POST['customer_name'] ?? '';
        $customer_phone   = $_POST['customer_phone'] ?? '';
        $fulfillment_type = $_POST['fulfillment_type'] ?? 'Online Delivery';
        $delivery_address = $_POST['delivery_address'] ?? 'In-Store Pickup (No address required)';
        $product_summary  = $_POST['product_summary'] ?? '';
        $total_amount     = $_POST['total_amount'] ?? 0;
        $payment_method   = $_POST['payment_method'] ?? 'Cash on Delivery';

        if (!empty($customer_name) && !empty($customer_phone) && !empty($product_summary)) {
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_name, customer_phone, fulfillment_type, delivery_address, product_summary, total_amount, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $customer_name, 
                $customer_phone, 
                $fulfillment_type, 
                $delivery_address, 
                $product_summary, 
                $total_amount, 
                $payment_method
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Order placed successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Please complete all customer details.']);
        }
        break;

    case 'create_product':
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $model          = $_POST['model'] ?? '';
        $condition_type = $_POST['condition_type'] ?? 'Brand New';
        $storage        = $_POST['storage'] ?? '128GB';
        $price          = $_POST['price'] ?? 0;
        $stock          = $_POST['stock'] ?? 1;
        $image_url      = $_POST['image_url'] ?? 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&auto=format&fit=crop';

        $status = ($stock > 5) ? 'In Stock' : (($stock > 0) ? 'Low Stock' : 'Out of Stock');

        if (!empty($model)) {
            $stmt = $pdo->prepare("INSERT INTO products (model, condition_type, storage, price, stock, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$model, $condition_type, $storage, $price, $stock, $status, $image_url]);
            echo json_encode(['status' => 'success', 'message' => 'iPhone added to inventory']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'iPhone Model is required']);
        }
        break;

    case 'delete_product':
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Product ID']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
        break;
}
?>