<?php

/*
Plugin Name: Hallmark Assets
Description: Loads JS and CSS assets
Version: 0.0.1
Author: Steve Kohlmeyer
 */

class HallmarkAssets
{

    public $contentURL          = false;
    public $gaID                = 'UA-38842445-2';
    public $dev                 = false;
    public $remoteScripts       = array ();
    public $remoteScriptsFooter = array ();
    public $remoteCSS           = array ();
    public $ajaxLoginURL        = "";

    public function __construct ()
    {

        $this->configSwitch ();
        $this->dirTheme     = get_template_directory ();
        $this->directoryURI = get_stylesheet_directory_uri ();
        add_action ( 'wp_enqueue_scripts', array ( $this, 'enqueue' ) );
        $this->init ();

    }

    public function init ()
    {

        $currentUri = $_SERVER[ 'REQUEST_URI' ];

        // Header Scripts
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/libs/require.js' ) , false );
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/libs/jquery.1.7.1.js' ) , false );
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/libs/jquery.validate.min.js' ) , false );
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/require-config.js' ) , false );
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/global.js' ) , false );
        $this->addRemoteScript ( ( "//" . $this->contentURL . '/assets/scripts/controllers/global-components.js' ) , false );

        // Footer Scripts
        $this->addRemoteScript ( "//" . $this->contentURL . '/scripts/omniture.js' );
        $this->addRemoteScript ( "//" . $this->contentURL . '/scripts/s_code.js' );
        $this->addRemoteScript ( "//" . $this->contentURL . '/scripts/GomezTag.js' );
        $this->addRemoteScript ( "//" . $this->contentURL . '/scripts/post_analytics.js' );

    public function addRemoteScript ( $scriptURL, $footer = true )
    {

        if ( $footer ) {

            $this->remoteScriptsFooter[ ] = $scriptURL;

        } else {

            $this->remoteScripts[ ] = $scriptURL;

        }

    }

    public function addRemoteCSS ( $cssURL )
    {

        $this->remoteCSS[ ] = $cssURL;

    }

    public function configSwitch ()
    {

        switch ( APP_ENV ) {

            case 'local':

                $this->contentURL   = "contentstage.hmkb2c.com";
                $this->ajaxLoginURL = "//stage.hmkb2c.com";
                $this->dev          = true;
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );

                break;

            case 'test1':

                $this->contentURL   = "contenttest1.hmkb2c.com";
                $this->ajaxLoginURL = "//test1.hmkb2c.com";
                $this->dev          = true;
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js"  , false );

                break;

            case 'test2':

                $this->contentURL   = "contenttest2.hmkb2c.com";
                $this->ajaxLoginURL = "//test2.hmkb2c.com";
                $this->dev          = true;
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );

                break;

            case 'test3':

                $this->contentURL   = "contenttest3.hmkb2c.com";
                $this->ajaxLoginURL = "//test3.hmkb2c.com";
                $this->dev          = true;
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );

                break;

            case 'test4':

