<?php
// includes/product_functions.php

class ProductFunctions {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    public function addProduct($vendor_id, $category_id, $name, $price, $quantity, $description = '', $short_description = '', $discount_price = null) {
        $slug = $this->generateSlug($name);
        
        $stmt = $this->db->prepare("
            INSERT INTO products (vendor_id, category_id, name, slug, description, short_description, price, discount_price, quantity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([$vendor_id, $category_id, $name, $slug, $description, $short_description, $price, $discount_price, $quantity]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Handle duplicate slug
            if ($e->getCode() === '23000') {
                $slug = $this->generateSlug($name, true);
                $stmt->execute([$vendor_id, $category_id, $name, $slug, $description, $short_description, $price, $discount_price, $quantity]);
                return $this->db->lastInsertId();
            }
            return false;
        }
    }

    public function updateProduct($product_id, $data) {
        $sql = "UPDATE products SET ";
        $params = [];
        $fields = [];

        foreach ($data as $key => $value) {
            if (!empty($value) || $value === 0) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql .= implode(", ", $fields) . " WHERE id = ?";
        $params[] = $product_id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteProduct($product_id) {
        $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$product_id]);
    }

    public function getVendorProducts($vendor_id, $page = 1, $limit = 20, $search = '') {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT p.*, c.name as category_name, 
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.vendor_id = ?";

        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $params = [$vendor_id];
        
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendorOrderCount($vendor_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE vendor_id = ?");
        $stmt->execute([$vendor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getVendorRevenue($vendor_id) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(commission_amount), 0) as revenue FROM vendor_payouts WHERE vendor_id = ? AND status = 'paid'");
        $stmt->execute([$vendor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
    }

    public function getVendorTotalSales($vendor_id) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE vendor_id = ? AND status = 'delivered'");
        $stmt->execute([$vendor_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];
    }

    public function isVendorProduct($vendor_id, $product_id) {
        $stmt = $this->db->prepare("SELECT id FROM products WHERE vendor_id = ? AND id = ?");
        $stmt->execute([$vendor_id, $product_id]);
        return $stmt->rowCount() > 0;
    }

    public function isVendorOrder($vendor_id, $order_id) {
        $stmt = $this->db->prepare("SELECT id FROM orders WHERE vendor_id = ? AND id = ?");
        $stmt->execute([$vendor_id, $order_id]);
        return $stmt->rowCount() > 0;
    }

    public function updateOrderStatus($order_id, $status) {
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $order_id]);
    }

    public function getVendorOrders($vendor_id, $page = 1, $limit = 20, $status = '') {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT o.*, u.full_name as customer_name 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.vendor_id = ?";

        if (!empty($status)) {
            $sql .= " AND o.status = ?";
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $params = [$vendor_id];
        
        if (!empty($status)) {
            $params[] = $status;
        }
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendorPayouts($vendor_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->db->prepare("
            SELECT * FROM vendor_payouts 
            WHERE vendor_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$vendor_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addProductImage($product_id, $image_url, $is_primary = 0) {
        $stmt = $this->db->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)");
        return $stmt->execute([$product_id, $image_url, $is_primary]);
    }

    public function addProductAttribute($product_id, $attribute_name, $attribute_value) {
        $stmt = $this->db->prepare("INSERT INTO product_attributes (product_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
        return $stmt->execute([$product_id, $attribute_name, $attribute_value]);
    }

    private function generateSlug($name, $retry = false) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        if ($retry) {
            $slug = $slug . '-' . uniqid();
        }
        return $slug;
    }
}

// Helper function to upload image
function uploadImage($tmp_name, $original_name) {
    $upload_dir = 'uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $path = $upload_dir . $filename;

    if (move_uploaded_file($tmp_name, $path)) {
        return $path;
    }
    return false;
}
