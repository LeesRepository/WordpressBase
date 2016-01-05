<?php

class Hallmark_Wordpress_Migration_Tool
{

    public $slug = "hallmark-content-sync";

    public function __construct ()
    {

        add_action ( 'admin_menu', array ( $this, 'adminMenu' ) );
        add_action ( 'current_screen', array ( $this, 'currentScreen' ) );

    }

    public function adminMenu ()
    {

        add_management_page ( 'Content Sync', 'Content Sync', 'manage_options', $this->slug, array ( $this, 'view' ) );

    }

    public function currentScreen ()
    {

        $curScreen = get_current_screen ();

        if ( $curScreen->base = 'tools_page_' . $this->slug ) {

            if ( isset( $_REQUEST[ 'contentsync' ] ) ) {

                global $hallmarkTools;

                if ( isset( $hallmarkTools ) ) {

                    $hallmarkTools->migrateContent ();

                }

            }

        }

    }

    public function view ()
    {

        ?>
        <div class="wrap">
            <h2>
                Hallmark Content Sync
            </h2>

            <form action="<?php echo admin_url ( 'tools.php' ); ?>" method="get" style="margin: 10px 0;">
                <input type="hidden" name="page" value="<?php echo $this->slug; ?>"/>
                <input type="hidden" name="contentsync" value="true"/>
                <input class="button button-primary" id="contentSync" type="submit" value="Sync To Production"/>
            </form>
        </div>
    <?php

    }


}

$hallmarkContentSync = new Hallmark_Wordpress_Migration_Tool();
