<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

define('APP_ROOT', dirname(__DIR__));

// would like to talk with James about setting environment varibles in httpd, which would be less fragile than this
//define('APP_ENV', getenv('APPLICATION_ENV') );

switch(true) {

    case(strpos($_SERVER['HTTP_HOST'], 'your-project-stage.hmkb2c.com') !== false ):
        define('APP_ENV', 'stage' );

    case(strpos($_SERVER['HTTP_HOST'], 'your-project-test1.hmkb2c.com') !== false ):
        define('APP_ENV', 'test1' );

    case(strpos($_SERVER['HTTP_HOST'], 'your-project.hallmark.com') !== false ):
        define('APP_ENV', 'production' );

    case(strpos($_SERVER['HTTP_HOST'], 'wp-base.local') !== false ):
        define('APP_ENV', 'local' );
}

if (file_exists(APP_ROOT.'/config/env/local.php')) {
    require APP_ROOT . '/config/env/local.php';
} else {
    require APP_ROOT . '/config/env/' . APP_ENV . '.php';
}

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
