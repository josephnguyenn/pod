<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('FPD_Settings_Pro_Export') ) {

	class FPD_Settings_Pro_Export {

		public static function get_options() {

			$options = apply_filters('fpd_pro_export_settings', array(

				'pro-general' => array(

					array(
						'title' => __('Output Details', 'radykal'),
						'type' => 'section-title',
						'id' => 'file-output-section',
					),

					array(
						'title' 	=> __( 'Output File', 'radykal' ),
						'description' 		=> __( 'Set the output file that will you or your customers will receive.', 'radykal' ),
						'id' 		=> 'fpd_ae_output_file',
						'default'	=> 'pdf',
						'type' 		=> 'select',
						'css'		=> 'width: 300px',
						'options'   => self::get_export_types()
					),

					array(
						'title' 	=> __( 'Hide Crop Marks', 'radykal' ),
						'description'	 => __( 'Hide crop marks in the PDF when a bleed is set. ', 'radykal' ),
						'id' 		=> 'fpd_ae_hide_crop_marks',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' => __( 'Image DPI', 'radykal' ),
						'description' 		=> 'Enter the image DPI for PNG or JPEG output.',
						'id' 		=> 'fpd_ae_image_dpi',
						'css' 		=> 'width: 100%;',
						'type' 		=> 'number',
						'default'	=> 300
					),

					array(
						'title' => __('File Receiving', 'radykal'),
						'type' => 'section-title',
						'id' => 'file-receiving-section',
						'description' => class_exists( 'WooCommerce' ) ? __( 'In WooCommerce the customer will receive the file(s) when the order is paid/completed.', 'radykal') : ''
					),

					array(
						'title' 	=> __( 'Download Link in E-Mail (Recommended)', 'radykal' ),
						'description'	 => __( 'A download link will be added in the mail. ', 'radykal' ),
						'id' 		=> 'fpd_ae_email_download_link',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
						'relations' => array(
							'fpd_ae_email_download_link_login' => true,
						)
					),

					array(
						'title' 	=> __( 'Download Link: Customer Login Required', 'radykal' ),
						'description'	 => __( 'The customer needs to log into his account to download the print file. ', 'radykal' ),
						'id' 		=> 'fpd_ae_email_download_link_login',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Print-ready file via Download Action', 'radykal' ),
						'description'	 => __( 'The customer can download the print file via the download action in the designer. ', 'radykal' ),
						'id' 		=> 'fpd_pro_export_print_download_designer',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'E-Mail Attachment', 'radykal' ),
						'description'	 => __( 'The files will be dispatched as email attachments. Please note that the delivery time may vary depending on the total size and the number of files being generated. 
						Large batches may lead to extended processing times, potentially maxing out your <a href="http://php.net/manual/en/function.set-time-limit.php" target="_blank">server execution limit</a>. 
						We recommend conducting a trial run with your typical order volume to ensure that this method aligns with your operational flow and timing requirements.', 'radykal' ),
						'id' 		=> 'fpd_ae_email_attachment',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 		=> __( 'Webhook URL', 'radykal' ),
						'description' 		=> __( 'Enter the URL for a webhook. The print server will transmit a JSON object that includes the URL along with additional relevant data to this specified webhook. For example, you can use a service like Zapier to transfer the print file to any cloud provider of your choice.', 'radykal' ),
						'id' 			=> 'fpd_pro_export_webhook_url',
						'default'		=> '',
						'type' 			=> 'text',
					),

					array(
						'title' 		=> __( 'Cloud', 'radykal' ),
						'description'	=> __( 'Choose a cloud provider to store the print-ready file when the order is received.', 'radykal' ),
						'id' 			=> 'fpd_ae_cloud',
						'default'		=> 'none',
						'type' 			=> 'radio',
						'options'   	=> array(
							'none' => __('None', 'radykal'),
							'dropbox' => __('Dropbox', 'radykal'),
							's3' => __('AWS S3', 'radykal')
						),
						'relations' => array(
							'none' => array(
								'fpd_ae_dropbox_client_id' => false,
                                'fpd_ae_dropbox_secret' => false,
                                'fpd_ae_dropbox_redirect_uri' => false,
                                'fpd_ae_dropbox_auth' => false,
								'fpd_ae_s3_access_key' => false,
								'fpd_ae_s3_access_secret' => false,
								'fpd_ae_s3_region' => false,
								'fpd_ae_s3_bucket' => false,
								'fpd_ae_s3_root_dir' => false,
                                'fpd_ae_dropbox_verify' => false,
                                'fpd_ae_s3_verify' => false
							),
							'dropbox' => array(
								'fpd_ae_dropbox_client_id' => true,
                                'fpd_ae_dropbox_secret' => true,
                                'fpd_ae_dropbox_redirect_uri' => true,
                                'fpd_ae_dropbox_auth' => true,
                                'fpd_ae_dropbox_verify' => true,
								'fpd_ae_s3_access_key' => false,
								'fpd_ae_s3_access_secret' => false,
								'fpd_ae_s3_region' => false,
								'fpd_ae_s3_bucket' => false,
								'fpd_ae_s3_root_dir' => false,
                                'fpd_ae_s3_verify' => false
							),
							's3' => array(
								'fpd_ae_dropbox_client_id' => false,
                                'fpd_ae_dropbox_secret' => false,
                                'fpd_ae_dropbox_redirect_uri' => false,
                                'fpd_ae_dropbox_auth' => false,
                                'fpd_ae_dropbox_verify' => false,
								'fpd_ae_s3_access_key' => true,
								'fpd_ae_s3_access_secret' => true,
								'fpd_ae_s3_region' => true,
								'fpd_ae_s3_bucket' => true,
								'fpd_ae_s3_root_dir' => true,
                                'fpd_ae_s3_verify' => true
							),
						)
					),
                    
                    array(
                        'title' 		=> __( 'Dropbox App Key', 'radykal' ),
                        'description' 	=> 'Enter your Dropbox App Key.',
                        'id' 			=> 'fpd_ae_dropbox_client_id',
                        'type' 			=> 'text',
                        'default'		=> ''
                    ),
                    
                    array(
                        'title' 		=> __( 'Dropbox App Secret', 'radykal' ),
                        'description' 	=> 'Enter your Dropbox App Secret.',
                        'id' 			=> 'fpd_ae_dropbox_secret',
                        'type' 			=> 'password',
                        'default'		=> ''
                    ),
                    
                    array(
                        'title' 		=> __( 'Dropbox Redirect URI', 'radykal' ),
                        'description' 	=> 'Copy this URI as redirect URI in the Dropbox App settings. DO NOT CHANGE!',
                        'id' 			=> 'fpd_ae_dropbox_redirect_uri',
                        'type' 			=> 'text',
                        'default'		=> get_site_url()
                    ),
                    
                    array(
                        'title' 		=> __( '', 'radykal' ),
                        'description' 	=> 'Enter App Key, Secret and copy the redirect URI in the Dropbox App settings. Then click "Connect Dropbox".',
                        'id' 			=> 'fpd_ae_dropbox_auth',
                        'type' 			=> 'button',
                        'placeholder'	=> 'Connect Dropbox',
                        'unbordered'    => true
                    ),
                    
                    array(
                        'title' 		=> __( 'Verify Dropbox Setup', 'radykal' ),
                        'description' 	=> 'An example PDF will be processed and uplodaed to Dropbox with your credentials. You should see it in your app folder. If there is a problem, an error with be shown. Important: You need to "Connect Dropbox" first in order to test the upload.',
                        'id' 			=> 'fpd_ae_dropbox_verify',
                        'type' 			=> 'button',
                        'placeholder'	=> 'Example Upload',
                        'value'         => 'test',
                        'unbordered'    => true
                    ),
                    
                    array(
                        'title' 		=> __( 'Dropbox Refresh Token', 'radykal' ),
                        'id' 			=> 'fpd_ae_dropbox_refresh_token',
                        'type' 			=> 'text',
                        'default'		=> ''
                    ),

					array(
						'title' 		=> __( 'S3 Access Key', 'radykal' ),
						'description' 	=> 'Enter your S3 Access Key.',
						'id' 			=> 'fpd_ae_s3_access_key',
						'type' 			=> 'text',
						'default'		=> ''
					),

					array(
						'title' 		=> __( 'S3 Access Secret', 'radykal' ),
						'description' 	=> 'Enter your S3 Access Secret.',
						'id' 			=> 'fpd_ae_s3_access_secret',
						'type' 			=> 'password',
						'default'		=> ''
					),

					array(
						'title' 		=> __( 'S3 Region', 'radykal' ),
						'description' 	=> 'Select your S3 region.',
						'id' 			=> 'fpd_ae_s3_region',
						'type' 			=> 'select',
						'default'		=> 'us-east-2',
						'options'		=> array(
							"af-south-1" => "af-south-1",
							"ap-east-1" => "ap-east-1",
							"ap-northeast-1" => "ap-northeast-1",
							"ap-northeast-2" => "ap-northeast-2",
							"ap-northeast-3" => "ap-northeast-3",
							"ap-south-1" => "ap-south-1",
							"ap-southeast-1" => "ap-southeast-1",
							"ap-southeast-2" => "ap-southeast-2",
							"ca-central-1" => "ca-central-1",
							"cn-north-1" => "cn-north-1",
							"cn-northwest-1" => "cn-northwest-1",
							"eu-central-1" => "eu-central-1",
							"eu-north-1" => "eu-north-1",
							"eu-south-1" => "eu-south-1",
							"eu-west-1" => "eu-west-1",
							"eu-west-2" => "eu-west-2",
							"eu-west-3" => "eu-west-3",
							"me-south-1" => "me-south-1",
							"sa-east-1" => "sa-east-1",
							"us-east-1" => "us-east-1",
							"us-east-2" => "us-east-2",
							"us-gov-east-1" => "us-gov-east-1",
							"us-gov-west-1" => "us-gov-west-1",
							"us-west-1" => "us-west-1",
							"us-west-2" => "us-west-2"
						)
					),

					array(
						'title' 		=> __( 'S3 Bucket Name', 'radykal' ),
						'description' 	=> 'Enter your Bucket name.',
						'id' 			=> 'fpd_ae_s3_bucket',
						'type' 			=> 'text',
						'default'		=> ''
					),

					array(
						'title' 		=> __( 'S3 Root Directory', 'radykal' ),
						'description' 	=> 'Enter a name without slashes for the root directory where the print-ready files will be stored.',
						'id' 			=> 'fpd_ae_s3_root_dir',
						'type' 			=> 'text',
						'default'		=> 'fpd-print-ready-files'
					),
                    
                    array(
                        'title' 		=> __( 'Verify S3 Setup', 'radykal' ),
                        'description' 	=> 'An example PDF will be processed and uplodaed to your AWS S3 with your credentials. You should see it in your app folder. If there is a problem, an error with be shown.',
                        'id' 			=> 'fpd_ae_s3_verify',
                        'type' 			=> 'button',
                        'placeholder'	=> 'Example Upload',
                        'value'         => 'test',
                        'unbordered'    => true
                    ),

					array(
						'title' => __('E-Mail Recipients', 'radykal'),
						'type' => 'section-title',
						'id' => 'recipients-section'
					),

					array(
						'title' 	=> __( 'Administrator', 'radykal' ),
						'description'	 => __( 'The administrator will receive the file when a new order is made.', 'radykal' ),
						'id' 		=> 'fpd_ae_recipient_admin',
						'default'	=> 'yes',
						'type' 		=> 'checkbox',
					),

					array(
						'title' 	=> __( 'Customer', 'radykal' ),
						'description'	 => __( 'Only in WooCommerce. The customer will receive the file when the WooCommerce order is completed/paid.', 'radykal' ),
						'id' 		=> 'fpd_ae_recipient_customer',
						'default'	=> 'no',
						'type' 		=> 'checkbox',
					),

				),

				'printful' => array(
					array(
						'title' 		=> __( 'API Token', 'radykal' ),
						'description' 		=> __( 'Enter the API Token of your Printful store.', 'radykal' ),
						'id' 			=> 'fpd_printful_api_key',
						'default'		=> '',
						'type' 			=> 'password'
					),
					array(
						'title' 		=> __( 'Sales Profit', 'radykal' ),
						'description' 		=> __( 'Enter the sales profit. Either a fixed value that will be added to the net price (e.g. 5) or a percentage value (e.g. 15%) - net price * 0.15 = your profit.', 'radykal' ),
						'id' 			=> 'fpd_printful_profit',
						'default'		=> '',
						'type' 			=> 'text',
						'placeholder' 	=> __( 'For example: 5 or 15%', 'radykal' ),
					),
					array(
						'title' 		=> __( 'Region', 'radykal' ),
						'description' 		=> __( 'In which region are you going to sell the Printful products.', 'radykal' ),
						'id' 			=> 'fpd_printful_region',
						'default'		=> 'US',
						'type' 			=> 'select',
						'options'		=> array(
							'US'		=> __( 'USA', 'radykal' ),
							'EU'		=> __( 'Europe', 'radykal' ),
							'AU'		=> __( 'Australia/New Zealand', 'radykal' ),
							'CA'		=> __( 'Canada', 'radykal' ),
							'JP'		=> __( 'Japan', 'radykal' ),
							'MX'		=> __( 'Mexico', 'radykal' ),
							'worldwide'	=> __( 'Worldwide', 'radykal' ),
						)
					),
					array(
						'title' 		=> __( 'Enable Order Failure Mail', 'radykal' ),
						'description' 		=> __( 'If something goes wrong during the order process, the administrator will receive a mail with some details. Otherwise you can view any error in wp-content/fpd_php.log.', 'radykal' ),
						'id' 			=> 'fpd_printful_failure_admin_mail',
						'default'		=> 'yes',
						'type' 			=> 'checkbox'
					),
				)

			));

			if( !empty( get_option('fpd_genius_license_key', $GLOBALS['FPD_GENIUS_LICENSE_KEY']) ) ) {

				array_unshift($options['pro-general'], 
					array(
						'title' 		=> __( 'Export Method', 'radykal' ),
						'description'	=> __( 'We offer two export methods to create print-ready files.<br><a href="https://support.fancyproductdesigner.com/support/solutions/articles/13000103311-export-methods-explained" target="_blank">More information.</a>', 'radykal' ),
						'id' 			=> 'fpd_pro_export_method',
						'default'		=> 'nodecanvas-svg',
						'type' 			=> 'radio',
						'options'   	=> array(
							'nodecanvas-svg' => __('Node Canvas + SVG2PDF (Recommended)', 'radykal'),
							'nodecanvas' => __('Node Canvas', 'radykal'),
							'svg2pdf' => __('SVG2PDF (Deprecated: Will be removed soon)', 'radykal'),
							
						),
						'unbordered'	=> true
					),
					array(
                        'title' 		=> __( '', 'radykal' ),
                        'description' 	=> 'Generating a print file in the Pro Export involves various processes. Here, you can test these processes independently to identify any issues.',
                        'id' 			=> 'fpd_pe_test_export',
                        'type' 			=> 'button',
                        'placeholder'	=> 'Test Export',
                    ),
				);

			}

			return $options;

		}

		public static function get_export_types() {

			return array(
				'pdf' => 'PDF',
				'jpeg' => 'JPEG',
				'png' => 'PNG',
				'zip_pdf_fonts' => __('Archive containing PDF and used fonts', 'radykal'),
				'zip_pdf_custom_images' => __('Archive containing PDF and custom images', 'radykal')
			);

		}

		public static function get_recipients() {

			return array(
				'admin' => __('Administrator', 'radykal'),
				'customer' =>  __('Customer', 'radykal')
			);

		}

	}

}

//deprecated: export addon
class FPD_Settings_Automated_Export extends FPD_Settings_Pro_Export {

	public static function get_options() {

		$options = array();
		$options['ae-general'] = array(

		);
		return apply_filters('fpd_automated_export_settings', $options );

	}

};