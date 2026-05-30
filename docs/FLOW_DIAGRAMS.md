# Flow Diagrams

## Order Tracking Flow

This file contains the flow diagram for the order tracking lifecycle.

```
Customer → Select Products → Add to Cart → Checkout → Pay
         ↓
         Status: PENDING
         Payment: awaiting verification

Payment verified
         ↓
Admin confirms order
         ↓
Status: CONFIRMED
         ↓
Status: PROCESSING & PACKING
         ↓
Status: SHIPPED
         ↓
Status: IN TRANSIT
         ↓
Status: ARRIVED AT HUB
         ↓
Status: OUT FOR DELIVERY
         ↓
Status: DELIVERED
```

## Simplified Workflow

```
Customer pays → Payment verified → Admin confirms order → Customer sees "Order Confirmed"
Admin updates tracking → Customer sees timeline updates
```

## Key Flow Notes

- Payment verification and order confirmation are separate steps.
- Admin updates drive the customer-visible tracking timeline.
- A cancelled order moves to a dedicated cancelled state.
