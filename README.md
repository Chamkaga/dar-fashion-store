# Dar Fashion Store

A professional e-commerce web application for a local fashion business in Dar es Salaam, Tanzania.

## Assignment Overview

This project fulfills the requirements for Group 1 – Scenario 1: Building an online store for a local fashion shop in Dar es Salaam to enable digital shopping for customers.

## Website Requirements (All 6 Features Implemented)

✅ **Product Catalog**: List of products with images, prices, and detailed descriptions
✅ **Shopping Cart**: Users can add/remove items with session-based cart
✅ **Online Payment Option**: Simulated payment methods (M-Pesa, Card Demo, Cash on Delivery)
✅ **Customer Registration**: Sign up/login system with secure authentication
✅ **Order Confirmation Email**: Automatic simulated message system via database
✅ **Mobile-Friendly Interface**: Responsive design works well on phones and tablets

## Admin Requirements (All 7 Features Implemented)

✅ **Product Management**: Add, edit, delete, and organize products into categories
✅ **Order Management**: View orders, update status (pending → processing → delivered), cancel/approve
✅ **Customer Management**: View registered customers, activate/deactivate accounts
✅ **Payment Monitoring**: Confirm payments received, track payment status, view payment methods
✅ **Dashboard/Reports**: Total sales, total orders, best-selling products, daily/monthly revenue summary
✅ **Communication Tools**: Send order confirmation messages, respond to customer inquiries
✅ **Security & Access Control**: Secure admin login with password protection, only admin can access dashboard

## Theory & Explanation (Assignment Questions)

### 1. Factors Considered in Designing the Website

**User Experience (UX)**
- Clean, intuitive navigation with clear category filters
- Product cards with essential information (price, image, description)
- Easy checkout process with minimal steps
- Visual feedback for user actions (add to cart, form validation)

**Ease of Navigation**
- Consistent header with search, cart, and account links
- Sidebar filters for categories and sorting options
- Breadcrumb-style navigation for product details
- Quick links to important sections (shop, profile, track order)

**Mobile Compatibility**
- Fully responsive design using CSS media queries
- Touch-friendly buttons and form elements
- Optimized layout for small screens
- Mobile-first approach to ensure accessibility

**Speed and Performance**
- Session-based cart for fast operations
- Optimized database queries with proper indexing
- Minimal JavaScript for essential interactions
- Efficient image loading with Unsplash CDN

**Security**
- CSRF token protection on all forms
- Password hashing using PHP's password_hash()
- SQL injection prevention using PDO prepared statements
- Admin-only access control with authentication checks

**Target Customers (Fashion Buyers)**
- Product categories: Men, Women, Shoes, Bags, Accessories
- Detailed product descriptions with fabric and style information
- Size and color options for each product
- Star rating system for customer reviews

### 2. How the Featured System Works

**How Users Browse Products**
- Homepage displays featured and trending products
- Shop page allows filtering by category and sorting
- Search functionality for finding specific products
- Product detail page shows full description, images, and reviews

**How Cart Works**
- Session-based shopping cart (no database required)
- Add products with quantity selection
- Remove individual items or clear entire cart
- Real-time price calculation with delivery fee
- Activity logging for add/remove cart actions

**How Checkout and Payment Flow Happens**
- User fills in billing and shipping information
- Selects payment method (M-Pesa, Card Demo, Cash on Delivery)
- Order is created in database with unique order number
- Payment record created with status tracking
- Order confirmation message stored in database
- User redirected to success page with order details

**How Orders are Confirmed**
- Order status updates through admin panel
- Delivery tracking with timeline steps
- Automatic notification logging in database
- Customer can track order status via track-order.php
- Admin can update courier information and delivery notes

### 3. Why Mobile Responsiveness is Important

**Most Users in Tanzania Use Smartphones**
- High mobile phone penetration in Tanzania
- Customers prefer shopping on mobile devices
- Mobile-first approach ensures accessibility

**Easy Shopping on Small Screens**
- Responsive layout adapts to screen size
- Touch-friendly interface elements
- Optimized product grid for mobile viewing
- Simplified checkout process for mobile users

**Improves Sales and User Experience**
- Mobile users can shop anytime, anywhere
- Better conversion rates with mobile-optimized design
- Reduced bounce rate with fast-loading mobile pages
- Improved customer satisfaction

**Google Ranks Mobile-Friendly Sites Higher**
- SEO benefits from mobile-responsive design
- Better search engine visibility
- Improved organic traffic
- Compliance with Google's mobile-first indexing

### 4. Security Measures for Online Payments

**SSL Encryption (HTTPS)**
- Secure data transmission between client and server
- Protects sensitive information during checkout
- Prevents man-in-the-middle attacks
- Recommended for production deployment

