# Order Workflow

## Payment Confirmation and Order Status Workflow

The workflow separates payment verification from order status updates.

### Step 1: Customer Pays
- Customer completes checkout and pays via the chosen method.
- Payment is recorded in the `payments` table.
- Payment status is updated to `verified` once confirmed.

### Step 2: Admin Reviews Payment
- Admin opens the order detail page in `/admin/orders/view.php`.
- The page shows payment status, method, and customer information.
- If the payment is verified, admin may proceed to confirm the order.

### Step 3: Admin Confirms the Order
- In the order detail form, select status `confirmed`.
- Add delivery company, notes, and estimated delivery days.
- Click `Save & notify tracking`.
- The customer sees the order confirmed step immediately.

## Admin Order Management

### Order Detail Page
- View customer name, email, phone, and address.
- Review order items, totals, and payment status.
- Update delivery status and courier information.
- Add notes visible to the customer.

### Payment Dashboard
- The payments dashboard lists order, customer, method, and status.
- Verified payments are safe to process.
- Pending payments should be followed up before confirmation.

## Manual vs Automatic Actions

- Payment verification is automatic when the gateway confirms the payment.
- Order status updates are manual and require admin action.
- The system separates financial confirmation from operational order tracking.

## Best Practices

- Do not confirm orders until the payment status is verified.
- Use the order detail page to add accurate courier notes.
- Keep the delivery timeframe realistic for the customer.
- Use the tracking page to verify that customers can see status updates.
