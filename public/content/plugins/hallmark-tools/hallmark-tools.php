<?php
/*
Plugin Name: Hallmark Tools
Description: Misc tools for helping build and deploy Wordpress Websites.
Version: 0.0.2
Author: Steve Kohlmeyer
*/

if ( ! defined ( 'ABSPATH' ) ) {
    exit;
}

require( plugin_dir_path( __FILE__ ) . 'lib/hallmark-misc.php' );
//require( plugin_dir_path( __FILE__ ) . 'lib/hallmark-migration.php' );

class Hallmark_Wordpress_Tools
{

    public $timeout       = 6000;
    public $errors        = array ();
    public $notices       = array ();
    public $updates       = array ();
    public $findQuery     = false;
    public $replaceQuery  = false;
    public $uploadFolders = array ();
    public $contentTables = array (
        'wp_posts',
        'wp_postmeta',
        'wp_term_relationships',
        'wp_term_taxonomy',
        'wp_terms'
    );
    public $dbOptionsKeyDbFile = "hm-database-downloaded";

    public $optionsCache     = array ();
    public $optionsToPersist = array (
        'this_key',
        'remote_host',
        'remote_key'
    );

    public $slug = "hallmark-wp-tools";

    public function __construct ()
    {

        add_action ( 'admin_menu', array ( $this, 'adminMenu' ) );
        add_action ( 'admin_notices', array ( $this, 'adminNotices' ) );
        add_action ( 'current_screen', array ( $this, 'currentScreen' ) );

        // AJAX
        add_action ( 'wp_ajax_nopriv_hm_migration', array ( $this, 'ajaxMigration' ) );
        add_action ( 'wp_ajax_hm_migration', array ( $this, 'ajaxMigration' ) );
        add_action ( 'wp_ajax_test_connection', array ( $this, 'ajaxTestConnection' ) );
        add_action ( 'wp_ajax_nopriv_test_connection', array ( $this, 'ajaxTestConnection' ) );
        add_action ( 'wp_ajax_remote_test_connection', array ( $this, 'ajaxTestConnectionRemote' ) );
        add_action ( 'wp_ajax_nopriv_remote_test_connection', array ( $this, 'ajaxTestConnectionRemote' ) );

        add_action ( 'wp_ajax_send_database', array ( $this, 'ajaxSendDatabase' ) );
        add_action ( 'wp_ajax_nopriv_send_database', array ( $this, 'ajaxSendDatabase' ) );
        add_action ( 'wp_ajax_remote_receive_database', array ( $this, 'ajaxReceiveDatabase' ) );
        add_action ( 'wp_ajax_nopriv_remote_receive_database', array ( $this, 'ajaxReceiveDatabase' ) );

        add_action ( 'wp_ajax_import_database', array ( $this, 'ajaxImportDatabase' ) );
        add_action ( 'wp_ajax_nopriv_import_database', array ( $this, 'ajaxImportDatabase' ) );
        add_action ( 'wp_ajax_remote_import_database', array ( $this, 'ajaxRemoteImportDatabase' ) );
        add_action ( 'wp_ajax_nopriv_remote_import_database', array ( $this, 'ajaxRemoteImportDatabase' ) );

        $this->uploadDir = wp_upload_dir ();


        add_action ( 'task_scheduler_action_after_loading_plugin', array ( $this, 'taskSchedulerModule' ) );

    }

    function taskSchedulerModule ()
    {

        // Register a custom action module.
        include ( dirname ( __FILE__ ) . '/lib/task.php' );
        include ( dirname ( __FILE__ ) . '/lib/task-thread.php' );
        include ( dirname ( __FILE__ ) . '/lib/task-wizard.php' );
        new TaskScheduler_Action_HallmarkTools;

    }

    public function adminMenu ()
    {

        add_management_page ( 'Hallmark Tools', 'Hallmark Tools', 'edit_plugins', $this->slug, array ( $this, 'view' ) );

    }

    public function currentScreen ()
    {

        $curScreen = get_current_screen ();

        if ( $curScreen->base = 'tools_page_' . $this->slug ) {

            if ( isset( $_REQUEST[ 'hmaction' ] ) ) {

                $hmAction = $_REQUEST[ 'hmaction' ];

                if ( method_exists ( $this, $hmAction ) ) {

                    $this->$hmAction();

                }

            }

            if ( isset( $_REQUEST[ 'replace' ] ) and ! empty( $_REQUEST[ 'replace' ] ) )
                $this->setReplaceParameter ( $_REQUEST[ 'replace' ] );

            if ( isset( $_REQUEST[ 'find' ] ) and ! empty( $_REQUEST[ 'find' ] ) ) {

                $this->setFindParameter ( $_REQUEST[ 'find' ] );
                $this->processFindReplace ();

            }

            if ( isset( $_REQUEST[ 'src' ] ) and ! empty( $_REQUEST[ 'src' ] ) and isset( $_REQUEST[ 'page' ] ) and $_REQUEST[ 'page' ] === $this->slug ) {

                $this->processImages ();

            }


        }

    }

    public function setReplaceParameter ( $strReplace = false )
    {

        if ( $strReplace !== false ) {

            $this->replaceQuery = $strReplace;

        }

    }

    public function setFindParameter ( $strFind = false )
    {

        if ( $strFind !== false ) {

            $this->findQuery = $strFind;
            $this->findFuzzy = "%" . $strFind . "%";

        }

    }




    public function view ()
    {

        $classesToolbox     = "";
        $classesFindReplace = "";
        $tab                = isset( $_REQUEST[ 'tab' ] ) ? $_REQUEST[ 'tab' ] : false;
        if ( false === $tab ) {
            $classesToolbox = "nav-tab-active";
        }

        ?>
        <div class="wrap">
            <h2>Hallmark Tools</h2>

            <h2 class="nav-tab-wrapper">
                <?php
                $this->tab ( "toolbox", "Toolbox", $classesToolbox );
                $this->tab ( "find-replace", "Find / Replace" );
                $this->tab ( "images", "Images" );
//                $this->tab ( "migration", "Migration" );
                ?>
            </h2>
            <?php

            switch ( $tab ) {

                default:
                    $this->pageDefault ();
                    break;

                case 'find-replace':
                    $this->pageFindReplace ();
                    break;

                case 'images':
                    $this->pageImages ();
                    break;

                case 'migration':
//                    $this->pageMigration ();
                    break;
            }

            ?>
        </div>
    <?php

    }