                $this->contentURL   = "contenttest4.hmkb2c.com";
                $this->ajaxLoginURL = "//test4.hmkb2c.com";
                $this->dev          = true;
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );

                break;

            case 'stage':

                $this->contentURL = "contentstage.hmkb2c.com";
				$this->ajaxLoginURL = "//stage.hmkb2c.com";
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );


                break;

            case 'prodfix':

                $this->contentURL = "contentprodfix.hallmark.com";
        		$this->ajaxLoginURL = "//prodfix.hallmark.com";
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a-staging.js" , false );

                break;

            case 'production':

                $this->contentURL = "content.hallmark.com";
                $this->ajaxLoginURL = "//www.hallmark.com";
                $this->gaID       = "UA-38842445-3";
                $this->addRemoteScript( "//assets.adobedtm.com/f883c22c9968c9151ecfa832037a990a96bfceef/satelliteLib-64bf9e975adfece54d2af85a9ba38ebfcd00792a.js" , false );

                break;

        }

    }

    public function enqueue ()
    {

        foreach ( $this->remoteScripts as $script ) {

            wp_enqueue_script ( sanitize_title ( $script ), $script, array (), '0.0.1', false );


        }

        foreach ( $this->remoteScriptsFooter as $script ) {

            wp_enqueue_script ( sanitize_title ( $script ), $script, array (), '0.0.1', true );

        }

        foreach ( $this->remoteCSS as $css ) {

                wp_enqueue_style ( sanitize_title ( $css ), ( "//" . $this->contentURL . $css ) , array (), '0.0.1' );

        }
    }

    public function headerHTML ( $echo = true )
    {

        return $this->headerFooter ( 'header', $echo );

    }

    public function footerHTML ( $echo = true )
    {

        return $this->headerFooter ( 'footer', $echo );

    }

    /**
     * Method for getting Header/Footer from API
     *
     * @param string  $switch Define what is returned: header or footer
     * @param boolean $echo   If true, this echo's the html and returns an empty string, if false, this returns the
     *                        html in a string
     *
     * @return string Either the header/footer html in a string, or an emptry string if echo is true
     */
    public function headerFooter ( $switch, $echo = true )
    {

        $switch = strtolower ( $switch );
        $name   = "Header";
        $url    = "";

        switch ( $switch ) {

            case 'footer':

                $name = "Footer";
                break;

            case 'header':
            default:

                $name = "Header";
                break;

        }

        // Url switch for different environments:
        switch ( APP_ENV ) {

            case 'dev':

                $url = 'http://stageapi.hallmark.com/contentassets/?name=';

                break;

            case 'test1':

                $url = 'https://test1api.hmkb2c.com/contentassets/?name=';

                break;

            case 'test2':

                $url = 'https://test2api.hmkb2c.com/contentassets/?name=';

                //break;
                break;

            case 'test3':

                $url = 'https://test3api.hmkb2c.com/contentassets/?name=';

               // break;
                break;

            case 'test4':

                $url = 'https://test4api.hmkb2c.com/contentassets/?name=';

                break;

            case 'stage':

                $url = 'http://stageapi.hallmark.com/contentassets/?name=';

                break;

            case 'prodfix':

                $url = 'https://prodfixapi.hallmark.com/contentassets/?name=';

                break;

            case 'production':

                $url = 'https://api.hallmark.com/contentassets/?name=';

                break;


        }

        $url = $url . $name;

//        $cache = get_transient ( $url );
//
//        if ( $cache !== false ) {
//
//            if ( $echo ) {
//
//                echo $cache;
//
//                return true;
//
//            }
//
//            return $cache;
//
//        }

        $opts = array (

            'http' => array (

                'method'        => "GET",
                'header'        => "Accept-language: en\r\n" .
                    "hd-apiversion: 1\r\n",
                'timeout'       => 3,
                'ignore_errors' => true

            ),

            'ssl'  => array (

                'allow_self_signed' => true,
                'verify_per_name'   => false,
                'verify_peer'       => false

            )

        );

        if ( phpversion () >= "5.6" ) {

            $opts[ 'ssl' ][ 'peer_name' ] = 'api.hallmark.com';

        } else {

            $opts[ 'ssl' ][ 'CN_MATCH' ] = 'api.hallmark.com';

        }

        if ( false === $this->dev ) {

            // Stage / Prod
            $context = @stream_context_create ( $opts );

            // Open the file using the HTTP headers set above
            $file = @file_get_contents ( $url, false, $context );

        } else {

            // DEV
            $context = stream_context_create ( $opts );

            // Open the file using the HTTP headers set above


            try {
                $file = file_get_contents ( $url, false, $context );
            } catch ( Exception $e ) {
                echo $e->getMessage ();
            }

        }


        if ( $file === false or empty( $file ) ) {

            return "<h1>empty header</h1>";

        }

        $jsonObj = @json_decode ( $file );

        if ( ! isset( $jsonObj ) or ! isset( $jsonObj->content ) ) {

            return "";

        }

//        set_transient ( $url, $jsonObj->content, ( 60 * 60 * 2 ) );

        if ( $echo ) {

            echo $jsonObj->content;

            return true;

        }

        return $jsonObj->content;

    }



}

$hmAssets = new HallmarkAssets();
