<?php

class Product {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        return $this->conn->query("
            SELECT products.*, categories.name AS category_name, categories.slug AS category_slug
            FROM products
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE products.status = 'active'
            ORDER BY products.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBySlug($slug) {
        $stmt = $this->conn->prepare("
            SELECT products.*, categories.name AS category_name, categories.slug AS category_slug
            FROM products
            LEFT JOIN categories ON categories.id = products.category_id
            WHERE products.slug = ? AND products.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function search($search = '', $category = '', $sort = 'featured', $priceRange = '', $minRating = 0) {
        $sql = "
            SELECT products.*, categories.name AS category_name, categories.slug AS category_slug,
                   COALESCE(AVG(reviews.rating), 0) AS avg_rating
            FROM products
            LEFT JOIN categories ON categories.id = products.category_id
            LEFT JOIN reviews ON reviews.product_id = products.id AND reviews.status = 'approved'
            WHERE products.status = 'active'
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                products.name LIKE ?
                OR products.description LIKE ?
                OR products.sku LIKE ?
                OR products.size_options LIKE ?
                OR products.color_options LIKE ?
                OR categories.name LIKE ?
                OR categories.slug LIKE ?
            )";
            for ($i = 0; $i < 7; $i++) {
                $params[] = '%' . $search . '%';
            }
        }

        if ($category !== '') {
            $sql .= " AND categories.slug = ?";
            $params[] = $category;
        }

        if ($priceRange === 'under-30000') {
            $sql .= " AND products.price < 30000";
        } elseif ($priceRange === '30000-70000') {
            $sql .= " AND products.price >= 30000 AND products.price <= 70000";
        } elseif ($priceRange === 'above-70000') {
            $sql .= " AND products.price > 70000";
        }

        $sql .= " GROUP BY products.id";

        if ($minRating > 0) {
            $sql .= " HAVING avg_rating >= ?";
            $params[] = (float) $minRating;
        }

        if ($sort === 'price-low') {
            $sql .= " ORDER BY products.price ASC";
        } elseif ($sort === 'newest') {
            $sql .= " ORDER BY products.created_at DESC";
        } else {
            $sql .= " ORDER BY products.is_featured DESC, products.is_trending DESC, products.created_at DESC";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImages($product_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM product_images
            WHERE product_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeatured($limit = 4) {
        $stmt = $this->conn->prepare("
            SELECT products.*, COALESCE(AVG(reviews.rating), 0) AS avg_rating
            FROM products
            LEFT JOIN reviews ON reviews.product_id = products.id AND reviews.status = 'approved'
            WHERE products.status = 'active' AND products.is_featured = 1
            GROUP BY products.id
            ORDER BY products.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTrending($limit = 6) {
        $stmt = $this->conn->prepare("
            SELECT products.*, COALESCE(AVG(reviews.rating), 0) AS avg_rating
            FROM products
            LEFT JOIN reviews ON reviews.product_id = products.id AND reviews.status = 'approved'
            WHERE products.status = 'active' AND products.is_trending = 1
            GROUP BY products.id
            ORDER BY products.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($product_id, $qty) {
        $stmt = $this->conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ?
        ");
        return $stmt->execute([$qty, $product_id]);
    }
    
    // Variant helpers - product-level model convenience methods
    public function getVariants($product_id) {
        $stmt = $this->conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getVariantById($variant_id) {
        $stmt = $this->conn->prepare("SELECT * FROM product_variants WHERE id = ? LIMIT 1");
        $stmt->execute([$variant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateVariantStock($variant_id, $qty) {
        $stmt = $this->conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        return $stmt->execute([$qty, $variant_id, $qty]);
    }

    public function getVariantImages($product_id) {
        $stmt = $this->conn->prepare("SELECT variant_id, image_url FROM product_variant_images WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$product_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $vid = $r['variant_id'] ?: 0;
            if (!isset($map[$vid])) $map[$vid] = [];
            $map[$vid][] = $r['image_url'];
        }
        return $map;
    }
}

?>
