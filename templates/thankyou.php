<div class="apd-thankyou-page" style="
    max-width: 100% ;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
">
    <!-- Header Section -->
    <div style="
        text-align: center;
        margin-bottom: 60px;
        padding: 40px 0;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    ">
        <div style="
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4, #10b981);
        "></div>
        
        <div style="
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        ">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="color: white;">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <h1 style="
            font-size: 3rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 20px 0;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        ">Thank You!</h1>
        
        <p style="
            font-size: 1.25rem;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        ">Your order has been successfully placed</p>
    </div>

    <!-- Order Details Section -->
    <div style="
        gap: 40px;
        margin-bottom: 40px;
    ">
        <!-- Order Summary Card -->
        <div style="
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        ">
            <h3 style="
                font-size: 1.5rem;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 25px 0;
                display: flex;
                align-items: center;
                gap: 12px;
            ">
                <div style="
                    width: 8px;
                    height: 8px;
                    background: #3b82f6;
                    border-radius: 50%;
                "></div>
                Order Summary
            </h3>
            
            <div style="margin-bottom: 20px;">
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 0;
                    border-bottom: 1px solid #f1f5f9;
                ">
                    <span style="color: #64748b; font-weight: 500;">Order ID</span>
                    <span style="color: #1e293b; font-weight: 600; font-family: monospace;">#<span id="order-id">Loading...</span></span>
                </div>
                
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 0;
                    border-bottom: 1px solid #f1f5f9;
                ">
                    <span style="color: #64748b; font-weight: 500;">Order Date</span>
                    <span style="color: #1e293b; font-weight: 600;" id="order-date">Loading...</span>
                </div>
                
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 0;
                    border-bottom: 1px solid #f1f5f9;
                ">
                    <span style="color: #64748b; font-weight: 500;">Status</span>
                    <span style="
                        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
                        color: #1e40af;
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 0.875rem;
                        font-weight: 600;
                    ">Processing</span>
                </div>
                
                <div style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 0;
                ">
                    <span style="color: #64748b; font-weight: 500;">Total Amount</span>
                    <span style="color: #1e293b; font-weight: 700; font-size: 1.25rem;" id="order-total">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    ">
        <a href="<?php echo home_url('/'); ?>" style="
            background: white;
            color: #3b82f6;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.125rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
            cursor: pointer;
        " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 35px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(0, 0, 0, 0.1)'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9,22 9,12 15,12 15,22"/>
            </svg>
            Continue Shopping
        </a>
    </div>

    <!-- Support Section -->
    <div style="
        margin-top: 60px;
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    ">
        <h3 style="
            font-size: 1.5rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 15px 0;
        ">Need Help?</h3>
        <p style="
            color: #64748b;
            margin: 0 0 25px 0;
            font-size: 1.125rem;
        ">Our customer support team is here to assist you</p>
        <div style="
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        ">
            <a href="mailto:support@example.com" style="
                color: #3b82f6;
                text-decoration: none;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                support@example.com
            </a>
            <span style="color: #cbd5e1;">•</span>
            <a href="tel:+1234567890" style="
                color: #3b82f6;
                text-decoration: none;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            ">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                </svg>
                +1 (234) 567-890
            </a>
        </div>
    </div>
</div>

<script>
// Load order details from session or URL parameters
document.addEventListener('DOMContentLoaded', function() {
    // Try to get order details from various sources
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('order_id') || sessionStorage.getItem('last_order_id');
    
    if (orderId) {
        document.getElementById('order-id').textContent = orderId;
        
        // Fetch order details via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'apd_get_order_details',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce('apd_order_details'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const order = data.data;
                document.getElementById('order-date').textContent = new Date(order.date).toLocaleDateString();
                document.getElementById('order-total').textContent = '$' + parseFloat(order.total).toFixed(2);
            }
        })
        .catch(error => {
            console.log('Could not fetch order details:', error);
            // Set default values
            document.getElementById('order-date').textContent = new Date().toLocaleDateString();
            document.getElementById('order-total').textContent = 'Processing...';
        });
    } else {
        // Set default values if no order ID found
        document.getElementById('order-id').textContent = 'N/A';
        document.getElementById('order-date').textContent = new Date().toLocaleDateString();
        document.getElementById('order-total').textContent = 'Processing...';
    }
});
</script>
