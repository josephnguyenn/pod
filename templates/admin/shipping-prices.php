<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Capability check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Default US states list (50 states + DC) used only for initial seeding
$default_us_states = array(
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
);

// Options: prices, enabled, and editable states list
$option_key = 'shop_us_state_shipping_prices';
$enabled_option_key = 'shop_us_state_shipping_enabled';
$states_option_key = 'shop_us_state_list';

$saved_prices = get_option($option_key, array());
$saved_enabled = get_option($enabled_option_key, array());
$saved_states = get_option($states_option_key, array()); // assoc: code => name

// Seed states with default list if not yet saved (backward compatible)
if (empty($saved_states) || !is_array($saved_states)) {
    $saved_states = $default_us_states;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_shipping_prices_nonce'])) {
    if (!wp_verify_nonce($_POST['shop_shipping_prices_nonce'], 'shop_shipping_prices_action')) {
        wp_die(__('Security check failed.'));
    }

    // Incoming rows: states[n][code|name|price|enabled]
    $incoming_states = isset($_POST['states']) && is_array($_POST['states']) ? $_POST['states'] : array();

    $new_states = array(); // code => name
    $new_prices = array(); // code => price
    $new_enabled = array(); // code => bool

    foreach ($incoming_states as $row) {
        if (!is_array($row)) { continue; }
        $code = isset($row['code']) ? strtoupper(sanitize_text_field($row['code'])) : '';
        // Keep only alnum and dash/underscore, typical region codes
        $code = preg_replace('/[^A-Z0-9_\-]/', '', $code);
        $code = substr($code, 0, 16); // prevent excessively long codes
        $name = isset($row['name']) ? sanitize_text_field($row['name']) : '';

        if ($code === '' || $name === '') { continue; }
        if (isset($new_states[$code])) { continue; } // skip duplicates, keep first occurrence

        // Price
        $price_val = null;
        if (isset($row['price']) && $row['price'] !== '') {
            $raw = (string) $row['price'];
            $raw = str_replace(',', '.', $raw);
            $raw = preg_replace('/[^0-9.\-]/', '', $raw);
            $val = floatval($raw);
            if ($val < 0) { $val = 0.0; }
            $price_val = $val;
        }

        $enabled_val = isset($row['enabled']) ? true : false;

        $new_states[$code] = $name;
        if ($price_val !== null) { $new_prices[$code] = $price_val; }
        $new_enabled[$code] = $enabled_val;
    }

    // Persist
    update_option($states_option_key, $new_states, false);
    update_option($option_key, $new_prices, false);
    update_option($enabled_option_key, $new_enabled, false);

    // Refresh local copies for rendering
    $saved_states = $new_states;
    $saved_prices = $new_prices;
    $saved_enabled = $new_enabled;

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('States and shipping settings saved.', 'shop') . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('Region/State Shipping Prices', 'shop'); ?></h1>
    <p class="description"><?php echo esc_html__('Manage your list of regions or states and set per-region shipping. You can add, edit, or remove entries. Leave price blank to use default behavior.', 'shop'); ?></p>

    <form method="post" action="">
        <?php wp_nonce_field('shop_shipping_prices_action', 'shop_shipping_prices_nonce'); ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;"><?php echo esc_html__('Code', 'shop'); ?></th>
                    <th style="width: 35%;"><?php echo esc_html__('Name', 'shop'); ?></th>
                    <th style="width: 15%;"><?php echo esc_html__('Enabled', 'shop'); ?></th>
                    <th><?php echo esc_html__('Shipping Price', 'shop'); ?> (<?php echo esc_html(function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$'); ?>)</th>
                    <th style="width: 10%; text-align:right;">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $row_index = 0;
                foreach ($saved_states as $code => $name) :
                    $val = isset($saved_prices[$code]) ? $saved_prices[$code] : '';
                    $display = ($val === '' || $val === null) ? '' : number_format((float)$val, 2, '.', '');
                    $enabled_checked = isset($saved_enabled[$code]) ? (bool)$saved_enabled[$code] : true; // default enabled when not set
                ?>
                    <tr class="apd-row">
                        <td>
                            <input type="text" name="states[<?php echo esc_attr($row_index); ?>][code]" value="<?php echo esc_attr($code); ?>" class="regular-text" style="max-width: 100px;" required />
                        </td>
                        <td>
                            <input type="text" name="states[<?php echo esc_attr($row_index); ?>][name]" value="<?php echo esc_attr($name); ?>" class="regular-text" style="width: 100%;" required />
                        </td>
                        <td style="text-align:center;">
                            <input type="checkbox" name="states[<?php echo esc_attr($row_index); ?>][enabled]" value="1" <?php checked($enabled_checked, true); ?> />
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="states[<?php echo esc_attr($row_index); ?>][price]" value="<?php echo esc_attr($display); ?>" class="regular-text" style="max-width: 140px;" placeholder="0.00" />
                        </td>
                        <td style="text-align:right;">
                            <button type="button" class="button button-secondary apd-remove-row"><?php echo esc_html__('Remove', 'shop'); ?></button>
                        </td>
                    </tr>
                <?php $row_index++; endforeach; ?>
            </tbody>
        </table>

        <p class="submit" style="margin-top: 16px;">
            <button type="button" class="button" id="apd-add-row"><?php echo esc_html__('Add State/Region', 'shop'); ?></button>
            &nbsp;
            <button type="submit" class="button button-primary"><?php echo esc_html__('Save Changes', 'shop'); ?></button>
        </p>
    </form>
</div>

<script>
// Minimal JS to add/remove rows dynamically
(function(){
    const addBtn = document.getElementById('apd-add-row');
    const tableBody = document.querySelector('table.wp-list-table tbody');
    if (!tableBody) return;
    function nextIndex(){
        const rows = tableBody.querySelectorAll('tr.apd-row');
        let max = -1;
        rows.forEach(r => {
            const inp = r.querySelector('input[name^="states["]');
            if (!inp) return;
            const m = inp.getAttribute('name').match(/states\[(\d+)\]/);
            if (m) { const n = parseInt(m[1],10); if (!isNaN(n) && n > max) max = n; }
        });
        return max + 1;
    }
    function bindRowEvents(tr){
        const btn = tr.querySelector('.apd-remove-row');
        if (btn) btn.addEventListener('click', function(){ tr.parentNode.removeChild(tr); });
    }
    // Bind existing rows
    document.querySelectorAll('tr.apd-row').forEach(bindRowEvents);
    if (addBtn) addBtn.addEventListener('click', function(){
        const i = nextIndex();
        const tr = document.createElement('tr');
        tr.className = 'apd-row';
        tr.innerHTML = `
            <td><input type="text" name="states[${i}][code]" value="" class="regular-text" style="max-width:100px;" placeholder="e.g. CA" required /></td>
            <td><input type="text" name="states[${i}][name]" value="" class="regular-text" style="width:100%;" placeholder="e.g. California" required /></td>
            <td style="text-align:center;"><input type="checkbox" name="states[${i}][enabled]" value="1" checked /></td>
            <td><input type="number" step="0.01" min="0" name="states[${i}][price]" value="" class="regular-text" style="max-width:140px;" placeholder="0.00" /></td>
            <td style="text-align:right;"><button type="button" class="button button-secondary apd-remove-row"><?php echo esc_js(__('Remove', 'shop')); ?></button></td>
        `;
        tableBody.appendChild(tr);
        bindRowEvents(tr);
    });
})();
</script>
