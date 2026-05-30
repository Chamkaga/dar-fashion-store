# Dar Fashion Store

A professional e-commerce web application for a local fashion business in Dar es Salaam, Tanzania.

## Project Overview

Dar Fashion Store is a PHP/MySQL e-commerce application built to support customers and administrators with product browsing, cart checkout, payment simulation, order tracking, and store management.

## Features

### Customer Features
- Browse products by category, search, and filters
- Product detail pages with images, descriptions, and reviews
- Session-based shopping cart
- Checkout with simulated payment methods
- Order tracking with progress timeline
- Customer profile and address management

### Admin Features
- Product and category management
- Order management and delivery status updates
- Payment verification and monitoring
- Customer account management
- Analytics dashboard and activity feed
- Customer message and inquiry management

## Technology Stack
- Backend: PHP with PDO
- Database: MySQL
- Frontend: HTML, CSS, JavaScript
- Charts: Chart.js for admin analytics
- Responsive design with custom CSS

## Installation
1. Copy the project files to your PHP web server.
2. Create a database and import `database/ecommerce.sql`.
3. Copy `.env.example` to `.env` and configure your database settings.
4. Open the site in your browser.
5. Access the admin panel at `/admin/login.php`.

## Database Overview

Key database tables:
- `users`
- `categories`
- `products`
- `reviews`
- `orders`
- `order_items`
- `payments`
- `user_wishlist`
- `user_addresses`
- `user_coupons`
- `user_returns`
- `user_activities`
- `messages`
- `order_confirmations`

## Default Accounts
- Admin: created during setup
- Customer demo accounts: seeded with sample data

## File Structure

```
.
├── admin/              # Admin panel files
├── public/             # Customer-facing pages
├── app/                # Application logic
├── assets/             # CSS, JS, and images
├── database/           # SQL schema and migrations
├── marketing/          # Marketing materials
├── analytics/          # Analytics setup
├── presentation/       # Presentation assets
├── scripts/            # Background and automation scripts
├── tests/              # Test scripts and simulations
└── README.md           # Project overview
```

## Additional Documentation

Detailed documentation has been moved to the `docs/` directory:

- `docs/ASSIGNMENT_REPORT.md`
- `docs/ORDER_WORKFLOW.md`
- `docs/ORDER_TRACKING.md`
- `docs/FLOW_DIAGRAMS.md`
- `docs/ADMIN_GUIDE.md`
- `docs/ACTIVITY_TRACKING.md`
- `docs/VERIFICATION_REPORT.md`

## Notes
- Use `.env.example` to configure environment values.
- Do not commit a real `.env` file.
- Log files and temporary files should remain out of source control.