    function tab ( $slug = "", $text = "", $classes = "" )
    {

        $tab = isset( $_REQUEST[ 'tab' ] ) ? $_REQUEST[ 'tab' ] : false;

        if ( $tab and $tab === $slug ) {
            $classes .= " nav-tab-active";
        }

        ?>
        <a href="<?php
        echo admin_url ( 'tools.php?page=' . $this->slug );
        ?>&tab=<?php
        echo $slug;
        ?>" class="nav-tab <?php
        echo $classes;
        ?>"><?php
            echo $text;
            ?></a>
    <?php

    }

    public function frmImages ()
    {

        $src   = "";
        $limit = "";
        $proxy = "";
        if ( isset( $_REQUEST[ 'src' ] ) ) {
            $src = $_REQUEST[ 'src' ];
        }
        if ( isset( $_REQUEST[ 'limit' ] ) ) {
            $limit = $_REQUEST[ 'limit' ];
        }
        if ( isset( $_REQUEST[ 'proxy' ] ) ) {
            $proxy = $_REQUEST[ 'proxy' ];
        }

        ?>
        <form action="<?php echo admin_url ( 'tools.php' ); ?>" method="get" style="margin: 10px 0;">
            <input type="hidden" name="page" value="<?php echo $this->slug; ?>"/>
            <input type="hidden" name="tab" value="images"/>
            <label for="#src">Source URL:</label>
            <br/>
            <input id="src" type="text" name="src" placeholder="http://source-url.com/wp-content/uploads" size="80"
                   value="<?php echo $src; ?>"/>
            <br/>
            <br/>
            <label for="limit">Limit number of images downloaded at one time</label><br/>
            <input id="limit" type="text" name="limit" placeholder="20" size="10" value="<?php echo $limit; ?>"/>
            <br/>
            <br/>
            <label for="proxy">Proxy Server IP ( for downloading on local development environment )</label><br/>
            <input id="proxy" type="text" name="proxy" placeholder="0.0.0.0" size="80" value="<?php echo $proxy; ?>"/>
            <br/>
            <br/>
            <input class="button button-primary" id="btnDownload" type="submit" value="Download"/>
        </form>
        <script>
            (function ($) {

                $(document).ready(function () {

                    $('#btnDownload').on('click', function (e) {

                        if ($('input[name="src"]').val().length < 0) {
                            e.preventDefault();
                        }
                    })

                })

            })(jQuery)
        </script>
    <?php

    }

    public function frmFindReplace ()
    {

        ?>
        <form action="<?php echo admin_url ( 'tools.php' ); ?>" method="get" style="margin: 10px 0;">
            <input type="hidden" name="page" value="<?php echo $this->slug; ?>"/>
            <input type="hidden" name="tab" value="find-replace"/>
            <input type="text" name="find" placeholder="Find" size="80" value="<?php echo $this->findQuery; ?>"/>
            <br/>
            <input type="text" name="replace" placeholder="Replace" size="80"
                   value="<?php echo $this->replaceQuery; ?>"/>
            <br/>
            <br/>
            <input class="button button-primary" id="btnFindReplace" type="submit" value="Find / Replace"/>
        </form>
        <script>
            (function ($) {

                $(document).ready(function () {

                    $('#btnFindReplace').on('click', function (e) {
                        if ($('input[name="replace"]').val().length > 0) {
                            e.preventDefault();
                            var confirmed = confirm("Are you sure you wish to replace?");
                            if (confirmed) {
                                $('#btnFindReplace').parent().submit();
                            }
                        } else {
                            if ($('input[name="find"]').val().length < 1) {
                                e.preventDefault();
                            }
                        }
                    })

                })

            })(jQuery)
        </script>
    <?php

    }

    public function btnCmd ( $text = "", $action = "", $popup = false, $popupRequired = false, $prompt = false, $confirmation = false )
    {

        $id  = "";
        $tab = false;

        if ( $popup or $confirmation ) {

            $id = uniqid ();

        }

        if ( false === $prompt ) {
            $prompt = "Arguements: ";
        }

        if ( isset( $_REQUEST[ 'tab' ] ) ) {
            $tab = $_REQUEST[ 'tab' ];
        }

        ?>
        <form action="<?php echo admin_url ( 'tools.php' ); ?>" method="get" style="margin: 10px 0;">
            <input type="hidden" name="page" value="<?php echo $this->slug; ?>"/>
            <input type="hidden" name="hmaction" value="<?php echo $action; ?>"/>
            <?php if ( $tab ): ?>
                <input type="hidden" name="tab" value="<?php echo $tab; ?>"/>
            <?php endif; ?>
            <input type="hidden" name="args" value=""/>
            <input class="button button-primary" id="<?php echo $id; ?>" type="submit" value="<?php echo $text; ?>"/>
        </form>
        <?php

        if ( $popup ) {

            ?>
            <script>
                (function ($) {

                    $(document).ready(function () {

                        $('#<?php echo $id; ?>').on('click', function (e) {
                            e.preventDefault();
                            var args = prompt("<?php echo $prompt; ?>");
                            <?php if( $popupRequired ): ?>
                            if (null !== args && args.length > 0) {
                                <?php endif; ?>
                                $('#<?php echo $id; ?>').parent().find('input[name="args"]').val(args).parent().submit();
                                <?php if( $popupRequired ): ?>
                            }
                            <?php endif; ?>

                        })

                    })

                })(jQuery)
            </script>
        <?php

        } elseif ( $confirmation ) {

            ?>
            <script>
                (function ($) {

                    $(document).ready(function () {

                        $('#<?php echo $id; ?>').on('click', function (e) {
                            e.preventDefault();
                            var confirmed = confirm("<?php echo $prompt; ?>");
                            if (confirmed) {
                                $('#<?php echo $id; ?>').parent().submit();
                            }
                        })

                    })

                })(jQuery)
            </script>
        <?php

        }

    }

