<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if( !class_exists('FPD_Export_Provider') ) {

    class FPD_Export_Provider {

        private $exported_file = null;

        public function __construct() {

            add_action( 'rest_api_init', array( &$this, 'register_routes') );
            add_action( 'template_redirect', array( &$this, 'handle_download') );

            //Order Mail: WooCommerce
            add_action( 'woocommerce_order_item_meta_end', array(&$this, 'woo_add_download_link') , 20, 4 );
            add_filter( 'woocommerce_email_attachments', array( &$this, 'woo_mail_attachments' ), 10, 3  );
            add_action( 'woocommerce_after_resend_order_email', array( &$this, 'woo_resend_order_mail_admin'), 10, 2);

            //Order Mail: Shortcode
            add_action( 'fpd_shortcode_order_mail_message', array(&$this, 'shortcode_add_download_link'), 10, 3);
            add_action( 'fpd_shortcode_order_mail_attachments', array(&$this, 'shortcode_mail_attachments'), 10, 3);

            //Order Mail: Gravity Form
            add_filter( 'gform_notification', array(&$this, 'gf_mail_attachments'), 10, 3 );

            //Cloud Export (filter: fpd_new_order_item_data)
            add_action( 'fpd_new_order_item_data', array( &$this, 'start_cloud_export'), 10, 4 );
            
            //Printful Export
            add_action( 'fpd_printful_resend_order', array( &$this, 'start_cloud_export'), 10, 4 );

        }

        public function register_routes() {

            register_rest_route( FPD_Pro_Export::ROUTE_NAMESPACE, '/print_job/(?P<id>.+)', array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array( &$this, 'webhook_create_pr_file'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => function($param, $request, $key) {
                            return is_string($param);
                        }
                    ),
                ),
                'permission_callback' => function () {
                    return true;
                }
            ) );

        }

        public function webhook_create_pr_file( $request ) {

            $response = array();
            
            if( $request->get_param('print_job_id') && $request->get_param('file_url') ) {
                   
                $print_job = new FPD_Print_Job( $request->get_param('print_job_id'), true );
                $print_job_details = $print_job->get_details();
                    
                //check if print job exists
                if( $print_job_details ) {

                    $remote_file_url = $request->get_param('file_url');
                    $response['file_url'] = $remote_file_url;

                    $local_file = FPD_Pro_Export::save_remote_file( $remote_file_url );
                    $local_file = FPD_ORDER_DIR . 'print_ready_files/' . $local_file;

                    //Printful
                    if( isset($print_job_details['variation_printful']) ) {

                        $cloud_path = '/'. $print_job_details['order_id'] .'/';

                        if( isset($print_job_details['item_id']) ) {
                            $cloud_path .= $print_job_details['item_id'] .'/';
                        }
                        
                        $print_job = new FPD_Print_Job( $request->get_param('print_job_id'), true );
                        $print_job->update( array('local_file' => $local_file), 'completed' );

                        $this->create_printful_order(
                            $local_file,
                            array(
                                'order_id'	 => $print_job_details['order_id'],
                                'item_id'	 => isset( $print_job_details['item_id'] ) ? $print_job_details['item_id'] : null,
                                'cloud_path' => $cloud_path,
                                'variation_id_printful' => isset( $print_job_details['variation_printful'] ) ? $print_job_details['variation_printful'] : null
                            )

                        );

                    }
                    //direct download (async)
                    else {
                        
                        $print_job = new FPD_Print_Job( $request->get_param('print_job_id'), true );
                        $print_job->update( array('local_file' => $local_file), 'completed' );

                    }

                }
                else {
                    fpd_logger('Print job '. $request->get_param('print_job_id') . ' does not exists.');
                }

            }

            return new WP_REST_Response( $response, 200 );

        }

        public function handle_download() {

            $login_required = get_option('fpd_ae_email_download_link_login');

            if( get_option('fpd_ae_email_download_link') == 'yes' && isset( $_GET['fpd_download'] )) {

                //check if logged-in, otherwise redirect to login page and then redirect to download page again
                if( $login_required == 'yes' && !is_user_logged_in() ) {

                    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    
                    wp_redirect( wp_login_url( $current_url ) );
                    exit;

                }

                $fpd_print_order = null;
                $filename = '';
                $export_method = get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                $product_data = array();

                //woocommerce
                if( $_GET['fpd_download'] === 'wc' && isset( $_GET['fpd_item_id'] ) ) {

                    $order = wc_get_order( intval( $_GET['fpd_order_id'] ) );
                    
                    if( $login_required == 'no' || 
                        current_user_can(Fancy_Product_Designer::CAPABILITY) || 
                        $order->get_user_id() === get_current_user_id() 
                    ) {

                        $fpd_print_order = wc_get_order_item_meta( intval( $_GET['fpd_item_id'] ), '_fpd_print_order', true );

                            
                        if(str_contains($export_method, 'nodecanvas')) {
                            
                            $fpd_data = fpd_wc_get_order_item_meta( intval( $_GET['fpd_item_id'] ) );
                            $fpd_product = json_decode( fpd_strip_multi_slahes($fpd_data), true );
                            $product_data = $fpd_product['product'];
                            
                        }

                        $filename = intval( $_GET['fpd_order_id'] ) . '_'. intval( $_GET['fpd_item_id'] );

                    }

                }
                //shortcode
                else if( $_GET['fpd_download'] === 'shortcode' && 
                        isset( $_GET['fpd_order_id'] ) 
                ) {

                    $order_data = FPD_Shortcode_Order::get_order( intval( $_GET['fpd_order_id'] ));
                    $fpd_print_order = isset( $order_data->print_order ) ? $order_data->print_order : null;
                    
                    $filename = intval( $_GET['fpd_order_id'] );

                    if($fpd_print_order && str_contains($export_method, 'nodecanvas')) {
                            
                        $fpd_product = json_decode( fpd_strip_multi_slahes( $order_data->views ), true );
                        $product_data = $fpd_product['product'];
                        
                    }

                }
                //gravity form
/*
                else if( $_GET['fpd_download'] === 'gf' && isset( $_GET['fpd_order_id'] ) ) {

                    $filename = $_GET['fpd_order_id'];

                }
*/

                $file = null;                                
                if( !empty($fpd_print_order) ) {

                    $fpd_print_order = json_decode( fpd_strip_multi_slahes( $fpd_print_order ), true );
                    $output_format = get_option('fpd_ae_output_file', 'pdf');
                    $image_dpi = get_option('fpd_ae_image_dpi', 300);

                    $print_data = array(
                        'output_format'         => strpos($output_format, 'pdf') === false ? $output_format : 'pdf',
                        'include_font_files'    => $output_format === 'zip_pdf_fonts' ? true : false,
                        'name'                  => $filename,
                        'used_fonts'            => $fpd_print_order['used_fonts'],
                        'svg_data'              => isset( $fpd_print_order['svg_data'] ) ? $fpd_print_order['svg_data'] : array(),
                        'product_data'          => $product_data,
                        'summary_json'          => false,
                        'dpi'                   => $image_dpi ? $image_dpi : 300,
                        'include_images'        => $output_format === 'zip_pdf_custom_images' ? $fpd_print_order['custom_images'] : null,
                        'create_print_job'      => false,
                    );

                    $file = Fancy_Product_Designer::create_print_ready_file( $print_data );
                                    
                    //is valid path ({random_string}.{jpg|pdf|zip})
                    $pattern = '/^[a-zA-Z0-9]{20,}\/\d+_\d+(\.\d+)*\.\w+$/';
                    
                    if(!preg_match($pattern, $file)) {

                        _e($file, 'radykal');
                        exit();
                    }

                    $file = FPD_ORDER_DIR . 'print_ready_files/' . $file;

                }

                
                

                if( $file ) {

                    header('Content-Disposition: attachment; filename=' . urlencode($file));
                    header('Content-Type: application/force-download');
                    header('Content-Type: application/octet-stream');
                    header('Content-Type: application/download');
                    header('Content-Description: File Transfer');
                    header('Content-Disposition: attachment; filename="'.basename($file).'"');
                    header('Content-Length: ' . filesize($file));
                    echo file_get_contents($file);
                    exit();

                }

            }

        }

        public function woo_add_download_link( $item_id, $item, $order, $plain_text=null ) {

            if( get_option('fpd_ae_email_download_link') != 'yes' || $plain_text )
                return;

            $display_to_admin = ( get_option('fpd_ae_recipient_admin') == 'yes' && FPD_WC_Index::$sent_to_admin );
            $display_to_customer = ( get_option('fpd_ae_recipient_customer') == 'yes' &&  ( FPD_WC_Index::$email_id === 'customer_completed_order' || $order->is_paid() ) );

            if( isset($item['_fpd_print_order']) && ($display_to_customer || $display_to_admin) ) {

                $ae_download_url = $this->get_print_file_uri( array(
                    'fpd_download' => 'wc',
                    'fpd_order_id' => $order->get_id(),
                    'fpd_item_id' => $item_id,
                ) );
                
                echo sprintf( '<div style="clear: both;"><a href="%s" target="_blank" class="fpd-download-print-ready-file" style="border: 1px solid rgba(0,0,0,0.8); padding: 4px 6px; border-radius: 2px; font-size: 0.85em; color: rgba(0,0,0,0.8); text-decoration: none; display: inline-block; margin: 10px 0 0;">%s</a></div>', esc_url( $ae_download_url ), FPD_Settings_Labels::get_translation( 'misc', 'pro_export:download' ) );

            }

        }

        public function woo_mail_attachments( $attachments, $email_id, $order ) {

            if( get_option('fpd_ae_email_attachment') != 'yes' || !is_a( $order, 'WC_Order' ) || !isset( $email_id ) )
                return $attachments;

            $display_to_admin = ( get_option('fpd_ae_recipient_admin') == 'yes' && $email_id === 'new_order' );
            $display_to_customer = ( get_option('fpd_ae_recipient_customer') == 'yes' && ( $email_id === 'customer_completed_order' || $order->is_paid() ) );

            foreach( $order->get_items() as $order_item ) {

                $order_item_id = $order_item->get_id();
                $fpd_print_order = wc_get_order_item_meta( $order_item_id, '_fpd_print_order', true );
                

                if( !empty($fpd_print_order) ) {

                    $fpd_print_order = json_decode( fpd_strip_multi_slahes( $fpd_print_order ), true );
                    
                    $export_method = get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                    if(str_contains($export_method, 'nodecanvas')) {
                        
                        $fpd_data = fpd_wc_get_order_item_meta( $order_item_id );
                        $fpd_product = json_decode( fpd_strip_multi_slahes($fpd_data), true );
                        
                        $fpd_print_order['product_data'] = $fpd_product['product'];
                    }

                    if( $display_to_admin || $display_to_customer ) {

                        $this->exported_file = self::get_print_ready_file_path( $fpd_print_order, 
                            $order->get_id() . '_' . $order_item_id 
                        );
                        
                        $attachments[] = $this->exported_file;

                    }
                }

            }

            return $attachments;

        }

        public function woo_resend_order_mail_admin( $order ) {

            $order_items = $order->get_items();

            foreach($order_items as $order_item) {
                
                $fpd_print_order = wc_get_order_item_meta( $order_item->get_id(), '_fpd_print_order' );
                
                if( !empty($fpd_print_order) ) {
                    
                    $fpd_product = wc_get_order_item_meta( $order_item->get_id(), '_fpd_data' );
                    
                    $fpd_data = array(
                        'fpd_product' => $fpd_product,
                        'fpd_print_order' => $fpd_print_order
                     );
                     
                     $additional_data['order_id'] = $order->get_id();
                     $additional_data['item_id'] = $order_item->get_id();
                     
                     
                    $this->start_cloud_export($fpd_data, 'wc', $additional_data );
                    
                }
                
            }            

        }

        public function shortcode_add_download_link( $message, $order_id, $order_data ) {

            if( get_option('fpd_ae_email_download_link') == 'yes' && get_option('fpd_ae_recipient_admin') == 'yes' && isset($order_data['data']['print_order']) ) {

                $ae_download_url = $this->get_print_file_uri( array(
                    'fpd_download' => 'shortcode',
                    'fpd_order_id' => $order_id,
                ) );

                $message .= sprintf( __('%s: %s', 'radykal'), FPD_Settings_Labels::get_translation( 'misc', 'pro_export:download' ), esc_url_raw( $ae_download_url ) )."\n";

            }

            return $message;

        }

        public function shortcode_mail_attachments( $attachments, $order_id, $order_data ) {

            if( get_option('fpd_ae_email_attachment') == 'yes' && 
                get_option('fpd_ae_recipient_admin') == 'yes' && 
                isset($order_data['data']['print_order']) 
            ) {

                $fpd_print_order = json_decode( stripslashes( $order_data['data']['print_order'] ), true );
                
                $export_method = get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                if(str_contains($export_method, 'nodecanvas')) {
                    
                    $fpd_product = json_decode( stripslashes( $order_data['data']['views'] ), true );
                    
                    $fpd_print_order['product_data'] = $fpd_product['product'];
                }

                $attachments[] = self::get_print_ready_file_path( $fpd_print_order, $order_id );

            }

            return $attachments;

        }

        public function gf_mail_attachments( $notification, $form, $entry ) {
            
            
            if( get_option('fpd_ae_email_attachment') == 'yes' && get_option('fpd_ae_recipient_admin') == 'yes' && $notification['name'] == 'Admin Notification')  {
                
                //loop all fields to get id in entry of fpd-order field
                foreach ( $form['fields'] as $field ) { 
                    
                    if( $field->cssClass == 'fpd-order') {

                        $fpd_data = json_decode($entry[$field->id], true);

                        if( isset($fpd_data['print_order']) ) {

                            $fpd_print_order = $fpd_data['print_order'];

                            $export_method = get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                            if(str_contains($export_method, 'nodecanvas')) {
                                
                                $fpd_product = $fpd_data['product'];                                
                                $fpd_print_order['product_data'] = $fpd_product;
                                
                            }

                            $notification['attachments'][] = self::get_print_ready_file_path( $fpd_print_order, $entry['id'] );

                        }

                    }

                }
                
            }

            return $notification;

        }
        
        //export to dropbox, s3 or printful
        public function start_cloud_export( $fpd_data, $order_type='wc', $additional_data=null, $update_order=false ) {
            
            if( $fpd_data && isset($fpd_data['fpd_print_order']) ) {

                $cloud = get_option( 'fpd_ae_cloud', 'none' );
                
                $fpd_print_order = is_array($fpd_data['fpd_print_order']) ? 
                                        $fpd_data['fpd_print_order'] 
                                    : 
                                        json_decode( fpd_strip_multi_slahes( $fpd_data['fpd_print_order'] ), true );
                
                //set filename from order and optional item id
                $filename = $additional_data['order_id'];
                if( isset( $additional_data['item_id'] ) )
                    $filename .= '_' . $additional_data['item_id'];
                    
                $print_job_data = array(
                    'name'          => $filename,
                    'order_id'      => $additional_data['order_id'],
                    'item_id'       => isset( $additional_data['item_id'] ) ? $additional_data['item_id'] : null
                );                

                //printful
                $variation_id_printful = null;
                if($order_type == 'wc' && isset( $additional_data['item_id'] )) {

                    //get wc variation id if printful variant is assigned
                    $variation_id_printful = $this->get_wc_variation_printful( $additional_data['order_id'], $additional_data['item_id'] );

                }
                
                //set export method
                $export_method = $variation_id_printful ? 'nodecanvas' : get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                if(str_contains($export_method, 'nodecanvas')) {
                    
                    $fpd_product = json_decode(fpd_strip_multi_slahes($fpd_data['fpd_product']), true);
                    $print_job_data['product_data'] = $fpd_product['product'];

                }

                if( !is_null($variation_id_printful) ) {
                
                    if(!$update_order) {
                        
                        wc_update_order_item_meta(
                            $additional_data['item_id'], '_fpd_printful_order_dashboard_url', 'process'
                        );
                        
                    }
                            
                    $filename = $additional_data['order_id'] . '_' . $additional_data['item_id'];
                    
                    $print_job_data = array(
                        'name'                  => $filename,
                        'output_format'         => 'pdf',
                        'single_pdfs'           => true,
                        'include_font_files'    => false,
                        'include_images'        => false,
                        'variation_printful'    => $variation_id_printful,
                        'order_id'      => $additional_data['order_id'],
                        'item_id'       => $additional_data['item_id']
                    );
                    
                    $fpd_product = json_decode(fpd_strip_multi_slahes($fpd_data['fpd_product']), true);
                        
                    $print_job_data['product_data'] = $fpd_product['product'];
                    $print_job_data['output_format'] = 'png';
                    $print_job_data['single_pdfs'] = false;
                        
                }
                //dropbox                        
                else if( $cloud == 'dropbox' ) {
                    
                    $dropbox_client_id = get_option( 'fpd_ae_dropbox_client_id', null );
                    $dropbox_secret = get_option( 'fpd_ae_dropbox_secret', null );
                    $dropbox_redirect_uri = get_option( 'fpd_ae_dropbox_redirect_uri', null );
                    $dropbox_refresh_token = get_option( 'fpd_ae_dropbox_refresh_token', null );
                    
                    if( $dropbox_client_id && $dropbox_secret && $dropbox_redirect_uri && $dropbox_refresh_token ) {
                        
                        $print_job_data['db_client_id'] = $dropbox_client_id;
                        $print_job_data['db_secret'] = $dropbox_secret;
                        $print_job_data['db_redirect_uri'] = $dropbox_redirect_uri;
                        $print_job_data['db_refresh_token'] = $dropbox_refresh_token;
                        
                    }
                    
                }
                //AWS S3
                else if( $cloud == 's3' ) {
                    
                    $s3_key = get_option( 'fpd_ae_s3_access_key' );
                    $s3_secret = get_option( 'fpd_ae_s3_access_secret' );
                    $s3_region = get_option( 'fpd_ae_s3_region' );
                    $s3_bucket = get_option( 'fpd_ae_s3_bucket' );
                    $s3_root_dir = get_option( 'fpd_ae_s3_root_dir', '' );
                    
                    $print_job_data['s3_credentials'] = array(
                        'bucket'        => $s3_bucket,
                        'region'        => $s3_region,
                        'access_key'    => $s3_key,
                        'secret_key'    => $s3_secret,
                        'root_dir'      => $s3_root_dir,
                        's3_host_name'  => 's3-'.$s3_region.'.amazonaws.com'
                    );
                                                                    
                }  

                if( $cloud !== 'none' || !is_null($variation_id_printful) || get_option('fpd_pro_export_webhook_url', null) )
                    $this->create_print_job( $print_job_data, $fpd_print_order );

            }
            
            //die($cloud);
            
            return $fpd_data;

        }
        
        //direct download without print job
        public static function get_print_ready_file_path( $fpd_print_order, $name ) {

            $output_format = get_option('fpd_ae_output_file', 'pdf');
            $image_dpi = get_option('fpd_ae_image_dpi', 300);

            $print_data = array(
                'output_format' => strpos($output_format, 'pdf') === false ? $output_format : 'pdf',
                'include_font_files' => $output_format === 'zip_pdf_fonts' ? true : false,
                'name' 			=> $name,
                'used_fonts' 	=> $fpd_print_order['used_fonts'],
                'include_images' => $output_format === 'zip_pdf_custom_images' ? $fpd_print_order['custom_images'] : null,
                'dpi' => $image_dpi ? $image_dpi : 300,
                'create_print_job'      => false,
            );
            
            if( isset($fpd_print_order['svg_data']) )
                $print_data['svg_data'] = $fpd_print_order['svg_data'];
                
            if( isset($fpd_print_order['product_data']) )
                $print_data['product_data'] = $fpd_print_order['product_data'];

            $file = Fancy_Product_Designer::create_print_ready_file( $print_data );
            $local_export_file = FPD_ORDER_DIR . 'print_ready_files/' . $file;

            return $local_export_file;

        }
        private function create_print_job( $print_job_data, $print_order ) {

            $output_format = get_option('fpd_ae_output_file', 'pdf');
            $image_dpi = get_option('fpd_ae_image_dpi', 300);
                        
            $print_job_data = array_merge( array(
                'output_format' 		=> strpos($output_format, 'pdf') === false ? $output_format : 'pdf',
                'include_font_files' 	=> $output_format === 'zip_pdf_fonts' ? true : false,
                'svg_data'				=> $print_order['svg_data'],
                'used_fonts' 			=> $print_order['used_fonts'],
                'include_images' 		=> $output_format === 'zip_pdf_custom_images' ? $print_order['custom_images'] : null,
                'dpi' 					=> $image_dpi ? $image_dpi : 300,
            ), $print_job_data );

            return Fancy_Product_Designer::create_print_ready_file( $print_job_data, false );

        }
        
        private function get_print_file_uri( $params=array() ) {
        
            return add_query_arg( $params, get_site_url() );
        
        }
        
        private function create_printful_order( $pr_file, $args=array() ) {
        
            $args = array_merge( array(
                'order_id' 				=> null,
                'item_id'				=> null,
                'cloud_path'		 	=> '',
                'variation_id_printful' => null,
            ), $args );
        
            $order_id = $args['order_id'];
            $item_id = $args['item_id'];
        
            try {
        
                //create printful order
                if( !empty($args['variation_id_printful']) ) {
        
                    $order = wc_get_order( $order_id );
                    $order_item = $order->get_item( $item_id );
        
                    $variation = new WC_Product_Variation();
                    $variation->set_id( $args['variation_id_printful'] );
                    $printful_variant_id = $variation->get_meta('fpd_printful_variant_id');
                    $printful_templates = $variation->get_meta('fpd_printful_templates');
        
                    if( empty($printful_variant_id) || empty($printful_templates) )
                        return;
        
                    $recipient = array(
                        'name' 			=> $order->get_billing_first_name() . ' ' . $order->get_shipping_last_name(),
                        'address1' 		=> $order->get_shipping_address_1(),
                        'address2' 		=> $order->get_shipping_address_2(),
                        'city' 			=> $order->get_shipping_city(),
                        'state_code' 	=> $order->get_shipping_state(),
                        'country_code' 	=> $order->get_shipping_country(),
                        'zip' 			=> $order->get_shipping_postcode(),
                        'company'		=> $order->get_shipping_company(),
                        'phone'			=> $order->get_billing_phone(),
                        'email'			=> $order->get_billing_email()
                    );
        
                    $shipping_cost = $order->get_shipping_total();
                    $items = array( array(
                        'variant_id' 	=> $printful_variant_id,
                        'quantity' 		=> $order_item->get_quantity(),
                        'retail_price'	=> $order->get_item_total( $order_item, false ),
                        'files' => array(),
                        'options' => array( array(
                            'id' => 'stitch_color',
                            'value' => 'black'
                        ) )
                    ) );
                    
                    $zip = new ZipArchive;
                    $zip_res = $zip->open($pr_file);
        
                    if ($zip_res ) {
        
                        $extract_to_dir = dirname($pr_file);
        
                        $zip->extractTo($extract_to_dir);
                        $zip->close();
        
                        //read all pdfs from extracted dir and add to files array
                        $export_method = get_option( 'fpd_pro_export_method', 'nodecanvas-svg' );
                        $print_file_type = $export_method == 'svg2pdf' ? "/*.pdf" : "/*.png";
                        $print_files = glob($extract_to_dir . $print_file_type);
        
                        $loop_index = 0;
                        foreach($printful_templates as $printful_template) {
        
                            $print_file_path = $print_files[$loop_index];
                            
                            if( $print_file_path ) {
        
                                $file_data = array(
                                    'type' 	=> $printful_template['type'],
                                    'url' 	=> content_url(strstr($print_file_path, '/fancy_products_orders/'))
                                );
                                        
                                array_push($items[0]['files'], $file_data);
        
                            }
        
                            $loop_index++;
        
                        }
        
                        $pf_order_data = array(
                            'items' 		=> $items,
                            'retail_costs' 	=> array (
                                'shipping' 		=> $shipping_cost,
                                'tax'			=> $order->get_total_tax()
                            )
                        );
        
                        $pf_order_data = apply_filters( 'fpd_printful_order_data', $pf_order_data  );
                        $request_body = array(
                            'order_data' => $pf_order_data
                        );
        
                        //just for test
                        //$order->delete_meta_data('_fpd_printful_order_data');
                        //$order->save();
        
                        //check if wc order already contains a printful order
                        $existing_pf_order_data = $order->get_meta( '_fpd_printful_order_data' );
                        
        
                        $printful_api = new FPD_Export_Printful_Api();
        
                        if( empty($existing_pf_order_data) ) {
        
                            //$request_body['order_data']['external_id'] = $order_id;
                            $request_body['order_data']['recipient'] = $recipient;
                            
                            $printful_order_res = $printful_api->call('create_order', $request_body );
        
                        }
                        //update existing order in printful
                        else {
        
                            $request_body['order_id'] =  $existing_pf_order_data['id'];
                            $request_body['order_data']['items'] = array_merge($existing_pf_order_data['items'], $request_body['order_data']['items']);
        
                            $request_body['order_data']['retail_costs']['tax'] = $order->get_total_tax();
                            $printful_order_res = $printful_api->call('update_order', $request_body );
        
                        }
                        
                        if( $printful_order_res && isset($printful_order_res['id']) ) {
        
                            if( empty($existing_pf_order_data) ) {
                                $order->add_meta_data( '_fpd_printful_order_data', $printful_order_res );
                                $order->save();
                            }
        
                            wc_update_order_item_meta(
                                $item_id, '_fpd_printful_order_dashboard_url', $printful_order_res['dashboard_url']
                            );
        
                        }
                        else {

                            fpd_logger($printful_order_res);
        
                            $error = isset( $printful_order_res['error'] ) ? $printful_order_res['error'] : __( 'Printful API could not be reached.', 'radykal' );
        
                            $this->printful_order_failed( $order_id, $item_id, $error );
        
                        }
        
                    }
        
                }
        
            }
            catch(Exception $e) {
        
                if( !empty($args['variation_id_printful']) ) {
        
                    $this->printful_order_failed( $order_id, $item_id, $e->getMessage() );
        
                }
        
            }
        
        }

        private function printful_order_failed( $order_id, $item_id, $error ) {

            wc_update_order_item_meta( $item_id, '_fpd_printful_order_dashboard_url', 'failed' );

            $message = sprintf(
                __('Printful order with the WooCommerce order #%s could not be processed. Following error occured: %s.', 'radykal'),
                $order_id,
                json_encode( $error )
            );

            if( get_option('fpd_printful_failure_admin_mail') == 'yes' )
                fpd_admin_sent_admin_mail(
                    sprintf( __( 'Printful Order Failure - #%s', 'radykal' ), $order_id),
                    $message
                );
            else
                fpd_logger($error);


        }

        //return wc variation from order item with printful item
        private function get_wc_variation_printful( $order_id, $item_id ) {

            $order = wc_get_order( $order_id );

            if( $order ) {

                $item = $order->get_item( $item_id );

                if( $item ) {

                    $variation_id = $item->get_variation_id();

                    if( $variation_id ) {

                        $variation = new WC_Product_Variation();
                        $variation->set_id( $variation_id );
                        $printful_variant_id = $variation->get_meta('fpd_printful_variant_id');

                        if( !empty($printful_variant_id) ) {
                            return $variation_id;
                        }

                    }
                }

            }

            return null;

        }

    }

    new FPD_Export_Provider();

}

?>