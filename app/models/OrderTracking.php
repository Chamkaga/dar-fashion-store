<?php

class OrderTracking {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public static function journeySteps() {
        return [
            [
                'code' => 'pending',
                'status' => 'pending',
                'label' => 'Order Placed',
                'icon' => '1',
                'summary' => 'Your order is received by Dar Fashion Store.',
                'customer' => 'You placed the order and payment is being confirmed.',
                'activity' => 'System logs the order and the Kariakoo team receives your request.',
                'default_location' => 'Dar es Salaam (Kariakoo)',
            ],
            [
                'code' => 'confirmed',
                'status' => 'confirmed',
                'label' => 'Order Confirmed',
                'icon' => '2',
                'summary' => 'Payment confirmed — seller prepares your items.',
                'customer' => 'Order confirmed. The store is preparing your parcel.',
                'activity' => 'Invoice generated and items queued for picking.',
                'default_location' => 'Dar Fashion Store — Kariakoo',
            ],
            [
                'code' => 'processing',
                'status' => 'processing',
                'label' => 'Processing & Packing',
                'icon' => '3',
                'summary' => 'Items picked, checked, and packed.',
                'customer' => 'Your products are being picked and quality-checked.',
                'activity' => 'Warehouse staff packs items and prints the shipping label.',
                'default_location' => 'Dar es Salaam Warehouse',
            ],
            [
                'code' => 'picked_up',
                'status' => 'shipped',
                'label' => 'Picked Up by Courier',
                'icon' => '4',
                'summary' => 'Courier collected the parcel from the shop.',
                'customer' => 'Parcel handed to the delivery company.',
                'activity' => 'First courier scan — tracking number is now live.',
                'default_location' => 'Dar es Salaam',
            ],
            [
                'code' => 'in_transit',
                'status' => 'in_transit',
                'label' => 'In Transit',
                'icon' => '5',
                'summary' => 'On the road between cities (e.g. Dar → Mwanza).',
                'customer' => 'Your parcel is travelling between regional hubs.',
                'activity' => 'Scanned at sorting centres; loaded onto inter-city trucks.',
                'default_location' => 'Morogoro / Dodoma corridor',
            ],
            [
                'code' => 'arrived_at_hub',
                'status' => 'in_transit',
                'label' => 'Arrived at Destination Hub',
                'icon' => '6',
                'summary' => 'Parcel reached the local distribution centre.',
                'customer' => 'Package arrived in your destination city.',
                'activity' => 'Sorted for local routes and assigned to a delivery rider.',
                'default_location' => 'Destination city hub',
            ],
            [
                'code' => 'out_for_delivery',
                'status' => 'out_for_delivery',
                'label' => 'Out for Delivery',
                'icon' => '7',
                'summary' => 'Rider is on the way to your address.',
                'customer' => 'Final delivery attempt in progress.',
                'activity' => 'Courier vehicle or boda en route to customer.',
                'default_location' => 'Local delivery zone',
            ],
            [
                'code' => 'delivered',
                'status' => 'delivered',
                'label' => 'Delivered',
                'icon' => '8',
                'summary' => 'Package received — order complete.',
                'customer' => 'You received your order. Thank you for shopping!',
                'activity' => 'Delivery confirmed; order closed in the system.',
                'default_location' => 'Customer address',
            ],
        ];
    }

    public static function statusProgressIndex($status) {
        $map = [
            'pending' => 0,
            'confirmed' => 1,
            'processing' => 2,
            'shipped' => 3,
            'in_transit' => 4,
            'out_for_delivery' => 6,
            'delivered' => 7,
            'cancelled' => -1,
        ];
        return $map[$status] ?? 0;
    }

