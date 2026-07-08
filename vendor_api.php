<?php
// vendor_api.php

session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/product_functions.php';

header('Content-Type: application/json');

$auth = new Auth($pdo);
$productFunctions = new ProductFunctions($pdo);

// Check if user is logged in and is a vendor
if (!$auth->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$vendor_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        // Get total products, orders, revenue
        $stats = [
            'total_products' => $productFunctions->getVendorProductCount($vendor_id),
            'total_orders' => $productFunctions->getVendorOrderCount($vendor_id),
            'total_revenue' => $productFunctions->getVendorRevenue($vendor_id),
            'total_sales' => $productFunctions->getVendorTotalSales($vendor_id)
        ];
        echo json_encode(['success' => true, 'data' => $stats]);
        break;

    case 'get_products':
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $search = $_GET['search'] ?? '';
        $products = $productFunctions->getVendorProducts($vendor_id, $page, $limit, $search);
        echo json_encode(['success' => true, 'data' => $products]);
        break;

    case 'add_product':
        // Validate input
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $price = $_POST['price'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $description = $_POST['description'] ?? '';
        $short_description = $_POST['short_description'] ?? '';
        $discount_price = $_POST['discount_price'] ?? null;

        if (empty($name) || $category_id == 0 || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $product_id = $productFunctions->addProduct($vendor_id, $category_id, $name, $price, $quantity, $description, $short_description, $discount_price);

        if ($product_id) {
            // Handle product images
            if (isset($_FILES['images'])) {
                // Process each image
                $imageUrls = [];
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $imageUrl = uploadImage($tmp_name, $_FILES['images']['name'][$key]);
                    if ($imageUrl) {
                        $imageUrls[] = $imageUrl;
                    }
                }
                // Save images to product_images table
                foreach ($imageUrls as $index => $url) {
                    $is_primary = ($index === 0) ? 1 : 0;
                    $productFunctions->addProductImage($product_id, $url, $is_primary);
                }
            }

            // Handle product attributes
            if (isset($_POST['attributes']) && is_array($_POST['attributes'])) {
                foreach ($_POST['attributes'] as $attr) {
                    $productFunctions->addProductAttribute($product_id, $attr['name'], $attr['value']);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $product_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product']);
        }
        break;

    case 'update_product':
        $product_id = $_POST['product_id'] ?? 0;
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }

        // Verify product belongs to vendor
        if (!$productFunctions->isVendorProduct($vendor_id, $product_id)) {
            echo json_encode(['success' => false, 'message' => 'You do not own this product']);
            exit;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'category_id' => $_POST['category_id'] ?? 0,
            'price' => $_POST['price'] ?? 0,
            'quantity' => $_POST['quantity'] ?? 0,
            'description' => $_POST['description'] ?? '',
            'short_description' => $_POST['short_description'] ?? '',
            'discount_price' => $_POST['discount_price'] ?? null
        ];

        $result = $productFunctions->updateProduct($product_id, $data);

        echo json_encode(['success' => $result, 'message' => $result ? 'Product updated successfully' : 'Failed to update product']);
        break;

    case 'delete_product':
        $product_id = $_POST['product_id'] ?? 0;
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            exit;
        }

        if (!$productFunctions->isVendorProduct($vendor_id, $product_id)) {
            echo json_encode(['success' => false, 'message' => 'You do not own this product']);
            exit;
        }

        $result = $productFunctions->deleteProduct($product_id);
        echo json_encode(['success' => $result, 'message' => $result ? 'Product deleted successfully' : 'Failed to delete product']);
        break;

    case 'get_orders':
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $status = $_GET['status'] ?? '';
        $orders = $productFunctions->getVendorOrders($vendor_id, $page, $limit, $status);
        echo json_encode(['success' => true, 'data' => $orders]);
        break;

    case 'update_order_status':
        $order_id = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';

        if ($order_id <= 0 || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Verify order belongs to vendor
        if (!$productFunctions->isVendorOrder($vendor_id, $order_id)) {
            echo json_encode(['success' => false, 'message' => 'You do not own this order']);
            exit;
        }

        $result = $productFunctions->updateOrderStatus($order_id, $status);
        echo json_encode(['success' => $result, 'message' => $result ? 'Order status updated' : 'Failed to update order status']);
        break;

    case 'get_payouts':
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $payouts = $productFunctions->getVendorPayouts($vendor_id, $page, $limit);
        echo json_encode(['success' => true, 'data' => $payouts]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Action not found']);
        break;
}
