<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php

$warning = null;

foreach($_FILES as $fieldName => $file) {

    // First things first: input sanitation and security checks
    try {
        $sanitized_name = FPD_Image_Utils::sanitize_filename($file['name'][0]);
    }
    catch (Exception $e) {
        die(json_encode(array('error' => $e->getMessage())));
    }

    // Determining file name parts using pathinfo() instead of explode()
    // prevents double extensions (file.jpg.php) and directory traversal (../../file.jpg)
    $parts = pathinfo($sanitized_name);
    $filename = $parts['filename'];
    $ext = strtolower($parts['extension']);

    //check for php errors
    if( isset($file['error']) && $file['error'][0] !== UPLOAD_ERR_OK ) {
        die( json_encode( array(
            'error' => FPD_Image_Utils::file_upload_error_message($file['error'][0]),
            'filename' => $filename
        )) );
    }

    //check if its an image
    if( (!getimagesize($file['tmp_name'][0]) && $ext !== 'svg') || !in_array($file['type'][0], $valid_mime_types) ) {

        die( json_encode(array(
            'error' => 'This file is not an image!',
            'filename' => $filename
        )) );

    }

    $upload_path = FPD_Image_Utils::get_upload_path($uploads_dir, $unique_file_name, $ext);
    $image_path = $upload_path['full_path'].'.'.$ext;
    $image_url = $uploads_dir_url.'/'.$upload_path['date_path'].'.'.$ext;

    if( @move_uploaded_file($file['tmp_name'][0], $image_path) ) {

        if($ext === 'jpg' || $ext === 'jpeg') {

            if( function_exists('exif_read_data') ) {

                $exif = @exif_read_data($image_path);

                if ($exif && isset($exif['Orientation']) && !empty($exif['Orientation'])) {

                    $image = imagecreatefromjpeg($image_path);
                    $resolution = imageresolution($image);
                    unlink($image_path);

                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;

                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;

                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }

                    if( is_array( $resolution ) ) {
                        imageresolution($image, $resolution[0], $resolution[1]);
                    }

                    imagejpeg($image, $image_path, 100);

                }
            }
            else
                $warning = 'exif_read_data function is not enabled.';

        }
        else if($ext === 'svg') {

            //sanitize svg content and resave image
            if( function_exists('file_get_contents') ) {
                $fpd_svg_handler = new FPD_Svg_Handler();
                $fpd_svg_handler->sanitize_svg($image_path);
            }

        }

        if ( isset($_GET['filter']) ) {

            $result = FPD_Image_Utils::apply_imagick_filter( $image_path, $_GET['filter'] );

            if( isset($result['error']) ) {

                $warning = $result['error'];

            }
            else if( isset($result['image_path']) ) {

                $image_path = $result['image_path'];

                //update image url if extension has changed
                if( $new_ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION)) ) {

                    if( $new_ext !== $ext ) {

                        $image_url = preg_replace('/\.(jpg|jpeg|svg)$/i', '.'.$new_ext, $image_url);

                    }
                    
                }
                                
            }

        }

        echo json_encode( array(
            'image_src' => $image_url,
            'filename' => $filename,
            'warning' => $warning
        ) );

    }
    else {

        echo json_encode( array(
            'error' => 'PHP Issue - move_upload_file failed.',
            'filename' => $filename
        ) );

    }

}
?>