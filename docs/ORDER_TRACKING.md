# Order Tracking Guide

## Delivery Status Flow

The order tracking system uses a journey of order statuses that map to customer-facing progress steps.

### Status flow
- `pending` → Order Placed
- `confirmed` → Order Confirmed
- `processing` → Processing & Packing
- `shipped` → Picked Up by Courier
- `in_transit` → In Transit
- `arrived_at_hub` → Arrived at Destination Hub
- `out_for_delivery` → Out for Delivery
- `delivered` → Delivered

## Status Definitions

| Status | Meaning |
|--------|---------|
| `pending` | Order placed and payment pending |
| `confirmed` | Payment verified and order confirmed |
| `processing` | Items are being picked and packed |
| `shipped` | Courier has picked up the parcel |
| `in_transit` | Parcel is moving between cities |
| `arrived_at_hub` | Parcel has reached the destination hub |
| `out_for_delivery` | Parcel is out for local delivery |
| `delivered` | Order has been delivered |

## Tracking Details

### Admin Workflow
- Admin updates the current order status on the order detail page.
- Status updates can include courier name, notes, and delivery estimates.
- Each update is logged and shown to the customer on the track order page.

### Customer Experience
- Customers enter order number and email on `/public/track-order.php`.
- They see a timeline of status updates and progress percentage.
- Cancelled orders show a dedicated cancelled state and history.

## Tracking Components

- **Timeline:** Shows completed, current, and upcoming delivery steps.
- **Progress bar:** Displays progress percent based on the current status.
- **Scan history:** Shows timestamped events if tracking logs exist.
- **Courier notes:** Customer-visible notes from admin.

## Notes

This guide documents how order tracking is rendered and how admin updates affect the customer experience.