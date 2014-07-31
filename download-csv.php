<?php
    include_once("../../../wp-load.php");

    $uld = wp_upload_dir();
    $temppath = $uld['basedir'] . "/mailchimp_casl/temp";
    if ( !is_dir( $temppath ) ) {
        echo "ERROR PATH NOT FOUND";
        return;
    }

    $temp_path_file = $temppath . "/" . $_GET['dl'];

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
