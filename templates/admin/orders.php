<?php

// Prevent direct access

if (!defined('ABSPATH')) {

    exit;

}

?>



<div class="apd-orders-admin">

    <style>

    .apd-orders-admin {

        max-width: 1200px;

        margin: 0 auto;

        padding: 20px;

    }

    

    .apd-header {

        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

        color: white;

        padding: 40px;

        border-radius: 12px;

        margin-bottom: 30px;

    }

    

    .apd-header h1 {

        font-size: 2.5rem;

        font-weight: bold;

        margin: 0 0 10px 0;

    }

    

    .apd-header p {

        color: rgba(255,255,255,0.9);

        font-size: 1.1rem;

        margin: 0;

    }

    

    .orders-stats {

        display: grid;

        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

        gap: 20px;

        margin-bottom: 30px;

    }

    

    .stat-card {

        background: white;

        border-radius: 12px;

        padding: 24px;

        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

        text-align: center;

    }

    

    .stat-number {

        font-size: 2rem;

        font-weight: bold;

        color: #2563eb;

        margin-bottom: 8px;

    }

    

    .stat-label {

        color: #6b7280;

        font-weight: 500;

    }

    

    .orders-table {

        background: white;

        border-radius: 12px;

        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

        overflow: hidden;

    }

    

    .table-header {

        background: #f8f9fa;

        padding: 20px;

        border-bottom: 1px solid #e5e7eb;

    }

    

    .table-header h2 {

        margin: 0;

        color: #1f2937;

    }

    

    .table-content {

        padding: 0;

    }

    

    .order-row {

        display: grid;

        grid-template-columns: 1fr 2fr 1fr 1fr 1fr;

        gap: 20px;

        padding: 20px;

        border-bottom: 1px solid #f3f4f6;

        align-items: center;

    }

    

    .order-row:last-child {

        border-bottom: none;

    }

    

    .order-row:hover {

        background: #f9fafb;

    }

    

    .order-id {

        font-weight: 600;

        color: #2563eb;

    }

    

    .customer-info {

        display: flex;

        flex-direction: column;

        gap: 4px;

    }

    

    .customer-name {

        font-weight: 600;

        color: #1f2937;

    }

    

    .customer-email {

        color: #6b7280;

        font-size: 0.9rem;

    }

    

    .order-date {

        color: #6b7280;

        font-size: 0.9rem;

    }

    

    .order-total {

        font-weight: 600;

        color: #059669;

    }

    

    .status-badge {

        padding: 6px 12px;

        border-radius: 20px;

        font-size: 0.8rem;

        font-weight: 600;

        text-transform: uppercase;

    }

    

    .status-pending {

        background: #fef3c7;

        color: #92400e;

    }

    

    .status-confirmed {

        background: #dbeafe;

        color: #1e40af;

    }

    

    .status-processing {

        background: #e0e7ff;

        color: #3730a3;

    }

    

    .status-shipped {

        background: #dcfce7;

        color: #166534;

    }

    

    .status-completed {

        background: #d1fae5;

        color: #065f46;

    }

    

    .status-canceled {

        background: #fee2e2;

        color: #991b1b;

    }

    

    .order-actions {

        display: flex;

        gap: 8px;

    }

    

    .btn-small {

        padding: 6px 12px;

        border: none;

        border-radius: 6px;

        font-size: 0.8rem;

        font-weight: 500;

        cursor: pointer;

        text-decoration: none;

        display: inline-block;

    }

    

    .btn-view {

        background: #2563eb;

        color: white;

    }

    

    .btn-view:hover {

        background: #1d4ed8;

    }

    

    .btn-edit {

        background: #059669;

        color: white;

    }

    

    .btn-edit:hover {

        background: #047857;

    }

    

    .empty-state {

        text-align: center;

        padding: 60px 20px;

        color: #6b7280;

    }

    

    .empty-state h3 {

        margin-bottom: 16px;

        color: #374151;

    }

    

    .empty-state p {

        margin-bottom: 24px;

    }

    

    .btn-primary {

        background: #2563eb;

        color: white;

        padding: 12px 24px;

        border-radius: 8px;

        text-decoration: none;

        font-weight: 600;

        display: inline-block;

    }

    

    .btn-primary:hover {

        background: #1d4ed8;

    }

    </style>

    

    <div class="apd-header">

        <h1>Orders Management</h1>

        <p>View and manage customer orders</p>

    </div>

    

    <?php

    // Calculate order statistics

    $total_orders = count($orders);

    $pending_orders = 0;

    $completed_orders = 0;

    $total_revenue = 0;

    

    foreach ($orders as $order) {

        $status = get_post_status($order->ID);

        $total = get_post_meta($order->ID, 'total_amount', true);

        

        if ($status === 'apd_pending') {

            $pending_orders++;

        } elseif ($status === 'apd_completed') {

            $completed_orders++;

        }

        

        if ($total) {

            $total_revenue += floatval($total);

        }

    }

    ?>

    

    <div class="orders-stats">

        <div class="stat-card">

            <div class="stat-number"><?php echo $total_orders; ?></div>

            <div class="stat-label">Total Orders</div>

        </div>

        <div class="stat-card">

            <div class="stat-number"><?php echo $pending_orders; ?></div>

            <div class="stat-label">Pending Orders</div>

        </div>

        <div class="stat-card">

            <div class="stat-number"><?php echo $completed_orders; ?></div>

            <div class="stat-label">Completed Orders</div>

        </div>

        <div class="stat-card">

            <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>

            <div class="stat-label">Total Revenue</div>

        </div>

    </div>

    

    <div class="orders-table">

        <div class="table-header">

            <h2>Recent Orders</h2>

        </div>

        

        <div class="table-content">

            <?php if (empty($orders)): ?>

                <div class="empty-state">

                    <h3>No Orders Yet</h3>

                    <p>Orders from your freight sign customizer will appear here.</p>

                    <a href="<?php echo admin_url('post-new.php?post_type=apd_product'); ?>" class="btn-primary">

                        Create Your First Product

                    </a>

                </div>

            <?php else: ?>

                <div class="order-row" style="background: #f8f9fa; font-weight: 600; color: #374151;">

                    <div>Order ID</div>

                    <div>Customer</div>

                    <div>Date</div>

                    <div>Total</div>

                    <div>Status</div>

                    <div>Actions</div>

                </div>

                

                <?php foreach ($orders as $order): ?>

                    <?php

                    $customer_name = get_post_meta($order->ID, 'customer_name', true);

                    $customer_email = get_post_meta($order->ID, 'customer_email', true);

                    $order_status = get_post_status($order->ID);

                    $order_total = get_post_meta($order->ID, 'total_amount', true);

                    $order_date = get_the_date('M j, Y', $order->ID);

                    

                    // Map status to display name

                    $status_map = array(

                        'apd_pending' => 'Pending',

                        'apd_confirmed' => 'Confirmed',

                        'apd_completed' => 'Completed'

                    );

                    

                    $status_display = isset($status_map[$order_status]) ? $status_map[$order_status] : 'Unknown';

                    $status_class = str_replace('apd_', 'status-', $order_status);

                    ?>

                    

                    <div class="order-row">

                        <div class="order-id">#<?php echo $order->ID; ?></div>

                        <div class="customer-info">

                            <div class="customer-name"><?php echo esc_html($customer_name); ?></div>

                            <div class="customer-email"><?php echo esc_html($customer_email); ?></div>

                        </div>

                        <div class="order-date"><?php echo $order_date; ?></div>

                        <div class="order-total">$<?php echo number_format(floatval($order_total), 2); ?></div>

                        <div>

                            <span class="status-badge <?php echo $status_class; ?>">

                                <?php echo $status_display; ?>

                            </span>

                        </div>

                        <div class="order-actions">

                            <a href="<?php echo admin_url('post.php?post=' . $order->ID . '&action=edit'); ?>" class="btn-small btn-view">

                                View

                            </a>

                            <a href="<?php echo admin_url('post.php?post=' . $order->ID . '&action=edit'); ?>" class="btn-small btn-edit">

                                Edit

                            </a>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

