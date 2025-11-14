<?php

/** Checkout template - renders the checkout interface */



// Prevent direct access

if (!defined('ABSPATH')) {

    exit;

}

error_log('✅ APD: checkout.php template is executing!');

?>

<!-- ✅ APD CHECKOUT TEMPLATE LOADED SUCCESSFULLY -->

<?php

// Load per-state shipping config and enabled list

// Dynamic list of regions/states (fallback to default US list)

$shop_us_states = get_option('shop_us_state_list', array(

    'AL' => 'Alabama',

    'AK' => 'Alaska',

    'AZ' => 'Arizona',

    'AR' => 'Arkansas',

    'CA' => 'California',

    'CO' => 'Colorado',

    'CT' => 'Connecticut',

    'DE' => 'Delaware',

    'FL' => 'Florida',

    'GA' => 'Georgia',

    'HI' => 'Hawaii',

    'ID' => 'Idaho',

    'IL' => 'Illinois',

    'IN' => 'Indiana',

    'IA' => 'Iowa',

    'KS' => 'Kansas',

    'KY' => 'Kentucky',

    'LA' => 'Louisiana',

    'ME' => 'Maine',

    'MD' => 'Maryland',

    'MA' => 'Massachusetts',

    'MI' => 'Michigan',

    'MN' => 'Minnesota',

    'MS' => 'Mississippi',

    'MO' => 'Missouri',

    'MT' => 'Montana',

    'NE' => 'Nebraska',

    'NV' => 'Nevada',

    'NH' => 'New Hampshire',

    'NJ' => 'New Jersey',

    'NM' => 'New Mexico',

    'NY' => 'New York',

    'NC' => 'North Carolina',

    'ND' => 'North Dakota',

    'OH' => 'Ohio',

    'OK' => 'Oklahoma',

    'OR' => 'Oregon',

    'PA' => 'Pennsylvania',

    'RI' => 'Rhode Island',

    'SC' => 'South Carolina',

    'SD' => 'South Dakota',

    'TN' => 'Tennessee',

    'TX' => 'Texas',

    'UT' => 'Utah',

    'VT' => 'Vermont',

    'VA' => 'Virginia',

    'WA' => 'Washington',

    'WV' => 'West Virginia',

    'WI' => 'Wisconsin',

    'WY' => 'Wyoming',

    'DC' => 'District of Columbia',

));



$state_prices = get_option('shop_us_state_shipping_prices', array());

$state_enabled = get_option('shop_us_state_shipping_enabled', array());

// Default currency symbol

$currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$';



// Build enabled states list (default to enabled=true if not explicitly set)

$enabled_states = array();

foreach ($shop_us_states as $code => $name) {

    $is_enabled = isset($state_enabled[$code]) ? (bool)$state_enabled[$code] : true;

    if ($is_enabled) {

        $enabled_states[$code] = $name;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - Freight Signs Customizer</title>

    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/css/checkout.css?v=' . time(); ?>">

    <script>

        // Expose shipping prices and currency to client for dynamic updates

        window.APD_SHIPPING = {

            prices: <?php echo wp_json_encode($state_prices); ?>,

            enabled: <?php echo wp_json_encode($state_enabled); ?>,

            currency: <?php echo wp_json_encode($currency_symbol); ?>

        };

    </script>

    <style>

        * {

            box-sizing: border-box;

        }

        

        body {

            margin: 0;

            padding: 0;

            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

            overflow-x: hidden;

            background: #f0f2f5;

        }

        

        /* Progress Indicator */

        .checkout-progress {

            display: flex;

            justify-content: center;

            align-items: center;

            gap: 20px;

            margin: 40px 0;

            padding: 0 20px;

        }

        

        .progress-step {

            padding: 8px 16px;

            border-radius: 20px;

            font-size: 0.875rem;

            font-weight: 500;

            color: #6c757d;

            background: #e9ecef;

        }

        

        .progress-step.active {

            background: #007bff;

            color: white;

        }

        

        .progress-arrow {

            color: #6c757d;

            font-size: 1.25rem;

        }

        

        /* Layout Changes */

        .checkout-content {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 40px;

            align-items: start;

            max-width: 1200px;

            margin: 0 auto;

            padding: 0 20px;

        }

        

        /* Design Preview Card */

        .design-preview-card {

            background: white;

            border-radius: 12px;

            padding: 20px;

            margin-bottom: 20px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

        }

        

        .design-preview-label {

            font-size: 0.75rem;

            color: #6c757d;

            margin-bottom: 12px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

        }

        

        .design-preview-image {

            margin-bottom: 12px;

        }

        

        .preview-sign {

            width: 100%;

            height: 200px;

            position: relative;

            border-radius: 8px;

            overflow: hidden;

        }

        

        .sign-background {

            position: absolute;

            top: 0;

            left: 0;

            right: 0;

            bottom: 0;

            background: linear-gradient(90deg, #8B4513 0%, #4B0082 100%);

        }

        

        .sign-content {

            position: absolute;

            top: 50%;

            left: 50%;

            transform: translate(-50%, -50%);

            text-align: center;

            color: white;

            z-index: 2;

        }

        

        .sign-logo {

            font-size: 1.5rem;

            font-weight: bold;

            margin-bottom: 8px;

        }

        

        .sign-location {

            font-size: 1rem;

            margin-bottom: 12px;

        }

        

        .sign-text {

            font-size: 1.25rem;

            font-weight: 600;

        }

        

        .design-preview-note {

            font-size: 0.75rem;

            color: #6c757d;

            text-align: center;

        }

        

        /* Product Details List */

        .product-details-list {

            background: white;

            border-radius: 12px;

            padding: 20px;

            margin-bottom: 20px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

        }

        

        .detail-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 8px 0;

            border-bottom: 1px solid #f8f9fa;

        }

        

        .detail-item:last-child {

            border-bottom: none;

        }

        

        .detail-label {

            font-weight: 600;

            color: #495057;

        }

        

        .detail-value {

            color: #6c757d;

        }

        

        /* Cost Summary */

        .cost-summary {

            background: white;

            border-radius: 12px;

            padding: 20px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

        }

        

        .cost-item.total {

            border-top: 2px solid #e9ecef;

            padding-top: 12px;

            margin-top: 12px;

            font-weight: bold;

            font-size: 1.125rem;

        }

        

        /* Payment Option */

        .payment-option {

            margin-bottom: 20px;

        }

        

        .payment-option-label {

            display: flex;

            align-items: center;

            padding: 16px;

            border: 2px solid #e9ecef;

            border-radius: 8px;

            cursor: pointer;

            transition: border-color 0.2s;

        }

        

        .payment-option-label:hover {

            border-color: #007bff;

        }

        

        .payment-option-label input[type="radio"] {

            margin-right: 12px;

            accent-color: #007bff;

        }

        

        .payment-option-content {

            display: flex;

            align-items: center;

            justify-content: space-between;

            width: 100%;

        }

        

        .payment-option-name {

            font-weight: 600;

            color: #212529;

        }

        

        .payment-option-logo {

            font-size: 1.25rem;

            font-weight: bold;

            color: #0070ba;

        }

        

        /* Test Payment Box */

        .test-payment-box {

            background: #d4edda;

            border: 2px solid #c3e6cb;

            border-radius: 12px;

            padding: 20px;

            margin-bottom: 20px;

        }

        

        .test-payment-header {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 8px;

        }

        

        .test-payment-icon {

            font-size: 1.25rem;

        }

        

        .test-payment-title {

            font-weight: 600;

            color: #155724;

        }

        

        .test-payment-description {

            font-size: 0.875rem;

            color: #155724;

            margin-bottom: 16px;

        }

        

        .mock-paypal-btn {

            width: 100%;

            background: #28a745;

            color: white;

            border: none;

            border-radius: 8px;

            padding: 16px;

            font-size: 1rem;

            font-weight: 600;

            cursor: pointer;

            transition: background-color 0.2s;

        }

        

        .mock-paypal-btn:hover {

            background: #218838;

        }

        

        /* Security Message */

        .payment-security {

            display: flex;

            align-items: center;

            gap: 8px;

            font-size: 0.875rem;

            color: #6c757d;

        }

        

        .security-icon {

            font-size: 1rem;

        }

        

        /* Select (state dropdown) */

        .checkout-form-group select {

            width: 100%;

            height: 44px;

            padding: 12px 14px;

            padding-right: 40px; /* room for chevron */

            border: 1px solid #ced4da;

            border-radius: 8px;

            background-color: #fff;

            color: #212529;

            font-size: 1rem;

            line-height: 1.5;

            outline: none;

            transition: border-color 0.2s, box-shadow 0.2s;

            -webkit-appearance: none;

            -moz-appearance: none;

            appearance: none;

            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'%3E%3Cpath d='M5.5 7.5l4.5 4.5 4.5-4.5' fill='none' stroke='%236c757d' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");

            background-repeat: no-repeat;

            background-position: right 12px center;

            background-size: 16px 16px;

        }



        .checkout-form-group select:hover {

            border-color: #adb5bd;

        }



        .checkout-form-group select:focus {

            border-color: #007bff;

            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);

        }



        .checkout-form-group select:disabled {

            background-color: #f8f9fa;

            color: #6c757d;

            cursor: not-allowed;

        }



        /* Form Validation Styles */
        .checkout-form-group input.error,
        .checkout-form-group select.error,
        .checkout-form-group textarea.error {
            border: 2px solid #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15) !important;
            background-color: #fff5f5;
        }
        
        .checkout-form-group input.error:focus,
        .checkout-form-group select.error:focus,
        .checkout-form-group textarea.error:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 4px;
            display: none;
        }
        
        .checkout-form-group input.error + .error-message,
        .checkout-form-group select.error + .error-message,
        .checkout-form-group textarea.error + .error-message {
            display: block;
        }

        /* Loading Animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .checkout-complete-btn:disabled {
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }

        /* Responsive */

        @media (max-width: 768px) {

            .checkout-content {

                grid-template-columns: 1fr;

                gap: 24px;

            }

            

            .checkout-progress {

                flex-wrap: wrap;

                gap: 12px;

            }

        }

    </style>

