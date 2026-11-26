<?php
/**
 * Svuota la cache delle pagine quando la manutenzione si accende o si spegne.
 *
 * Perché: i plugin di cache (WP Rocket, LiteSpeed…) servono le pagine salvate PRIMA che WordPress parta. Accendendo la
 * manutenzione senza svuotare, i visitatori continuerebbero a vedere il sito dalla cache; spegnendola, vedrebbero ancora
 * la schermata. La schermata stessa non viene mai salvata in cache (DONOTCACHEPAGE e intestazioni no-cache).
 * Le cache esterne (Cloudflare, Varnish del provider) vanno svuotate dal loro pannello se non rispettano le intestazioni.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LU3G_Manutenzione_Cache {

	/** @return string[] nomi delle cache svuotate */
	public static function purge() {
		$done = array();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
			$done[] = 'WP Rocket';
		}
		if ( defined( 'LSCWP_V' ) || has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
			$done[] = 'LiteSpeed Cache';
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			$done[] = 'W3 Total Cache';
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$done[] = 'WP Super Cache';
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
			$done[] = 'SiteGround Optimizer';
		}
		$actions = array(
			'wpfc_clear_all_cache'               => 'WP Fastest Cache',
			'breeze_clear_all_cache'             => 'Breeze',
			'cache_enabler_clear_complete_cache' => 'Cache Enabler',
			'wphb_clear_page_cache'              => 'Hummingbird',
			'rt_nginx_helper_purge_all'          => 'Nginx Helper',
		);
		foreach ( $actions as $hook => $name ) {
			if ( has_action( $hook ) ) {
				do_action( $hook );
				$done[] = $name;
			}
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		/** Per aggiungere altre cache. */
		do_action( 'lu3g_manutenzione_purge_cache' );
		return $done;
	}
}
