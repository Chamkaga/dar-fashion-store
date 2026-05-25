<?php

class ActivityLog {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Log a user activity
     * @param int $user_id
     * @param string $activity_type (login, logout, view_product, add_to_cart, remove_from_cart, checkout, order_placed, payment_attempted, order_status_update)
     * @param array $params Additional parameters (product_id, order_id, details, ip_address, user_agent)
     */
    public function log($user_id, $activity_type, $params = []) {
        try {
            $product_id = $params['product_id'] ?? null;
            $order_id = $params['order_id'] ?? null;
            $details = isset($params['details']) ? json_encode($params['details']) : null;
            $ip_address = $params['ip_address'] ?? $this->getClientIP();
            $user_agent = $params['user_agent'] ?? substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $stmt = $this->conn->prepare("
                INSERT INTO user_activities 
                (user_id, activity_type, product_id, order_id, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $user_id,
                $activity_type,
                $product_id,
                $order_id,
                $details,
                $ip_address,
                $user_agent
            ]);
        } catch (Throwable $e) {
            error_log("Activity log error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all recent activities (for admin dashboard)
     * @param int $limit
     * @return array
     */
    public function getRecentActivities($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ua.id,
                    ua.user_id,
                    ua.activity_type,
                    ua.product_id,
                    ua.order_id,
                    ua.details,
                    ua.created_at,
                    u.fullname,
                    u.email,
                    p.name AS product_name,
                    o.order_number
                FROM user_activities ua
                JOIN users u ON ua.user_id = u.id
                LEFT JOIN products p ON ua.product_id = p.id
                LEFT JOIN orders o ON ua.order_id = o.id
                ORDER BY ua.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get recent activities error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activities for a specific user
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getUserActivities($user_id, $limit = 30) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ua.*,
                    p.name AS product_name,
                    o.order_number
                FROM user_activities ua
                LEFT JOIN products p ON ua.product_id = p.id
                LEFT JOIN orders o ON ua.order_id = o.id
                WHERE ua.user_id = ?
                ORDER BY ua.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get user activities error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activities for a specific order
     * @param int $order_id
     * @return array
     */
    public function getOrderActivities($order_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ua.*,
                    u.fullname,
                    u.email
                FROM user_activities ua
                JOIN users u ON ua.user_id = u.id
                WHERE ua.order_id = ?
                ORDER BY ua.created_at DESC
            ");
            $stmt->execute([$order_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get order activities error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get activity statistics by type (for dashboard)
     * @param string $period 'today', 'week', 'month'
     * @return array
     */
    public function getActivityStats($period = 'today') {
        try {
            $dateFilter = match($period) {
                'week' => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
                'month' => "DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
                default => "DATE(created_at) = CURDATE()"
            };

            $stmt = $this->conn->prepare("
                SELECT 
                    activity_type,
                    COUNT(*) as count
                FROM user_activities
                WHERE $dateFilter
                GROUP BY activity_type
                ORDER BY count DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get activity stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get online users (logged in within last 5 minutes)
     * @return array
     */
    public function getOnlineUsers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT
                    u.id,
                    u.fullname,
                    u.email,
                    u.role,
                    MAX(ua.created_at) as last_activity
                FROM users u
                JOIN user_activities ua ON u.id = ua.user_id
                WHERE ua.activity_type IN ('login', 'view_product', 'add_to_cart')
                AND ua.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY u.id
                ORDER BY ua.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get online users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's current cart activity
     * @param int $user_id
     * @return array Cart items from recent activity
     */
    public function getUserCartActivity($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ua.created_at,
                    ua.activity_type,
                    p.id,
                    p.name,
                    p.price,
                    p.image,
                    ua.details
                FROM user_activities ua
                LEFT JOIN products p ON ua.product_id = p.id
                WHERE ua.user_id = ? AND ua.activity_type IN ('add_to_cart', 'remove_from_cart')
                ORDER BY ua.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Get user cart activity error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Helper: Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }
}
?>
