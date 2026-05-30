# Admin Guide

## Quick Reference

### Quick Start
1. Login at `/admin/login.php`
2. Open **Orders** in the sidebar
3. Select an order to view details
4. Choose a new status from the dropdown
5. Add courier notes if needed
6. Click **Save & notify tracking**

## Order Lifecycle Checklist

### Payment Received
- Confirm payment status is `verified`
- Do not update order status before payment is confirmed

### Confirm Order
- Select `confirmed`
- Add note: "Payment received, preparing items"
- Click **Save & notify tracking**

### Packing
- Select `processing`
- Add note: "Items picked and packed"
- Click **Save & notify tracking**

### Courier Pickup
- Select `shipped`
- Add delivery company name
- Add note: "Handed to courier, heading to hub"
- Click **Save & notify tracking**

### In Transit
- Select `in_transit`
- Add note: "Parcel traveling between cities"
- Click **Save & notify tracking**

### Destination Hub
- Select `arrived_at_hub`
- Check `Log step: Arrived at destination hub`
- Add hub location and note
- Click **Save & notify tracking**

### Delivery
- Select `out_for_delivery` then later `delivered`
- Add note with delivery update
- Click **Save & notify tracking**

## Troubleshooting

### Customer does not see update
- Ask the customer to refresh the tracking page
- Verify the order number and email are correct
- Ensure the order status was saved successfully

### Payment still pending
- Check the payment method and transaction details
- Wait 5-15 minutes for gateway confirmation
- Follow up with the customer if needed

### Skipping steps
- The system allows moving directly to any status in the dropdown
- Use direct updates only when operationally appropriate
