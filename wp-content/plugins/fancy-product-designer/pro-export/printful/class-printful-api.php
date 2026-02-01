<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Export_Printful_Api') ) {

    class FPD_Export_Printful_Api {

        public $pf_client = null;
        private $api_url = 'https://api.printful.com/';

        public function call( $action, $request=null ){

            if( $action == 'get_products') {
                
                $res = fpd_genius_request('printful/products/');
                
                if($res['status'] == 'success') {
                    return $res['data'];
                }
                else {
                    return array('error' => isset($res['message']) ? $res['message'] : 'Please try again!');  
                }

            }
            else if( $action == 'get_product' && isset($request['product_id']) ) {

                $region = get_option( 'fpd_printful_region', 'US' );
                $product_id = $request['product_id'];

                $query = array(
                    'region' => $region
                );

                if(isset($request['include_colors']))
                    $query['include_colors'] = $request['include_colors'];
                
                if(isset($request['include_sizes']))
                    $query['include_sizes'] = $request['include_sizes'];

                $res = fpd_genius_request('printful/products/'.$product_id.'?'.http_build_query($query));
                if($res['status'] == 'success') {
                    return $res['data'];
                }
                else {
                    return array('error' => isset($res['message']) ? $res['message'] : 'Please try again!');  
                }

            }
            else if( $action == 'create_order'  && isset($request['order_data']) ) {

                return $this->jwt_request('orders', $request['order_data'] );

            }
            else if( $action == 'update_order' && isset($request['order_id'])  && isset($request['order_data']) ) {

                return $this->jwt_request(
                    'orders/'.$request['order_id'],
                     $request['order_data'],
                     true
                );

            }


        }

        private function findInArrayObject($arr, $key, $val) {

            $index = array_search($val, array_column($arr, $key));

            if( $index !== false)
                return $arr[$index];

            return null;
        }
        
        private function jwt_request( $endpoint, $post_data=null, $put=false) {
            
            $token = get_option( 'fpd_printful_api_key', '' );
            $url = $this->api_url . $endpoint;
            
            header('Content-Type: application/json');
            $ch = curl_init($url);
            $authorization = "Authorization: Bearer ".$token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if( !empty($post_data) ) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $put ? 'PUT' : 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            }
            
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            
            $json_arr = json_decode($result, true);
            
            if( isset($json_arr['code']) ) {
                return $json_arr['code'] == 200 ? $json_arr['result'] : array( 'error' => $json_arr['error'] );
            }
            else {
                return array('error' => 'API can not be reached');
            }
            
        }

    }

}