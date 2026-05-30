# Production Deployment Checklist

## Database
- [ ] Backup current production database before applying migrations.
- [ ] Apply migration:
  - `mysql -u <user> -p <database> < database/migrations/2026_05_27_production_prelaunch_migration.sql`
- [ ] Verify new tables and indexes: `audit_logs`, `jobs`, added `idempotency_key`, `payment_reference`, and queue indexes.
- [ ] Confirm `inventory_reservations` has new indexes and `orders.status` now supports `refunded`.

## Worker setup
- [ ] Add scheduled cron job for reservation cleanup every 5 minutes:
  - `*/5 * * * * php /path/to/dar-fashion-store/scripts/reservation_cleanup.php >> /path/to/dar-fashion-store/logs/reservation_cleanup.log 2>&1`
- [ ] Add worker cron for queued jobs every minute or run continuously:
  - `* * * * * php /path/to/dar-fashion-store/scripts/queue_worker.php >> /path/to/dar-fashion-store/logs/queue_worker.log 2>&1`

## Application
- [ ] Deploy updated PHP files to production.
- [ ] Ensure `app/bootstrap.php` and `app/includes/auth.php` are included on all pages before session use.
- [ ] Verify `logs/` directory is writable by the web server.

## Security
- [ ] Confirm `session.cookie_secure`, `session.cookie_httponly`, and `session.cookie_samesite` are enabled.
- [ ] Confirm CSRF tokens are present on checkout, login, and admin status forms.
- [ ] Verify login audit and `audit_logs` tracking are recorded.

## Validation
- [ ] Test guest checkout and customer checkout.
- [ ] Verify cart persists after login and checkout token prevents duplicate submissions.
- [ ] Confirm reservation cleanup restores stock after timeout.
- [ ] Verify stock restores for cancelled orders and failed/refunded payments.
- [ ] Confirm admin low-stock alerts appear in analytics.
- [ ] Check logs for errors in `logs/error.log`, `logs/audit.log`, `logs/info.log`, `logs/job.log`.

## Rollback
- [ ] Keep `database/rollback/2026_05_27_production_prelaunch_rollback.sql` available.
- [ ] If rollback is needed, restore from backup first, then apply rollback SQL.

## Known limitations
- Email, SMS, and external payment gateway integrations are currently queued placeholders.
- Reservation cleanup and queue workers depend on cron or process scheduling.
- Concurrency simulation is provided as a script only; full load testing should be conducted in staging.
