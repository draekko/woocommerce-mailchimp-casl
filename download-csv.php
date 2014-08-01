<?php

    $localpath = pathinfo( __FILE__ );
    $downloadfile = $localpath['dirname'] . '/' . 'path.txt';

    if ( isset( $_GET['fl1'] ) ) {
        $filename = base64_decode( base64_decode( $_GET['fl1'] ) );
    } else {
        die();
    }

    if ( file_exists( $downloadfile ) ) {
        $temppath = base64_decode( base64_decode( file_get_contents( $downloadfile ) ) );
    } else {
        die();
    }

    if ( !is_dir( $temppath ) ) {
        die("ERROR PATH NOT FOUND");
    }

    $temp_path_file = $temppath . '/mailchimp_casl/temp/' . basename( $filename );

    if ( file_exists ( $temp_path_file ) ) {
        header( 'Pragma: no-cache' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename=' . basename( $temp_path_file ) );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Connection: Keep-Alive' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $temp_path_file ) );
        ob_clean();
        flush();
        readfile( $temp_path_file );
        unlink( $temp_path_file );
        $msg = 'OK';
    } else {
        $msg = "ERROR FILE NOT FOUND";
    }

    die($msg);
?>