</head>

<body>
<script>
// IMMEDIATE TEST - This should appear FIRST in console
console.log('🔥🔥🔥 [APD] BODY SCRIPT LOADED - CHECKOUT PAGE');
console.log('🔥 [APD] URL:', window.location.href);
console.log('🔥 [APD] Timestamp:', new Date().toISOString());
</script>



<div class="checkout-container">

    <!-- Navigation -->

    <div class="checkout-nav">

        <a href="#" class="checkout-back-link">← Continue Shopping</a>

    </div>

    

    <!-- Header -->

    <div class="checkout-header">

        <h1 class="checkout-title">Checkout</h1>

        <p class="checkout-subtitle">Complete your order</p>

    </div>

    

    <div class="checkout-content">

        <!-- Left Column - Forms -->

        <div class="checkout-forms">

            

            <!-- Contact Information -->

            <div class="checkout-card">

                <div class="checkout-card-header">

                    <div class="checkout-step-number">1</div>

                    <h3 class="checkout-card-title">Contact Information</h3>

                </div>

                <div class="checkout-form-group">

                    <label for="email">Email Address</label>

                    <input type="email" id="email" name="email" required>

                </div>

                <div class="checkout-form-group">

                    <label for="phone">Phone Number</label>

                    <input type="tel" id="phone" name="phone" required>

                </div>

            </div>

            

            <!-- Shipping Address -->

            <div class="checkout-card">

                <div class="checkout-card-header">

                    <div class="checkout-step-number">2</div>

                    <h3 class="checkout-card-title">Shipping Address</h3>

                </div>

                <div class="checkout-form-row">

                    <div class="checkout-form-group">

                        <label for="first_name">First Name</label>

                        <input type="text" id="first_name" name="first_name" required>

                    </div>

                    <div class="checkout-form-group">

                        <label for="last_name">Last Name</label>

                        <input type="text" id="last_name" name="last_name" required>

                    </div>

                </div>

                <div class="checkout-form-group">

                    <label for="street_address">Street Address</label>

                    <input type="text" id="street_address" name="street_address" required>

                </div>

                <div class="checkout-form-group">

                    <label for="apartment">Apartment, suite, etc. (optional)</label>

                    <input type="text" id="apartment" name="apartment">

                </div>

                <div class="checkout-form-row three-columns">

                    <div class="checkout-form-group">

                        <label for="city">City</label>

                        <input type="text" id="city" name="city" required>

                    </div>

                    <div class="checkout-form-group">

                        <label for="state">State</label>

                        <select id="state" name="state" required>

                            <?php

                            $default_state = 'NY';

                            foreach ($enabled_states as $code => $name) :

                                $selected = ($code === $default_state) ? 'selected' : '';

                                ?>

                                <option value="<?php echo esc_attr($code); ?>" <?php echo $selected; ?>><?php echo esc_html($name . ' (' . $code . ')'); ?></option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="checkout-form-group">

                        <label for="zip_code">ZIP Code</label>

                        <input type="text" id="zip_code" name="zip_code" required>

                    </div>

                </div>

            </div>

            

            <!-- Shipping Method -->

            <div class="checkout-card">

                <div class="checkout-card-header">

                    <div class="checkout-step-number">3</div>

                    <h3 class="checkout-card-title">Shipping Method</h3>

                </div>

                <div class="shipping-options">

                    <div class="shipping-option">

                        <label class="shipping-option-label">

                            <input type="radio" name="shipping_method" value="standard" checked>

                            <div class="shipping-option-content">

                                <div class="shipping-option-info">

                                    <span class="shipping-option-name">Standard Shipping</span>

                                    <span class="shipping-option-duration">5-7 business days</span>

                                </div>

                                <span class="shipping-option-price" data-method="standard">$0.00</span>

                            </div>

                        </label>

                    </div>

                    <div class="shipping-option">

                        <label class="shipping-option-label">

                            <input type="radio" name="shipping_method" value="express">

                            <div class="shipping-option-content">

                                <div class="shipping-option-info">

                                    <span class="shipping-option-name">Express Shipping</span>

                                    <span class="shipping-option-duration">2-3 business days</span>

                                </div>

                                <span class="shipping-option-price" data-method="express">$0.00</span>

                            </div>

                        </label>

                    </div>

                </div>

            </div>

            

            <!-- Payment Information -->

            <div class="checkout-card">

                <div class="checkout-card-header">

                    <div class="checkout-step-number">4</div>

                    <h3 class="checkout-card-title">Payment Information</h3>

                </div>

                <div class="checkout-form-group">

                    <label for="card_number">Card Number</label>

                    <div class="card-input-wrapper">

                        <input type="text" id="card_number" name="card_number" required>

                        <div class="card-icon">💳</div>

                    </div>

                </div>

                <div class="checkout-form-row">

                    <div class="checkout-form-group">

                        <label for="expiry_date">MM / YY</label>

                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM / YY" required>

                    </div>

                    <div class="checkout-form-group">

                        <label for="cvc">CVC</label>

                        <input type="text" id="cvc" name="cvc" required>

                    </div>

                </div>

                <div class="checkout-form-group">

                    <label for="card_name">Name on Card</label>

                    <input type="text" id="card_name" name="card_name" required>

                </div>

                <div class="payment-security">

                    <span class="security-icon">🔒</span>

                    <span class="security-text">Your payment information is secure and encrypted</span>

                </div>

            </div>

            

        </div>

        

        <!-- Right Column - Order Summary -->

        <div class="checkout-summary">

            <div class="checkout-summary-card">

                <h3 class="checkout-summary-title">Order Summary</h3>



                <!-- Dynamic cart items from server/localStorage -->

                <div id="apd-order-items">
                    <!-- Items will be loaded here -->
                    <div style="padding: 20px; text-align: center; color: #6c757d;">
                        <p>Loading cart items...</p>
                    </div>
                </div>

                <div class="checkout-divider"></div>

                

                <!-- Cost Breakdown -->

                <div class="checkout-costs">

                    <div class="cost-item">

                        <span class="cost-label">Subtotal</span>

                        <span class="cost-value" id="apd-subtotal">$0.00</span>

                    </div>

                    <div class="cost-item">

                        <span class="cost-label">Shipping</span>

                        <span class="cost-value" id="apd-shipping">$0.00</span>

                    </div>

                    <div class="cost-item">

                        <span class="cost-label">Tax</span>

                        <span class="cost-value">$0.00</span>

                    </div>

                </div>

                

                <div class="checkout-divider"></div>

                

                <!-- Total -->

                <div class="checkout-total">

                    <span class="total-label">Total</span>

                    <span class="total-value" id="apd-total">$0.00</span>

                </div>

                

                <!-- Complete Order Button -->

                <button class="checkout-complete-btn">Complete Order</button>

                

                <!-- Terms -->

                <div class="checkout-terms">

                    By completing your order, you agree to our <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a>

                </div>

            </div>

        </div>

    </div>