    public function importImageFiles ( $atts )
    {

        global $wpdb;

        $filesDownloaded      = 0;
        $fileGetContentsWorks = true;
        $defaults             = array (
            'url'        => false,
            'upload_dir' => $this->uploadDir,
            'folders'    => false,
            'proxy'      => '',
        );

        $atts = array_merge ( $defaults, $atts );

        $stmt = $wpdb->prepare (
            "SELECT
                $wpdb->postmeta.post_id,
                $wpdb->postmeta.meta_key,
                $wpdb->postmeta.meta_value
            FROM
                $wpdb->posts
                    INNER JOIN $wpdb->postmeta
                    ON $wpdb->posts.ID = $wpdb->postmeta.post_id
            WHERE
                ($wpdb->posts.post_type = '%s') AND
                ($wpdb->postmeta.meta_key = '_wp_attached_file')
            ",
            'attachment'
        );

        $results      = $wpdb->get_results ( $stmt, OBJECT );
        $filesMissing = $this->iterateResults ( $results );
        $this->rebuildFolderStruct ();
        unset( $results );


        if ( $filesMissing === false or empty( $filesMissing ) ) {
            return false;
        }

        if ( isset( $atts[ 'url' ] ) and ! empty( $atts[ 'url' ] ) ) {

            $ctx = stream_context_create ( array (
                'http' =>
                    array (
                        'timeout' => 5, // In Seconds
                    )
            ) );

            foreach ( $filesMissing as $thisFile ) {

                $remoteFile = rtrim ( $atts[ 'url' ], "/" ) . "/" . $thisFile;
                $localFile  = $atts[ 'upload_dir' ][ 'basedir' ] . "/" . $thisFile;


                $loadedFile = false;
                $fileSize   = 0;

                if ( $fileGetContentsWorks === true ) {

                    $loadedFile = @file_get_contents ( $remoteFile, 0, $ctx, 0, 1 );

                    $fileSize = count ( $loadedFile );

                }


                if ( false !== $loadedFile and ( $fileSize > 10 ) ) {


                    $bytes = file_put_contents ( $localFile, file_get_contents ( $remoteFile, 0, $ctx, 0, 1 ) | LOCK_EX );

                    $this->updates[ ] = $remoteFile . " -> " . $localFile . " - " . $bytes . "B<br />\n";

                    if ( $bytes < 10 ) {
                        $fileGetContentsWorks = false;
                    }
                    $filesDownloaded++;

                } else {

                    $fileGetContentsWorks = false;

                    $command = 'wget --tries=2 --read-timeout=7 ' . $remoteFile . ' -O ' . $localFile . ' 2>&1; echo $?';
                    $return  = shell_exec ( $command );
                    $filesDownloaded++;


                }

            }

        }

        return $filesDownloaded;


    }


    public function processImages ()
    {

        global $wpdb;

        $limit        = 20;
        $upload_dir   = wp_upload_dir ();
        $folderStruct = array ();
        $filesMissing = array ();
        $url          = $_REQUEST[ 'src' ];
        $proxy        = "";

        if ( isset( $_REQUEST[ 'proxy' ] ) and ! empty( $_REQUEST[ 'proxy' ] ) ) {
            $proxy = $_REQUEST[ 'proxy' ];
            $proxy = "http://" . $proxy . ":8080/?url=";
        }

        if ( isset( $_REQUEST[ 'limit' ] ) and count ( $_REQUEST[ 'limit' ] ) > 0 ) {
            $limit = (int) $_REQUEST[ 'limit' ];
        }

        $this->updates[ ] = "Upload Directory: " . $upload_dir[ 'basedir' ] . "<br/>URL for Download: " . $url;

        flush ();

        try {

            $stmt = $wpdb->prepare (

                "SELECT
                    $wpdb->postmeta.post_id,
                    $wpdb->postmeta.meta_key,
                    $wpdb->postmeta.meta_value
                FROM
                    $wpdb->posts
                        INNER JOIN $wpdb->postmeta
                        ON $wpdb->posts.ID = $wpdb->postmeta.post_id
                WHERE
                    ($wpdb->posts.post_type = '%s') AND
                    ($wpdb->postmeta.meta_key = '_wp_attached_file')
                ",

                'attachment'

            );

            $results      = $wpdb->get_results ( $stmt, OBJECT );
            $filesMissing = $this->iterateResults ( $results );

            unset( $results );

            if ( $filesMissing === false or empty( $filesMissing ) ) {

                $this->notices[ ] = "No files appear to be missing.";

                return;

            }

            foreach ( $folderStruct as $year => $months ) {

                if ( ! file_exists ( $upload_dir[ 'basedir' ] . "/" . $year ) ) {
                    $this->updates[ ] = "Folder $year doesn't exist: creating it.<br />\n";
                    mkdir ( $upload_dir[ 'basedir' ] . "/" . $year );
                }

                foreach ( $months as $month ) {

//            echo "month: " . $month . "<br />";

                    if ( ! file_exists ( $upload_dir[ 'basedir' ] . "/" . $year . "/" . $month ) ) {
                        $this->updates[ ] = "Folder $month doesn't exist: creating it.<br />\n";
                        mkdir ( $upload_dir[ 'basedir' ] . "/" . $year . "/" . $month );
                    }

                }

            }

            flush ();

            if ( isset( $url ) and ! empty( $url ) ) {

                $failCount = 0;

                $ctx = stream_context_create ( array (
                    'http' =>
                        array (
                            'timeout' => 5, // In Seconds
                        )
                ) );

                $fileGetContentsWorks = true;

                foreach ( $filesMissing as $thisFile ) {

                    $remoteFile = rtrim ( $url, "/" ) . "/" . $thisFile;
                    $localFile  = $upload_dir[ 'basedir' ] . "/" . $thisFile;

                    if ( isset( $_REQUEST[ 'live' ] ) ) {

                        $loadedFile = false;
                        $fileSize   = 0;

                        if ( $fileGetContentsWorks === true ) {

                            $loadedFile = @file_get_contents ( $remoteFile, 0, $ctx, 0, 1 );

                            $fileSize = count ( $loadedFile );

                        }


                        if ( false !== $loadedFile and ( $fileSize > 10 ) ) {


                            $bytes = file_put_contents ( $localFile, file_get_contents ( $remoteFile, 0, $ctx, 0, 1 ) | LOCK_EX );

                            $this->updates[ ] = $remoteFile . " -> " . $localFile . " - " . $bytes . "B<br />\n";
                            flush ();

                        } else {

                            $fileGetContentsWorks = false;

                            $command = 'wget --tries=2 --read-timeout=7 ' . $proxy . $remoteFile . ' -O ' . $localFile . ' 2>&1; echo $?';
//                    echo $command;
                            $return = shell_exec ( $command );
//                    echo nl2br( $return );
                            $this->updates[ ] = $remoteFile . " -> " . $localFile . "<br />\n";
                            flush ();


                        }

                    } else {

                        $this->updates[ ] = $remoteFile . " -> " . $localFile . "<br />\n";
                        flush ();

                    }

                    flush ();

                }

            }

        } catch ( Exception $e ) {

            print "Error!: <br/>";
            var_dump ( $e );
            die();

        }

    }

