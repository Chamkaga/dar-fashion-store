# Production Architecture Notes

## Core production improvements

### Reservation expiration
- `inventory_reservations` stores temporary stock holds for pending orders.
- A scheduled worker regularly expires old reservations, restores stock, and cancels stale pending orders.
- Default reservation timeout is 15 minutes.

### Duplicate checkout protection
- `checkout.php` now issues per-form idempotency tokens.
- Orders use `orders.idempotency_key` to prevent duplicated order creation on refresh or double-click.
- Payment references are unique and are stored in `payments.payment_reference`.

### Payment architecture
- `app/services/PaymentService.php` provides a payment abstraction layer.
- `app/models/Payment.php` now supports payment provider, transaction refs, failure tracking, and refunds.
- This architecture supports future Stripe, PayPal, M-Pesa, Airtel Money, and Tigo Pesa providers.

### Queue system
- Backlog jobs are stored in `jobs` and processed asynchronously by `scripts/queue_worker.php`.
- Queued job types include order confirmation emails, analytics events, and notifications.
- Heavy operations are moved off the checkout request path.

### Audit logging
- `audit_logs` captures admin and system-level actions with user id, action, target context, IP, and timestamp.
- A separate model, `app/models/AuditLog.php`, centralizes audit writes.

### Error and activity logging
- `app/helpers/Logger.php` writes structured logs to `logs/` files.
- Separate log types prevent error noise from being mixed with queue and audit events.

## Deployment notes
- Ensure the application user can write to `logs/` and `database/rollback/`.
- Reservation cleanup and job queue worker scripts must be scheduled on the server.
- `app/bootstrap.php` sets secure session cookie parameters for production.

## Recovery strategy
- Keep database backups before migration.
- Use rollback SQL if migrations must be undone.
- Check `logs/error.log` and `logs/queue_worker.log` immediately after deployment.
