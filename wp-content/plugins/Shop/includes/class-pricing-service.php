<?php
/**
 * APD Pricing Service
 * 
 * Handles quantity-based tiered pricing calculations and management.
 * Supports percentage-based discounts for bulk orders.
 *
 * @package APD
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class APD_Pricing_Service
 * 
 * Manages product pricing tiers and calculates quantity-based discounts.
 */
class APD_Pricing_Service {
    
    /**
     * Meta key for storing price tiers
     */
    const TIER_META_KEY = '_apd_price_tiers';
    
    /**
     * Meta key for storing variant-specific price tiers
     */
    const VARIANT_TIER_META_KEY = '_apd_variant_price_tiers';
    
    /**
     * Calculate the final price for a product based on quantity
     * 
     * @param int $product_id Product ID
     * @param int $quantity Order quantity
     * @param float $base_price Base price per unit
     * @param string $variant_sku Optional variant SKU for variant-specific pricing
     * @return array Array with 'price', 'discount', 'tier' keys
     */
    public function calculate_tiered_price($product_id, $quantity, $base_price, $variant_sku = '') {
        $tiers = $this->get_price_tiers($product_id, $variant_sku);
        
        if (empty($tiers)) {
            return array(
                'price' => $base_price,
                'discount' => 0,
                'tier' => null,
                'total' => $base_price * $quantity
            );
        }
        
        $applicable_tier = $this->get_applicable_tier($tiers, $quantity);
        
        if (!$applicable_tier) {
            return array(
                'price' => $base_price,
                'discount' => 0,
                'tier' => null,
                'total' => $base_price * $quantity
            );
        }
        
        $discount_percent = floatval($applicable_tier['discount_percent']);
        $discount_amount = ($base_price * $discount_percent) / 100;
        $discounted_price = $base_price - $discount_amount;
        
        return array(
            'price' => $discounted_price,
            'discount' => $discount_percent,
            'tier' => $applicable_tier,
            'total' => $discounted_price * $quantity,
            'savings' => $discount_amount * $quantity
        );
    }
    
    /**
     * Get the applicable pricing tier for a given quantity
     * 
     * @param array $tiers Array of pricing tiers
     * @param int $quantity Order quantity
     * @return array|null Applicable tier or null
     */
    private function get_applicable_tier($tiers, $quantity) {
        $applicable_tier = null;
        
        foreach ($tiers as $tier) {
            $min_qty = intval($tier['min_qty']);
            if ($quantity >= $min_qty) {
                $applicable_tier = $tier;
            } else {
                break; // Tiers are sorted, so we can break early
            }
        }
        
        return $applicable_tier;
    }
    
    /**
     * Get price tiers for a product or variant
     * 
     * @param int $product_id Product ID
     * @param string $variant_sku Optional variant SKU for variant-specific pricing
     * @return array Array of pricing tiers sorted by min_qty ascending
     */
    public function get_price_tiers($product_id, $variant_sku = '') {
        // Try to get variant-specific tiers first if variant SKU is provided
        if (!empty($variant_sku)) {
            $variant_tiers_all = get_post_meta($product_id, self::VARIANT_TIER_META_KEY, true);
            if (is_array($variant_tiers_all) && isset($variant_tiers_all[$variant_sku]) && is_array($variant_tiers_all[$variant_sku])) {
                $tiers = $variant_tiers_all[$variant_sku];
                // Sort tiers by minimum quantity (ascending)
                usort($tiers, function($a, $b) {
                    return intval($a['min_qty']) - intval($b['min_qty']);
                });
                return $tiers;
            }
        }
        
        // Fall back to product-level tiers
        $tiers = get_post_meta($product_id, self::TIER_META_KEY, true);
        
        if (!is_array($tiers)) {
            return array();
        }
        
        // Sort tiers by minimum quantity (ascending)
        usort($tiers, function($a, $b) {
            return intval($a['min_qty']) - intval($b['min_qty']);
        });
        
        return $tiers;
    }
    