    public function rebuildFolderStruct ()
    {

        $upload_dir = wp_upload_dir ();

        $folderStruct = $this->uploadFolders;

        foreach ( $folderStruct as $year => $months ) {

            if ( ! file_exists ( $upload_dir[ 'basedir' ] . "/" . $year ) ) {

                mkdir ( $upload_dir[ 'basedir' ] . "/" . $year );

            }

            foreach ( $months as $month ) {

                if ( ! file_exists ( $upload_dir[ 'basedir' ] . "/" . $year . "/" . $month ) ) {

                    mkdir ( $upload_dir[ 'basedir' ] . "/" . $year . "/" . $month );

                }

            }

        }

    }

    public function iterateResults ( $results = false, $limit = false )
    {

        if ( $results === false )
            return false;

        if ( false === $limit )
            $limit = 2000;

        $filesMissing = array ();

        foreach ( $results as $row ) {

            if ( count ( $filesMissing ) >= $limit ) {
                return $filesMissing;
            }

            if ( ! file_exists ( $this->uploadDir[ 'basedir' ] . "/" . $row->meta_value ) ) {

                $filesMissing[ ] = $row->meta_value;

//            echo $row->post_id . ": " . $row->meta_value . "<br />";
                $this->updates[ ] = $row->meta_value . "<br />\n";

                $exploded = explode ( '/', $row->meta_value );

                if ( ! isset( $this->uploadFolders[ $exploded[ 0 ] ] ) ) {

                    $this->uploadFolders[ $exploded[ 0 ] ] = array ();

                }

                if ( ! in_array ( $exploded[ 1 ], $this->uploadFolders[ $exploded[ 0 ] ] ) ) {

                    $this->uploadFolders[ $exploded[ 0 ] ][ ] = $exploded[ 1 ];

                }


            }

        }

        return $filesMissing;

    }








    ///////////////////////////////////////////////////////////////////////////
    //////////////////////////      ADMIN PAGES     ///////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    public function pageDefault ()
    {


        $this->btnCmd ( "GIT Pull", 'gitPull', true, false, "Arguements for git pull: ( origin dev / --all )" );
        $this->btnCmd ( "GIT Push", 'gitPush', true, false, "Arguements for git push: ( origin dev / --all )" );
        $this->btnCmd ( "GIT Status", 'gitStatus' );
        $this->btnCmd ( "GIT List Branches", 'gitBranch' );
        $this->btnCmd ( "GIT Add", 'gitAdd', true, true, "File: " );
        $this->btnCmd ( "GIT Commit", 'gitCommit', true, true, "Commit Message: " );
        $this->btnCmd ( "GIT Commit All", 'gitCommitAll', true, true, "Commit Message: " );
        $this->btnCmd ( "GIT Stash", 'gitStash' );
        $this->btnCmd ( "GIT Checkout Branch", 'gitCheckout', true, true, "Branch Name: " );
        $this->btnCmd ( "DB Dump", 'dbDump', true, true, "Filename (with .sql):" );
        $this->btnCmd ( "DB Import", 'dbImport', true, true, "Filename (with .sql):" );
        $this->btnCmd ( "Delete Database File", 'fileDelete', true, true, "Filename (with .sql):" );
        $this->btnCmd ( "Current Timestamp", 'timestamp' );
        $this->btnCmd ( "List SQL Files", 'dbListFiles' );
        $this->btnCmd ( "Delete ACF Category Meta", 'deleteACF_CategoryMeta' );
//        $this->btnCmd ( "Regenerate Missing Image Sizes", 'regenImagesOutput' );
        $this->btnCmd ( "PHP INFO", 'phpinfo' );

    }

    public function pageFindReplace ()
    {

        $this->frmFindReplace ();

    }

    public function pageImages ()
    {

        $this->frmImages ();

    }

    public function pageMigration ()
    {

        ?>
        <form action="<?php echo admin_url ( 'tools.php' ); ?>" method="get" style="margin: 10px 0;">
            <input type="hidden" name="page" value="<?php echo $this->slug; ?>"/>
            <input type="hidden" name="tab" value="<?php echo $_REQUEST[ 'tab' ]; ?>"/>
            <input type="hidden" name="hmaction" value="saveMigrationData"/>

            <label for="remote_host">Remote Host:</label>
            <input id="remote_host" name="remote_host" placeholder="Remote Host (http://test.com)" size="70" type="text"
                   value="<?= $this->getOption ( 'remote_host' ); ?>"/><br/>
            <label for="remote_key">Remote Key:</label>
            <input id="remote_key" name="remote_key" placeholder="Remote Key" size="70" type="text"
                   value="<?= $this->getOption ( 'remote_key' ); ?>"/><br/>
            <label for="this_key">This Key:</label>
            <input id="this_key" name="this_key" placeholder="Remote Key" size="70" type="text"
                   value="<?= $this->getOption ( 'this_key' ); ?>"/><br/>
            <br/>
            <input class="button button-primary" id="generate_key" type="button" value="Generate Key"/>
            <input class="button button-primary" type="submit" value="Save"/>
            <input class="button button-primary" id="test_connection" type="button" value="Test Connection"/>
            <br/>
            <br/>

        </form>

        <?php $this->btnCmd ( "Migrate Content", 'migrateContent', false, false, 'Are you sure?', true ); ?>

        <?php $this->btnCmd ( "Migrate Whole Site", 'migrateSite', false, false, 'Are you sure?', true ); ?>

        <br />

        <input class="button button-primary" id="ajax_send_database" type="button" value="Ajax Send Database"/>
        <input class="button button-primary" id="ajax_import_database" type="button" value="Ajax Import Database"/>


        <?php $this->javascriptNoficationsAPI(); ?>

        <?php $this->jsMigration(); ?>

        <?php

    }


    ///////////////////////////////////////////////////////////////////////////
    /////////////////////////      BUTTON METHODS     /////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    ////////////////////////////////// GIT ////////////////////////////////////