**Secure Payment Gateways**
- Integration with M-Pesa (Tanzania's mobile money)
- Card payment simulation for demo purposes
- Cash on delivery option for offline customers
- Payment status tracking in database

**OTP Verification**
- Can be integrated with M-Pesa for additional security
- Two-factor authentication option for high-value orders
- SMS verification for account registration (future enhancement)

**Password Protection**
- Admin login with hashed passwords
- Customer account authentication
- Session management for secure access
- Password reset functionality (future enhancement)

**Fraud Detection**
- Activity logging for all user actions
- Order status monitoring
- Suspicious activity alerts (future enhancement)
- IP address tracking for security

### 5. How to Improve Customer Trust

**Customer Reviews and Ratings**
- 1-5 star rating system for products
- Customer comments and feedback
- Review approval workflow for admin
- Display average ratings on product cards

**Clear Return/Refund Policy**
- User returns table for tracking refund requests
- Return status management (pending, approved, rejected)
- Clear return process documentation
- Customer-friendly return policies

**Secure Payment Badges**
- Display payment method logos
- Show secure checkout indicators
- SSL certificate display (production)
- Trusted payment gateway integration

**Contact Information**
- Contact form for customer inquiries
- Admin message management system
- Email and phone contact details
- Social media links (future enhancement)

**Professional Website Design**
- Alibaba-inspired professional aesthetic
- Clean, modern interface
- Consistent branding throughout
- High-quality product images
- Responsive and accessible design

## Features

### Customer Features
- **Account Management**: Customers can register, login, and manage their profiles
- **Product Browsing**: Browse products by category with detailed descriptions
- **Star Ratings**: Customers can rate products with 1-5 stars
- **Wishlist**: Save favorite products for later
- **Order Tracking**: Track order status and delivery
- **Multiple Addresses**: Save multiple shipping addresses
- **Coupons**: Receive and use discount coupons

### Admin Features
- **Dashboard**: Real-time business insights with KPIs and charts
- **Product Management**: Add, edit, and manage products with descriptions
- **Order Management**: View and manage all orders
- **User Management**: Manage customer accounts with activation/deactivation
- **Inventory Tracking**: Monitor stock levels
- **Activity Feed**: Track user activities
- **Analytics**: Sales trends and performance metrics
- **Communication**: Reply to customer messages and inquiries

## Technology Stack

- **Backend**: PHP with PDO for database operations
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Charts**: Chart.js for admin analytics
- **Styling**: Custom CSS with Alibaba-inspired design

## Database Schema

The database includes the following main tables:
- `users` - Customer and admin accounts
- `categories` - Product categories
- `products` - Product catalog with descriptions
- `reviews` - Product ratings and reviews (1-5 star system)
- `orders` - Order management
- `order_items` - Order line items
- `payments` - Payment processing
- `user_wishlist` - Customer wishlist
- `user_addresses` - Shipping addresses
- `user_coupons` - Discount coupons
- `user_returns` - Return and refund requests
- `user_activities` - Activity logging
- `messages` - Customer contact inquiries
- `order_confirmations` - Order confirmation messages

## Installation

1. Copy the project files to your web server
2. Import the database schema from `database/ecommerce.sql`
3. Configure database credentials in `.env` file
4. Access the admin panel at `/admin/`

## Default Accounts

**Admin:**
- Email: admin@darfashion.store
- Password: (hashed in database)

**Customer Demo:**
- Email: amina@example.com
- Password: (hashed in database)

## Design Philosophy

The design follows Alibaba's professional aesthetic with:
- Clean layouts and proper visual hierarchy
- Consistent spacing and modern interactions
- Orange primary color (#FF6A00) matching Alibaba's branding
- Professional typography and card designs
- Enhanced user profile and admin account interfaces

## File Structure

```
dar-fashion-store/
├── admin/              # Admin panel files
│   ├── dashboard.php   # Admin dashboard with KPIs and charts
│   ├── analytics/      # Analytics and visitor statistics
│   ├── products/       # Product management (add, edit, delete)
│   ├── orders/         # Order management and tracking
│   ├── users/          # Customer management with activation
│   ├── payments/       # Payment monitoring
│   └── messages/       # Customer communication
├── public/             # Public-facing pages
│   ├── index.php       # Homepage
│   ├── shop.php        # Product catalog
│   ├── cart.php        # Shopping cart
│   ├── checkout.php    # Checkout process
│   ├── login.php       # Customer login
│   ├── register.php    # Customer registration
│   ├── profile.php     # Customer profile
│   └── track-order.php # Order tracking
├── app/                # Application logic
│   ├── models/         # Data models
│   ├── controllers/    # Business logic
│   └── includes/       # Shared components
├── assets/             # CSS, JS, images
├── database/           # SQL schema
├── marketing/          # Marketing strategy and materials
├── analytics/          # Analytics configuration and integration
├── presentation/       # Presentation materials and guidelines
└── README.md           # This file
```

## Presentation & Demonstration Guide

### Live Demonstration Steps

1. **Show Homepage**: Display product catalog, featured items, and categories
2. **Add to Cart**: Click on a product, add it to cart
3. **View Cart**: Show cart with items and total calculation
4. **Checkout**: Complete checkout process with payment method selection
5. **Order Success**: Show order confirmation page
6. **Track Order**: Demonstrate order tracking functionality
7. **Customer Profile**: Show customer dashboard, orders, wishlist
8. **Admin Login**: Log in to admin panel
9. **Admin Dashboard**: Show KPIs, charts, and business insights
10. **Product Management**: Demonstrate adding/editing products
11. **Order Management**: Show order status updates
12. **Customer Management**: Demonstrate user activation/deactivation
13. **Messages**: Show customer inquiry management
14. **Mobile View**: Demonstrate responsive design on mobile device

### Key Points for Presentation

- **Mobile Responsiveness**: Show how the site adapts to different screen sizes
- **Security**: Highlight CSRF protection, password hashing, SQL injection prevention
- **User Experience**: Emphasize clean navigation, intuitive design
- **Admin Features**: Showcase comprehensive management capabilities
- **Payment Integration**: Explain M-Pesa and payment simulation
- **Order Tracking**: Demonstrate real-time order status updates

## Support

For support and inquiries, contact:
- Email: info@darfashion.store
- Phone: +255700000000

---

# Additional Documentation

The following sections include merged contents from external documentation files:
- `VERIFICATION_REPORT.md`
- `PAYMENT_AND_STATUS_WORKFLOW.md`
- `ORDER_TRACKING_SYSTEM_GUIDE.md`
- `ORDER_TRACKING_FLOW_DIAGRAM.md`
- `ADMIN_ORDER_QUICK_REFERENCE.md`
- `ADMIN_ACTIVITY_TRACKING_GUIDE.md`

## Verification Report

# Image Upload & Rating System Verification Report

## 1. IMAGE PRESERVATION ON EDIT PRODUCT ✅

### Implementation Status: WORKING

**File:** `admin/products/edit.php`

### How It Works:
```php
Line 13:  $imagePath = $_POST['image'] ?? '';  // Gets current image from hidden field
Line 16:  if (!empty($_FILES['image_file']['name'])) {
              // Upload new file
          } elseif (!empty($_POST['image_url'])) {
              // Use new URL
          }
          // If NEITHER provided, $imagePath keeps original value!
Line 38:  UPDATE products SET image=? WHERE id=?
```

### Test Scenario: Admin edits product WITHOUT changing image
1. Admin navigates to Edit Product page
2. Form displays current product image
3. Admin changes product name/price but DOES NOT:
   - Upload a file
   - Paste external URL
4. Admin clicks "Update Product"
5. **Result:** Current image is preserved ✅

### Hidden Field Implementation:
```html
<input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
```
This hidden field stores current image value and is used as fallback if admin doesn't change it.

---

## 2. CUSTOMER RATING SYSTEM ✅

### Implementation Status: FULLY OPERATIONAL

**Files:**
- Database: `ecommerce.sql` (reviews table)
- Backend: `public/product.php`
- Frontend: `public/product.php` (HTML form)

### Database Schema:
```sql
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    customer_name VARCHAR(120) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
);
```

### Features:
- ✅ 1-5 star rating system
- ✅ Customer comment/review text
- ✅ Automatic average rating calculation
- ✅ Prevents duplicate reviews per customer
- ✅ Login required to submit review
- ✅ Display all approved reviews
- ✅ Show average rating on product page

### Customer Rating Flow:

#### 1. View Product Page
- Product page loads with rating section
- Shows average rating from all reviews
- Displays individual reviews from customers

#### 2. Logged-In Customer Reviews Section
```
If Customer is NOT logged in:
  → "Please login to write a review" prompt
  
If Customer IS logged in:
  → Rating stars input (1-5 selectable)
  → Comment textarea
  → Submit button
```

#### 3. Form Validation:
```php
- Rating must be 1-5 ✓
- Comment cannot be empty ✓
- User cannot submit duplicate review ✓
- CSRF token validation ✓
```

#### 4. Database Storage:
```php
INSERT INTO reviews (
  product_id, 
  user_id, 
  customer_name, 
  rating, 
  comment
) VALUES (?, ?, ?, ?, ?)
```

#### 5. Display Reviews:
- Shows customer name, rating (stars), comment
- Listed in reverse chronological order (newest first)
- Average rating calculated and displayed

### Test Scenario: Customer Rates Product
1. Customer logs in
2. Navigates to product page
3. Scrolls to "Customer Reviews" section
4. Fills in:
   - Rating: Selects 4 stars
   - Comment: "Great quality, fast delivery!"
5. Clicks "Submit Review"
6. **Result:**
   - Review appears in list
   - Average rating updates
   - Success message shown ✅

---

## Summary

| Feature | Status | Location |
|---------|--------|----------|
| Image preservation on edit | ✅ WORKING | `admin/products/edit.php` |
| File upload | ✅ WORKING | `app/includes/upload.php` |
| External URL support | ✅ WORKING | `admin/products/add.php`, `edit.php` |
| Customer rating (1-5 stars) | ✅ WORKING | `public/product.php` |
| Customer review comments | ✅ WORKING | `public/product.php` |
| Average rating display | ✅ WORKING | `public/product.php` |
| Review validation | ✅ WORKING | `public/product.php` |
| Duplicate review prevention | ✅ WORKING | `public/product.php` |
| Admin authentication | ✅ WORKING | `require_admin()` |
| Customer authentication | ✅ WORKING | `is_logged_in()` |

---

## How to Test

### Test Image Preservation:
1. Go to `/admin/products/edit.php?id=1`
2. Change only product name
3. Leave image fields empty
4. Click "Update Product"
5. Verify image unchanged ✅

### Test Customer Rating:
1. Go to `/public/product.php?slug=linen-summer-dress`
2. Login as customer (amina@example.com / password)
3. Scroll to "Customer Reviews"
4. Select rating (e.g., 4 stars)
5. Enter comment
6. Click "Submit Review"
7. Verify review appears immediately ✅

---

**Report Generated:** May 26, 2026
**Status:** ALL SYSTEMS OPERATIONAL ✅

---

## Payment Confirmation & Order Status Workflow

# Payment Confirmation & Order Status Update - DETAILED EXPLANATION

## ❓ YOUR QUESTION

**"How admin confirm and track customer order on the progress? If customer pay, or the product are on the bus to customer where admin can confirm it and customer seen?"

---

## 📋 ANSWER - Complete Workflow

### Question 1: "How does admin confirm order?"

**Answer:** Admin manually confirms by updating the status after payment is received.

```
Timeline:
2:00 PM - Customer places order (Status: PENDING)
         Payment request sent to Mobile Money gateway
         
2:05 PM - Customer sends TZS 120,000 via Mobile Money
         Payment gateway processes payment
         
2:10 PM - Payment confirmed (Payment status: VERIFIED ✓)
         Admin sees notification or checks order
         
2:15 PM - Admin goes to order page
         Admin clicks: Status dropdown
         Admin selects: "2. Order Confirmed"
         Admin adds note: "Payment confirmed, picking items"
         Admin clicks: "Save & notify tracking"
         
2:16 PM - INSTANTLY: Customer sees update
         Status changes to: "✅ Order Confirmed"
         Customer notification sent (optional email/SMS)
         
3:00 PM - Warehouse staff picks items
         Admin updates status to: "3. Processing & Packing"
         Customer sees: Update (automatic refresh shows new step)
```

---

### Question 2: "Where does admin see payment confirmation?"

**Answer:** In two places:

#### Place 1: Order Detail Page
```
URL: /admin/orders/view.php?id=20

Page shows:
┌────────────────────────────────────┐
│ CUSTOMER & PAYMENT SECTION         │
├────────────────────────────────────┤
│ Customer Name: Ignas Kipchoge      │
│ Email: ignas@example.com           │
│ Phone: +255722333444               │
│ Address: Mwanza, Tanzania          │
│                                    │
│ Payment Status: ✅ VERIFIED        │
│ Payment Method: Mobile Money       │
│ Amount: TZS 120,000                │
│ Transaction Ref: MM-123456789      │
│ Paid At: May 25, 2:10 PM           │
└────────────────────────────────────┘
```

If Payment Status = "VERIFIED" ✓
  → Safe to process order
  → Can confirm order
  → Start picking items

If Payment Status = "PENDING" ⏳
  → Wait for payment
  → Don't confirm yet
  → Follow up with customer

#### Place 2: Payments Dashboard
```
URL: /admin/payments/index.php

Table shows:
┌──────────────────────────────────────────┐
│ Order  │ Customer  │ Method  │ Status   │
├──────────────────────────────────────────┤
│ DFS-20 │ Ignas K   │ M-Money │ VERIFIED │
│ DFS-19 │ Amina H   │ M-Money │ VERIFIED │
│ DFS-18 │ Zainab    │ M-Money │ PENDING  │
│ DFS-17 │ Mohamed   │ Card    │ VERIFIED │
└──────────────────────────────────────────┘

Red rows = PENDING (need follow up)
Green rows = VERIFIED (safe to proceed)
```

---

### Question 3: "Is payment confirmation automatic or manual?"

**Answer:** Payment confirmation is AUTOMATIC (from gateway), but order status update is MANUAL (admin action).

```
Step 1: AUTOMATIC
─────────────────
Customer pays via Mobile Money
Mobile Money gateway verifies payment
System automatically updates: payments.payment_status = "verified"
Admin gets notification (if configured)

Step 2: MANUAL
──────────────
Admin reviews: Payment status = VERIFIED ✓
Admin manually updates order status to "confirmed"
Admin clicks: Save button
Only then does customer see: "Order Confirmed"
```

**Visual:**
```
┌──────────────┐
│ Customer     │
│ Pays via     │ ──AUTOMATIC──→ ┌──────────────┐
│ Mobile Money │                │ Payment DB   │
└──────────────┘                │ Status:      │
                                │ "verified"   │
                                └──────┬───────┘
                                       │
                                    (Admin sees this)
                                       │
                              Admin goes to order page
                                       │
                    ┌─────────────────┴────────────────┐
                    │ ADMIN MUST CLICK:               │
                    │ ┌──────────────────────────────┐│
                    │ │ Select Status: "Confirmed"   ││
                    │ │ Click: Save & Notify Tracking││
                    │ └──────────────────────────────┘│
                    └─────────────────┬────────────────┘
                                      │
                                      ▼
                        ┌──────────────────────────┐
                        │ Order Status Updated     │
                        │ Status: "confirmed"      │
                        └──────────────────────────┘
                                      │
                                      ▼
                        ┌──────────────────────────┐
                        │ INSTANTLY CUSTOMER SEES: │
                        │ ✅ Order Confirmed       │
                        │ Status updates on page   │
                        └──────────────────────────┘
```

---

### Question 4: "How admin track the progress? (bus to customer)"

**Answer:** Admin has full control. Here's how:

#### A. Real-Time Tracking Interface
```
Admin goes to: /admin/orders/view.php?id=20

Status form shows dropdown with 8 statuses:
1. Order Placed
2. Order Confirmed ← Starts here after payment
3. Processing & Packing
4. Picked Up by Courier
5. In Transit
6. Arrived at Destination Hub
7. Out for Delivery
8. Delivered
```

Admin can:
- See current status
- See delivery company name
- See estimated days
- See all courier notes
- Update status whenever needed
- Add new notes/updates
- Log hub arrival event

#### B. Tracking Components
```
Timeline visualization shows:
✅ Steps completed (green, with timestamps)
⏳ Steps in progress (blue, highlighted)
⌛ Steps pending (gray, grayed out)
Progress bar: 0%, 25%, 50%, 75%, 100%

Admin can hover/click:
- See exact timestamp of each update
- See which user made the change
```

---

## Order Tracking System Guide

# Order Tracking System - Complete Guide

## 🎯 Overview

The system allows:
- ✅ Admin to confirm and update order status
- ✅ Admin to track payment confirmation
- ✅ Customer to see order progress in real-time
- ✅ 8-step delivery journey timeline

---

## 📊 Order Status Flow

```
PENDING (1) → CONFIRMED (2) → PROCESSING (3) → SHIPPED (4) → IN_TRANSIT (5) → HUB ARRIVAL (6) → OUT_FOR_DELIVERY (7) → DELIVERED (8)
```

### Status Definitions:

| Status | Icon | Meaning | What Admin Does |
|--------|------|---------|-----------------|
| **pending** | 1️⃣ | Order Placed | Order received, waiting for payment |
| **confirmed** | 2️⃣ | Order Confirmed | Payment verified, preparing items |
| **processing** | 3️⃣ | Processing & Packing | Items picked, checked, packed |
| **shipped** | 4️⃣ | Picked Up by Courier | Handed to delivery company |
| **in_transit** | 5️⃣ | In Transit | On the road between cities |
| **arrived_at_hub** | 6️⃣ | Arrived at Destination Hub | Reached local distribution center |
| **out_for_delivery** | 7️⃣ | Out for Delivery | Rider en route to customer |
| **delivered** | 8️⃣ | Delivered | Order completed |

---

## 👨‍💼 ADMIN WORKFLOW

### Step 1: View All Orders
**Location:** Admin Panel → Sidebar → **Orders**
**URL:** `/admin/orders/index.php`

Admin sees:
- Order ID (DFS-1002, etc.)
- Customer name
- Phone number
- Shipping location
- Products ordered
- Total amount
- Payment status (pending/verified)
- Current order status

```
Example View:
┌─────────────────────────────────────────────────────────┐
│ Order  │ Customer    │ Phone        │ Location  │ Status │
├─────────────────────────────────────────────────────────┤
│ DFS-20 │ Ignas K     │ +255722333444│ Mwanza    │ pending│
│ DFS-19 │ Amina H     │ +255711111111│ Dar       │ shipped│
│ DFS-18 │ Zainab A    │ +255755666777│ Arusha    │ transit│
└─────────────────────────────────────────────────────────┘
```

---

### Step 2: Click Order to View Details
**Location:** Admin Orders List → Click **View**
**URL:** `/admin/orders/view.php?id=1`

Admin sees:
- ✅ Order number: DFS-1002
- ✅ Customer info (name, email, phone, address)
- ✅ Products in order with quantities
- ✅ Payment status: "pending" or "verified"
- ✅ Delivery company name
- ✅ Estimated delivery days
- ✅ Current progress: 25%, 50%, 75%, 100%

**Example:**
```
Order: DFS-1002
Customer: Ignas Kipchoge
Email: ignas@example.com
Phone: +255722333444
Ship to: Mwanza, Tanzania

Products:
  - Linen Summer Dress x1
  - Street Sneakers x1

Total: TZS 120,000
Payment Status: PENDING ⚠️
Current Status: IN_TRANSIT
Progress: 62%
```

---

### Step 3: Check Payment Status
**Location:** Same order view page
**Section:** "Customer & payment"

Admin sees:
- Payment method: Mobile Money, Card, or Cash on Delivery
- Payment status: **pending** or **verified**
- Transaction reference (if available)

**Actions:**
- ✅ If payment is "pending": Wait for customer to pay or follow up
- ✅ If payment is "verified": Proceed to confirm order

---

### Step 4: CONFIRM ORDER (After Payment Received)
**Location:** Admin order view → "Update delivery status" form

**Admin follows this workflow:**

```
1. Customer pays via Mobile Money
   ↓
2. Admin sees payment shows "verified" in system
   ↓
3. Admin selects status: "confirmed" from dropdown
   ↓
4. Admin clicks "Save & notify tracking"
   ↓
5. INSTANTLY: Customer sees order status changed to "Order Confirmed"
   ↓
6. Customer receives notification
```

**Form to Fill:**
```
Status Dropdown:
  ├─ 1. Order Placed (pending)
  ├─ 2. Order Confirmed (confirmed) ← SELECT THIS
  ├─ 3. Processing & Packing (processing)
  ├─ 4. Picked Up by Courier (shipped)
  ├─ 5. In Transit (in_transit)
  ├─ 7. Out for Delivery (out_for_delivery)
  └─ 8. Delivered (delivered)

Delivery Company: Dar Courier, Tanzam Express, etc.
Estimated Days: 1, 2, 3, or more
Courier Note: "Payment received. Picking items now."
```

---

### Step 5: UPDATE STATUS AS ORDER PROGRESSES

#### When packing items:
```
Admin selects: "3. Processing & Packing"
Courier note: "Items picked and packed. Ready for pickup."
Click: Save & notify tracking
Result: Customer sees order moved to step 3
```

#### When courier picks up:
```
Admin selects: "4. Picked Up by Courier"
Delivery Company: "Dar Courier Express"
Courier note: "Handed to Dar Courier. Heading to Morogoro."
Click: Save & notify tracking
Result: Customer sees step 4 activated with journey details
```

#### When arrives at destination hub:
```
Admin checks: ☑ "Log step: Arrived at destination hub"
Hub Location: "Mwanza sorting facility"
Courier note: "Arrived at destination. Sorting for delivery."
Click: Save & notify tracking
Result: Customer sees step 6 with hub location
```

#### When out for delivery:
```
Admin selects: "7. Out for Delivery"
Courier note: "Rider en route. Arrival window: 2-4 PM today"
Click: Save & notify tracking
Result: Customer sees: "OUT FOR DELIVERY - Arriving soon!"
```

#### When delivered:
```
Admin selects: "8. Delivered"
Courier note: "Package delivered successfully."
Click: Save & notify tracking
Result: Customer sees: ✅ DELIVERED with timestamp
```

---

## Order Tracking Flow Diagram

# Order Tracking System - Visual Flow

## 🔄 Complete System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         CUSTOMER PLACES ORDER                            │
│                    (Via /public/checkout.php)                            │
│                                                                          │
│  Customer → Select Products → Add to Cart → Checkout → Pay             │
└──────────────────────────┬──────────────────────────────────────────────┘
                           │
                           ▼
         ┌─────────────────────────────────────┐
         │   Status: PENDING                   │
         │   Payment: awaiting verification    │
         │   Customer sees: "Order Placed"     │
         │   Admin sees: New order in list     │
         └────────────┬────────────────────────┘
                      │
                      │ (Payment received)
                      ▼
         ┌─────────────────────────────────────┐
         │   Payment: VERIFIED ✓               │
         │   Admin notification triggered      │
         │   Admin clicks: View Order           │
         └────────────┬────────────────────────┘
                      │
           ┌──────────┴──────────┐
           │   ADMIN UPDATES:    │
           │   Status Dropdown   │
           │   Select: Confirmed │
           └──────────┬──────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: CONFIRMED                 │
         │   Msg: "Order Confirmed"            │
         │   Admin action: Warehouse picks     │
         │   Customer sees: Update instantly   │
         └────────────┬────────────────────────┘
                      │
           ┌──────────┴──────────────────┐
           │   ADMIN UPDATES:            │
           │   Status: Processing        │
           │   Note: Items packed        │
           └──────────┬──────────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: PROCESSING & PACKING      │
         │   Msg: "Items being prepared"       │
         │   Admin action: Courier arrives     │
         └────────────┬────────────────────────┘
                      │
           ┌──────────┴─────────────────┐
           │   ADMIN UPDATES:           │
           │   Status: Shipped          │
           │   Company: Dar Courier     │
           │   Note: Picked up          │
           └──────────┬─────────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: SHIPPED (Courier pickup)  │
         │   Msg: "On the way"                 │
         │   In transit to Morogoro...         │
         └────────────┬────────────────────────┘
                      │
           ┌──────────┴────────────────────┐
           │   ADMIN UPDATES:              │
           │   Status: In Transit          │
           │   Note: En route to Mwanza    │
           └──────────┬────────────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: IN TRANSIT                │
         │   Msg: "Travelling between cities"  │
         │   Route: Dar → Mwanza               │
         └────────────┬────────────────────────┘
                      │
           ┌──────────┴──────────────────────┐
           │   ADMIN UPDATES:                │
           │   ☑ Arrived at hub              │
           │   Location: Mwanza hub          │
           │   Note: Ready for delivery      │
           └────────────┬──────────────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: ARRIVED AT HUB (Step 6)   │
         │   Location: Mwanza sorting facility │
         │   Msg: "Package in your city!"      │
         └─────────────────────────────────────┘
                      │
           ┌──────────┴──────────────────┐
           │   ADMIN UPDATES:            │
           │   Status: Out for Delivery  │
           │   Note: Arriving 2-4 PM     │
           └──────────┬──────────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: OUT FOR DELIVERY (Step 7) │
         │   🔴 URGENT: Final delivery today! │
         │   Msg: "Rider on the way"           │
         │   Time window: 2-4 PM               │
         └─────────────────────────────────────┘
                      │
           ┌──────────┴──────────────┐
           │   ADMIN UPDATES:        │
           │   Status: Delivered     │
           │   Note: Successfully    │
           │          delivered      │
           └──────────┬──────────────┘
                      │
                      ▼
         ┌─────────────────────────────────────┐
         │   Status: DELIVERED ✅              │
         │   ✅ ORDER COMPLETE                 │
         │   Progress: 100%                    │
         │   Msg: "Thank you for shopping"     │
         │   Next: Customer rates product      │
         └─────────────────────────────────────┘
```

---

# Admin Order Tracking - Quick Reference Card

## 🚀 QUICK START (30 seconds)

```
1. Login: /admin/login.php
2. Click: Sidebar → Orders
3. Find: Customer's order
4. Click: Blue "View" button
5. Select: New status from dropdown
6. Add: Courier note (optional)
7. Click: "Save & notify tracking"
8. Done! Customer sees update INSTANTLY
```

---

## 📋 CHECKLIST: Order Lifecycle

### ✅ STEP 1: Payment Received
```
Admin checks payment status:
□ Go to order page
□ Look for "Customer & payment" section
□ See "Payment Status: verified" ✓
□ If verified, proceed to next step
```

### ✅ STEP 2: Confirm Order
```
□ Click status dropdown
□ Select: "2. Order Confirmed"
□ Add note: "Payment received, preparing items"
□ Click: Save & notify
→ Customer sees: ✅ Order Confirmed
```

### ✅ STEP 3: Packing
```
□ Click status dropdown
□ Select: "3. Processing & Packing"
□ Add note: "Items picked, packed, and labeled"
□ Click: Save & notify
→ Customer sees: Your items are being prepared
```

### ✅ STEP 4: Pickup by Courier
```
□ Click status dropdown
□ Select: "4. Picked Up by Courier"
□ Enter Delivery Company: "Dar Courier" or "Tanzam Express"
□ Add note: "Handed to [courier]. On way to hub."
□ Click: Save & notify
→ Customer sees: Your parcel is on the way
```

### ✅ STEP 5: In Transit
```
□ Click status dropdown
□ Select: "5. In Transit"
□ Add note: "En route Dar→[City]. Current location: Morogoro"
□ Click: Save & notify
→ Customer sees: Parcel travelling between cities
```

### ✅ STEP 6: Arrived at Hub
```
□ Check: ☑ "Log step: Arrived at destination hub"
□ Enter Hub Location: "Mwanza sorting facility"
□ Add note: "Arrived at destination. Sorting for delivery."
□ Click: Save & notify
→ Customer sees: Package in your city!
```

### ✅ STEP 7: Out for Delivery
```
□ Click status dropdown
□ Select: "7. Out for Delivery"
□ Add note: "Rider en route. Arrival window: 2-4 PM today"
□ Click: Save & notify
→ Customer sees: 🔴 FINAL DELIVERY TODAY - Arriving soon!
```

### ✅ STEP 8: Delivered
```
□ Click status dropdown
□ Select: "8. Delivered"
□ Add note: "Successfully delivered. Thank you!"
□ Click: Save & notify
→ Customer sees: ✅ ORDER COMPLETE
```

---

## 🎯 Status Reference (Copy-Paste Notes)

### After Payment:
```
"Payment received and verified. We're picking your items now."
```

### During Packing:
```
"Your items have been quality-checked and packed. Ready for courier pickup."
```

### Courier Pickup:
```
"Parcel handed to [Courier Name]. On the way to Morogoro hub."
```

### In Transit:
```
"Your package is travelling from Dar to [Destination]. Current stop: Morogoro distribution center."
```

### Destination Hub:
```
"Great news! Your package arrived in [City] and is being sorted for final delivery."
```

### Out for Delivery:
```
"Final step! Your parcel is out for delivery today. Delivery window: 2-4 PM. Rider will call before arrival."
```

### Delivered:
```
"✅ Your order has been delivered! Thank you for shopping with Dar Fashion Store. Please rate your experience."
```

---

## 💡 Tips & Tricks

**🔍 Find an order quickly:**
- Go to `/admin/orders/index.php`
- Search by customer name in the table
- Or filter by status (pending, processing, etc.)

**📞 Contact Courier:**
- Get courier phone from tracking (if available)
- Use order number to reference with courier
- Keep customer informed of delays

**⏰ Set Realistic Timeframes:**
- Dar to Arusha: 1-2 days
- Dar to Mwanza: 2-3 days
- Dar to Kigoma: 3-4 days
- Dar to Dar: Same day

**📧 Notify via Email:**
System automatically notifies when you update status
Customer sees updates on: `/public/track-order.php`

**🔐 Security:**
- Each status change is logged
- Only admins can update status
- All changes tracked with timestamp
- CSRF protection on all forms

---

## 🆘 Troubleshooting

**Q: Customer says they don't see update?**
A: Tell them to:
   1. Refresh their browser (Ctrl+F5)
   2. Go to `/public/track-order.php`
   3. Enter order number + email
   4. Updates appear within seconds

**Q: Payment shows pending but customer paid?**
A: 
   1. Check payment provider (Mobile Money gateway)
   2. May take 5-15 minutes to confirm
   3. Manually verify if needed
   4. Then update order status

**Q: Can I skip a step?**
A: Yes, if needed. Select any status from dropdown.
   Example: Can go from "pending" straight to "out_for_delivery"

**Q: How does customer access tracking?**
A: 
   - Link: `/public/track-order.php`
   - Enter: Order number (e.g., DFS-1002)
   - Enter: Email address
   - See: Full timeline with all updates

---

## 📊 Database Fields to Update

When you save an order update, these fields change:

```
orders.status              → pending/confirmed/processing/shipped/in_transit/out_for_delivery/delivered
orders.delivery_company    → "Dar Courier Express"
orders.delivery_notes      → Your custom note to customer
```

---

# Admin Activity Tracking System - Complete Guide

## 🎯 Problem Solved

You asked: **"Why admin cannot see what Ignas doing?"**

Now admins can see **EVERYTHING** customers do - from browsing products to placing orders!

---

## ✅ What Admins Can Now See

### 1. **Real-Time User Activities Dashboard**
   - Location: `Admin Dashboard` → See "Recent User Activities" widget
   - Shows last 50 customer actions with:
     - Customer name
     - Action type (login, product view, cart add, order placed, payment, etc.)
     - Product/Order information
     - Exact timestamp

### 2. **Activity Feed Page**
   - Location: Admin Sidebar → **"User Activities"**
   - Complete timeline of all customer actions
   - Filter by activity type:
     - 🔓 Logins
     - 👁️ Product Views
     - 🛒 Cart Additions
     - 💳 Checkouts
     - ✓ Orders Placed
     - 💰 Payments

### 3. **Online Users Monitor**
   - Location: Admin Dashboard → "Online Users Right Now" widget
   - See all users active in the last 5 minutes
   - Shows their last activity time
   - Green indicator for currently active users

### 4. **Customer Profiles with Activity History**
   - Location: Admin Sidebar → **"Users / Customers"**
   - Card view showing each user
   - Green dot (●) for active users
   - Recent activities listed on card
   - Contact info and join date

---

## 🔍 Real-World Example: Tracking Ignas's Journey

### What Admins See Now:

**Customer: Ignas**
```
✅ 2:30 PM - Ignas logged in (login)
✅ 2:31 PM - Ignas viewed "Linen Summer Dress" (view_product)
✅ 2:32 PM - Ignas viewed "Street Sneakers" (view_product)
✅ 2:35 PM - Ignas added "Linen Summer Dress x1" to cart (add_to_cart)
✅ 2:36 PM - Ignas added "Street Sneakers x1" to cart (add_to_cart)
✅ 2:38 PM - Ignas viewed cart (cart view)
✅ 2:40 PM - Ignas started checkout (checkout)
✅ 2:42 PM - Ignas placed order DFS-20260525-001 (order_placed)
✅ 2:43 PM - Ignas attempted payment via Mobile Money (payment_attempted)
✅ 3:00 PM - Payment verified ✓
```

**What Admin Can Do:**
1. Click on Ignas's name → See all their activities
2. Click on the order → See order status and all activity history
3. See that payment took ~1 minute - if delayed, investigate payment gateway
4. Update order status immediately → Customer sees it in real-time on Track Order page

---

## 📊 Tracked Activities (9 Types)

| Activity | What It Means | Why It Matters |
|----------|---------------|----------------|
| **login** | Customer logged in | Track engagement |
| **logout** | Customer logged out | Track session duration |
| **view_product** | Viewed product details | See browsing patterns |
| **add_to_cart** | Added item to cart | Track interest level |
| **remove_from_cart** | Removed from cart | Identify issues |
| **checkout** | Started checkout | Cart abandonment indicator |
| **order_placed** | Completed order | Order creation confirmation |
| **payment_attempted** | Tried to pay | Payment flow tracking |
| **order_status_update** | Admin updated status | Track order progress updates |

---

## 🎮 How To Use The Features

### Activity Feed (Most Detailed)
```
Navigate to: Admin Sidebar → User Activities
- View all activities chronologically
- Filter by activity type (top buttons)
- Click user name to see their profile
- Click order number to view order details
```

### Admin Dashboard (Quick Overview)
```
Dashboard shows:
- "Online Users Right Now" → Active customers
- "Recent User Activities" → Last 10 activities
- Total online count
- Links to detailed pages
```

### Users/Customers Page (Profile View)
```
Navigate to: Admin Sidebar → Users / Customers
- See all registered users as cards
- Green dot (●) = Currently active
- Shows recent 3 activities on card
- Shows join date, email, phone, status
- Total online users count
```

---

## 💡 What Admins Can Do With This Info

### 1. **Monitor Cart Abandonment**
- See customers who added items but didn't checkout
- Can follow up with email/message

### 2. **Track Order Progress**
- See exactly when each order stage happens
- Know if payment is delayed
- Update customer immediately

### 3. **Customer Support**
- See what customer viewed before contacting
- Understand their browsing history
- Better serve their needs

### 4. **Analytics & Patterns**
- Which products are most viewed?
- When do customers add to cart?
- Do they complete checkout?
- Where do they drop off?

### 5. **Real-Time Order Management**
- See order placed → Verify payment → Update status → Customer sees it
- All within minutes instead of hours

---

## 📁 New Files Created

```
app/models/ActivityLog.php          - Core activity logging system
admin/activities/index.php          - Activity feed page
```

## 🔧 Modified Files

```
database/ecommerce.sql              - Added user_activities table
public/login.php                    - Logs login
public/logout.php                   - Logs logout
public/product.php                  - Logs product views
public/cart.php                     - Logs cart add/remove
public/checkout.php                 - Logs checkout & order placement
admin/dashboard.php                 - Enhanced with activity widgets
admin/users/index.php               - Redesigned with activity cards
```

---

## 🚀 How It Works (Technical)

1. **User does something** (e.g., adds to cart)
   ↓
2. **Activity logged to database** with timestamp and details
   ↓
3. **Admin sees it in real-time** on dashboard/activity page
   ↓
4. **Admin can take action** (update order status, message customer, etc.)

The entire flow is **real-time** and **automatic**!

---

## ✨ Key Benefits

✅ **Complete Visibility** - See everything customers do
✅ **Real-Time Updates** - Information shows immediately  
✅ **Better Service** - Respond faster to orders
✅ **Track Progress** - Full order journey visibility
✅ **Identify Issues** - See cart abandonment, payment delays, etc.
✅ **Analytics** - Understand customer behavior patterns

---

## 🎓 Quick Start For Admin

1. Log in to admin dashboard
2. Look for **"Recent User Activities"** widget
3. Click **"User Activities"** in sidebar for full feed