    /**
     * Save price tiers for a product
     * 
     * @param int $product_id Product ID
     * @param array $tiers Array of pricing tiers
     * @return bool Success status
     */
    public function save_price_tiers($product_id, $tiers) {
        if (!is_array($tiers)) {
            return false;
        }
        
        // Validate and sanitize tiers
        $sanitized_tiers = array();
        foreach ($tiers as $tier) {
            if (isset($tier['min_qty']) && isset($tier['discount_percent'])) {
                $min_qty = intval($tier['min_qty']);
                $discount = floatval($tier['discount_percent']);
                
                // Skip invalid entries
                if ($min_qty <= 0 || $discount < 0 || $discount > 100) {
                    continue;
                }
                
                $sanitized_tiers[] = array(
                    'min_qty' => $min_qty,
                    'discount_percent' => $discount,
                    'name' => isset($tier['name']) ? sanitize_text_field($tier['name']) : ''
                );
            }
        }
        
        // Sort by minimum quantity
        usort($sanitized_tiers, function($a, $b) {
            return $a['min_qty'] - $b['min_qty'];
        });
        
        return update_post_meta($product_id, self::TIER_META_KEY, $sanitized_tiers);
    }
    
    /**
     * Save variant-specific price tiers
     * 
     * @param int $product_id Product ID
     * @param string $variant_sku Variant SKU
     * @param array $tiers Array of pricing tiers
     * @return bool Success status
     */
    public function save_variant_tiers($product_id, $variant_sku, $tiers) {
        if (empty($variant_sku) || !is_array($tiers)) {
            return false;
        }
        
        // Get all variant tiers
        $all_variant_tiers = get_post_meta($product_id, self::VARIANT_TIER_META_KEY, true);
        if (!is_array($all_variant_tiers)) {
            $all_variant_tiers = array();
        }
        
        // Validate and sanitize tiers
        $sanitized_tiers = array();
        foreach ($tiers as $tier) {
            if (isset($tier['min_qty']) && isset($tier['discount_percent'])) {
                $min_qty = intval($tier['min_qty']);
                $discount = floatval($tier['discount_percent']);
                
                // Skip invalid entries
                if ($min_qty <= 0 || $discount < 0 || $discount > 100) {
                    continue;
                }
                
                $sanitized_tiers[] = array(
                    'min_qty' => $min_qty,
                    'discount_percent' => $discount,
                    'name' => isset($tier['name']) ? sanitize_text_field($tier['name']) : ''
                );
            }
        }
        
        // Sort by minimum quantity
        usort($sanitized_tiers, function($a, $b) {
            return $a['min_qty'] - $b['min_qty'];
        });
        
        // Update variant tiers
        if (empty($sanitized_tiers)) {
            unset($all_variant_tiers[$variant_sku]);
        } else {
            $all_variant_tiers[$variant_sku] = $sanitized_tiers;
        }
        
        return update_post_meta($product_id, self::VARIANT_TIER_META_KEY, $all_variant_tiers);
    }
    
    /**
     * Delete price tiers for a product
     * 
     * @param int $product_id Product ID
     * @return bool Success status
     */
    public function delete_price_tiers($product_id) {
        return delete_post_meta($product_id, self::TIER_META_KEY);
    }
    
    /**
     * Delete variant-specific price tiers
     * 
     * @param int $product_id Product ID
     * @param string $variant_sku Optional variant SKU (deletes all if empty)
     * @return bool Success status
     */
    public function delete_variant_tiers($product_id, $variant_sku = '') {
        if (empty($variant_sku)) {
            // Delete all variant tiers
            return delete_post_meta($product_id, self::VARIANT_TIER_META_KEY);
        }
        
        // Delete specific variant tiers
        $all_variant_tiers = get_post_meta($product_id, self::VARIANT_TIER_META_KEY, true);
        if (is_array($all_variant_tiers) && isset($all_variant_tiers[$variant_sku])) {
            unset($all_variant_tiers[$variant_sku]);
            return update_post_meta($product_id, self::VARIANT_TIER_META_KEY, $all_variant_tiers);
        }
        
        return false;
    }
    