    public function gitPull ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git pull ' . Hallmark_Misc::removeQuotes ( Hallmark_Misc::args () ) );

    }

    public function gitPush ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git push ' . Hallmark_Misc::removeQuotes ( Hallmark_Misc::args () ) );

    }

    public function gitStatus ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git status' );

    }

    public function gitBranch ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git branch -a' );

    }

    public function gitCheckout ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git checkout ' . Hallmark_Misc::removeQuotes ( Hallmark_Misc::args () ) );

    }

    public function gitAdd ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git add ' . Hallmark_Misc::removeQuotes ( Hallmark_Misc::args () ) );
        $this->gitStatus ();

    }

    public function gitCommit ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git commit -m ' . Hallmark_Misc::args () );
        $this->gitStatus ();

    }

    public function gitCommitAll ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git commit -am ' . Hallmark_Misc::args () );
        $this->gitStatus ();

    }

    public function gitStash ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'git stash ' );

    }

    public function ensureSettings ()
    {

        $remoteHost = $this->getOption ( 'remote_host' );
        $remoteKey  = $this->getOption ( 'remote_key' );

        if ( ! $remoteHost or ! $remoteKey ) {
            $this->errors[ ] = "Remote Key or Host not set.";

            return false;
        }

        return true;
    }

    public function fileDelete ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'rm ' . Hallmark_Misc::args () );
        $this->dbListFiles ();


    }

    public function timestamp ()
    {

        $this->updates[ ] = Hallmark_Misc::shell ( 'date' );

    }

    public function migrateContent ()
    {

        if ( ! $this->ensureSettings () )
            return false;

        $tables = "";

        foreach ( $this->contentTables as $table ) {

            $tables .= $table . " ";

        }

        $file = $this->dbDumpTable ( $tables );
        $file = home_url ( '/' . $file );

        // Send Database
        $return = $this->sendDatabaseFile ( $file );
        $this->handleOutput ( $return );

//        return;

        // Send Images
        $return = $this->sendImageDownloads ();
        $this->handleOutput ( $return );

//        return;

        // Regen Images
        $return = $this->regenImageDownloads ();
        $this->handleOutput ( $return );

        return;


    }

    public function migrateSite ()
    {

        if ( ! $this->ensureSettings () )
            return false;

        $file = $this->dbDumpDatabase ();
        $file = home_url ( '/' . $file );

        // Send Database
        $return = $this->sendDatabaseFile ( $file );
        $this->handleOutput ( $return );

        // Send Images
        $return = $this->sendImageDownloads ();
        $this->handleOutput ( $return );

        // Regen Images
        $return = $this->regenImageDownloads ();
        $this->handleOutput ( $return );


    }

    public function saveMigrationData ()
    {

        $remoteHost = rtrim ( Hallmark_Misc::request ( 'remote_host' ), "/" );
        $remoteKey  = Hallmark_Misc::request ( 'remote_key' );
        $thisKey    = Hallmark_Misc::request ( 'this_key' );

        $this->saveOption ( 'remote_host', $remoteHost );
        $this->saveOption ( 'remote_key', $remoteKey );
        $this->saveOption ( 'this_key', $thisKey );


    }

    public function handleOutput ( $output )
    {

        if ( isset( $output->success ) and $output->success ) {

            if ( isset( $output->message ) and ! empty( $output->message ) )
                $this->updates[ ] = $output->message;

        } else {

            if ( isset( $output->message ) and ! empty( $output->message ) )
                $this->errors[ ] = $output->message;

        }

    }

    public function saveOption ( $key, $data = false )
    {

        wp_cache_delete ( $key, 'options' );

        if ( $data !== false and ! empty ( $data ) ) {
            $data = trim ( $data );

            return update_option ( $this->slug . '_' . $key, $data );
        } else {
            return update_option ( $this->slug . '_' . $key, "" );
        }

    }

    public function getOption ( $key )
    {

        return get_option ( $this->slug . '_' . $key );

    }



    public function regenImageDownloads ()
    {


        $remoteHost = $this->getOption ( 'remote_host' );
        $remoteKey  = $this->getOption ( 'remote_key' );

        $return = array (
            'success' => true,
            'message' => ''
        );

        $uploadDir = wp_upload_dir ();
        $url       = $remoteHost . "/wp-admin/admin-ajax.php?action=hm_migration&key=" .
            $remoteKey . "&image-regen=true";

        $this->notices[ ] = $url;


        set_time_limit ( $this->timeout );
        ini_set ( 'default_socket_timeout', $this->timeout );

        $ctx    = stream_context_create ( array (
                'http' => array (
                    'timeout' => $this->timeout
                )
            )
        );

        $return = file_get_contents ( $url, 0, $ctx );

        return json_decode ( $return );

    }

    public function sendImageDownloads ()
    {

        $remoteHost = $this->getOption ( 'remote_host' );
        $remoteKey  = $this->getOption ( 'remote_key' );

        $return = array (
            'success' => true,
            'message' => ''
        );

        $uploadDir = wp_upload_dir ();
        $url       = $remoteHost .
            "/wp-admin/admin-ajax.php?action=hm_migration&key=" .
            $remoteKey .
            "&upload_dir=" .
            $uploadDir[ 'baseurl' ];

        $this->notices[ ] = $url;

        $ctx    = stream_context_create ( array (
                'http' => array (
                    'timeout' => $this->timeout
                )
            )
        );

        $return = file_get_contents ( $url, 0, $ctx );

        return json_decode ( $return );
    }

    public function sendDatabaseFile ( $fileName = false )
    {

        if ( false === $fileName )
            return false;

        $remoteHost = $this->getOption ( 'remote_host' );
        $remoteKey  = $this->getOption ( 'remote_key' );

        if ( ! $remoteHost or ! $remoteKey ) {
            $this->errors[ ] = "Remote Key or Host not set.";

            return false;
        }

        $url = $remoteHost .
            "/wp-admin/admin-ajax.php?action=hm_migration&key=" .
            $remoteKey .
            "&file=" .
            $fileName .
            "&host=" .
            get_home_url ();

        $this->notices[ ] = $url;

        set_time_limit ( $this->timeout );
        ini_set ( 'default_socket_timeout', $this->timeout );
        $ctx    = stream_context_create ( array (
                'http' => array (
                    'timeout' => $this->timeout
                )
            )
        );
        $return = file_get_contents ( $url, 0, $ctx );

        return json_decode ( $return );

    }

    public function regenImagesOutput ()
    {

        $this->updates[ ] = $this->regenImages ();

    }

    public function regenImages ()
    {

        global $wpdb;

        $counter = array ( 'success' => 0, 'failed' => 0 );
        $images  = $wpdb->get_results ( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC" );
        $ids     = array ();
        $missingSourceImages = 0;

        $totalImages = count( $images );

//        echo "Total Images: " . $totalImages . "\n<br />\n";
//        var_dump( $images );
//        die;



        foreach ( $images as $image ) {

            // SK
            if ( $path = $this->AllImageSizesExist ( $image->ID ) ) {

//                $ids[ $image->ID ] = $path;

                $fullsizepath = get_attached_file( $image->ID );

                if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {

                    $missingSourceImages++;

                } else {

                    $ids[ $image->ID ] = $fullsizepath;

                }

            }

        }

        if ( ! function_exists ( 'wp_generate_attachment_metadata' ) )
            include ( ABSPATH . 'wp-admin/includes/image.php' );

        foreach ( $ids as $id => $path ) {

            $imageMeta = wp_generate_attachment_metadata ( $id, $path );

            if ( is_wp_error( $imageMeta ) or empty( $imageMeta ) ) {
                $counter[ 'failed' ]++;
            } else {
                $counter[ 'success' ]++;
                wp_update_attachment_metadata( $id, $imageMeta );
            }

        }

        $output = $counter[ 'success' ] . " image(s) regenerated out of " . $totalImages .  " total images.<br />";

        if ( $counter[ 'failed' ] > 0 ) {
            $output .= $counter[ 'failed' ] . " image(s) failed regenerating.<br />";
        }
        if ( $missingSourceImages > 0 ) {
            $output .= $missingSourceImages . " source image(s) missing.<br />";
        }

        return $output;

    }

    function AllImageSizesExist ( $imgID )
    {

        $uploadDir = wp_upload_dir ();

        $baseDir = $uploadDir[ 'basedir' ];

        $fullHost = 'http://' . $_SERVER[ 'HTTP_HOST' ] . "/";

        $imageData = wp_get_attachment_metadata ( $imgID );

        foreach ( $imageData[ 'sizes' ] as $size => $thisImgArray ) {

            $img = wp_get_attachment_image_src ( $imgID, $size );

            $filePath = $img[ 0 ];

            $localPath = str_replace ( $fullHost, ABSPATH, $filePath );


//            var_dump( $fullHost );
//            var_dump( ABSPATH );
//            var_dump( $filePath );
//            var_dump( $localPath );
//
//            var_dump( $baseDir );
//            die;
//
//            echo $baseDir . "/" . $imageData[ 'file' ];

            if ( false == file_exists ( $localPath ) ) {

                return $baseDir . "/" . $imageData[ 'file' ];

            }

        }

        return false;


    }

    public function phpinfo ()
    {

        ob_start ();
        phpinfo ();
        $variable = ob_get_contents ();
        ob_get_clean ();
        $this->updates[ ] = $variable;

    }

    //////////////////////////////// AJAX /////////////////////////////////

    public function ajaxMigration ()
    {

        $thisKey    = $this->getOption ( 'this_key' );
        $file       = Hallmark_Misc::request ( 'file' );
        $keyPosted  = Hallmark_Misc::request ( 'key' );
        $imageUrl   = Hallmark_Misc::request ( 'upload_dir' );
        $imageRegen = Hallmark_Misc::request ( 'image-regen' );
        $return     = array (
            'success' => true,
            'message' => ''
        );

        if ( $keyPosted !== $thisKey ) {
            echo json_encode ( array (
                'success' => false,
                'message' => 'Security breach attempt logged.'
            ) );
            die;
        }

        if ( $file ) {

            set_time_limit ( $this->timeout );

            $fullFile = basename ( $file );
            $fullFile = ABSPATH . $fullFile;

            $return[ 'message' ] .= $this->downloadFile ( $file ) . "<br />";

            sleep ( 10 );

            $return[ 'message' ] .= $this->importDumpFile ( $fullFile ) . "<br />";

            sleep( 5 );

            // Delete file so nobody can download it.
            unlink( $fullFile );

            if ( isset( $_REQUEST[ 'host' ] ) ) {

                $this->setFindParameter ( $_REQUEST[ 'host' ] );
                $this->setReplaceParameter ( str_replace ( 'http://', '', get_home_url () ) );
                $this->processFindReplace ();

            }

        }

        if ( $imageUrl ) {

            set_time_limit ( $this->timeout );

            $imagesImported = $this->importImageFiles ( array (
                'url' => $imageUrl
            ) );

            if ( false === $imagesImported ) {

                $return[ 'success' ] = false;
                $return[ 'message' ] .= "No images imported. <br />";

            } else {

                $return[ 'message' ] .= $imagesImported . " Images imported.<br />";

            }


        }

        if ( $imageRegen ) {

            set_time_limit ( $this->timeout );

            $return[ 'message' ] .= $this->regenImages ();

        }

        echo json_encode ( $return );
        die;

    }

    public function ajaxTestConnection() {

        $return = $this->sendAjax( 'remote_test_connection' );

        echo $return;
        die;

    }

    public function ajaxTestConnectionRemote() {

        $this->ajaxSecurity();

        echo json_encode( array(
            'success'   => true,
            'message'   => 'Connection Successful'
        ));
        die;

    }

    public function ajaxSendDatabase () {

        if ( ! $this->ensureSettings () )
            Hallmark_Misc::jsonResponse ( false, 'Missing migration settings' );

        $tables = "";

        foreach ( $this->contentTables as $table ) {

            $tables .= $table . " ";

        }

        $file = $this->dbDumpTable ( $tables );
        $file = home_url ( '/' . $file );

        echo $this->sendAjax( 'remote_receive_database', array(
            'file'  => $file,
            'host'  => get_home_url()
        ));

        die;

    }
    public function ajaxReceiveDatabase () {

        $this->ajaxSecurity();

        $file       = Hallmark_Misc::request ( 'file' );
        $host       = Hallmark_Misc::request ( 'host' );

        if( ! $file )
            Hallmark_Misc::jsonResponse ( false, 'File not specified' );

        if( $this->cmdDownloadFile( $file ) ) {
            Hallmark_Misc::jsonResponse ( true, 'Successfully downloaded database' );
        }

        Hallmark_Misc::jsonResponse ( false, 'Failed downloaded database' );

    }

    public function ajaxImportDatabase() {

        if ( ! $this->ensureSettings () )
            Hallmark_Misc::jsonResponse ( false, 'Missing migration settings' );

        echo $this->sendAjax( 'remote_import_database' );

        die;

    }

    public function ajaxRemoteImportDatabase() {

        $this->ajaxSecurity();

        $file = get_option( $this->dbOptionsKeyDbFile );

        if( $file === false )
            Hallmark_Misc::jsonResponse ( false, 'Cached Database File Missing' );

        if( ! file_exists( $file ) )
            Hallmark_Misc::jsonResponse ( false, 'Database File Missing on Remote Server' );

        $return  = "";
        $return2 = "";

        $this->cacheOptions ();

        $cmd = 'cd ' . ABSPATH . " && mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " < " . $file;

        exec ( $cmd, $return, $return2 );

        Hallmark_Misc::clearOptionsCache ();
        $this->optionsRestore ();

        unlink( $file );
        delete_option( $this->dbOptionsKeyDbFile );

        if ( $return2 == 0 )
            Hallmark_Misc::jsonResponse ( true, 'Successfully imported database file on remote. ');

        Hallmark_Misc::jsonResponse ( false, 'Failed to import database file.' );


    }

    public function ajaxSecurity( ) {

        $keyLocal    = $this->getOption ( 'this_key' );
        $keyPosted  = Hallmark_Misc::request ( 'key' );

        if( $keyLocal === $keyPosted )
            return true;

        echo json_encode( array(
            'success'   => false,
            'message'   => 'Security breach attempt logged.'
        ));
        die;

    }

    public function sendAjax( $action = false, $extraParams = array() ) {

        if( $action === false )
            return false;

        $params     = "";
        $remoteHost = $this->getOption ( 'remote_host' );
        $remoteKey  = $this->getOption ( 'remote_key' );

        foreach( $extraParams as $key => $value ) {

            $params .= "&" . $key . "=" . $value;

        }

        $return = array (
            'success' => true,
            'message' => ''
        );

        $uploadDir = wp_upload_dir ();
        $url       = $remoteHost . "/wp-admin/admin-ajax.php?action=" . $action . "&key=" .
            $remoteKey . $params;

        $this->notices[ ] = $url;


        set_time_limit ( $this->timeout );
        ini_set ( 'default_socket_timeout', $this->timeout );

        $ctx    = stream_context_create ( array (
                'http' => array (
                    'timeout' => $this->timeout
                )
            )
        );

        $return = file_get_contents ( $url, 0, $ctx );

        return $return;

    }

    public function cmdDownloadFile( $file ) {

        $newFile = basename ( $file );
        $newFile = ABSPATH . $newFile;
        $return  = "";
        $exitCode = "";
        $output  = "";

        if ( file_exists ( $newFile ) ) {
            unlink ( $newFile );
        }

        $cmd = 'cd ' . ABSPATH . ' && ';
        $cmd .= 'wget --tries=2 ' . $file;

        set_time_limit ( $this->timeout );

        exec ( $cmd, $return, $exitCode );

        if ( $exitCode == 0 ) {
            update_option( $this->dbOptionsKeyDbFile, $newFile );
            return true;
        }

        return false;

    }

    public function downloadFile ( $file )
    {

        $output = "";

        if ( $this->cmdDownloadFile( $file ) ) {

            $output .= "<br />Successfully downloaded database file on production: " . $localFile . "<br />";

        } else {

            $output .= "<br />" . $return2 . ": Failed downloading database file on production.<br />";
            $output .= $returnStr;

        }

        return $output;

    }

    public function importDumpFile ( $file = false )
    {

        if ( false === $file )
            return false;

        if ( ! file_exists( $file ) )
            return "File doesn't exist: " . $file . "<br />";

        $output  = "";
        $return  = "";
        $return2 = "";

        $this->cacheOptions ();

        $cmd = 'cd ' . ABSPATH . " && mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " < " . $file;

        exec ( $cmd, $return, $return2 );

        if ( $return2 == 0 ) {

            $output = "Successfully imported database file.";

        } else {

            $output = "Failed importing database file: " . $file . "<br />";
            $output .= print_r ( $return, true );

        }

        Hallmark_Misc::clearOptionsCache ();
        $this->optionsRestore ();

        return $output;

    }

    public function dbDumpTable ( $table = false, $file = false )
    {

        if ( false === $table )
            return false;

        if ( false === $file )
            $file = "combined.sql";

        $cmd = 'cd ' . ABSPATH . ' && ' . "mysqldump --add-drop-table -h " . DB_HOST . " -u " . DB_USER . " -p" .
            DB_PASSWORD . " " . DB_NAME . " " . $table . " > " . $file;

        exec ( $cmd );

        sleep ( 1 );

        return $file;

    }

    public function dbDumpDatabase ( $sqlFile = false )
    {

        if ( false === $sqlFile )
            $sqlFile = DB_NAME . ".sql";

        $cmd = 'cd ' . ABSPATH . ' && ' . "mysqldump --add-drop-table -h " . DB_HOST . " -u " . DB_USER . " -p" .
            DB_PASSWORD . " " . DB_NAME . " > " . $sqlFile;

        exec ( $cmd );

        sleep ( 1 );

        return $sqlFile;

    }

    public function downloadDump ( $file )
    {


    }

    public function dbDump ( $fileName = false, $showFiles = true )
    {


        $args = Hallmark_Misc::args ();

        if ( empty( $args ) ) {
            if ( $fileName ) {
                $args = $fileName;
            } else {
                return false;
            }
        }

        $args = Hallmark_Misc::removeQuotes ( $args );

        $cmd = 'cd ' . ABSPATH . ' && ' . "mysqldump --add-drop-table -h " . DB_HOST . " -u " . DB_USER . " -p" .
            DB_PASSWORD . " " . DB_NAME . " > " . $args;

        $returnVar = "unkown";
        passthru ( $cmd, $returnVar );

        if ( 0 === $returnVar ) {

            $this->updates[ ] = "Database successfully exported to: " . ABSPATH . $args;
            if ( $showFiles )
                $this->dbListFiles ();

            return true;

        } else {

            $this->errors[ ] = "Error Code Returned: " . $returnVar;

            return false;

        }


    }

    public function dbImport ( $fileName = false )
    {

        $success = true;

        $args = Hallmark_Misc::args ();

        if ( empty( $args ) ) {

            if ( $fileName ) {

                $args = $fileName;

            } else {

                $this->notices[ ] = "Filename empty!";
                $success          = false;

                return $success;

            }

        } else {

            $args = Hallmark_Misc::removeQuotes ( $args );

        }


        $file = ABSPATH . $args;

        if ( ! file_exists ( $file ) ) {

            $this->notices[ ] = "File Doesn't Exist: " . ABSPATH . $args;

            return $success;
        }

        $cmd       = 'cd ' . ABSPATH . ' && ' . "mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASSWORD . "
<< EOF
drop database " . DB_NAME . ";
create database " . DB_NAME . ";
EOF
";
        $returnVar = "unknown";
        passthru ( $cmd, $returnVar );

        if ( $returnVar === 0 ) {

            $this->updates[ ] = $returnVar . ": Successfully deleted database.";

        } else {

            $this->errors[ ] = $returnVar . ": Error !";
            $success         = false;

        }

        $cmd = 'cd ' . ABSPATH . ' && ' . "mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " < " . $file;

        $returnVar = "unkown";

        passthru ( $cmd, $returnVar );

        if ( $returnVar === 0 ) {

            $this->updates[ ] = $returnVar . ": Successfully imported database.";

        } else {

            if ( $returnVar == 127 ) {

                $this->errors[ ] = $returnVar . ": 'mysql' command not found! ";

            } else {
                $this->errors[ ] = "Failed Importing Database - Error Code Returned: " . $returnVar;
            }


            $success = false;

        }

        return $success;

    }

    public function dbListFiles ()
    {

        $this->timestamp ();
        $this->updates[ ] = Hallmark_Misc::shell ( 'ls -alh | grep .sql' );

    }

    //////////////////////////////// CLEAN-UP /////////////////////////////////

    public function deleteACF_CategoryMeta ()
    {

        global $wpdb;

        $prepared = $wpdb->prepare ( "
DELETE
FROM
	$wpdb->options
WHERE
    option_name REGEXP %s;

        ",
            "category[_][0-9]*1[0-]*"
        );

        $output = $wpdb->query ( $prepared );

        if ( isset( $wpdb->last_error ) and ! empty( $wpdb->last_error ) )
            $this->errors[ ] = $wpdb->last_error;

        $this->updates[ ] = print_r ( $output . " rows deleted", true );

    }

    ///////////////////////////////////////////////////////////////////////////
    //////////////////////////     FIND / REPLACE    //////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    public function processFindReplace ()
    {

        if ( $this->replaceQuery ) {

            $this->replace ();

        } else {

            $this->find ();

        }

    }

    public function find ()
    {

        global $wpdb;

        $sql = "

        SELECT
	        COUNT(*)
        FROM
	        $wpdb->posts
        WHERE
	        post_content LIKE %s

        ";

        $fullSQL = $wpdb->prepare ( $sql, $this->findFuzzy );

        $postsRowCount = $wpdb->get_var ( $fullSQL );

//        $this->updates[] = $fullSQL;
        $this->updates[ ] = "wp_posts: " . $postsRowCount . " rows found.";


        $sql = "SELECT
	        COUNT(*)
        FROM
	        $wpdb->postmeta
        WHERE
	        (meta_value LIKE %s) AND
	        (meta_key <> %s) AND
            (meta_key <> %s) AND
	        (meta_key <> %s)

        ";

        $fullSQL = $wpdb->prepare (
            $sql,
            $this->findFuzzy,
            '_preflight_data',
            '_batch_deploy_messages',
            '_ramp_mm_comp_data'
        );

        $postsRowCount = $wpdb->get_var ( $fullSQL );

//        $this->updates[] = $fullSQL;
        $this->updates[ ] = "post_meta: " . $postsRowCount . " rows found.";


    }


    public function replace ()
    {

        global $wpdb;

        $preSQL = "

        UPDATE
          $wpdb->posts
        SET
          post_content = replace(post_content, %s, %s)
        WHERE
	        post_content LIKE %s

        ";

        $sql = $wpdb->prepare (
            $preSQL,
            $this->findQuery,
            $this->replaceQuery,
            $this->findFuzzy
        );

        $rowsUpdated = $wpdb->query ( $sql );

        $this->updates[ ] = "$rowsUpdated rows updated in wp_posts table.";

        $preSQL = "

        UPDATE
          $wpdb->postmeta
        SET
          meta_value = replace(meta_value, %s, %s)
        WHERE
	        (meta_value LIKE %s) AND
	        (meta_key <> %s) AND
            (meta_key <> %s) AND
	        (meta_key <> %s)

        ";

        $sql = $wpdb->prepare (
            $preSQL,
            $this->findQuery,
            $this->replaceQuery,
            $this->findFuzzy,
            '_preflight_data',
            '_batch_deploy_messages',
            '_ramp_mm_comp_data'
        );

        $rowsUpdated = $wpdb->query ( $sql );

        $this->updates[ ] = "$rowsUpdated rows updated in wp_postmeta table.";

    }

    ///////////////////////////////////////////////////////////////////////////
    //////////////////////////      WP NOTICE API     /////////////////////////
    ///////////////////////////////////////////////////////////////////////////

    public function adminNotices ()
    {

        if ( $this->errors ) {

            $this->handleNotices ( $this->errors, 'error' );

        }
        if ( $this->notices ) {

            $this->handleNotices ( $this->notices, 'update-nag' );

        }
        if ( $this->updates ) {

            $this->handleNotices ( $this->updates );

        }

    }

    /**
     * @param string|array $notices a string of output, or an array of strings to output separately
     * @param string       $class   options: updated, update-nag, error
     */
    public function handleNotices ( $notices, $class = "updated" )
    {

        if ( $notices ) {

            if ( is_string ( $notices ) ) {

                Hallmark_Misc::adminNotice ( $notices, $class );

            } elseif ( is_array ( $notices ) ) {

                foreach ( $notices as $notice ) {

                    Hallmark_Misc::adminNotice ( $notice, $class );

                }

            }

        }

    }

    

    ///////////////////////////////////////////////////////////////////////////
    //////////////////////////    UTILITY METHODS    //////////////////////////
    ///////////////////////////////////////////////////////////////////////////



    

    public function cacheOptions ()
    {

        foreach ( $this->optionsToPersist as $option ) {

            $this->optionsCache[ $option ] = $this->getOption ( $option );

        }

    }

    public function optionsRestore ()
    {

        foreach ( $this->optionsToPersist as $option ) {

            if ( isset( $this->optionsCache[ $option ] ) )
                $this->saveOption ( $option, $this->optionsCache[ $option ] );

        }

    }

    public function javascriptNoficationsAPI() {

        ?>
        <script>

            (function ($) {

                window.notification = function( notice, classes) {

                    var strGUID = guid();

                    if( typeof classes == "undefined" ) {
                        classes = "updated";
                    }
                    if( typeof notice == "undefined" ) {
                        notice = "";
                    }

                    var html = '<div class="' + classes + '" id="' + strGUID + '" ><p>' + notice + '</p></div>';
                    $('#wpbody .wrap').append(html);

                    setTimeout( function() {

                        $('#' + strGUID).fadeOut(1000, function() {
                            $(this).remove();
                        });

                    }, 5000);

                }

            })(jQuery);

        </script>
    <?php

    }

    public function jsMigration() {

        ?>
        <script type="text/javascript" src="<?= plugin_dir_url( __FILE__ ); ?>migration.js"></script>

    <?php

    }

}






$hallmarkTools = new Hallmark_Wordpress_Tools();
