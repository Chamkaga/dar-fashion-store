/**
 * Google Analytics Integration Simulation
 * 
 * This file simulates Google Analytics tracking for demonstration purposes.
 * In production, replace with actual Google Analytics tracking code.
 */

// Simulated Google Analytics configuration
const GA_CONFIG = {
    trackingId: 'UA-DEMO-123456-1', // Demo tracking ID
    enableEcommerce: true,
    anonymizeIp: true,
    cookieDomain: 'auto'
};

// Simulated data layer
window.dataLayer = window.dataLayer || [];

// Simulated gtag function
function gtag() {
    window.dataLayer.push(arguments);
}

// Initialize Google Analytics (simulated)
function initGoogleAnalytics() {
    console.log('📊 Google Analytics initialized (Simulation Mode)');
    console.log('Tracking ID:', GA_CONFIG.trackingId);
    
    // Simulate page view tracking
    trackPageView(window.location.pathname);
    
    // Simulate user timing
    trackTiming('page_load', 'Page Load Time', Math.random() * 2000 + 500);
}

// Track page view (simulated)
function trackPageView(pagePath, pageTitle) {
    const pageData = {
        event: 'page_view',
        page_path: pagePath || window.location.pathname,
        page_title: pageTitle || document.title,
        page_location: window.location.href,
        timestamp: new Date().toISOString()
    };
    
    console.log('📄 Page View Tracked:', pageData);
    gtag('event', 'page_view', pageData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('page_view', pageData);
}

// Track event (simulated)
function trackEvent(eventName, eventParams) {
    const eventData = {
        event: eventName,
        ...eventParams,
        timestamp: new Date().toISOString()
    };
    
    console.log('🎯 Event Tracked:', eventData);
    gtag('event', eventName, eventData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('custom_event', eventData);
}

// Track product view (simulated)
function trackProductView(productId, productName, price, category) {
    const productData = {
        event: 'view_item',
        currency: 'TZS',
        value: price,
        items: [{
            item_id: productId,
            item_name: productName,
            item_category: category,
            price: price
        }]
    };
    
    console.log('👁️ Product View Tracked:', productData);
    gtag('event', 'view_item', productData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('product_view', productData);
}

// Track add to cart (simulated)
function trackAddToCart(productId, productName, price, quantity, category) {
    const cartData = {
        event: 'add_to_cart',
        currency: 'TZS',
        value: price * quantity,
        items: [{
            item_id: productId,
            item_name: productName,
            item_category: category,
            price: price,
            quantity: quantity
        }]
    };
    
    console.log('🛒 Add to Cart Tracked:', cartData);
    gtag('event', 'add_to_cart', cartData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('add_to_cart', cartData);
}

// Track purchase (simulated)
function trackPurchase(orderId, total, items) {
    const purchaseData = {
        event: 'purchase',
        transaction_id: orderId,
        value: total,
        currency: 'TZS',
        items: items
    };
    
    console.log('💳 Purchase Tracked:', purchaseData);
    gtag('event', 'purchase', purchaseData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('purchase', purchaseData);
}

// Track user signup (simulated)
function trackSignup(userId, method) {
    const signupData = {
        event: 'sign_up',
        user_id: userId,
        method: method || 'email'
    };
    
    console.log('👤 Signup Tracked:', signupData);
    gtag('event', 'sign_up', signupData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('signup', signupData);
}

// Track timing (simulated)
function trackTiming(category, variable, value, label) {
    const timingData = {
        event: 'timing',
        event_category: category,
        name: variable,
        value: Math.round(value),
        event_label: label
    };
    
    console.log('⏱️ Timing Tracked:', timingData);
    gtag('event', 'timing_complete', timingData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('timing', timingData);
}

// Track error (simulated)
function trackError(description, fatal) {
    const errorData = {
        event: 'exception',
        description: description,
        fatal: fatal || false
    };
    
    console.log('❌ Error Tracked:', errorData);
    gtag('event', 'exception', errorData);
    
    // Store in localStorage for demo analytics
    storeAnalyticsEvent('error', errorData);
}

// Store analytics event in localStorage for demo
function storeAnalyticsEvent(eventType, eventData) {
    try {
        const events = JSON.parse(localStorage.getItem('demo_analytics_events') || '[]');
        events.push({
            type: eventType,
            data: eventData,
            timestamp: new Date().toISOString()
        });
        
        // Keep only last 100 events
        if (events.length > 100) {
            events.shift();
        }
        
        localStorage.setItem('demo_analytics_events', JSON.stringify(events));
    } catch (e) {
        console.error('Failed to store analytics event:', e);
    }
}

// Get demo analytics data
function getDemoAnalyticsData() {
    try {
        const events = JSON.parse(localStorage.getItem('demo_analytics_events') || '[]');
        
        // Calculate metrics
        const metrics = {
            total_events: events.length,
            page_views: events.filter(e => e.type === 'page_view').length,
            product_views: events.filter(e => e.type === 'product_view').length,
            add_to_cart: events.filter(e => e.type === 'add_to_cart').length,
            purchases: events.filter(e => e.type === 'purchase').length,
            signups: events.filter(e => e.type === 'signup').length,
            unique_pages: [...new Set(events.filter(e => e.type === 'page_view').map(e => e.data.page_path))].length
        };
        
        return {
            events: events,
            metrics: metrics
        };
    } catch (e) {
        console.error('Failed to get demo analytics data:', e);
        return { events: [], metrics: {} };
    }
}

// Clear demo analytics data
function clearDemoAnalyticsData() {
    localStorage.removeItem('demo_analytics_events');
    console.log('🗑️ Demo analytics data cleared');
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGoogleAnalytics);
} else {
    initGoogleAnalytics();
}

// Export functions for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        trackPageView,
        trackEvent,
        trackProductView,
        trackAddToCart,
        trackPurchase,
        trackSignup,
        trackTiming,
        trackError,
        getDemoAnalyticsData,
        clearDemoAnalyticsData
    };
}
