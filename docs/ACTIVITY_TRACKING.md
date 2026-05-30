# Activity Tracking Guide

## Overview

The activity tracking system logs user actions and makes them visible to admins in real time.

## What is Tracked?

- `login` – customer login events
- `logout` – customer logout events
- `view_product` – product detail views
- `add_to_cart` – cart additions
- `remove_from_cart` – cart removals
- `checkout` – checkout initiation
- `order_placed` – completed orders
- `payment_attempted` – payment actions
- `order_status_update` – admin status changes

## Admin Benefits

- Monitor cart abandonment trends
- Track order progress and payment delays
- Identify customer actions before support requests
- Use activity insights to improve product placement

## Feature Workflow

1. User action occurs
2. Action is recorded in the `user_activities` table
3. Admin dashboard and activity feed display the event
4. Admin can use the activity data for support and follow-up

## How Admins Use It

### Activity Feed
- Access from the admin sidebar
- View chronological event history
- Filter by activity type
- Drill into user profiles and order details

### Online Users
- Displays currently active customers
- Shows recent activity timestamps
- Helps identify engaged users in real time

### User Profiles
- View customer activity history
- Monitor order and payment behavior
- Support users with context about their interactions

## Practical Example

A customer adds items to cart, checks out, and completes payment. Admin sees:

- Add to cart events
- Checkout event
- Order placed event
- Payment verification event

This enables faster response and better customer service.