</div>



<script>
// VERSION: v2.1.0 - Force cache refresh with immediate execution
// CRITICAL: This script MUST run - check browser console for these logs
(function() {
    'use strict';
    console.log('🚀🚀🚀 [APD] Checkout script v2.1.0 STARTING!');
    console.log('🚀 [APD] Current URL:', window.location.href);
    console.log('🚀 [APD] Script execution started at:', new Date().toISOString());
    console.log('🚀 [APD] Document ready state:', document.readyState);
    
    // Immediate check for apd-order-items element
    const immediateCheck = document.getElementById('apd-order-items');
    console.log('🚀 [APD] Immediate apd-order-items check:', immediateCheck ? 'FOUND' : 'NOT FOUND');
    
    // Check if DOM is already loaded
    if (document.readyState === 'loading') {
        console.log('🚀 [APD] DOM still loading, waiting for DOMContentLoaded...');
    } else {
        console.log('🚀 [APD] DOM already loaded, will execute immediately');
    }
})();

// Checkout functionality
document.addEventListener('DOMContentLoaded', function() {
    try {
    console.log('[APD] ========== CHECKOUT PAGE LOADED ==========');
    console.log('[APD] ✅ DOMContentLoaded event fired successfully!');
    console.log('[APD] DOMContentLoaded event fired at:', new Date().toISOString());
    console.log('[APD] Document ready state:', document.readyState);
    
    // Test: Check if element exists
    const itemsWrap = document.getElementById('apd-order-items');
    console.log('[APD] itemsWrap element:', itemsWrap);
    console.log('[APD] itemsWrap exists:', !!itemsWrap);
    
    if (!itemsWrap) {
        console.error('[APD] ❌ CRITICAL: apd-order-items element not found in DOM!');
        console.error('[APD] Document body:', document.body);
        console.error('[APD] Available elements with "apd" in id:', Array.from(document.querySelectorAll('[id*="apd"]')).map(function(el) { return el.id; }));
        console.error('[APD] All divs with id:', Array.from(document.querySelectorAll('div[id]')).map(function(el) { return el.id; }));
        // Don't return - continue and try to create element or use fallback
        console.warn('[APD] Continuing despite missing element...');
    } else {
        console.log('[APD] ✅ apd-order-items element found');
        console.log('[APD] itemsWrap innerHTML length:', itemsWrap.innerHTML.length);
    }

    const formatMoney = v => '$' + (Number(v||0).toFixed(2));

    let CURRENT_CART = [];

    let CURRENT_SUBTOTAL = 0;
    
    console.debug('[APD] Initialized checkout variables');



    // Determine if this is an "instant" one-click checkout via query param.
    const _apd_url_params = new URLSearchParams(window.location.search || '');
    const instantParam = _apd_url_params.get('instant');
    // Accept both "instant=true" and "instant=1" as instant mode
    const _apd_instant = instantParam === 'true' || instantParam === '1' || instantParam === true || instantParam === 1;

    // Debug: log param parsing and instant mode detection
    try { 
        console.debug('[APD] URL search:', window.location.search); 
        console.debug('[APD] URLSearchParams instant param:', instantParam); 
        console.debug('[APD] _apd_instant (parsed):', _apd_instant); 
    } catch(e) {}



    // Helper: return the raw checkout payload string. If instant=true prefer the oneclick key,

    // but fall back to the regular key when missing.

    function apd_getCheckoutPayloadRaw() {

        // Debug helper: show which keys exist in localStorage

        try {

            const keys = Object.keys(localStorage || {}).filter(function(k) { return k.startsWith('apd_'); });

            console.debug('[APD] localStorage keys (apd_):', keys);

        } catch(e) { console.debug('[APD] localStorage keys: error', e); }

        // ALWAYS check oneclick payload first (Buy Now) - this takes priority over cart
        // This ensures when user clicks "Buy Now", that product is checked out, not the cart
        try {

            const rawOne = localStorage.getItem('apd_checkout_payload_oneclick');

            console.debug('[APD] read apd_checkout_payload_oneclick:', rawOne ? '(present)' : '(null)');

            if (rawOne) {

                console.debug('[APD] Using oneclick payload (Buy Now mode) - ignoring cart');

                return rawOne;

            }

        } catch(e) { 

            console.debug('[APD] error reading apd_checkout_payload_oneclick', e); 

        }

        // Fall back to regular checkout payload

        try {

            const raw = localStorage.getItem('apd_checkout_payload');

            console.debug('[APD] read apd_checkout_payload:', raw ? '(present)' : '(null)');

            if (raw) {

                console.debug('[APD] Using regular checkout payload');

            }

            return raw;

        } catch (e) { 

            console.debug('[APD] error reading apd_checkout_payload', e); 

            return null; 

        }

    }



    // Helper: parse the payload safely and return object or null.

    function apd_getCheckoutPayload() {

        const raw = apd_getCheckoutPayloadRaw();

        if (!raw) return null;

        try { return JSON.parse(raw); } catch(_) { return null; }

    }



    function getPreviewUrl(it){

        let url = it.preview_image_svg || it.preview_image_png || it.preview_image_url || it.customization_image_url || it.image_url;

        if (!url && it.customization_data) {

            try {

                const cd = typeof it.customization_data === 'string' ? JSON.parse(it.customization_data) : it.customization_data;

                url = cd?.preview_image_svg || cd?.preview_image_png || cd?.preview_image_url || cd?.customization_image_url || cd?.image_url || '';

            } catch(_) {}

        }

        return url || '';

    }



    function getSelectedState(){

        const el = document.getElementById('state');

        const v = (el && el.value) ? String(el.value).toUpperCase() : 'NY';

        return v;

    }



    function getStateShippingPrice(code){

        try {

            const prices = (window.APD_SHIPPING && window.APD_SHIPPING.prices) ? window.APD_SHIPPING.prices : {};

            const raw = prices && Object.prototype.hasOwnProperty.call(prices, code) ? prices[code] : 0;

            const n = Number(raw);

            return isFinite(n) && n >= 0 ? n : 0;

        } catch(_) { return 0; }

    }



    function updateShippingAndTotal(){

        const code = getSelectedState();

        const shipping = getStateShippingPrice(code);

        const subEl = document.getElementById('apd-subtotal');

        const shipEl = document.getElementById('apd-shipping');

        const totEl = document.getElementById('apd-total');

        // Update shipping option price labels as well

        document.querySelectorAll('.shipping-option-price').forEach(function(el) { el.textContent = formatMoney(shipping); });

        if (shipEl) shipEl.textContent = formatMoney(shipping);

        const subtotal = CURRENT_SUBTOTAL || 0;

        if (totEl) totEl.textContent = formatMoney(subtotal + shipping);

    }



    function buildSummary(items, isInstantCheckout){
        console.log('[APD] ========== buildSummary CALLED ==========');
        console.log('[APD] buildSummary called with items:', items);
        console.log('[APD] items type:', Array.isArray(items) ? 'array' : typeof items);
        console.log('[APD] items length:', Array.isArray(items) ? items.length : 'N/A');
        console.log('[APD] isInstantCheckout:', isInstantCheckout);
        
        // Get itemsWrap element
        const itemsWrap = document.getElementById('apd-order-items');
        console.log('[APD] itemsWrap in buildSummary:', itemsWrap);
        if (!itemsWrap) {
            console.error('[APD] ❌ CRITICAL: itemsWrap not found in buildSummary!');
            return;
        }
        
        // Ensure items is an array
        if (!Array.isArray(items)) {
            console.debug('[APD] Items is not array, converting...');
            if (items && typeof items === 'object') {
                items = Object.values(items);
                console.debug('[APD] Converted from object to array, new length:', items.length);
            } else {
                console.warn('[APD] Items is not array or object, setting to empty array');
                items = [];
            }
        }
        
        console.debug('[APD] Items after array conversion:', items.length, 'items');
        if (items.length > 0) {
            console.debug('[APD] First item:', items[0]);
        }

        // Ensure all items have id field
        items = items.map(function(item, index) {
            if (!item) {
                console.warn('[APD] Item at index', index, 'is null/undefined');
                return null;
            }
            if (!item.id && item.cart_item_id) {
                item.id = item.cart_item_id;
            }
            // If still no id, generate one
            if (!item.id) {
                item.id = 'item_' + Date.now() + '_' + index + '_' + Math.random().toString(36).substr(2, 9);
            }
            // Ensure cart_item_id exists
            if (!item.cart_item_id && item.id) {
                item.cart_item_id = item.id;
            }
            return item;
        }).filter(function(item) { return item !== null; }); // Remove null items
        
        console.debug('[APD] Items after ID normalization:', items.length, 'items');

        // For instant checkout (Buy Now), skip cart selection filter and use all items
        if (isInstantCheckout) {
            console.debug('[APD] Instant checkout mode - skipping cart selection filter');
            console.debug('[APD] Items received for instant checkout:', items);
            console.debug('[APD] Items count:', items ? items.length : 0);
            CURRENT_CART = items && Array.isArray(items) ? items.slice() : (items ? [items] : []);
            console.debug('[APD] CURRENT_CART set to:', CURRENT_CART);
            console.debug('[APD] CURRENT_CART length:', CURRENT_CART.length);
            
            // Ensure CURRENT_CART has at least one item
            if (CURRENT_CART.length === 0 && items) {
                console.warn('[APD] ⚠️ CURRENT_CART is empty but items exist, converting to array');
                CURRENT_CART = Array.isArray(items) ? items : [items];
            }
        } else {
            // Respect selected items from cart page (stored in localStorage.apd_cart_selected)
            let selectedIds = [];
            try { 
                selectedIds = JSON.parse(localStorage.getItem('apd_cart_selected') || '[]') || []; 
                selectedIds = selectedIds.map(function(id) { return String(id); }); // Ensure all are strings
            } catch(e) { 
                console.debug('[APD] Error reading apd_cart_selected:', e);
                selectedIds = []; 
            }

            console.debug('[APD] Selected IDs from localStorage:', selectedIds);
            console.debug('[APD] Items before filtering:', items.map(function(it) {
                return { 
                    id: it.id, 
                    cart_item_id: it.cart_item_id,
                    name: it.product_name 
                };
            }));

            // Normalize all items to ensure consistent IDs first
            const normalizedItems = items.map(function(it) {
                // Create a copy to avoid mutating original
                const normalized = {...it};
                // Ensure both id and cart_item_id are set
                if (!normalized.id && normalized.cart_item_id) {
                    normalized.id = normalized.cart_item_id;
                }
                if (!normalized.cart_item_id && normalized.id) {
                    normalized.cart_item_id = normalized.id;
                }
                // If both exist but different, prefer id
                if (normalized.id && normalized.cart_item_id && normalized.id !== normalized.cart_item_id) {
                    normalized.cart_item_id = normalized.id;
                }
                return normalized;
            });
            
            // Get all available IDs from items (both id and cart_item_id)
            const availableIds = new Set();
            normalizedItems.forEach(function(it) {
                if (it.id) availableIds.add(String(it.id));
                if (it.cart_item_id) availableIds.add(String(it.cart_item_id));
            });
            
            // If no items selected OR selectedIds is empty, show all items
            // Otherwise, try to filter to only show selected items
            if (selectedIds.length > 0) {
                console.debug('[APD] Attempting to filter', normalizedItems.length, 'items by', selectedIds.length, 'selected IDs');
                
                // Filter items that match selected IDs
                CURRENT_CART = normalizedItems.filter(function(it) {
                    const itemId = String(it.id || '');
                    const cartItemId = String(it.cart_item_id || '');
                    
                    // Check if either id matches (case-sensitive first)
                    const matchesId = itemId && selectedIds.indexOf(itemId) !== -1;
                    const matchesCartItemId = cartItemId && selectedIds.indexOf(cartItemId) !== -1;
                    const isSelected = matchesId || matchesCartItemId;
                    
                    if (isSelected) {
                        console.debug('[APD] ✅ Item MATCHED:', { 
                            id: itemId, 
                            cart_item_id: cartItemId, 
                            product_name: it.product_name,
                            matched_by: matchesId ? 'id' : 'cart_item_id'
                        });
                    }
                    
                    return isSelected;
                });
                
                // If after filtering we have 0 items but we had selectedIds and items, try case-insensitive matching
                if (CURRENT_CART.length === 0 && selectedIds.length > 0 && normalizedItems.length > 0) {
                    console.warn('[APD] ⚠️ No items matched with case-sensitive comparison. Trying case-insensitive...');
                    
                    // Try case-insensitive and trimmed matching
                    const selectedIdsNormalized = selectedIds.map(function(id) {
                        return String(id).toLowerCase().trim();
                    });
                    const matchedItems = normalizedItems.filter(function(it) {
                        const itemId = String(it.id || '').toLowerCase().trim();
                        const cartItemId = String(it.cart_item_id || '').toLowerCase().trim();
                        return selectedIdsNormalized.indexOf(itemId) !== -1 || 
                               selectedIdsNormalized.indexOf(cartItemId) !== -1;
                    });
                    
                    if (matchedItems.length > 0) {
                        console.debug('[APD] ✅ Found', matchedItems.length, 'matches after case-insensitive comparison');
                        CURRENT_CART = matchedItems;
                    }
                }
                
                // CRITICAL FALLBACK: If still no items matched, show ALL items
                // This ensures users always see their products even if ID matching fails
                if (CURRENT_CART.length === 0 && normalizedItems.length > 0) {
                    console.error('[APD] ❌ CRITICAL: No items matched selected IDs after all attempts!');
                    console.error('[APD] Selected IDs:', selectedIds);
                    console.error('[APD] Available IDs from items:', Array.from(availableIds));
                    console.error('[APD] Items detail:', normalizedItems.map(function(it) {
                        return { 
                            id: String(it.id || ''), 
                            cart_item_id: String(it.cart_item_id || ''),
                            product_name: it.product_name
                        };
                    }));
                    console.warn('[APD] ⚠️ FALLBACK: Showing ALL items to prevent empty checkout');
                    CURRENT_CART = normalizedItems.slice();
                    // Update localStorage to match all available IDs so future checks work
                    try {
                        localStorage.setItem('apd_cart_selected', JSON.stringify(Array.from(availableIds)));
                        console.debug('[APD] Updated localStorage with all available IDs');
                    } catch(e) {
                        console.error('[APD] Error updating localStorage:', e);
                    }
                }
            } else {
                // No selection filter - show all items
                console.debug('[APD] No items selected in localStorage, showing all items');
                CURRENT_CART = normalizedItems.slice();
                // Update localStorage to select all items
                try {
                    const allIds = Array.from(availableIds);
                    if (allIds.length > 0) {
                        localStorage.setItem('apd_cart_selected', JSON.stringify(allIds));
                        console.debug('[APD] Updated localStorage to select all items');
                    }
                } catch(e) {
                    console.error('[APD] Error updating localStorage:', e);
                }
            }
        }

        console.debug('[APD] ========== buildSummary RESULT ==========');
        console.debug('[APD] Input items count:', items.length);
        console.debug('[APD] isInstantCheckout:', isInstantCheckout);
        console.debug('[APD] CURRENT_CART after filtering:', CURRENT_CART.length, 'items');
        if (CURRENT_CART.length > 0) {
            console.debug('[APD] ✅ CURRENT_CART items:', CURRENT_CART.map(function(it) {
                return { 
                    id: it.id, 
                    cart_item_id: it.cart_item_id,
                    product_name: it.product_name,
                    quantity: it.quantity,
                    price: it.price
                };
            }));
        } else {
            console.error('[APD] ❌ CURRENT_CART is EMPTY!');
            console.error('[APD] Input items were:', items);
            try {
                console.error('[APD] localStorage.apd_cart_selected:', localStorage.getItem('apd_cart_selected'));
                console.error('[APD] localStorage.apd_checkout_payload_oneclick:', localStorage.getItem('apd_checkout_payload_oneclick') ? 'exists' : 'none');
            } catch(e) {
                console.error('[APD] Error reading localStorage:', e);
            }
        }
        console.debug('[APD] =========================================');

        let subtotal = 0;

        if (!itemsWrap) {
            console.error('[APD] ❌ itemsWrap element not found!');
            return;
        }

        itemsWrap.innerHTML = '';

        if (CURRENT_CART.length === 0) {
            console.error('[APD] ❌❌❌ NO ITEMS TO DISPLAY IN CHECKOUT ❌❌❌');
            console.error('[APD] Input items count:', items.length);
            console.error('[APD] Filtered items count:', CURRENT_CART.length);
            console.error('[APD] isInstantCheckout:', isInstantCheckout);
            console.error('[APD] Items before filtering:', items);
            itemsWrap.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545; border: 2px solid #dc3545; border-radius: 8px; background: #f8d7da;"><strong>⚠️ No items in cart</strong><br/>Please add items to your cart before checkout.<br/><small>Check browser console (F12) for debug information.</small></div>';
            CURRENT_SUBTOTAL = 0;
            const subEl = document.getElementById('apd-subtotal');
            const totEl = document.getElementById('apd-total');
            if (subEl) subEl.textContent = formatMoney(0);
            if (totEl) totEl.textContent = formatMoney(0);
            updateShippingAndTotal();
            return;
        }

        console.log('[APD] 🎨 Starting to render', CURRENT_CART.length, 'items...');
        
        (CURRENT_CART).forEach(function(it, index) {
            console.log('[APD] Rendering item', index + 1, 'of', CURRENT_CART.length, ':', it.product_name);
            
            // Extract customization data if nested
            let customizationData = {};
            if (it.customization_data) {
                if (typeof it.customization_data === 'string') {
                    try {
                        customizationData = JSON.parse(it.customization_data);
                        console.log('[APD] Parsed customization_data for item', index + 1);
                    } catch(e) {
                        console.error('[APD] Error parsing customization_data for item', index + 1, ':', e);
                    }
                } else if (typeof it.customization_data === 'object') {
                    customizationData = it.customization_data;
                }
            }

            // Use top-level fields or fall back to customization_data
            const basePrice = Number(it.base_price ?? customizationData.base_price ?? 0);
            const salePrice = it.sale_price ? Number(it.sale_price) : (customizationData.sale_price ? Number(customizationData.sale_price) : null);
            const materialPrice = Number(it.material_price ?? customizationData.material_price ?? 0);
            
            // Calculate correct unit price: base_price + material_price (or sale_price + material_price if sale exists)
            // If price is already set and includes material (from instant checkout), use it directly
            // Otherwise, calculate from base_price/sale_price + material_price
            let unitPrice = Number(it.price ?? 0);
            if (!unitPrice || unitPrice <= 0) {
                // Fallback: calculate from base_price/sale_price + material_price
                const productBasePrice = salePrice || basePrice || Number(it.product_price ?? customizationData.product_price ?? 0);
                unitPrice = productBasePrice + materialPrice;
            }

            const qty = Number(it.quantity ?? 1);

            const line = unitPrice * qty;

            subtotal += line;

            const name = (it.product_name || 'Product') + (qty? ' × ' + qty : '');

            const printColor = it.print_color ?? customizationData.print_color ?? '';
            const vinylMaterial = it.vinyl_material ?? customizationData.vinyl_material ?? '';
            const textFields = it.text_fields ?? customizationData.text_fields ?? {};

            // Use string concatenation to avoid PHP parsing issues with template strings
            const color = printColor ? '<div class="detail-item"><span class="detail-label">Color:</span><span class="detail-value">' + printColor + '</span></div>' : '';

            const material = vinylMaterial ? '<div class="detail-item"><span class="detail-label">Material:</span><span class="detail-value">' + vinylMaterial + '</span></div>' : '';

            const textName = textFields && Object.values(textFields)[0] ? Object.values(textFields)[0] : '';

            const nameRow = textName ? '<div class="detail-item"><span class="detail-label">Name:</span><span class="detail-value">' + textName + '</span></div>' : '';

            // Get preview URL - check both top-level and customization_data
            let purl = getPreviewUrl(it);
            if (!purl && customizationData) {
                purl = customizationData.preview_image_svg || customizationData.preview_image_png || customizationData.preview_image_url || customizationData.customization_image_url || customizationData.image_url || '';
            }

            // Create preview with placeholder and data attributes for lazy loading
            const previewId = 'preview-' + index;
            let preview = '';
            if (purl) {
                preview = '<div style="width:100%;height:120px;border-radius:8px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden"><img src="' + purl + '" alt="Design" style="max-width:100%;max-height:120px;object-fit:contain;display:block"/></div>';
            } else if (it.product_id) {
                // Show placeholder and mark for lazy loading
                preview = '<div id="' + previewId + '" data-product-id="' + it.product_id + '" style="width:100%;height:120px;border-radius:8px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#999;font-size:12px;">Loading preview...</div>';
            }

            // Build price breakdown
            let priceBreakdown = '';
            if (basePrice > 0 || salePrice || materialPrice > 0) {
                const productBasePrice = (salePrice && salePrice > 0) ? salePrice : basePrice;
                priceBreakdown = '<div class="detail-item" style="border-top: 1px solid #e9ecef; margin-top: 8px; padding-top: 8px;">';
                priceBreakdown += '<span class="detail-label" style="font-size: 0.9rem; color: #6c757d;">Price Breakdown:</span>';
                priceBreakdown += '<div style="text-align: right; margin-top: 4px;">';
                
                if (productBasePrice > 0) {
                    const priceLabel = salePrice && salePrice > 0 ? 'Sale Price' : 'Base Price';
                    priceBreakdown += '<div style="font-size: 0.875rem; color: #495057; margin-bottom: 2px;">' + priceLabel + ': ' + formatMoney(productBasePrice) + '</div>';
                }
                
                if (materialPrice > 0 && vinylMaterial) {
                    priceBreakdown += '<div style="font-size: 0.875rem; color: #495057; margin-bottom: 2px;">Material (' + vinylMaterial + '): ' + formatMoney(materialPrice) + '</div>';
                }
                
                if (productBasePrice > 0 || materialPrice > 0) {
                    const totalUnitPrice = productBasePrice + materialPrice;
                    priceBreakdown += '<div style="font-size: 0.875rem; font-weight: 600; color: #212529; margin-top: 4px; padding-top: 4px; border-top: 1px solid #dee2e6;">Unit Price: ' + formatMoney(totalUnitPrice) + '</div>';
                }
                
                priceBreakdown += '</div></div>';
            }

            const card = document.createElement('div');

            card.className = 'product-details-list';

            // Build HTML using string concatenation to avoid PHP parsing issues
            let cardHtml = '<div class="design-preview-card" style="margin-bottom:12px;">' + preview + '</div>';
            cardHtml += '<div class="detail-item"><span class="detail-label">Product:</span><span class="detail-value">' + (name || 'Product') + '</span></div>';
            cardHtml += color + material;
            cardHtml += priceBreakdown;
            cardHtml += '<div class="detail-item" style="margin-top: 8px; padding-top: 8px; border-top: 2px solid #dee2e6;">';
            cardHtml += '<span class="detail-label" style="font-weight: 700; font-size: 1rem;">Line Total:</span>';
            cardHtml += '<span class="detail-value" style="font-weight: 700; font-size: 1rem; color: #28a745;">' + formatMoney(line) + '</span>';
            cardHtml += '</div>';
            cardHtml += nameRow;
            
            card.innerHTML = cardHtml;

            console.log('[APD] Appending card for item', index + 1, 'to itemsWrap');
            try {
                itemsWrap.appendChild(card);
                console.log('[APD] ✅ Card successfully appended for item', index + 1);
            } catch(e) {
                console.error('[APD] ❌ Error appending card for item', index + 1, ':', e);
            }

        });
        
        console.log('[APD] ✅ Finished rendering all items');
        console.log('[APD] itemsWrap children count:', itemsWrap ? itemsWrap.children.length : 'N/A');
        console.log('[APD] itemsWrap innerHTML length:', itemsWrap ? itemsWrap.innerHTML.length : 'N/A');
        console.log('[APD] itemsWrap.innerHTML preview:', itemsWrap ? itemsWrap.innerHTML.substring(0, 200) : 'N/A');

        const subEl = document.getElementById('apd-subtotal');

        const totEl = document.getElementById('apd-total');

        CURRENT_SUBTOTAL = subtotal;

        if (subEl) subEl.textContent = formatMoney(subtotal);

        updateShippingAndTotal();

        // After rendering, lazy load template previews for products without preview images
        lazyLoadTemplatePreviews();

    }

    // Lazy load template previews for products that don't have preview images
    function lazyLoadTemplatePreviews() {
        const previewsToLoad = document.querySelectorAll('[id^="preview-"][data-product-id]');
        console.log('[APD] Found', previewsToLoad.length, 'previews to lazy load');
        
        previewsToLoad.forEach(function(previewEl) {
            const productId = previewEl.getAttribute('data-product-id');
            if (!productId) return;
            
            console.log('[APD] Lazy loading template preview for product', productId);
            
            // Check if apd_ajax is available
            const ajaxObj = (typeof window !== 'undefined' && window.apd_ajax) || (typeof apd_ajax !== 'undefined' ? apd_ajax : null);
            if (!ajaxObj || !ajaxObj.ajax_url) {
                console.error('[APD] apd_ajax not available for lazy loading preview');
                previewEl.innerHTML = '<div style="color:#999;font-size:11px;text-align:center;">Preview not available</div>';
                return;
            }
            
            // Fetch template data
            fetch(ajaxObj.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'apd_get_customizer_data',
                    product_id: productId,
                    nonce: ajaxObj.nonce || ''
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success && data.data && data.data.product) {
                    const product = data.data.product;
                    // Use product image as fallback preview
                    if (product.image) {
                        previewEl.innerHTML = '<img src="' + product.image + '" alt="Product" style="max-width:100%;max-height:120px;object-fit:contain;display:block"/>';
                    } else {
                        previewEl.innerHTML = '<div style="color:#999;font-size:11px;text-align:center;">No preview available</div>';
                    }
                } else {
                    console.warn('[APD] Failed to load template data for product', productId);
                    previewEl.innerHTML = '<div style="color:#999;font-size:11px;text-align:center;">Preview not available</div>';
                }
            })
            .catch(function(error) {
                console.error('[APD] Error lazy loading preview for product', productId, ':', error);
                previewEl.innerHTML = '<div style="color:#999;font-size:11px;text-align:center;">Preview error</div>';
            });
        });
    }



    function loadCartFromLocal(){

        let items = [];

        try { 
            const raw = localStorage.getItem('apd_cart'); 
            if (raw) {
                const parsed = JSON.parse(raw);
                // Handle both array and object formats
                if (Array.isArray(parsed)) {
                    items = parsed.map(function(item) {
                        // Ensure id and cart_item_id are set and consistent
                        if (!item.id && item.cart_item_id) {
                            item.id = item.cart_item_id;
                        }
                        if (!item.cart_item_id && item.id) {
                            item.cart_item_id = item.id;
                        }
                        // If both exist but different, prefer id
                        if (item.id && item.cart_item_id && item.id !== item.cart_item_id) {
                            item.cart_item_id = item.id;
                        }
                        return item;
                    });
                } else if (parsed && typeof parsed === 'object') {
                    // Convert object to array, preserving the key as cart_item_id if needed
                    items = Object.entries(parsed).map(function(entry) {
                        const key = entry[0];
                        const item = entry[1];
                        // Ensure item has an id field - use the key if item.id is missing
                        if (!item.id) {
                            item.id = item.cart_item_id || key;
                        }
                        // Also ensure cart_item_id is set
                        if (!item.cart_item_id) {
                            item.cart_item_id = item.id || key;
                        }
                        // Ensure id and cart_item_id are the same for consistency
                        if (item.id && item.cart_item_id && item.id !== item.cart_item_id) {
                            item.cart_item_id = item.id;
                        }
                        return item;
                    });
                }
            }
        } catch(e){ 
            console.debug('[APD] Error loading cart from localStorage:', e);
        }

        // Normalize all items to ensure consistent IDs
        items = items.map(function(item) {
            // Ensure both id and cart_item_id are set
            if (!item.id && item.cart_item_id) {
                item.id = item.cart_item_id;
            }
            if (!item.cart_item_id && item.id) {
                item.cart_item_id = item.id;
            }
            // If both exist but different, prefer id
            if (item.id && item.cart_item_id && item.id !== item.cart_item_id) {
                item.cart_item_id = item.id;
            }
            return item;
        });

        if (!items.length) {

            try {

                const p = apd_getCheckoutPayload();

                if (p) {

                    items = [{ 
                        id: 'checkout_' + Date.now(),
                        product_id:p.product_id, 
                        product_name:p.product_name, 
                        price:Number(p.product_price||0), 
                        base_price:Number(p.base_price||0),
                        sale_price:p.sale_price ? Number(p.sale_price) : null,
                        material_price:Number(p.material_price||0),
                        quantity:Number(p.quantity||1), 
                        print_color:p.print_color, 
                        vinyl_material:p.vinyl_material, 
                        material_texture_url:p.material_texture_url, 
                        text_fields:p.text_fields, 
                        preview_image_svg:p.preview_image_svg || p.preview_image_png 
                    }];

                }

            } catch(e){ 
                console.debug('[APD] Error loading checkout payload:', e);
            }

        }

        console.debug('[APD] loadCartFromLocal returning items:', items);
        return items;

    }



    // Helper function to load cart from server
    function loadCartFromServer() {
        // Check both window.apd_ajax and global apd_ajax
        const ajaxObj = (typeof window !== 'undefined' && window.apd_ajax) || (typeof apd_ajax !== 'undefined' ? apd_ajax : null);
        
        if (!ajaxObj || !ajaxObj.ajax_url) {
            console.warn('[APD] apd_ajax not available');
            console.warn('[APD] window.apd_ajax:', typeof window !== 'undefined' ? window.apd_ajax : 'window undefined');
            console.warn('[APD] global apd_ajax:', typeof apd_ajax !== 'undefined' ? apd_ajax : 'undefined');
            return Promise.resolve(null);
        }

        console.debug('[APD] Fetching cart from server:', ajaxObj.ajax_url);
        console.debug('[APD] Using nonce:', ajaxObj.nonce ? 'present' : 'missing');

        return fetch(ajaxObj.ajax_url, { 
            method:'POST', 
            headers:{'Content-Type':'application/x-www-form-urlencoded'}, 
            body: new URLSearchParams({ action:'apd_get_cart', nonce: ajaxObj.nonce || '' }) 
        })
        .then(r=>{
            console.debug('[APD] Server response status:', r.status, r.statusText);
            if (!r.ok) {
                throw new Error('HTTP ' + r.status + ': ' + r.statusText);
            }
            return r.json();
        })
        .then(resp=>{
            console.debug('[APD] Server cart response:', resp);

            if (resp && resp.success && resp.data && resp.data.cart) {
                const raw = resp.data.cart;
                console.debug('[APD] Raw cart data from server:', raw);
                console.debug('[APD] Cart data type:', Array.isArray(raw) ? 'array' : typeof raw);

                let serverItems = [];
                if (Array.isArray(raw)) {
                    serverItems = raw.map(function(item) {
                        // Ensure id field exists
                        if (!item.id) {
                            item.id = item.cart_item_id || ('item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));
                        }
                        // Also ensure cart_item_id is set if missing
                        if (!item.cart_item_id && item.id) {
                            item.cart_item_id = item.id;
                        }
                        return item;
                    });
                } else if (raw && typeof raw === 'object') {
                    // Convert object to array, preserving the key as cart_item_id if needed
                    serverItems = Object.entries(raw).map(function(entry) {
                        const key = entry[0];
                        const item = entry[1];
                        // Ensure item has an id field - use the key if item.id is missing
                        if (!item.id) {
                            item.id = item.cart_item_id || key || ('item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));
                        }
                        // Also ensure cart_item_id is set
                        if (!item.cart_item_id) {
                            item.cart_item_id = item.id || key;
                        }
                        // Ensure id and cart_item_id are the same for consistency
                        if (item.id && item.cart_item_id && item.id !== item.cart_item_id) {
                            // Prefer id over cart_item_id
                            item.cart_item_id = item.id;
                        }
                        return item;
                    });
                }

                console.debug('[APD] Processed server items:', serverItems.length, 'items');
                console.debug('[APD] Server items IDs:', serverItems.map(function(it) {
                    return { id: it.id, cart_item_id: it.cart_item_id, product_name: it.product_name };
                }));
                return serverItems;
            } else {
                console.warn('[APD] Server cart response was not successful or empty:', resp);
                return null;
            }
        })
        .catch(err=>{ 
            console.error('[APD] Error fetching cart from server:', err);
            return null;
        });
    }

    // Priority order for loading cart:
    // 1. Checkout payload (oneclick or regular) - for Buy Now / instant checkout
    // 2. Server cart (if no checkout payload)
    // 3. localStorage cart (fallback)

    function hasCheckoutPayload() {
        try {
            // Check for oneclick payload first (Buy Now)
            const oneclick = localStorage.getItem('apd_checkout_payload_oneclick');
            if (oneclick) {
                console.debug('[APD] Found oneclick checkout payload');
                return true;
            }
            // Check for regular checkout payload
            const regular = localStorage.getItem('apd_checkout_payload');
            if (regular) {
                console.debug('[APD] Found regular checkout payload');
                return true;
            }
        } catch(e) {
            console.debug('[APD] Error checking checkout payload:', e);
        }
        return false;
    }

    // SIMPLE LOGIC:
    // 1. If ?instant=true -> load from payload (Buy Now)
    // 2. If NOT instant -> load from cart (normal checkout)
    
    let payload = null;
    
    // Step 1: Check if instant mode
    if (_apd_instant) {
        console.log('[APD] 🔍 Instant mode - loading from payload');
        // Try to load payload
        try {
            const payloadRaw = localStorage.getItem('apd_checkout_payload_oneclick') || localStorage.getItem('apd_checkout_payload');
            if (payloadRaw) {
                payload = JSON.parse(payloadRaw);
                console.log('[APD] ✅ Payload loaded:', payload.product_name || 'Unknown');
            }
        } catch(e) {
            console.error('[APD] ❌ Error loading payload:', e);
        }
    } else {
        // Not instant mode - clear any old payloads
        console.log('[APD] 🔍 Normal checkout - loading from cart');
        try {
            localStorage.removeItem('apd_checkout_payload_oneclick');
            localStorage.removeItem('apd_checkout_payload');
        } catch(e) {}
    }
    
    // Step 2: If we have payload, use it (instant checkout)
    if (payload && _apd_instant) {
        console.log('[APD] ✅ INSTANT CHECKOUT - Using payload');
        
        // Convert payload to item - SIMPLE
        const basePrice = Number(payload.base_price || payload.product_price || payload.price || 29.99);
        const salePrice = payload.sale_price ? Number(payload.sale_price) : null;
        const materialPrice = Number(payload.material_price || 0);
        const unitPrice = (salePrice || basePrice) + materialPrice;
        
        const instantItem = {
            id: 'instant_' + Date.now(),
            cart_item_id: 'instant_' + Date.now(),
            product_id: payload.product_id || 0,
            product_name: payload.product_name || 'Custom Product',
            price: unitPrice,
            base_price: basePrice,
            sale_price: salePrice,
            material_price: materialPrice,
            quantity: Number(payload.quantity || 1),
            print_color: payload.print_color || '',
            vinyl_material: payload.vinyl_material || '',
            text_fields: payload.text_fields || {},
            preview_image_svg: payload.preview_image_svg || payload.preview_image_png || '',
            customization_data: payload
        };
        
        // Clear cart selection
        try {
            localStorage.removeItem('apd_cart_selected');
        } catch(e) {}
        
        // Render item - SIMPLE, no complex retry logic
        buildSummary([instantItem], true);
        return; // Done - don't load cart
    }
    
    // Normal checkout - load from cart
    console.log('[APD] ✅ CART CHECKOUT - Loading from cart');
    
    // Try localStorage first (fast)
    const localItems = loadCartFromLocal();
    if (localItems && localItems.length > 0) {
        console.log('[APD] ✅ Found', localItems.length, 'items in localStorage');
        buildSummary(localItems, true);
        return; // Done
    }
    
    // If no local items, try server
    console.log('[APD] ⚠️ No items in localStorage, trying server...');

    // Load from server
    const ajaxObj = (typeof window !== 'undefined' && window.apd_ajax) || (typeof apd_ajax !== 'undefined' ? apd_ajax : null);
    
    if (ajaxObj && ajaxObj.ajax_url) {
        loadCartFromServer().then(function(serverItems) {
            if (serverItems && serverItems.length > 0) {
                console.log('[APD] ✅ Loaded', serverItems.length, 'items from server');
                buildSummary(serverItems, true);
            } else {
                console.warn('[APD] ⚠️ No items from server');
                // Show empty message
                const itemsWrap = document.getElementById('apd-order-items');
                if (itemsWrap) {
                    itemsWrap.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545;"><p>No items in cart</p></div>';
                }
            }
        }).catch(function(error) {
            console.error('[APD] ❌ Error loading from server:', error);
            // Show error message
            const itemsWrap = document.getElementById('apd-order-items');
            if (itemsWrap) {
                itemsWrap.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545;"><p>Error loading cart. Please refresh the page.</p></div>';
            }
        });
    } else {
        console.warn('[APD] ⚠️ apd_ajax not available');
        // Show error message
        const itemsWrap = document.getElementById('apd-order-items');
        if (itemsWrap) {
            itemsWrap.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545;"><p>Error: Cannot load cart. Please refresh the page.</p></div>';
        }
    }
    
    // Safety check: After a delay, verify that items are displayed
    // If not, log detailed debugging information
    setTimeout(function() {
        const itemsWrap = document.getElementById('apd-order-items');
        if (itemsWrap) {
            const hasContent = itemsWrap.innerHTML.trim().length > 0;
            const hasItems = CURRENT_CART && CURRENT_CART.length > 0;
            
            console.debug('[APD] ========== SAFETY CHECK (after 2s) ==========');
            console.debug('[APD] itemsWrap has content:', hasContent);
            console.debug('[APD] CURRENT_CART length:', CURRENT_CART ? CURRENT_CART.length : 0);
            console.debug('[APD] itemsWrap innerHTML length:', itemsWrap.innerHTML.trim().length);
            
            if (!hasContent && !hasItems) {
                console.error('[APD] ❌❌❌ SAFETY CHECK FAILED: No items displayed! ❌❌❌');
                console.error('[APD] Attempting emergency fallback...');
                
                // Emergency fallback: Try to load from localStorage
                try {
                    const emergencyItems = loadCartFromLocal();
                    if (emergencyItems && emergencyItems.length > 0) {
                        console.debug('[APD] Emergency: Found', emergencyItems.length, 'items in localStorage');
                        // Show all items when checking out from cart page
                        buildSummary(emergencyItems, true); // true = skip selection filter, show all items
                    } else {
                        // Check for payload
                        const emergencyPayload = localStorage.getItem('apd_checkout_payload_oneclick');
                        if (emergencyPayload) {
                            try {
                                const payload = JSON.parse(emergencyPayload);
                                console.debug('[APD] Emergency: Found payload, creating instant item');
                                const instantItem = {
                                    id: 'emergency_' + Date.now(),
                                    cart_item_id: 'emergency_' + Date.now(),
                                    product_id: payload.product_id || 0,
                                    product_name: payload.product_name || 'Custom Product',
                                    price: Number(payload.product_price || payload.price || 0),
                                    quantity: Number(payload.quantity || 1),
                                    print_color: payload.print_color || '',
                                    vinyl_material: payload.vinyl_material || '',
                                    customization_data: payload
                                };
                                buildSummary([instantItem], true);
                            } catch(e) {
                                console.error('[APD] Emergency payload parse error:', e);
                            }
                        } else {
                            console.error('[APD] Emergency: No items or payload found');
                            itemsWrap.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545; border: 2px solid #dc3545; border-radius: 8px; background: #f8d7da;"><strong>⚠️ No items found</strong><br/>Please add items to your cart or use "Buy Now" from a product page.<br/><small>If this error persists, please contact support.</small></div>';
                        }
                    }
                } catch(e) {
                    console.error('[APD] Emergency fallback error:', e);
                }
            } else if (hasItems && !hasContent) {
                console.warn('[APD] ⚠️ CURRENT_CART has items but itemsWrap is empty - calling buildSummary again');
                if (CURRENT_CART.length > 0) {
                    buildSummary(CURRENT_CART, false);
                }
            }
            console.debug('[APD] =========================================');
        }
    }, 2000); // Check after 2 seconds

    // Form validation

    const form = document.querySelector('.checkout-forms');

    const mockPaypalBtn = document.querySelector('.mock-paypal-btn');

    

    if (mockPaypalBtn) mockPaypalBtn.addEventListener('click', function(e) {

        e.preventDefault();

        

        // Basic validation

        const requiredFields = form ? form.querySelectorAll('input[required], textarea[required]') : [];

        let isValid = true;

        

        requiredFields.forEach(field => {

            if (!field.value.trim()) {

                field.classList.add('error');

                // Add error message if not exists
                if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.insertBefore(errorMsg, field.nextSibling);
                }

                isValid = false;

            } else {

                field.classList.remove('error');

                // Remove error message
                const errorMsg = field.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }

            }

        });

        

        if (isValid) {

            // Show loading state

            mockPaypalBtn.textContent = 'Processing...';

            mockPaypalBtn.disabled = true;

            

            // Simulate payment processing delay

            setTimeout(() => {

                // Simulate successful payment without server call

                showSuccessMessage();

                

                // Reset button

                mockPaypalBtn.textContent = 'Mock PayPal Payment';

                mockPaypalBtn.disabled = false;

            }, 2000);

        } else {

            alert('Please fill in all required fields.');

        }

    });



    // New UI: Complete Order button handler

    const completeBtn = document.querySelector('.checkout-complete-btn');

    if (completeBtn) completeBtn.addEventListener('click', function(e) {

        e.preventDefault();
        
        // Form validation
        const form = document.querySelector('.checkout-forms');
        const requiredFields = form ? form.querySelectorAll('input[required], textarea[required], select[required]') : [];
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                // Add error message if not exists
                if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-message')) {
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = 'This field is required';
                    field.parentNode.insertBefore(errorMsg, field.nextSibling);
                }
                isValid = false;
            } else {
                field.classList.remove('error');
                // Remove error message
                const errorMsg = field.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields.');
            return;
        }

        // Show loading animation
        const originalBtnText = completeBtn.textContent;
        completeBtn.disabled = true;
        completeBtn.style.cursor = 'not-allowed';
        completeBtn.style.opacity = '0.6';
        completeBtn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">⏳</span> Processing...';

        try {

            // Use CURRENT_CART if we already rendered it; otherwise rebuild from localStorage

            let cart = Array.isArray(CURRENT_CART) && CURRENT_CART.length ? CURRENT_CART.slice() : [];

            if (!cart.length) {

                try { cart = JSON.parse(localStorage.getItem('apd_cart') || '[]'); } catch(_) {}

                if (!cart.length) {

                    const p = apd_getCheckoutPayload();

                    if (p) {

                        cart.push({ 
                            product_id:p.product_id, 
                            product_name:p.product_name, 
                            price:parseFloat(p.product_price||0), 
                            base_price:parseFloat(p.base_price||0),
                            sale_price:p.sale_price ? parseFloat(p.sale_price) : null,
                            material_price:parseFloat(p.material_price||0),
                            quantity:parseInt(p.quantity||1,10), 
                            print_color:p.print_color,
                            vinyl_material:p.vinyl_material,
                            customization_data:p 
                        });

                    }

                }

            }

            if (!cart.length) { 
                // Restore button on error
                completeBtn.disabled = false;
                completeBtn.style.cursor = 'pointer';
                completeBtn.style.opacity = '1';
                completeBtn.textContent = originalBtnText;
                alert('Cart is empty. Please add items before checkout.'); 
                return; 
            }



            // Collect form data

            const firstName = document.getElementById('first_name')?.value || '';

            const lastName = document.getElementById('last_name')?.value || '';

            const email = document.getElementById('email')?.value || '';

            const phone = document.getElementById('phone')?.value || '';

            const streetAddress = document.getElementById('street_address')?.value || '';

            const apartment = document.getElementById('apartment')?.value || '';

            const city = document.getElementById('city')?.value || '';

            const state = document.getElementById('state')?.value || '';

            const zipCode = document.getElementById('zip_code')?.value || '';

            const name = firstName + ' ' + lastName;

            const address = streetAddress + (apartment ? ', ' + apartment : '') + ', ' + city + ', ' + state + ' ' + zipCode;



            const body = new URLSearchParams({

                action: 'apd_place_order',

                nonce: (typeof apd_ajax !== 'undefined' ? apd_ajax.nonce : ''),

                customer_name: name,

                customer_email: email,

                customer_phone: phone,

                customer_address: address,

                payment_method: 'mock_paypal',

                cart: JSON.stringify(cart)

            });



            fetch((typeof apd_ajax !== 'undefined' ? apd_ajax.ajax_url : '/wp-admin/admin-ajax.php'), {

                method: 'POST',

                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },

                body

            }).then(r => {
                // Check if response is OK
                if (!r.ok) {
                    throw new Error('Network response was not ok: ' + r.statusText);
                }
                // Get response text first to check for JSON parsing issues
                return r.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error. Response text:', text.substring(0, 200));
                        throw new Error('Invalid JSON response: ' + e.message);
                    }
                });
            }).then(resp => {

                if (resp && resp.success && resp.data) {

                    if (resp.data.order_id) sessionStorage.setItem('last_order_id', resp.data.order_id);

                    // Clear cart and checkout payloads after successful order
                    try { localStorage.removeItem('apd_cart'); } catch(_) {}
                    try { localStorage.removeItem('apd_checkout_payload_oneclick'); } catch(_) {}
                    try { localStorage.removeItem('apd_checkout_payload'); } catch(_) {}
                    try { localStorage.removeItem('apd_cart_selected'); } catch(_) {}

                    if (resp.data.redirect) { window.location.href = resp.data.redirect; }

                    else { 
                        // Restore button on error
                        completeBtn.disabled = false;
                        completeBtn.style.cursor = 'pointer';
                        completeBtn.style.opacity = '1';
                        completeBtn.textContent = originalBtnText;
                        alert('Order created, but missing redirect URL.'); 
                    }

                } else {

                    // Restore button on error
                    completeBtn.disabled = false;
                    completeBtn.style.cursor = 'pointer';
                    completeBtn.style.opacity = '1';
                    completeBtn.textContent = originalBtnText;
                    alert('Failed to place order: ' + (resp && resp.data ? (resp.data.message || resp.data) : 'Unknown'));

                }

            }).catch(err => {

                // Restore button on error
                completeBtn.disabled = false;
                completeBtn.style.cursor = 'pointer';
                completeBtn.style.opacity = '1';
                completeBtn.textContent = originalBtnText;
                console.error('Place order error:', err);

                alert('Network error while placing order.');

            });

        } catch (err) {

            console.error('Complete order failed:', err);

        }

    });

    

    function showSuccessMessage() {

        // Create success modal

        const modal = document.createElement('div');

        modal.style.cssText = `

            position: fixed;

            top: 0;

            left: 0;

            right: 0;

            bottom: 0;

            background: rgba(0, 0, 0, 0.5);

            display: flex;

            align-items: center;

            justify-content: center;

            z-index: 1000;

        `;

        

        const modalContent = document.createElement('div');

        modalContent.style.cssText = `

            background: white;

            border-radius: 12px;

            padding: 40px;

            max-width: 400px;

            text-align: center;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);

        `;

        

        modalContent.innerHTML = `

            <div style="font-size: 48px; margin-bottom: 20px;">✅</div>

            <h2 style="margin: 0 0 16px 0; color: #28a745;">Order Successful!</h2>

            <p style="margin: 0 0 24px 0; color: #6c757d;">

                Your custom freight sign order has been placed successfully.

            </p>

            <button onclick="this.closest('.modal').remove()" style="

                background: #007bff;

                color: white;

                border: none;

                border-radius: 8px;

                padding: 12px 24px;

                font-size: 1rem;

                font-weight: 600;

                cursor: pointer;

            ">Continue Shopping</button>

        `;

        

        modal.className = 'modal';

        modal.appendChild(modalContent);

        document.body.appendChild(modal);

        

        // Auto remove after 5 seconds

        setTimeout(() => {

            if (modal.parentNode) {

                modal.remove();

            }

        }, 5000);

    }

    

    // Remove error class on input

    const inputs = document.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {

        input.addEventListener('input', function() {

            this.classList.remove('error');

            // Remove error message

            const errorMsg = this.nextElementSibling;

            if (errorMsg && errorMsg.classList.contains('error-message')) {

                errorMsg.remove();

            }

        });
        
        input.addEventListener('change', function() {

            this.classList.remove('error');

            // Remove error message

            const errorMsg = this.nextElementSibling;

            if (errorMsg && errorMsg.classList.contains('error-message')) {

                errorMsg.remove();

            }

        });

    });



    // Listen for state changes to update shipping and total

    const stateSelect = document.getElementById('state');

    if (stateSelect) {

        stateSelect.addEventListener('change', function(){

            updateShippingAndTotal();

        });

        // Initial sync in case the default state has a price

        updateShippingAndTotal();

    }
    
 } catch (checkoutError) {
     console.error('[APD] ❌ Error in checkout page initialization:', checkoutError);
     console.error('[APD] Error stack:', checkoutError.stack);
     alert('Error loading checkout page. Please check console for details.');
 }
 });
 
// Note: Functions are defined inside DOMContentLoaded, so this fallback won't work
// The main logic should run inside DOMContentLoaded handler above
 
 </script>



</body>

</html>

