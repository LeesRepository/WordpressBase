<?php

class Hallmark_Misc {

    public static function strbool ( $value )
    {

        return $value ? 'true' : 'false';

    }

    public static function returnLine ( $line )
    {

        return $line . "<br />";

    }

    public static function args ()
    {

        if ( isset( $_REQUEST[ 'args' ] ) ) {

            $args = $_REQUEST[ 'args' ];

            if ( ! empty( $args ) ) {

                return escapeshellarg ( $args );

            }

        }

        return "";

    }

    public static function shell ( $cmd )
    {

        return nl2br ( shell_exec ( 'cd ' . ABSPATH . ' && ' . $cmd ) );

    }

    public static function request ( $key )
    {

        if ( isset( $_REQUEST[ $key ] ) ) {
            return $_REQUEST[ $key ];
        } else {
            return false;
        }
    }

    public static function removeQuotes ( $string )
    {

        $string = str_replace ( '"', "", $string );
        $string = str_replace ( "'", "", $string );

        return $string;

    }

    public function adminNotice ( $notice, $classes = "updated" )
    {

        ?>
        <div class="<?php echo $classes; ?>">
            <p><?php echo $notice; ?></p>
        </div>
        <?php

    }

    public function clearOptionsCache ()
    {

        wp_cache_delete ( 'alloptions', 'options' );

    }

    public function jsonResponse( $success = false, $message = "" ) {

        echo json_encode( array(
            'success'   => $success,
            'message'   => $message
        ));
        die;

    }

}
