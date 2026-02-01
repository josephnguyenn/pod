<?php
/**
 * Material Manager
 * 
 * Handles material CRUD operations and category management
 *
 * @package AdvancedProductDesigner
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class APD_Material_Manager
{
    /**
     * Main plugin instance reference
     * @var AdvancedProductDesigner
     */
    private $plugin;

    /**
     * Constructor
     * 
     * @param AdvancedProductDesigner $plugin Main plugin instance
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Handle material upload
     */
    public function handle_material_upload()
    {
        $material_name = sanitize_text_field($_POST['material_name']);
        $material_price = isset($_POST['material_price']) ? floatval($_POST['material_price']) : 0;
        if ($material_price < 0) {
            $material_price = 0;
        }
        $material_category = isset($_POST['material_category']) ? sanitize_text_field($_POST['material_category']) : 'Uncategorized';

        $materials = get_option('apd_materials', array());
        
        // Check if using media library selection
        if (isset($_POST['material_media_id']) && !empty($_POST['material_media_id'])) {
            $media_id = intval($_POST['material_media_id']);
            $media_url = wp_get_attachment_url($media_id);
            
            if ($media_url) {
                $materials[] = array(
                    'name' => $material_name,
                    'filename' => basename($media_url),
                    'url' => $media_url,
                    'type' => 'media',
                    'date' => current_time('mysql'),
                    'price' => $material_price,
                    'media_id' => $media_id,
                    'category' => $material_category
                );
                update_option('apd_materials', $materials);
                
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success"><p>✅ Material added from media library successfully!</p></div>';
                });
                return;
            }
        }
        
        // Fallback to file upload
        if (!isset($_FILES['material_file']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Please select an image from media library or upload a file.</p></div>';
            });
            return;
        }

        $file = $_FILES['material_file'];

        // Validate file type
        $allowed_types = array('image/png', 'image/jpeg', 'image/jpg');
        if (!in_array($file['type'], $allowed_types)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Only PNG and JPG files are allowed.</p></div>';
            });
            return;
        }

        // Create materials directory if it doesn't exist
        $material_dir = APD_PLUGIN_PATH . 'uploads/material/';
        if (!is_dir($material_dir)) {
            wp_mkdir_p($material_dir);
        }

        // Generate filename
        $filename = sanitize_file_name($material_name . '_' . time() . '.png');
        $file_path = $material_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            chmod($file_path, 0644);

            $materials[] = array(
                'name' => $material_name,
                'filename' => $filename,
                'url' => APD_PLUGIN_URL . 'uploads/material/' . $filename,
                'type' => 'uploaded',
                'date' => current_time('mysql'),
                'price' => $material_price,
                'category' => $material_category
            );
            update_option('apd_materials', $materials);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Material uploaded successfully!</p></div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Failed to save material file.</p></div>';
            });
        }
    }

    /**
     * Handle material price update
     */
    public function handle_material_price_update()
    {
        $material_index = intval($_POST['material_index']);
        $material_price = isset($_POST['material_price']) ? floatval($_POST['material_price']) : 0;
        if ($material_price < 0) {
            $material_price = 0;
        }

        $materials = get_option('apd_materials', array());

        if (isset($materials[$material_index])) {
            $materials[$material_index]['price'] = $material_price;
            
            update_option('apd_materials', $materials, false);
            
            set_transient('apd_material_price_updated', true, 30);
        } else {
            set_transient('apd_material_price_error', true, 30);
        }
    }

    /**
     * Handle material deletion
     */
    public function handle_material_deletion()
    {
        $material_index = intval($_POST['material_index']);
        $materials = get_option('apd_materials', array());

        if (isset($materials[$material_index])) {
            $material = $materials[$material_index];

            // Delete file if it's uploaded material
            if ($material['type'] === 'uploaded') {
                $file_path = APD_PLUGIN_PATH . 'uploads/material/' . $material['filename'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Remove from array
            unset($materials[$material_index]);
            $materials = array_values($materials);

            update_option('apd_materials', $materials);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Material deleted successfully!</p></div>';
            });
        }
    }

    /**
     * Get material categories
     */
    public function get_material_categories()
    {
        $categories = get_option('apd_material_categories', array(
            'Vinyl',
            'Metal',
            'Specialty',
            'Texture'
        ));
        return $categories;
    }

    /**
     * Handle add material category
     */
    public function handle_add_material_category()
    {
        $category_name = sanitize_text_field($_POST['category_name']);
        
        if (empty($category_name)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Category name cannot be empty.</p></div>';
            });
            return;
        }

        $categories = $this->get_material_categories();
        
        if (in_array($category_name, $categories)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>❌ Category already exists.</p></div>';
            });
            return;
        }

        $categories[] = $category_name;
        update_option('apd_material_categories', $categories);

        add_action('admin_notices', function () {
            echo '<div class="notice notice-success"><p>✅ Category added successfully!</p></div>';
        });
    }

    /**
     * Handle delete material category
     */
    public function handle_delete_material_category()
    {
        $category_name = sanitize_text_field($_POST['category_name']);
        $categories = $this->get_material_categories();
        
        $key = array_search($category_name, $categories);
        if ($key !== false) {
            unset($categories[$key]);
            $categories = array_values($categories);
            update_option('apd_material_categories', $categories);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Category deleted successfully!</p></div>';
            });
        }
    }

    /**
     * Handle material category update
     */
    public function handle_material_category_update()
    {
        $material_index = intval($_POST['material_index']);
        $material_category = sanitize_text_field($_POST['material_category']);

        $materials = get_option('apd_materials', array());

        if (isset($materials[$material_index])) {
            $materials[$material_index]['category'] = $material_category;
            update_option('apd_materials', $materials);

            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>✅ Material category updated!</p></div>';
            });
        }
    }

    /**
     * Get materials list
     */
    public function get_materials_list()
    {
        $materials = get_option('apd_materials', array());
        return $materials;
    }

    /**
     * Get material filename
     * 
     * @param string $material_name Material name
     * @return string Material filename
     */
    public function get_material_filename($material_name)
    {
        $materials = get_option('apd_materials', array());
        
        foreach ($materials as $material) {
            if ($material['name'] === $material_name) {
                return isset($material['filename']) ? $material['filename'] : '';
            }
        }
        
        return '';
    }
}