    public function tableExists() {
        try {
            $this->conn->query("SELECT 1 FROM order_tracking_events LIMIT 1");
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function logEvent($orderId, $stepCode, array $data = []) {
        if (!$this->tableExists()) {
            return false;
        }

        $step = $this->findStep($stepCode);
        if (!$step) {
            return false;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO order_tracking_events
            (order_id, step_code, title, description, location, activity_note, event_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $orderId,
            $stepCode,
            $data['title'] ?? $step['label'],
            $data['description'] ?? $step['customer'],
            $data['location'] ?? $step['default_location'],
            $data['activity_note'] ?? $step['activity'],
            $data['event_at'] ?? date('Y-m-d H:i:s'),
            $data['created_by'] ?? 'system',
        ]);
    }

    public function logStatusChange($orderId, $newStatus, array $order = [], $createdBy = 'admin') {
        if (!$this->tableExists()) {
            return false;
        }

        $code = $this->statusToStepCode($newStatus);
        if ($code === null) {
            return false;
        }

        $destination = $this->guessDestination($order['shipping_address'] ?? '');
        $locations = [
            'picked_up' => 'Dar es Salaam — courier pickup',
            'in_transit' => 'En route to ' . $destination,
            'arrived_at_hub' => $destination . ' sorting facility',
            'out_for_delivery' => $destination . ' — out for delivery',
            'delivered' => $order['shipping_address'] ?? $destination,
            'cancelled' => $order['shipping_address'] ?? 'Order cancelled',
        ];

        $payload = [
            'location' => $locations[$code] ?? ($order['shipping_address'] ?? 'Tanzania'),
            'created_by' => $createdBy,
        ];

        if (!empty(trim($order['delivery_notes'] ?? ''))) {
            $payload['description'] = trim($order['delivery_notes']);
        }

        return $this->logEvent($orderId, $code, $payload);
    }

    public function seedInitialEvents($orderId, array $order = []) {
        $this->logEvent($orderId, 'pending', [
            'event_at' => $order['created_at'] ?? date('Y-m-d H:i:s'),
            'created_by' => 'system',
        ]);
    }

    public function getEvents($orderId) {
        if (!$this->tableExists()) {
            return [];
        }

        $stmt = $this->conn->prepare("
            SELECT * FROM order_tracking_events
            WHERE order_id = ?
            ORDER BY event_at ASC, id ASC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buildTimeline(array $order) {
        $steps = self::journeySteps();
        $events = $this->getEvents((int) $order['id']);
        $eventsByCode = [];

        foreach ($events as $event) {
            $eventsByCode[$event['step_code']] = $event;
        }

        $status = $order['status'] ?? 'pending';
        if ($status === 'cancelled') {
            $cancelEvent = null;
            foreach ($events as $event) {
                if ($event['step_code'] === 'cancelled') {
                    $cancelEvent = $event;
                    break;
                }
            }
            $cancelEvent = $cancelEvent ?: end($events) ?: null;

            return [
                'steps' => [
                    [
                        'step' => $this->getCancelledStep(),
                        'event' => $cancelEvent,
                        'state' => 'cancelled',
                    ],
                ],
                'progress' => 0,
                'cancelled' => true,
                'events' => $events,
            ];
        }

        $maxComplete = $this->maxCompletedStepIndex($status, $eventsByCode);
        $timeline = [];

        foreach ($steps as $step) {
            $index = $this->stepLogicalIndex($step['code']);
            $event = $eventsByCode[$step['code']] ?? null;

            if ($index < $maxComplete) {
                $state = 'done';
            } elseif ($index === $maxComplete) {
                $state = 'current';
            } else {
                $state = 'upcoming';
            }

            $timeline[] = [
                'step' => $step,
                'event' => $event,
                'state' => $state,
            ];
        }

        $progress = $status === 'delivered'
            ? 100
            : (int) round(($maxComplete / max(count($steps) - 1, 1)) * 100);

        return [
            'steps' => $timeline,
            'progress' => min(100, max($progress, 5)),
            'cancelled' => false,
            'events' => $events,
        ];
    }

    private function stepLogicalIndex($code) {
        $indices = [
            'pending' => 0,
            'confirmed' => 1,
            'processing' => 2,
            'picked_up' => 3,
            'in_transit' => 4,
            'arrived_at_hub' => 5,
            'out_for_delivery' => 6,
            'delivered' => 7,
        ];
        return $indices[$code] ?? 0;
    }

    private function maxCompletedStepIndex($status, array $eventsByCode) {
        $map = [
            'pending' => 0,
            'confirmed' => 1,
            'processing' => 2,
            'shipped' => 3,
            'in_transit' => 4,
            'out_for_delivery' => 6,
            'delivered' => 7,
        ];

        $index = $map[$status] ?? 0;

        if (isset($eventsByCode['arrived_at_hub'])) {
            $index = max($index, 5);
        }

        return $index;
    }

    private function findStep($code) {
        if ($code === 'cancelled') {
            return $this->getCancelledStep();
        }

        foreach (self::journeySteps() as $step) {
            if ($step['code'] === $code) {
                return $step;
            }
        }
        return null;
    }

    private function getCancelledStep() {
        return [
            'code' => 'cancelled',
            'status' => 'cancelled',
            'label' => 'Order Cancelled',
            'icon' => '×',
            'summary' => 'This order was cancelled and will not be delivered.',
            'customer' => 'We are sorry, this order has been cancelled by the store.',
            'activity' => 'The order was marked cancelled by admin.',
            'default_location' => 'Order cancelled',
        ];
    }

    private function statusToStepCode($status) {
        $map = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'shipped' => 'picked_up',
            'in_transit' => 'in_transit',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
        ];
        return $map[$status] ?? null;
    }

    private function guessDestination($address) {
        $cities = ['Mwanza', 'Mbeya', 'Morogoro', 'Arusha', 'Dodoma', 'Zanzibar', 'Mwanza', 'Kinondoni', 'Ilala', 'Upanga'];
        foreach ($cities as $city) {
            if (stripos($address, $city) !== false) {
                return $city;
            }
        }
        return 'Destination city';
    }
}
