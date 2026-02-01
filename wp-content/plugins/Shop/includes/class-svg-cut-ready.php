<?php
/**
 * SVG Cut-Ready Processor
 *
 * Makes SVG compatible with CorelDRAW/cutting: minimal cleanup, pattern extraction.
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_SVG_Cut_Ready
{
    /**
     * Make SVG CorelDRAW-compatible (minimal cleanup, keeps 100% content).
     *
     * @param string $svg_content Source SVG content
     * @param int    $order_id    Order ID for logging
     * @return string Clean SVG content
     */
    public function make_coreldraw_compatible($svg_content, $order_id = 0)
    {
        error_log("APD CorelDRAW Compatible - Order #$order_id: Starting minimal cleanup (keeps 100% content)");

        if (strpos($svg_content, 'data:image/svg+xml') === 0) {
            if (strpos($svg_content, 'base64,') !== false) {
                $svg_content = base64_decode(substr($svg_content, strpos($svg_content, 'base64,') + 7));
            } else {
                $svg_content = urldecode(substr($svg_content, strpos($svg_content, ',') + 1));
            }
        }

        $detected_encoding = mb_detect_encoding($svg_content, array('UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'), true);
        if ($detected_encoding && $detected_encoding !== 'UTF-8') {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', $detected_encoding);
            error_log("APD CorelDRAW Compatible - Order #$order_id: Converted from $detected_encoding to UTF-8");
        }

        $original_length = strlen($svg_content);
        error_log("APD CorelDRAW Compatible - Order #$order_id: Original content length: $original_length bytes");

        $svg_content = preg_replace('/(\w+(-\w+)*)="=""/', '', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+round=""/', 'stroke-linejoin="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+miter=""/', 'stroke-linejoin="miter"', $svg_content);
        $svg_content = preg_replace('/stroke-linejoin=""\s+bevel=""/', 'stroke-linejoin="bevel"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+round=""/', 'stroke-linecap="round"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+square=""/', 'stroke-linecap="square"', $svg_content);
        $svg_content = preg_replace('/stroke-linecap=""\s+butt=""/', 'stroke-linecap="butt"', $svg_content);
        $svg_content = preg_replace('/vector-effect=""\s+non-scaling-stroke=""/', 'vector-effect="non-scaling-stroke"', $svg_content);
        $svg_content = preg_replace('/\s+round=""(?!\s*\w+)/', '', $svg_content);
        $svg_content = preg_replace('/\s+miter=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+bevel=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+square=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+butt=""/', '', $svg_content);
        $svg_content = preg_replace('/\s+non-scaling-stroke=""/', '', $svg_content);

        $svg_content = preg_replace_callback(
            '/(<[^>]*)\sstyle="([^"]*)"([^>]*>)/i',
            array($this, 'duplicate_styles_as_attributes'),
            $svg_content
        );

        $svg_content = preg_replace_callback(
            '/@font-face\s*\{[^}]*\}/s',
            function ($m) {
                $fontface = $m[0];
                $properties = array();
                if (preg_match('/font-family:\s*["\']?([^;"\']+)["\']?/i', $fontface, $m1)) {
                    $properties[] = "font-family: '" . trim($m1[1]) . "'";
                }
                if (preg_match('/font-weight:\s*([^;]+)/i', $fontface, $m1)) {
                    $properties[] = "font-weight: " . trim($m1[1]);
                }
                if (preg_match('/font-style:\s*([^;]+)/i', $fontface, $m1)) {
                    $properties[] = "font-style: " . trim($m1[1]);
                }
                return !empty($properties) ? "@font-face { " . implode("; ", $properties) . "; }" : '';
            },
            $svg_content
        );

        $svg_content = preg_replace_callback(
            '/(stroke|fill):\s*url\(([\'"]?)([^)]+)\2\)\s*!?important;?/i',
            function ($m) {
                $pattern_id = trim(str_replace(array('&quot;', '"', "'"), '', $m[3]));
                return $m[1] . ': url(' . $pattern_id . ')';
            },
            $svg_content
        );

        $svg_content = preg_replace('/<\?xml[^?]*\?>\s*/i', '', $svg_content);
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . $svg_content;

        if (preg_match('/<svg[^>]*>/i', $svg_content, $svg_match)) {
            $metadata = "\n  <metadata>Cut-ready SVG from Order #$order_id on " . date('Y-m-d H:i:s') . ". " .
                "CorelDRAW compatible. All content preserved.</metadata>\n";
            $svg_content = str_replace($svg_match[0], $svg_match[0] . $metadata, $svg_content);
        }

        $svg_content = $this->process_patterns_for_coreldraw($svg_content, $order_id);

        if (!mb_check_encoding($svg_content, 'UTF-8')) {
            $svg_content = mb_convert_encoding($svg_content, 'UTF-8', 'UTF-8');
        }

        $final_length = strlen($svg_content);
        $reduction_percent = $original_length > 0 ? (($original_length - $final_length) / $original_length * 100) : 0;
        error_log(sprintf(
            "APD CorelDRAW Compatible - Order #%d: Processed. Original: %d bytes, Final: %d bytes (%.1f%% reduction)",
            $order_id,
            $original_length,
            $final_length,
            $reduction_percent
        ));
        if ($reduction_percent > 40) {
            error_log("APD CorelDRAW Compatible - Order #$order_id: WARNING - Large content reduction detected.");
        }

        return $svg_content;
    }

    /**
     * Duplicate style fill/stroke as attributes for CorelDRAW.
     */
    private function duplicate_styles_as_attributes($matches)
    {
        $before = $matches[1];
        $original_style = $matches[2];
        $after = $matches[3];
        $full_element = $before . $after;
        $attributes = array();

        if (!preg_match('/\sfill=/i', $full_element)) {
            if (preg_match('/fill:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                $attributes[] = 'fill="' . sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]) . '"';
            } elseif (preg_match('/fill:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                $pid = trim(str_replace(array('&quot;', '&amp;'), array('', '&'), $m[1]));
                $attributes[] = 'fill="url(' . htmlspecialchars($pid, ENT_QUOTES) . ')"';
            } elseif (preg_match('/fill:\s*([#\w]+)/i', $original_style, $m) && trim($m[1]) !== 'none' && trim($m[1]) !== '') {
                $attributes[] = 'fill="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
            }
        }
        if (!preg_match('/\sstroke=/i', $full_element)) {
            if (preg_match('/stroke:\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/i', $original_style, $m)) {
                $attributes[] = 'stroke="' . sprintf('#%02x%02x%02x', $m[1], $m[2], $m[3]) . '"';
            } elseif (preg_match('/stroke:\s*url\([\'"]?([^)\'"]+)[\'"]?\)/i', $original_style, $m)) {
                $pid = trim(str_replace(array('&quot;', '&amp;'), array('', '&'), $m[1]));
                $attributes[] = 'stroke="url(' . htmlspecialchars($pid, ENT_QUOTES) . ')"';
            } elseif (preg_match('/stroke:\s*([#\w]+)/i', $original_style, $m) && trim($m[1]) !== 'none' && trim($m[1]) !== '') {
                $attributes[] = 'stroke="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
            }
        }
        if (!preg_match('/\sstroke-width=/i', $full_element) && preg_match('/stroke-width:\s*([^;]+)/i', $original_style, $m)) {
            $attributes[] = 'stroke-width="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
        }
        if (!preg_match('/\sfill-opacity=/i', $full_element) && preg_match('/fill-opacity:\s*([^;]+)/i', $original_style, $m)) {
            $attributes[] = 'fill-opacity="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
        }
        if (!preg_match('/\sstroke-opacity=/i', $full_element) && preg_match('/stroke-opacity:\s*([^;]+)/i', $original_style, $m)) {
            $attributes[] = 'stroke-opacity="' . htmlspecialchars(trim($m[1]), ENT_QUOTES) . '"';
        }

        $result = $before;
        if (!empty($attributes)) {
            $result .= ' ' . implode(' ', $attributes);
        }
        $result .= ' style="' . $original_style . '"' . $after;
        return $result;
    }

    /**
     * Process patterns with data:image for CorelDRAW (extract to external files).
     *
     * @param string $svg_content SVG content
     * @param int    $order_id    Order ID for logging
     * @return string Processed SVG content
     */
    private function process_patterns_for_coreldraw($svg_content, $order_id = 0)
    {
        $upload_dir = wp_upload_dir();
        $extracted_images = array();

        $svg_content = preg_replace_callback(
            '/<pattern([^>]*)>(.*?)<\/pattern>/is',
            function ($matches) use ($order_id, $upload_dir, &$extracted_images) {
                $pattern_attrs = $matches[1];
                $pattern_content = $matches[2];
                $pattern_id = 'pattern';
                if (preg_match('/id=["\']([^"\']+)["\']/', $pattern_attrs, $m)) {
                    $pattern_id = $m[1];
                }
                $pattern_content = preg_replace_callback(
                    '/<image([^>]*)>/i',
                    function ($img_matches) use ($pattern_id, $order_id, $upload_dir, &$extracted_images) {
                        $img_attrs = $img_matches[1];
                        if (preg_match('/(href|xlink:href)=["\']data:image\/([^;]+);base64,([^"\']+)["\']/', $img_attrs, $data_match)) {
                            $mime_type = strtolower($data_match[2]);
                            $decoded = base64_decode($data_match[3], true);
                            if ($decoded === false) {
                                return $img_matches[0];
                            }
                            $ext = ($mime_type === 'jpg') ? 'jpeg' : $mime_type;
                            $ext = preg_replace('/[^a-z]/', '', $ext);
                            if ($ext === 'jpeg') {
                                $ext = 'jpg';
                            }
                            $filename = 'order-' . $order_id . '-pattern-' . sanitize_file_name($pattern_id) . '.' . $ext;
                            $filepath = $upload_dir['path'] . '/' . $filename;
                            $file_url = $upload_dir['url'] . '/' . $filename;
                            if (file_put_contents($filepath, $decoded, LOCK_EX) !== false) {
                                $extracted_images[] = array('pattern_id' => $pattern_id, 'filename' => $filename);
                                $new_attrs = preg_replace('/(href|xlink:href)=["\'][^"\']*["\']/', '', $img_attrs);
                                $new_attrs = ' href="' . esc_url($file_url) . '" xlink:href="' . esc_url($file_url) . '"' . $new_attrs;
                                return '<image' . $new_attrs . '>';
                            }
                        }
                        return $img_matches[0];
                    },
                    $pattern_content
                );
                return '<pattern' . $pattern_attrs . '>' . $pattern_content . '</pattern>';
            },
            $svg_content
        );

        if (!empty($extracted_images)) {
            error_log("APD Pattern STRONG - Order #$order_id: Extracted " . count($extracted_images) . " pattern images");
        }
        return $svg_content;
    }
}
