<?php
/**
 * Plugin Name:       LU3G Manutenzione
 * Plugin URI:        https://github.com/lu3gofficial/lu3g-manutenzione
 * Description:       Modalità manutenzione: i visitatori vedono una schermata "Sito in manutenzione" con il logo LU3G; dal lucchetto in alto a destra chi ha un account accede con le credenziali del sito e lo vede normalmente.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            LU3G Agenzia Web
 * Author URI:        https://lu3g.it
 * License:           GPL-2.0-or-later
 * Text Domain:       lu3g-manutenzione
 * Domain Path:       /languages
 * Update URI:        https://github.com/lu3gofficial/lu3g-manutenzione
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LU3G_MANUTENZIONE_VERSION', '1.0.0' );
define( 'LU3G_MANUTENZIONE_FILE', __FILE__ );
define( 'LU3G_MANUTENZIONE_PATH', plugin_dir_path( __FILE__ ) );
define( 'LU3G_MANUTENZIONE_URL', plugin_dir_url( __FILE__ ) );

// Aggiornamenti da GitHub (file generato da lu3gofficial/lu3g-updater, non modificarlo a mano).
require_once LU3G_MANUTENZIONE_PATH . 'includes/class-updater.php';
LU3G_Manutenzione_Updater::init( __FILE__ );

require_once LU3G_MANUTENZIONE_PATH . 'includes/class-settings.php';
require_once LU3G_MANUTENZIONE_PATH . 'includes/class-cache.php';
require_once LU3G_MANUTENZIONE_PATH . 'includes/class-gate.php';

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'lu3g-manutenzione', false, dirname( plugin_basename( LU3G_MANUTENZIONE_FILE ) ) . '/languages' );
		LU3G_Manutenzione_Gate::init();
		if ( is_admin() ) {
			require_once LU3G_MANUTENZIONE_PATH . 'includes/class-admin.php';
			LU3G_Manutenzione_Admin::init();
		}
	}
);

// Disattivando il plugin il sito torna visibile: svuoto la cache, così nessuno vede più la schermata salvata.
register_deactivation_hook(
	__FILE__,
	function () {
		if ( LU3G_Manutenzione_Settings::get()['enabled'] ) {
			LU3G_Manutenzione_Cache::purge();
		}
	}
);
