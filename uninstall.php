<?php
/**
 * Disinstallazione: rimuove l'opzione del plugin (in ogni sito della rete, se multisito).
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $lu3g_m_site ) {
		switch_to_blog( $lu3g_m_site );
		delete_option( 'lu3g_manutenzione' );
		restore_current_blog();
	}
} else {
	delete_option( 'lu3g_manutenzione' );
}
delete_transient( 'lu3g_manutenzione_release' );
