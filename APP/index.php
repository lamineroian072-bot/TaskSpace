<?php
// Database Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'app_db';

// 1. Connect to MySQL server
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Automatically create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

// 3. Select app_db
$conn->select_db($db);

// ---------------- Handle Form Submissions ----------------

// Add / Edit Product
if (isset($_POST['save_product'])) {
    $id             = $_POST['product_id'] ?? null;
    $model          = $_POST['model'];
    $condition_type = $_POST['condition_type'];
    $storage        = $_POST['storage'];
    $price          = $_POST['price'];
    $stock          = $_POST['stock'];
    $status         = $_POST['status'];
    $image_url      = $_POST['image_url'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE products SET model=?, condition_type=?, storage=?, price=?, stock=?, status=?, image_url=? WHERE id=?");
        $stmt->bind_param("sssdissi", $model, $condition_type, $storage, $price, $stock, $status, $image_url, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO products (model, condition_type, storage, price, stock, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdiss", $model, $condition_type, $storage, $price, $stock, $status, $image_url);
    }
    $stmt->execute();
    header("Location: index.php?tab=inventory");
    exit();
}

// Delete Product
if (isset($_GET['delete_product'])) {
    $id = intval($_GET['delete_product']);
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: index.php?tab=inventory");
    exit();
}

// Create Order
if (isset($_POST['create_order'])) {
    $customer_name  = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $product_id     = $_POST['product_id'];
    $quantity       = $_POST['quantity'];

    // Get product price
    $res = $conn->query("SELECT price, stock FROM products WHERE id = $product_id");
    $prod = $res->fetch_assoc();
    $price = $prod['price'];
    $total = $price * $quantity;

    // Insert Order
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_phone, total_amount, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("ssd", $customer_name, $customer_phone, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert Order Item
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $price);
    $stmt_item->execute();

    // Deduct Stock
    $new_stock = $prod['stock'] - $quantity;
    $new_status = $new_stock <= 0 ? 'Out of Stock' : ($new_stock <= 2 ? 'Low Stock' : 'In Stock');
    $conn->query("UPDATE products SET stock = $new_stock, status = '$new_status' WHERE id = $product_id");

    header("Location: index.php?tab=orders");
    exit();
}

// Update Order Status
if (isset($_GET['update_order_status'])) {
    $order_id = intval($_GET['update_order_status']);
    $new_status = $_GET['status'];
    $conn->query("UPDATE orders SET status = '$new_status' WHERE id = $order_id");
    header("Location: index.php?tab=orders");
    exit();
}

// ---------------- Data Queries ----------------

$products_table_exists = $conn->query("SHOW TABLES LIKE 'products'")->num_rows > 0;
$orders_table_exists   = $conn->query("SHOW TABLES LIKE 'orders'")->num_rows > 0;

$total_products = $products_table_exists ? $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] : 0;
$total_orders   = $orders_table_exists ? $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'] : 0;
$total_revenue  = $orders_table_exists ? ($conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0) : 0;
$low_stock      = $products_table_exists ? $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock <= 2")->fetch_assoc()['total'] : 0;

// Fetch All Products
$products = $products_table_exists ? $conn->query("SELECT * FROM products ORDER BY id DESC") : false;

// Fetch All Orders
$orders = ($orders_table_exists && $products_table_exists) ? $conn->query("
    SELECT o.*, oi.quantity, p.model, p.storage 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    JOIN products p ON oi.product_id = p.id 
    ORDER BY o.id DESC
") : false;

$active_tab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Store Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-area { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            body { background: #fff !important; color: #000 !important; }
            .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; }
            .border { border-color: #e2e8f0 !important; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-30 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-600 flex items-center justify-center font-black text-lg shadow-md shadow-indigo-500/20">
                    📱
                </div>
                <div>
                    <span class="font-extrabold text-lg tracking-tight text-white block leading-none">iStore Central</span>
                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Inventory & Operations</span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="inline-flex items-center space-x-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700/60 px-3.5 py-2 rounded-lg text-xs font-semibold transition active:scale-95">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Print Report</span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Sidebar Navigation -->
            <aside class="lg:col-span-3 no-print">
                <nav class="space-y-1.5 bg-white p-2.5 rounded-2xl border border-slate-200/80 shadow-sm">
                    <a href="?tab=dashboard" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all <?php echo $active_tab == 'dashboard' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                        <svg class="w-5 h-5 <?php echo $active_tab == 'dashboard' ? 'text-white' : 'text-slate-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="?tab=inventory" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all <?php echo $active_tab == 'inventory' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                        <svg class="w-5 h-5 <?php echo $active_tab == 'inventory' ? 'text-white' : 'text-slate-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Inventory Listing</span>
                    </a>
                    <a href="?tab=orders" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all <?php echo $active_tab == 'orders' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                        <svg class="w-5 h-5 <?php echo $active_tab == 'orders' ? 'text-white' : 'text-slate-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <span>Customer Orders</span>
                    </a>
                </nav>
            </aside>

            <!-- Main Content Display -->
            <main class="lg:col-span-9 print-area space-y-6">

                <?php if (!$products_table_exists): ?>
                    <div class="bg-amber-50 border border-amber-200/80 p-5 rounded-2xl shadow-sm flex items-start space-x-3">
                        <span class="text-xl">⚠️</span>
                        <div>
                            <p class="text-amber-900 font-bold text-sm">Database Setup Required</p>
                            <p class="text-amber-700 text-xs mt-0.5">The database <code class="font-mono font-semibold">app_db</code> was detected, but requires initial tables. Please execute the SQL seed script via phpMyAdmin.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($active_tab == 'dashboard'): ?>
                    <!-- DASHBOARD -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Real-time inventory and sales metrics</p>
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                            <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Products</span>
                            <span class="text-2xl font-extrabold text-slate-900 mt-1 block"><?php echo $total_products; ?></span>
                            <div class="absolute -right-2 -bottom-2 w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 font-bold text-xl">📦</div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                            <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Orders</span>
                            <span class="text-2xl font-extrabold text-slate-900 mt-1 block"><?php echo $total_orders; ?></span>
                            <div class="absolute -right-2 -bottom-2 w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center text-slate-300 font-bold text-xl">🛒</div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                            <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Total Revenue</span>
                            <span class="text-2xl font-extrabold text-emerald-600 mt-1 block">₱<?php echo number_format($total_revenue, 2); ?></span>
                            <div class="absolute -right-2 -bottom-2 w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-300 font-bold text-xl">💰</div>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                            <span class="text-[10px] font-extrabold uppercase text-slate-400 tracking-wider block">Low Stock Alerts</span>
                            <span class="text-2xl font-extrabold text-rose-600 mt-1 block"><?php echo $low_stock; ?></span>
                            <div class="absolute -right-2 -bottom-2 w-14 h-14 bg-rose-50 rounded-full flex items-center justify-center text-rose-300 font-bold text-xl">🚨</div>
                        </div>
                    </div>

                    <!-- Stock Overview Table -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="text-base font-bold text-slate-900">Stock Status Overview</h3>
                            <a href="?tab=inventory" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View Full Inventory →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="py-3.5 px-6">Model</th>
                                        <th class="py-3.5 px-4">Condition</th>
                                        <th class="py-3.5 px-4">Storage</th>
                                        <th class="py-3.5 px-4">Price</th>
                                        <th class="py-3.5 px-4">Stock</th>
                                        <th class="py-3.5 px-6">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <?php 
                                    if ($products && $products->num_rows > 0):
                                        $products->data_seek(0);
                                        while($row = $products->fetch_assoc()): 
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-6 font-bold text-slate-900"><?php echo htmlspecialchars($row['model']); ?></td>
                                        <td class="py-3.5 px-4 text-slate-600"><?php echo htmlspecialchars($row['condition_type']); ?></td>
                                        <td class="py-3.5 px-4 text-slate-600"><?php echo htmlspecialchars($row['storage']); ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900">₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td class="py-3.5 px-4 font-extrabold text-slate-800"><?php echo $row['stock']; ?></td>
                                        <td class="py-3.5 px-6">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $row['status'] == 'In Stock' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : ($row['status'] == 'Low Stock' ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60'); ?>">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo $row['status'] == 'In Stock' ? 'bg-emerald-500' : ($row['status'] == 'Low Stock' ? 'bg-amber-500' : 'bg-rose-500'); ?>"></span>
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile; 
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400">No product records available.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($active_tab == 'inventory'): ?>
                    <!-- INVENTORY LISTING -->
                    <div class="flex items-center justify-between no-print">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Inventory Catalog</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Manage products, pricing, and stock levels</p>
                        </div>
                        <button onclick="openModal()" class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 transition active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            <span>Add Product</span>
                        </button>
                    </div>

                    <!-- Printable Header -->
                    <div class="hidden print:block mb-4 border-b border-slate-200 pb-4">
                        <h1 class="text-xl font-bold text-slate-900">Inventory Stock Report</h1>
                        <p class="text-xs text-slate-500">Generated: <?php echo date('F j, Y, g:i a'); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="py-3.5 px-6">Product</th>
                                        <th class="py-3.5 px-4">Condition</th>
                                        <th class="py-3.5 px-4">Storage</th>
                                        <th class="py-3.5 px-4">Price</th>
                                        <th class="py-3.5 px-4">Stock</th>
                                        <th class="py-3.5 px-4">Status</th>
                                        <th class="py-3.5 px-6 text-right no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <?php 
                                    if ($products && $products->num_rows > 0):
                                        $products->data_seek(0);
                                        while($row = $products->fetch_assoc()): 
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-6 flex items-center space-x-3">
                                            <img src="<?php echo htmlspecialchars($row['image_url'] ?? 'https://via.placeholder.com/60'); ?>" alt="Device" class="w-10 h-10 object-cover rounded-xl border border-slate-200/80 shadow-sm">
                                            <span class="font-bold text-slate-900"><?php echo htmlspecialchars($row['model']); ?></span>
                                        </td>
                                        <td class="py-3.5 px-4 text-slate-600"><?php echo htmlspecialchars($row['condition_type']); ?></td>
                                        <td class="py-3.5 px-4 text-slate-600"><?php echo htmlspecialchars($row['storage']); ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900">₱<?php echo number_format($row['price'], 2); ?></td>
                                        <td class="py-3.5 px-4 font-extrabold text-slate-800"><?php echo $row['stock']; ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $row['status'] == 'In Stock' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : ($row['status'] == 'Low Stock' ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60'); ?>">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo $row['status'] == 'In Stock' ? 'bg-emerald-500' : ($row['status'] == 'Low Stock' ? 'bg-amber-500' : 'bg-rose-500'); ?>"></span>
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 text-right space-x-1.5 no-print">
                                            <button onclick='editProduct(<?php echo json_encode($row); ?>)' class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold transition">Edit</button>
                                            <a href="?delete_product=<?php echo $row['id']; ?>" onclick="return confirm('Delete product?')" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg font-semibold transition">Delete</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile; 
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-slate-400">No inventory products found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php elseif ($active_tab == 'orders'): ?>
                    <!-- CUSTOMER ORDERS -->
                    <div class="flex items-center justify-between no-print">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Orders</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Track and update customer purchases</p>
                        </div>
                        <button onclick="openOrderModal()" class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-xs font-semibold shadow-md shadow-indigo-600/20 transition active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            <span>New Order</span>
                        </button>
                    </div>

                    <!-- Printable Header -->
                    <div class="hidden print:block mb-4 border-b border-slate-200 pb-4">
                        <h1 class="text-xl font-bold text-slate-900">Customer Orders Report</h1>
                        <p class="text-xs text-slate-500">Generated: <?php echo date('F j, Y, g:i a'); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-50/60 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="py-3.5 px-6">Order ID</th>
                                        <th class="py-3.5 px-4">Customer</th>
                                        <th class="py-3.5 px-4">Contact</th>
                                        <th class="py-3.5 px-4">Purchased Item</th>
                                        <th class="py-3.5 px-4">Total Amount</th>
                                        <th class="py-3.5 px-4">Status</th>
                                        <th class="py-3.5 px-6 text-right no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <?php 
                                    if ($orders && $orders->num_rows > 0):
                                        while($ord = $orders->fetch_assoc()): 
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-6 font-mono font-extrabold text-indigo-600">#ORD-<?php echo sprintf('%04d', $ord['id']); ?></td>
                                        <td class="py-3.5 px-4 font-bold text-slate-900"><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                        <td class="py-3.5 px-4 text-slate-600"><?php echo htmlspecialchars($ord['customer_phone']); ?></td>
                                        <td class="py-3.5 px-4 text-slate-700"><?php echo htmlspecialchars($ord['model']); ?> (<?php echo $ord['storage']; ?>) <span class="font-bold text-slate-900">x<?php echo $ord['quantity']; ?></span></td>
                                        <td class="py-3.5 px-4 font-extrabold text-slate-900">₱<?php echo number_format($ord['total_amount'], 2); ?></td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo $ord['status'] == 'Completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : ($ord['status'] == 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-slate-100 text-slate-700 border border-slate-200'); ?>">
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo $ord['status'] == 'Completed' ? 'bg-emerald-500' : ($ord['status'] == 'Pending' ? 'bg-amber-500' : 'bg-slate-400'); ?>"></span>
                                                <?php echo $ord['status']; ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 text-right space-x-1.5 no-print">
                                            <?php if ($ord['status'] == 'Pending'): ?>
                                                <a href="?update_order_status=<?php echo $ord['id']; ?>&status=Completed" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg font-semibold transition">Complete</a>
                                                <a href="?update_order_status=<?php echo $ord['id']; ?>&status=Cancelled" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-semibold transition">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-slate-400">No customer orders recorded.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- PRODUCT MODAL -->
    <div id="productModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 no-print p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-slate-100">
            <h3 id="modalTitle" class="text-lg font-bold text-slate-900 mb-4">Add Product</h3>
            <form method="POST" action="index.php">
                <input type="hidden" name="product_id" id="prod_id">
                <input type="hidden" name="save_product" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Model Name</label>
                        <input type="text" name="model" id="prod_model" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Condition</label>
                            <select name="condition_type" id="prod_condition" class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="Brand New">Brand New</option>
                                <option value="Secondhand">Secondhand</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Storage</label>
                            <input type="text" name="storage" id="prod_storage" placeholder="e.g. 128GB" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Price (PHP)</label>
                            <input type="number" step="0.01" name="price" id="prod_price" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Stock</label>
                            <input type="number" name="stock" id="prod_stock" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Status</label>
                            <select name="status" id="prod_status" class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="In Stock">In Stock</option>
                                <option value="Low Stock">Low Stock</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Image URL</label>
                            <input type="url" name="image_url" id="prod_image_url" placeholder="https://..." required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 shadow-sm transition">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW ORDER MODAL -->
    <div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 no-print p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-slate-100">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Create Customer Order</h3>
            <form method="POST" action="index.php">
                <input type="hidden" name="create_order" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Customer Name</label>
                        <input type="text" name="customer_name" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Contact Phone</label>
                        <input type="text" name="customer_phone" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Select Product</label>
                        <select name="product_id" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                            <?php 
                            if ($products && $products->num_rows > 0):
                                $products->data_seek(0);
                                while($prod = $products->fetch_assoc()): 
                                    if ($prod['stock'] > 0):
                            ?>
                                <option value="<?php echo $prod['id']; ?>">
                                    <?php echo htmlspecialchars($prod['model']); ?> (<?php echo $prod['storage']; ?>) — ₱<?php echo number_format($prod['price'], 2); ?> [Stock: <?php echo $prod['stock']; ?>]
                                </option>
                            <?php 
                                    endif;
                                endwhile; 
                            endif;
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-slate-400 tracking-wider mb-1">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" required class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeOrderModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700 shadow-sm transition">Place Order</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('prod_id').value = '';
            document.getElementById('modalTitle').innerText = 'Add Product';
            document.getElementById('prod_model').value = '';
            document.getElementById('prod_condition').value = 'Brand New';
            document.getElementById('prod_storage').value = '';
            document.getElementById('prod_price').value = '';
            document.getElementById('prod_stock').value = '';
            document.getElementById('prod_status').value = 'In Stock';
            document.getElementById('prod_image_url').value = '';
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function editProduct(data) {
            document.getElementById('prod_id').value = data.id;
            document.getElementById('modalTitle').innerText = 'Edit Product';
            document.getElementById('prod_model').value = data.model;
            document.getElementById('prod_condition').value = data.condition_type;
            document.getElementById('prod_storage').value = data.storage;
            document.getElementById('prod_price').value = data.price;
            document.getElementById('prod_stock').value = data.stock;
            document.getElementById('prod_status').value = data.status;
            document.getElementById('prod_image_url').value = data.image_url;
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('productModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.getElementById('productModal').classList.remove('flex');
        }

        function openOrderModal() {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('orderModal').classList.add('flex');
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
            document.getElementById('orderModal').classList.remove('flex');
        }
    </script>
</body>
</html>