<?php
/**
 * Il "cancello": con la manutenzione accesa mostra la schermata a chi non può vedere il sito.
 *
 * - Chi vede il sito: gli utenti collegati con il permesso scelto nel pannello (predefinito: chi lavora sul sito).
 * - Tutti gli altri: schermata con stato HTTP 503 e Retry-After (Google capisce che è temporaneo e non toglie le
 *   pagine dall'indice), mai salvata in cache.
 * - Restano raggiungibili: wp-login.php, la bacheca, admin-ajax, cron, WP-CLI, robots.txt e favicon.
 * - API REST: per chi non può vedere il sito rispondono 503 (altrimenti i contenuti sarebbero leggibili da lì).
 * - Lucchetto: il modulo invia a wp-login.php (funzionano captcha, limiti di tentativi, 2FA e indirizzi di login
 *   personalizzati). Se le credenziali sono sbagliate si torna alla schermata con l'errore, non alla pagina di login.
 * - Emergenza: define( 'LU3G_MANUTENZIONE_OFF', true ); in wp-config.php spegne la schermata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LU3G_Manutenzione_Gate {

	const PREVIEW = 'lu3g_manutenzione_anteprima';
	const ERROR   = 'lu3g-login';

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_show' ), 0 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'rest' ), 101 );
		add_action( 'wp_login_failed', array( __CLASS__, 'login_failed' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 100 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'admin_bar_style' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_bar_style' ) );
	}

	public static function is_active() {
		if ( defined( 'LU3G_MANUTENZIONE_OFF' ) && LU3G_MANUTENZIONE_OFF ) {
			return false;
		}
		return (bool) LU3G_Manutenzione_Settings::get()['enabled'];
	}

	public static function maybe_show() {
		// Anteprima per gli amministratori (anche con la manutenzione spenta).
		$preview = isset( $_GET[ self::PREVIEW ] ) && current_user_can( 'manage_options' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo visualizzazione
		if ( ! $preview ) {
			if ( ! self::is_active() || LU3G_Manutenzione_Settings::user_can_bypass() ) {
				return;
			}
			if ( is_robots() || ( function_exists( 'is_favicon' ) && is_favicon() ) ) {
				return;
			}
			/** Filtro per lasciare passare richieste particolari (true = mostra il sito). */
			if ( apply_filters( 'lu3g_manutenzione_allow_request', false ) ) {
				return;
			}
		}
		self::render( $preview );
		exit;
	}

	/** Indirizzo della pagina richiesta, senza il parametro dell'errore. */
	private static function current_url() {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$url  = set_url_scheme( 'http://' . $host . $uri );
		return remove_query_arg( array( self::ERROR ), $url );
	}

	/** Logo da mostrare: SVG LU3G inline oppure URL di un'immagine. */
	private static function logo( array $s ) {
		$id = 0;
		if ( 'site' === $s['logo'] ) {
			$id = (int) get_theme_mod( 'custom_logo' );
		} elseif ( 'custom' === $s['logo'] ) {
			$id = (int) $s['logo_id'];
		}
		$url = $id ? wp_get_attachment_image_url( $id, 'medium_large' ) : '';
		if ( $url ) {
			return array( 'type' => 'img', 'value' => $url );
		}
		return array( 'type' => 'svg', 'value' => (string) file_get_contents( LU3G_MANUTENZIONE_PATH . 'assets/lu3g-logo.svg' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	private static function render( $preview ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // WP Rocket, W3TC, WP Super Cache, LiteSpeed…
		}
		if ( ! $preview ) {
			status_header( 503 );
			header( 'Retry-After: 3600' );
		}
		nocache_headers();
		header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

		$s        = LU3G_Manutenzione_Settings::get();
		$logo     = self::logo( $s );
		$url      = self::current_url();
		$user     = wp_get_current_user();
		$logged   = $user && $user->exists();
		$no_entry = $logged && ! LU3G_Manutenzione_Settings::user_can_bypass() && ! $preview;
		$error    = isset( $_GET[ self::ERROR ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo visualizzazione
		$action   = site_url( 'wp-login.php', 'login_post' );
		include LU3G_MANUTENZIONE_PATH . 'includes/screen.php';
	}

	/** API REST chiuse a chi non può vedere il sito. */
	public static function rest( $result ) {
		if ( ! empty( $result ) || ! self::is_active() || LU3G_Manutenzione_Settings::user_can_bypass() ) {
			return $result;
		}
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';
		/** Filtro per lasciare aperte alcune rotte (true = aperta). */
		if ( apply_filters( 'lu3g_manutenzione_rest_allowed', false, $route ) ) {
			return $result;
		}
		return new WP_Error( 'lu3g_manutenzione', __( 'Sito in manutenzione.', 'lu3g-manutenzione' ), array( 'status' => 503 ) );
	}

	/** Credenziali sbagliate dal lucchetto: si torna alla schermata con l'errore. */
	public static function login_failed() {
		if ( empty( $_POST['lu3g_manutenzione'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- wp-login.php non usa nonce; solo un rinvio
			return;
		}
		$to = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ), home_url( '/' ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_safe_redirect( add_query_arg( self::ERROR, '1', $to ) );
		exit;
	}

	/** Promemoria rosso nella barra di amministrazione finché la manutenzione è accesa. */
	public static function admin_bar( $bar ) {
		if ( ! self::is_active() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'lu3g-manutenzione',
				'title' => '<span class="ab-icon dashicons dashicons-lock" aria-hidden="true"></span>' . esc_html__( 'Manutenzione attiva', 'lu3g-manutenzione' ),
				'href'  => admin_url( 'options-general.php?page=lu3g-manutenzione' ),
				'meta'  => array( 'title' => __( 'I visitatori vedono la schermata di manutenzione', 'lu3g-manutenzione' ) ),
			)
		);
	}

	public static function admin_bar_style() {
		if ( is_admin_bar_showing() && self::is_active() && current_user_can( 'manage_options' ) ) {
			wp_add_inline_style( 'admin-bar', '#wpadminbar #wp-admin-bar-lu3g-manutenzione>.ab-item{background:#dc2626;color:#fff}#wpadminbar #wp-admin-bar-lu3g-manutenzione>.ab-item .ab-icon:before{color:#fff;top:2px}#wpadminbar #wp-admin-bar-lu3g-manutenzione:hover>.ab-item{background:#b91c1c;color:#fff}' );
		}
	}
}