    /**
     * Get HTML table for displaying pricing tiers
     * 
     * @param int $product_id Product ID
     * @param float $base_price Base price per unit
     * @param string $variant_sku Optional variant SKU
     * @return string HTML table or empty string
     */
    public function get_tier_table_html($product_id, $base_price, $variant_sku = '') {
        $tiers = $this->get_price_tiers($product_id, $variant_sku);
        
        if (empty($tiers)) {
            return '';
        }
        
        $html = '<div class="apd-pricing-tiers">';
        $html .= '<h4>' . esc_html__('Volume Discounts', 'apd') . '</h4>';
        $html .= '<table class="apd-tier-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . esc_html__('Quantity', 'apd') . '</th>';
        $html .= '<th>' . esc_html__('Discount', 'apd') . '</th>';
        $html .= '<th>' . esc_html__('Price/Unit', 'apd') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($tiers as $tier) {
            $discount_percent = floatval($tier['discount_percent']);
            $discounted_price = $base_price - (($base_price * $discount_percent) / 100);
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html($tier['min_qty']) . '+</td>';
            $html .= '<td>' . esc_html($discount_percent) . '%</td>';
            $html .= '<td>$' . number_format($discounted_price, 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get pricing message for cart/product display
     * 
     * @param int $product_id Product ID
     * @param int $quantity Current quantity
     * @param float $base_price Base price
     * @param string $variant_sku Optional variant SKU
     * @return string Pricing message HTML
     */
    public function get_pricing_message($product_id, $quantity, $base_price, $variant_sku = '') {
        $pricing = $this->calculate_tiered_price($product_id, $quantity, $base_price, $variant_sku);
        
        if ($pricing['discount'] > 0) {
            $message = sprintf(
                '<div class="apd-pricing-message apd-discount-active">' .
                '<strong>%s</strong> You save %s%% ($%s total savings)' .
                '</div>',
                esc_html__('Volume Discount Applied!', 'apd'),
                esc_html($pricing['discount']),
                number_format($pricing['savings'], 2)
            );
            return $message;
        }
        
        // Show next tier incentive
        $tiers = $this->get_price_tiers($product_id, $variant_sku);
        if (!empty($tiers)) {
            foreach ($tiers as $tier) {
                if ($quantity < intval($tier['min_qty'])) {
                    $more_needed = intval($tier['min_qty']) - $quantity;
                    $message = sprintf(
                        '<div class="apd-pricing-message apd-next-tier">' .
                        'Order %d more to save %s%%' .
                        '</div>',
                        $more_needed,
                        esc_html($tier['discount_percent'])
                    );
                    return $message;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Calculate final price including all factors
     * 
     * @param array $item_data Cart item data
     * @return float Final price
     */
    public function calculate_final_price($item_data) {
        $product_id = isset($item_data['product_id']) ? intval($item_data['product_id']) : 0;
        $quantity = isset($item_data['quantity']) ? intval($item_data['quantity']) : 1;
        $base_price = isset($item_data['base_price']) ? floatval($item_data['base_price']) : 0;
        
        if (!$product_id || !$base_price) {
            return $base_price * $quantity;
        }
        
        $pricing = $this->calculate_tiered_price($product_id, $quantity, $base_price);
        return $pricing['total'];
    }
    
    /**
     * Get formatted pricing breakdown for display
     * 
     * @param int $product_id Product ID
     * @param int $quantity Quantity
     * @param float $base_price Base price
     * @return array Formatted pricing data
     */
    public function get_pricing_breakdown($product_id, $quantity, $base_price) {
        $pricing = $this->calculate_tiered_price($product_id, $quantity, $base_price);
        
        return array(
            'base_price' => number_format($base_price, 2),
            'discounted_price' => number_format($pricing['price'], 2),
            'discount_percent' => $pricing['discount'],
            'subtotal' => number_format($base_price * $quantity, 2),
            'total' => number_format($pricing['total'], 2),
            'savings' => isset($pricing['savings']) ? number_format($pricing['savings'], 2) : '0.00',
            'tier_name' => isset($pricing['tier']['name']) ? $pricing['tier']['name'] : ''
        );
    }
    
    /**
     * Check if product has tiered pricing
     * 
     * @param int $product_id Product ID
     * @param string $variant_sku Optional variant SKU
     * @return bool True if product has tiers
     */
    public function has_tiered_pricing($product_id, $variant_sku = '') {
        $tiers = $this->get_price_tiers($product_id, $variant_sku);
        return !empty($tiers);
    }
    
    /**
     * Check if product has any variant-specific tiers
     * 
     * @param int $product_id Product ID
     * @return bool True if product has variant tiers
     */
    public function has_variant_tiers($product_id) {
        $variant_tiers = get_post_meta($product_id, self::VARIANT_TIER_META_KEY, true);
        return is_array($variant_tiers) && !empty($variant_tiers);
    }
    
    /**
     * Get all products with tiered pricing
     * 
     * @return array Array of product IDs
     */
    public function get_products_with_tiers() {
        global $wpdb;
        
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
            self::TIER_META_KEY
        ));
        
        return array_map('intval', $product_ids);
    }
    
    /**
     * Duplicate tiers from one product to another
     * 
     * @param int $source_product_id Source product ID
     * @param int $target_product_id Target product ID
     * @return bool Success status
     */
    public function duplicate_tiers($source_product_id, $target_product_id) {
        $tiers = $this->get_price_tiers($source_product_id);
        
        if (empty($tiers)) {
            return false;
        }
        
        return $this->save_price_tiers($target_product_id, $tiers);
    }
}
