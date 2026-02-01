<?php
/**
 * Order SVG Resolver
 *
 * Single source for resolving SVG content from an order (meta, cart_items, URLs).
 * Used by download_order_svg, apd_process_cut_ready_svg, apd_export_pdf, apd_get_order_svg.
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Order_SVG_Resolver
{
    /**
     * Get SVG content for an order from meta, cart items, or override.
     *
     * @param int         $order_id Order post ID.
     * @param string|null $override Optional raw SVG or data URL from client (e.g. apd_export_pdf POST).
     * @return array|null Array with 'content' (string) and 'source' (string), or null if not found.
     */
    public static function get_svg_for_order($order_id, $override = null)
    {
        $order_id = absint($order_id);
        $svg_content = '';
        $source = '';

        if (!empty($override)) {
            $svg_content = is_string($override) ? wp_unslash($override) : '';
            if (!empty($svg_content)) {
                $source = 'client override';
                $result = self::decode_and_fetch($svg_content, $order_id, $source);
                return $result ? array('content' => $result['content'], 'source' => $result['source']) : null;
            }
        }

        // 1: Direct meta
        $svg_content = get_post_meta($order_id, 'preview_image_svg', true);
        if (!empty($svg_content)) {
            $source = 'preview_image_svg meta';
            $result = self::decode_and_fetch($svg_content, $order_id, $source);
            if ($result) {
                return array('content' => $result['content'], 'source' => $result['source']);
            }
        }

        // 2: PNG meta (may be data URL SVG)
        $preview_png = get_post_meta($order_id, 'preview_image_png', true);
        if (!empty($preview_png) && strpos($preview_png, 'data:image/svg') !== false) {
            $svg_content = $preview_png;
            $source = 'preview_image_png meta (contains SVG)';
            $result = self::decode_and_fetch($svg_content, $order_id, $source);
            if ($result) {
                return array('content' => $result['content'], 'source' => $result['source']);
            }
        }

        // 3: URL meta
        $preview_url = get_post_meta($order_id, 'preview_image_url', true);
        if (!empty($preview_url) && strpos($preview_url, 'data:image/svg') !== false) {
            $svg_content = $preview_url;
            $source = 'preview_image_url meta (contains SVG)';
            $result = self::decode_and_fetch($svg_content, $order_id, $source);
            if ($result) {
                return array('content' => $result['content'], 'source' => $result['source']);
            }
        }

        // 4: Cart items
        $cart_items = get_post_meta($order_id, 'cart_items', true);
        if (is_string($cart_items)) {
            $cart_items = json_decode($cart_items, true);
        }
        if (!empty($cart_items) && is_array($cart_items)) {
            $first = isset($cart_items[0]) ? $cart_items[0] : null;
            if ($first) {
                // customization_data first
                if (!empty($first['customization_data'])) {
                    $cd = is_array($first['customization_data'])
                        ? $first['customization_data']
                        : json_decode($first['customization_data'], true);
                    if (is_array($cd)) {
                        foreach (array('preview_image_svg', 'svgContent', 'svg_content', 'preview_image_png') as $key) {
                            if (!empty($cd[$key])) {
                                $val = $cd[$key];
                                if ($key === 'preview_image_png' && strpos($val, 'data:image/svg') === false) {
                                    continue;
                                }
                                $source = 'cart_items[0].customization_data.' . $key;
                                $result = self::decode_and_fetch($val, $order_id, $source);
                                if ($result) {
                                    return array('content' => $result['content'], 'source' => $result['source']);
                                }
                            }
                        }
                    }
                }
                // Item-level
                foreach (array('preview_image_svg', 'preview_image_png') as $key) {
                    if (!empty($first[$key])) {
                        $val = $first[$key];
                        if ($key === 'preview_image_png' && strpos($val, 'data:image/svg') === false) {
                            continue;
                        }
                        $source = 'cart_items[0].' . $key;
                        $result = self::decode_and_fetch($val, $order_id, $source);
                        if ($result) {
                            return array('content' => $result['content'], 'source' => $result['source']);
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Decode data URL or fetch from URL; return raw SVG string and updated source.
     *
     * @param string $svg_content Content (may be data URL or URL).
     * @param int    $order_id    Order ID for logging.
     * @param string $source      Source label.
     * @return array|null Array 'content' and 'source', or null on failure.
     */
    private static function decode_and_fetch($svg_content, $order_id, $source)
    {
        if (empty($svg_content) || !is_string($svg_content)) {
            return null;
        }

        // URL: fetch
        if (strpos($svg_content, 'http://') === 0 || strpos($svg_content, 'https://') === 0) {
            $response = wp_remote_get($svg_content);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                error_log("APD Order SVG Resolver - Order #$order_id: Failed to fetch URL: " . (is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response)));
                return null;
            }
            $svg_content = wp_remote_retrieve_body($response);
            $source .= ' (fetched from URL)';
            if (strpos($svg_content, '<svg') === false) {
                error_log("APD Order SVG Resolver - Order #$order_id: URL content is not SVG");
                return null;
            }
            return array('content' => $svg_content, 'source' => $source);
        }

        // Data URL: decode
        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = base64_decode(substr($svg_content, strpos($svg_content, 'base64,') + 7));
            } else {
                $svg_content = urldecode(substr($svg_content, strpos($svg_content, ',') + 1));
            }
            $source .= ' (decoded from data URL)';
        }

        if (empty($svg_content) || strpos($svg_content, '<svg') === false) {
            return null;
        }

        return array('content' => $svg_content, 'source' => $source);
    }
}
