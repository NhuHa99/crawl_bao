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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'baonhanh' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '0VlOcIJ`{[]HUPczPa^>%PgD>yN?Sa(mt6X5z2/^=X,iFNXYJ]37NL.X}SLf-KGP' );
define( 'SECURE_AUTH_KEY',  'w:_9_9TDo&JudT*liu>Xk!Kb&$f}mErIe#K|{@~N!B*4J-cA5F|a8Rmy*/Q#H7;}' );
define( 'LOGGED_IN_KEY',    'f(a9>qIP?DMUuA3:~!)^^tjt[F+TSrDV?Z>< 2/]omSM,l]G>bueR%=:u(6`_]fP' );
define( 'NONCE_KEY',        'ospso*t{q];~>q#}#3Y1n_bC_y):^1nnmR3!S<N;mh2NWYste?e,GAij$)U}MnXn' );
define( 'AUTH_SALT',        '<f=Ry Yq0ic~J?yNdaNVC<hv0-5xCM5Bgnwqbl>}J2U2FfP;d,Aw^rp&qN]|n>R<' );
define( 'SECURE_AUTH_SALT', 'Bm_wT2oZFv8ERMHS2lP)Y;QT(y3V40~$=jc1 ]7<SdcVX:o=_%dp.,|AGGSx%eiJ' );
define( 'LOGGED_IN_SALT',   'Z8%uUn_r~]UPXIfq-x]9(V<Xk/Ay!b%JtYCJeUPI)pI<Y/JoMPxA9;Nu]a,;1$iE' );
define( 'NONCE_SALT',       '6U<Pw-cS;({xz-yK4X=G+Y%bj}wJ_k;4ZdQ/r@fD%KNI}ujFl?=d:C0mf?,?It <' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'bn_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

define ('WP_CONTENT_FOLDERNAME', 'bao-nhanh');
define ('WP_CONTENT_DIR', ABSPATH . WP_CONTENT_FOLDERNAME) ;

define('WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST'] . '/baonhanh/');  
define('WP_CONTENT_URL', WP_SITEURL . WP_CONTENT_FOLDERNAME);

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
