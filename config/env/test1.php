<?php

//these 2 override the options table in DB
define('WP_HOME', 'http://your-project.local');
define('WP_SITEURL', WP_HOME . '/site/');

define('WP_CONTENT_DIR', APP_ROOT . '/public/content');
define('WP_CONTENT_URL', WP_HOME . '/content');

define('WP_DEBUG', true);


// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'database_name_here');

/** MySQL database username */
define('DB_USER', 'username_here');

/** MySQL database password */
define('DB_PASSWORD', 'password_here');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');


/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '$4dZ,%<|O(m;!3Jf=-PNe{Z8-A!_*x!|i>|H7a:gUiU8LzkAA$bnbAEE($C&.={W');
define('SECURE_AUTH_KEY',  'Z`^T>$BP|e2uPI4;RBr_VM%3}2Xz(Ya2L?BvQ[||MNgUYJA^%VN5)_J5(U2C}G^?');
define('LOGGED_IN_KEY',    'wL1O-?.F-{0,I(D3[-OQ4?g&#}O2Ocrd0y,Q]3R4#+?FMi-Un{iI=B^U~^sXj0Ro');
define('NONCE_KEY',        ',~)lwoKpNIi+#TM/D5Ebn0+2d@k?8|[W|*90.;>N@w8`Q#a!A=Q#IYs-M8Y-n6:K');
define('AUTH_SALT',        'f45deVGy&Of1W!b=tRDbO$)[ZLeXZ-oJ:`QS{jLt<N!A1Dg1MMtN>_2}hyP+1-xg');
define('SECURE_AUTH_SALT', '0R/+s{!8LA.ls3-8V2X7*08IrJfX[z@,{^6lK(V^H+0o==xb#NL^!ov9Jc+51+L:');
define('LOGGED_IN_SALT',   '9;_sJb}|e5t-M9/-;1[c:&c[k8*.#ej/.!5vbw|tr/|mb<rYeFG@)K_iIa:r Et ');
define('NONCE_SALT',       ']@%?^`4Ivc--2VF/i)Y,/%|*i|M+U7z^l[gQ&Sh-0|NiPHLq3mokvsVL&N9+- 8S');
