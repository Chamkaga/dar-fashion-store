# Assignment Report

## Project Goals

This report covers the design considerations and implementation decisions for Dar Fashion Store, an e-commerce application built for a local fashion business in Dar es Salaam.

## Design Considerations

### User Experience (UX)
- Clean, intuitive navigation with visible category filters.
- Product cards with pricing, ratings, and key product details.
- Simplified checkout flow for fast conversions.
- Clear feedback on actions like add to cart and order confirmation.

### Ease of Navigation
- Consistent header with search, cart, and account links.
- Sidebar category filters and sorting options on the shop page.
- Persistent cart access and easy checkout path.
- Product detail pages with clear purchase actions.

### Mobile Compatibility
- Responsive layout using CSS media queries.
- Touch-friendly buttons and form controls.
- Optimized mobile grid for product browsing.
- Mobile-first design to support Tanzanian smartphone users.

### Performance
- Session-based cart for fast interactions.
- PDO prepared statements and targeted queries.
- Minimal JavaScript for essential interactivity.
- Optimized asset loading for page speed.

### Security
- CSRF protection for all form submissions.
- Password hashing with PHP `password_hash()`.
- SQL injection mitigation using PDO prepared statements.
- Admin-only route protection for sensitive pages.

### Target Customer Profile
- Product categories for men, women, shoes, bags, and accessories.
- Detailed product descriptions for fashion shoppers.
- Size and color variant support.
- Review and rating system for customer validation.

## How the Featured System Works

### Browsing Products
- Homepage and shop page display featured and trending products.
- Category filters, sorting, and search functionality are available.
- Product pages show images, descriptions, reviews, and variant options.

### Cart and Checkout
- Session-based cart for add/remove operations.
- Quantity selection and live price calculation.
- Checkout collects shipping and payment details.
- Orders are stored in the database with unique order numbers.

### Order Management
- Admin panel allows order status updates and cancellation.
- Delivery tracking is visible to customers on the track order page.
- Admin can save courier notes and estimated delivery days.

## Design Philosophy

The site uses a professional layout inspired by modern e-commerce design: clear visual hierarchy, consistent spacing, and a strong call-to-action for checkout and product browsing.

## Security Summary

This application uses standard security practices for a PHP/MySQL site, including session protection, prepared queries, password hashing, and admin authentication.

## Notes

This documentation is intentionally separate from the project README to keep the main repository overview clear and concise.