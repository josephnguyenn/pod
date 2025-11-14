/**
 * Orders Page JavaScript
 * Handles order management and interactions
 */

const APDOrders = {
    init: function() {
        console.log('🚀 Initializing APDOrders...');
        this.bindEvents();
        this.loadOrders();
    },

    bindEvents: function() {
        const self = this;
        const jQuery = window.jQuery || window.$;
        
        if (!jQuery) {
            console.error('❌ jQuery not available for orders');
            return;
        }

        // View order details
        jQuery(document).on('click', '.apd-btn-view-order', function() {
            const orderId = jQuery(this).data('order-id');
            const $orderCard = jQuery(this).closest('.apd-order-card');
            
            // Toggle order details visibility
            $orderCard.toggleClass('apd-order-expanded');
            
            if ($orderCard.hasClass('apd-order-expanded')) {
                jQuery(this).text('Hide Details');
            } else {
                jQuery(this).text('View Details');
            }
        });

        // Pay order
        jQuery(document).on('click', '.apd-btn-pay-order', function() {
            const orderId = jQuery(this).data('order-id');
            
            if (confirm('Proceed to payment for order #' + orderId + '?')) {
                // Redirect to checkout with order ID
                const checkoutUrl = window.apd_ajax?.checkout_url || '/checkout/';
                window.location.href = checkoutUrl + '?order=' + orderId;
            }
        });

        // Refresh orders
        jQuery(document).on('click', '.apd-btn-refresh-orders', function() {
            self.loadOrders();
        });
    },

    loadOrders: function() {
        const self = this;
        let ajaxConfig;
        
        try {
            ajaxConfig = window.apd_ajax || (typeof apd_ajax !== 'undefined' ? apd_ajax : null);
        } catch (e) {
            ajaxConfig = null;
        }
        
        if (!ajaxConfig || !ajaxConfig.ajax_url) {
            console.warn('⚠️ apd_ajax not available for loading orders');
            return;
        }
        
        const jQuery = window.jQuery || window.$;
        if (!jQuery || typeof jQuery.ajax === 'undefined') {
            console.error('❌ jQuery or jQuery.ajax not available for orders');
            return;
        }
        
        console.log('📋 Loading orders...');
        
        jQuery.ajax({
            url: ajaxConfig.ajax_url,
            type: 'POST',
            data: {
                action: 'apd_get_orders',
                nonce: ajaxConfig.nonce
            },
            success: function(response) {
                console.log('📋 Orders response:', response);
                if (response && response.success) {
                    self.renderOrders(response.data.orders);
                } else {
                    console.error('❌ Failed to load orders:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading orders:', error);
            }
        });
    },

    renderOrders: function(orders) {
        console.log('📋 Rendering orders:', orders);
        
        if (!orders || orders.length === 0) {
            this.showEmptyState();
            return;
        }
        
        // Orders are already rendered by PHP template
        // This function can be used for dynamic updates if needed
        console.log('✅ Orders rendered successfully');
    },

    showEmptyState: function() {
        console.log('📦 Showing empty orders state');
        // Empty state is handled by PHP template
    },

    createOrder: function(cartData) {
        const self = this;
        let ajaxConfig;
        
        try {
            ajaxConfig = window.apd_ajax || (typeof apd_ajax !== 'undefined' ? apd_ajax : null);
        } catch (e) {
            ajaxConfig = null;
        }
        
        if (!ajaxConfig || !ajaxConfig.ajax_url) {
            console.error('❌ apd_ajax not available for creating order');
            return Promise.reject('AJAX config not available');
        }
        
        const jQuery = window.jQuery || window.$;
        if (!jQuery || typeof jQuery.ajax === 'undefined') {
            console.error('❌ jQuery or jQuery.ajax not available for creating order');
            return Promise.reject('jQuery not available');
        }
        
        console.log('🛒 Creating order from cart...');
        
        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: ajaxConfig.ajax_url,
                type: 'POST',
                data: {
                    action: 'apd_create_order',
                    nonce: ajaxConfig.nonce,
                    cart_data: cartData
                },
                success: function(response) {
                    console.log('🛒 Order creation response:', response);
                    if (response && response.success) {
                        resolve(response.data);
                    } else {
                        reject(response.data || 'Failed to create order');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error creating order:', error);
                    reject(error);
                }
            });
        });
    }
};

// Initialize when document is ready
jQuery(document).ready(function($) {
    // Only initialize on orders page
    if (jQuery('.apd-orders-page').length > 0) {
        APDOrders.init();
    }
});

// Make APDOrders globally available
window.APDOrders = APDOrders;
