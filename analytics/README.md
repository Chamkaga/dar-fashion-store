# Analytics & Reporting

## Overview
Analytics configuration, data models, and reporting insights for Dar Fashion Store.

## Google Analytics Integration

### Setup Instructions

#### 1. Create Google Analytics Account
1. Go to [analytics.google.com](https://analytics.google.com)
2. Sign in with Google account
3. Click "Start measuring" to create a new property
4. Fill in account name, property name, and website URL
5. Choose industry category and time zone

#### 2. Add Tracking Code to Website
```html
<!-- Add to app/includes/header.php -->
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

#### 3. Configure E-commerce Tracking
```javascript
// Track product views
gtag('event', 'view_item', {
  currency: 'TZS',
  value: product_price,
  items: [{
    item_id: product_sku,
    item_name: product_name,
    price: product_price
  }]
});

// Track add to cart
gtag('event', 'add_to_cart', {
  currency: 'TZS',
  value: product_price,
  items: [{
    item_id: product_sku,
    item_name: product_name,
    price: product_price,
    quantity: quantity
  }]
});

// Track purchase
gtag('event', 'purchase', {
  transaction_id: order_number,
  value: order_total,
  currency: 'TZS',
  items: order_items
});
```

#### 4. Set Up Goals and Conversions
- **Goal 1**: Product page view
- **Goal 2**: Add to cart
- **Goal 3**: Checkout initiation
- **Goal 4**: Purchase completion
- **Goal 5**: User registration

### Key Metrics to Track

#### Acquisition Metrics
- **Sessions**: Total number of visits
- **Users**: Unique visitors
- **New Users**: First-time visitors
- **Traffic Sources**: Organic, direct, referral, social
- **Bounce Rate**: Single-page sessions

#### Engagement Metrics
- **Page Views**: Total pages viewed
- **Avg Session Duration**: Time spent on site
- **Pages per Session**: Average pages viewed per visit
- **Event Tracking**: Custom interactions (add to cart, etc.)

#### Conversion Metrics
- **Conversion Rate**: Percentage of visitors who purchase
- **E-commerce Conversion Rate**: Purchases per session
- **Revenue**: Total sales revenue
- **Transactions**: Number of completed orders
- **Average Order Value**: Average revenue per order

#### Audience Metrics
- **Demographics**: Age, gender, location
- **Interests**: User interests and categories
- **Behavior**: New vs returning visitors
- **Device**: Mobile, desktop, tablet
- **Browser**: Chrome, Safari, Firefox, etc.

### Custom Dimensions

#### Product-Level Dimensions
- Product Category
- Product Color
- Product Size
- Product Price Range

#### User-Level Dimensions
- Customer Type (new vs returning)
- Purchase Frequency
- Average Order Value
- Favorite Category

## Microsoft Power BI Integration

### Setup Instructions

#### 1. Install Power BI Desktop
1. Download from [powerbi.microsoft.com](https://powerbi.microsoft.com)
2. Install Power BI Desktop application
3. Sign in with Microsoft account

#### 2. Connect to MySQL Database
1. Open Power BI Desktop
2. Click "Get Data" → "Database" → "MySQL Database"
3. Enter server details:
   - Server: localhost (or your database host)
   - Database: fashion_storedb
   - Username: your MySQL username
   - Password: your MySQL password
4. Test connection and load data

#### 3. Create Data Models
```sql
-- Products Table
SELECT id, name, category_id, price, sale_price, stock_quantity, 
       is_featured, is_trending, created_at
FROM products

-- Orders Table
SELECT id, order_number, user_id, customer_name, customer_email,
       total, status, created_at
FROM orders

-- Order Items Table
SELECT id, order_id, product_id, product_name, quantity, price, line_total
FROM order_items

-- Payments Table
SELECT id, order_id, method, amount, payment_status, created_at
FROM payments

-- Users Table
SELECT id, fullname, email, phone, role, status, created_at
FROM users

-- User Activities Table
SELECT id, user_id, activity_type, product_id, created_at
FROM user_activities
```

#### 4. Build Relationships
- Products → Order Items (one-to-many)
- Orders → Order Items (one-to-many)
- Orders → Payments (one-to-one)
- Users → Orders (one-to-many)
- Users → User Activities (one-to-many)

### Power BI Dashboards

#### Dashboard 1: Sales Overview
- **Total Revenue**: Card showing total sales
- **Total Orders**: Card showing order count
- **Revenue Trend**: Line chart showing sales over time
- **Top Products**: Bar chart of best-selling products
- **Revenue by Category**: Pie chart of sales by category
- **Payment Methods**: Donut chart of payment distribution

#### Dashboard 2: Customer Analytics
- **Total Customers**: Card showing customer count
- **New Customers**: Card showing new signups
- **Customer Activity**: Line chart of customer engagement
- **Top Customers**: Table showing highest-spending customers
- **Customer Segments**: Pie chart of customer types
- **Geographic Distribution**: Map of customer locations

#### Dashboard 3: Product Performance
- **Total Products**: Card showing product count
- **Low Stock Alert**: Card showing products needing restock
- **Product Views**: Bar chart of most viewed products
- **Conversion Rate**: Card showing product conversion
- **Category Performance**: Bar chart by category
- **Trending Products**: List of trending items

#### Dashboard 4: Operational Metrics
- **Order Status**: Donut chart of order statuses
- **Delivery Performance**: Bar chart of delivery times
- **Payment Status**: Donut chart of payment statuses
- **Inventory Levels**: Gauge showing stock health
- **Customer Support**: Card showing open tickets
- **System Performance**: Line chart of response times

### Power BI Reports

#### Report 1: Monthly Sales Report
- Sales by month
- Revenue by product category
- Top 10 products
- Customer acquisition
- Marketing channel performance

#### Report 2: Product Performance Report
- Product views vs purchases
- Conversion rate by product
- Inventory turnover
- Price sensitivity analysis
- Seasonal trends

#### Report 3: Customer Behavior Report
- Customer journey analysis
- Purchase frequency
- Average order value
- Customer lifetime value
- Churn analysis

### Scheduled Refresh

#### Configure Automatic Refresh
1. Publish report to Power BI Service
2. Set up data gateway for on-premises MySQL
3. Configure refresh schedule (daily/hourly)
4. Set up email notifications for refresh failures

#### Refresh Frequency
- **Sales Data**: Every hour
- **Customer Data**: Daily
- **Product Data**: Daily
- **Activity Data**: Every 6 hours

## Custom Analytics Implementation

### Database Views for Analytics

```sql
-- Daily Sales View
CREATE VIEW daily_sales AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as orders,
    SUM(total) as revenue,
    AVG(total) as avg_order_value
FROM orders
WHERE status = 'delivered'
GROUP BY DATE(created_at);

-- Product Performance View
CREATE VIEW product_performance AS
SELECT 
    p.id,
    p.name,
    p.category_id,
    COUNT(DISTINCT o.id) as orders,
    SUM(oi.quantity) as units_sold,
    SUM(oi.line_total) as revenue,
    AVG(oi.line_total) as avg_revenue_per_order
FROM products p
LEFT JOIN order_items oi ON oi.product_id = p.id
LEFT JOIN orders o ON o.id = oi.order_id
GROUP BY p.id, p.name, p.category_id;

-- Customer Behavior View
CREATE VIEW customer_behavior AS
SELECT 
    u.id,
    u.fullname,
    u.email,
    COUNT(DISTINCT o.id) as total_orders,
    SUM(o.total) as total_spent,
    AVG(o.total) as avg_order_value,
    MIN(o.created_at) as first_purchase,
    MAX(o.created_at) as last_purchase
FROM users u
LEFT JOIN orders o ON o.user_id = u.id
WHERE u.role = 'customer'
GROUP BY u.id, u.fullname, u.email;
```

### Analytics API Endpoints

#### Get Visitor Statistics
```php
// Endpoint: /api/analytics/visitors
// Returns: Total visitors, unique visitors, page views, bounce rate
```

#### Get Product Analytics
```php
// Endpoint: /api/analytics/products
// Returns: Most viewed products, conversion rates, sales data
```

#### Get Sales Analytics
```php
// Endpoint: /api/analytics/sales
// Returns: Daily/weekly/monthly sales, revenue trends
```

#### Get Customer Analytics
```php
// Endpoint: /api/analytics/customers
// Returns: Customer acquisition, retention, lifetime value
```

## Data Privacy and Compliance

### GDPR Compliance
- Anonymize IP addresses
- Obtain user consent for tracking
- Provide data export options
- Allow data deletion requests

### Data Retention
- Analytics data: 2 years
- User activities: 1 year
- Sales data: 7 years (for tax purposes)
- Personal data: Until account deletion

### Access Control
- Admin-only access to detailed analytics
- Role-based permissions for reports
- Audit logging for data access
- Secure data transmission (HTTPS)

## Performance Optimization

### Database Optimization
- Add indexes on frequently queried columns
- Use database views for complex queries
- Implement query caching
- Optimize JOIN operations

### Caching Strategy
- Cache analytics data for 15 minutes
- Use Redis for session caching
- Implement CDN for static assets
- Enable browser caching

### Query Optimization
- Use LIMIT for large result sets
- Avoid SELECT * queries
- Use prepared statements
- Monitor slow query logs

## Future Enhancements

### Advanced Analytics
- Predictive analytics for sales forecasting
- Customer segmentation using machine learning
- Recommendation engine for products
- A/B testing framework

### Real-time Analytics
- WebSocket for live data updates
- Real-time sales dashboard
- Live visitor tracking
- Instant notification system

### Mobile Analytics
- Mobile app analytics
- Push notification analytics
- In-app behavior tracking
- Mobile conversion optimization
