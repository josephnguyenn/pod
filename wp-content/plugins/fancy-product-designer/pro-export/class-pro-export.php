<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('FPD_Pro_Export')) {

    class FPD_Pro_Export
    {

        const ROUTE_NAMESPACE = 'fpd-export/v1.0';
        const ENABLE_PRINT_JOB = true;
        const JOB_TIMEOUT = 180; //in secs

        public function __construct()
        {

            require_once __DIR__ . '/class-admin.php';
            require_once __DIR__ . '/class-export-provider.php';
            require_once __DIR__ . '/printful/class-printful.php';
        }

        public static function create_print_ready_file($print_data, $job_async = true, $export_method = null)
        {

            if (is_null($export_method))
                $export_method = get_option('fpd_pro_export_method', 'nodecanvas-svg');

            $print_data = array_merge(array(
                'output_format'             => 'pdf',
                'name'                     => 'test',
                'svg_data'                 => array(), //svg2pdf export
                'product_data'          => array(), //nodecanvas export
                'used_fonts'             => array(),
                'include_font_files'     => false,
                'summary_json'             => array(),
                'dpi'                     => 300,
                'include_images'         => null,
                'hide_crop_marks'        => fpd_get_option('fpd_ae_hide_crop_marks'),
                'create_print_job'        => true,
                'print_job_id'          => null,
                'file_ready_webhook'    => null,
                'order_id'              => null,
                'item_id'               => null,
                'variation_printful'    => null,
                'export_method'         => $export_method,
                'cloud_webhook'         => get_option('fpd_pro_export_webhook_url', null),
                'api_version'           => '2.0',
                'rtl'                   => is_rtl(),
                'print_ready'           => true //deprecated but needed for svg2pdf
            ), $print_data);

            //beta
            if ($export_method == 'nodecanvas-svg' && $print_data['output_format'] == 'pdf') {
                $print_data['output_format'] = 'svg-pdf';
            }

            $print_data['name'] = sanitize_key($print_data['name']);

            if (!Fancy_Product_Designer::LOCAL && self::ENABLE_PRINT_JOB && $print_data['create_print_job']) {

                $print_job_data = array(
                    'name'          => $print_data['name'],
                    'output_format' => $print_data['output_format'],
                );

                if (isset($print_data['order_id']))
                    $print_job_data['order_id'] = $print_data['order_id'];


                if ($print_data['item_id'])
                    $print_job_data['item_id'] = $print_data['item_id'];

                if ($print_data['variation_printful'])
                    $print_job_data['variation_printful'] = $print_data['variation_printful'];

                $print_job_id = FPD_Print_Job::create($print_job_data);

                $print_data['print_job_id'] = $print_job_id;
                $print_data['file_ready_webhook'] = get_rest_url(null, self::ROUTE_NAMESPACE  . '/print_job/') . $print_data['print_job_id'];
            }

            if (!file_exists(FPD_ORDER_DIR))
                wp_mkdir_p(FPD_ORDER_DIR);

            if (!file_exists(FPD_ORDER_DIR . 'print_ready_files'))
                wp_mkdir_p(FPD_ORDER_DIR . 'print_ready_files');

            //-------------NODECANVAS

            //modify product data json to reduce json size    
            if (isset($print_data['product_data']) && !empty($print_data['product_data'])) {

                $allow_option_keys = [
                    'stageWidth',
                    'stageHeight',
                    'output',
                    'printingBox'
                ];

                //reduce view options
                foreach ($print_data['product_data'] as $view_key => $product_view) {

                    if (isset($product_view['options'])) {
                        $print_data['product_data'][$view_key]['options'] = self::filter_by_keys(
                            $product_view['options'],
                            $allow_option_keys
                        );
                    }

                    //reduce element parameters                    
                    if (isset($print_data['product_data'][$view_key]['elements'])) {

                        foreach ($print_data['product_data'][$view_key]['elements'] as $elem_key => $elem) {

                            if (isset($elem['parameters']['originParams']))
                                unset($elem['parameters']['originParams']);

                            $print_data['product_data'][$view_key]['elements'][$elem_key] = $elem;
                        }
                    }
                }

                $print_data['summary_json'] = !empty($print_data['summary_json']);
                $print_data['svg_data'] = null;
            }

            $remote_result = fpd_genius_request('export', null, 'POST', $print_data);

            if (Fancy_Product_Designer::DEBUG) {

                fpd_logger($print_data, true);
                fpd_logger($remote_result);
            }

            if (is_array($remote_result) && isset($remote_result['data'])) {

                $res_data = $remote_result['data'];

                //get print file directly and save on local server
                if (isset($res_data['file_url'])) {

                    $local_file = self::save_remote_file($res_data['file_url']);

                    if ($local_file)
                        return $local_file;
                    else
                        throw new Exception(__('Local file could not be stored. Please try again!'));
                }
                //create print job and return array with job id
                else if (isset($res_data['print_job_id'])) {

                    if (!$job_async) return $remote_result;

                    $file_downloaded = false;
                    $count = 0;
                    $sleep = 2; //sleep for 2 secs

                    while (!$file_downloaded) {

                        $print_job = new FPD_Print_Job($res_data['print_job_id'], true);
                        $local_file = $print_job->get_local_file();

                        if ($count > intval(self::JOB_TIMEOUT / $sleep) || is_string($local_file)) {

                            if (is_string($local_file))
                                return explode('/print_ready_files/', $local_file)[1];
                            else
                                throw new Exception(__('The file generation tooks more than 120 seconds. Process canceled!', 'radykal'));

                            $file_downloaded = true;
                        }

                        sleep($sleep);
                        $count++;
                    }
                }
            } else {

                $error_msg = __('Remote file could not be created. Please try again!', 'radykal');

                if (is_array($remote_result) && isset($remote_result['message']))
                    $error_msg = $remote_result['message'];

                if ($job_async && $print_data['create_print_job']) {
                    throw new Exception($error_msg);
                } else {
                    return $error_msg;
                }
            }
        }

        public static function save_remote_file($remote_file_url)
        {

            $unique_dir = time() . bin2hex(random_bytes(16));
            $temp_dir = FPD_ORDER_DIR . 'print_ready_files/' . $unique_dir;
            mkdir($temp_dir);

            $local_file_path = $temp_dir;

            $filename = fpd_copy_file_to_directory(
                $remote_file_url,
                $local_file_path
            );

            return $filename ? $unique_dir . '/' . $filename : null;
        }

        private static function filter_by_keys(array $input, array $allowedKeys)
        {
            return array_filter(
                $input,
                function ($key) use ($allowedKeys) {
                    return in_array($key, $allowedKeys, true);
                },
                ARRAY_FILTER_USE_KEY
            );
        }
    }

    new FPD_Pro_Export();
}
