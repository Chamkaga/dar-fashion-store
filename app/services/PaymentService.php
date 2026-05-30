<?php

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/InventoryReservation.php';
require_once __DIR__ . '/../helpers/Logger.php';

class PaymentService {
    private $conn;
    private $payment;
    private $order;
    private $reservation;

    public function __construct($db) {
        $this->conn = $db;
        $this->payment = new Payment($db);
        $this->order = new Order($db);
        $this->reservation = new InventoryReservation($db);
    }

    public function createPaymentRequest($order_id, $method, $amount, $order_number) {
        $reference = $this->generateReference($order_number);
        $provider = $this->normalizeProvider($method);
        $transactionRef = strtoupper(bin2hex(random_bytes(6)));

        $result = $this->payment->create($order_id, $method, $amount, $reference, $provider, $transactionRef);
        if ($result) {
            Logger::info('Payment request queued', ['order_id' => $order_id, 'payment_reference' => $reference, 'provider' => $provider]);
        }
        return ['payment_reference' => $reference, 'transaction_ref' => $transactionRef, 'payment_provider' => $provider];
    }

    public function verifyPayment($order_id, $transaction_ref, $provider_response = []) {
        $success = $this->payment->verifyPayment($order_id, $transaction_ref, $provider_response);
        if ($success) {
            $this->reservation->consumeForOrder($order_id);
            $this->order->updateStatus($order_id, 'confirmed');
            Logger::info('Payment verified', ['order_id' => $order_id, 'transaction_ref' => $transaction_ref]);
        }
        return $success;
    }

    public function failPayment($order_id, $reason = '') {
        $result = $this->payment->markFailed($order_id, $reason);
        if ($result) {
            $this->reservation->restoreForOrder($order_id);
            $this->order->updateStatus($order_id, 'pending');
            Logger::warning('Payment failed', ['order_id' => $order_id, 'reason' => $reason]);
        }
        return $result;
    }

    public function refundPayment($order_id, $amount, $reason = '') {
        $result = $this->payment->refund($order_id, $amount, $reason);
        if ($result) {
            $this->reservation->restoreForOrder($order_id);
            $this->order->updateStatus($order_id, 'refunded');
            Logger::info('Payment refunded', ['order_id' => $order_id, 'amount' => $amount, 'reason' => $reason]);
        }
        return $result;
    }

    private function generateReference($order_number) {
        return sprintf('PAY-%s-%s', $order_number, strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)));
    }

    private function normalizeProvider($method) {
        return match ($method) {
            'mobile_money', 'M-Pesa', 'Airtel Money', 'Tigo Pesa' => 'mobile_money',
            'card_demo', 'Stripe', 'PayPal' => 'card',
            'cash_on_delivery' => 'cash_on_delivery',
            default => 'internal'
        };
    }
}
