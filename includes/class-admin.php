<?php
/**
 * Pannello LU3G → Manutenzione (sotto il menu di LU3G Hub; senza Hub, voce "LU3G Manutenzione").
 * Salvataggio da admin-post con nonce e permesso manage_options. Se lo stato cambia (o cambiano i testi a manutenzione
 * accesa) svuota la cache delle pagine e lo dice nel messaggio.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LU3G_Manutenzione_Admin {

	const PAGE = 'lu3g-manutenzione';
	const CAP  = 'manage_options';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 ); // dopo LU3G Hub (priorità 10)
		add_action( 'admin_post_lu3g_manutenzione_save', array( __CLASS__, 'save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( LU3G_MANUTENZIONE_FILE ), array( __CLASS__, 'links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	public static function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::PAGE ), $args ), admin_url( 'admin.php' ) );
	}

	/** Sotto il menu "LU3G" di LU3G Hub, come gli altri plugin LU3G; senza Hub, voce propria "LU3G Manutenzione". */
	public static function menu() {
		global $admin_page_hooks;
		if ( isset( $admin_page_hooks['lu3g-hub'] ) ) {
			$hook = add_submenu_page( 'lu3g-hub', 'LU3G Manutenzione', __( 'Manutenzione', 'lu3g-manutenzione' ), self::CAP, self::PAGE, array( __CLASS__, 'render' ) );
		} else {
			$svg  = (string) file_get_contents( LU3G_MANUTENZIONE_PATH . 'assets/lu3g-icon.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$hook = add_menu_page( 'LU3G Manutenzione', 'LU3G Manutenzione', self::CAP, self::PAGE, array( __CLASS__, 'render' ), 'data:image/svg+xml;base64,' . base64_encode( $svg ), 83 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		}
		add_action( 'load-' . $hook, array( __CLASS__, 'load' ) );
	}

	public static function load() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter(
			'admin_body_class',
			function ( $c ) {
				return $c . ' lu3g-manutenzione-page';
			}
		);
	}

	public static function assets() {
		wp_enqueue_media();
		wp_enqueue_style( 'lu3g-manutenzione-admin', LU3G_MANUTENZIONE_URL . 'assets/css/admin.css', array(), LU3G_MANUTENZIONE_VERSION );
		wp_add_inline_style( 'lu3g-manutenzione-admin', '@font-face{font-family:"LU3G Manrope";src:url(' . esc_url( LU3G_MANUTENZIONE_URL . 'assets/fonts/manrope-latin-wght.woff2' ) . ') format("woff2");font-weight:200 800;font-display:swap}' );
		wp_enqueue_script( 'lu3g-manutenzione-admin', LU3G_MANUTENZIONE_URL . 'assets/js/admin.js', array(), LU3G_MANUTENZIONE_VERSION, true );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Impostazioni', 'lu3g-manutenzione' ) . '</a>' );
		return $links;
	}

	/** Promemoria in Bacheca e in Plugin finché la manutenzione è accesa. */
	public static function notice() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins' ), true ) || ! LU3G_Manutenzione_Gate::is_active() || ! current_user_can( self::CAP ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Manutenzione attiva:', 'lu3g-manutenzione' ) . '</strong> ' . esc_html__( 'i visitatori vedono la schermata di manutenzione.', 'lu3g-manutenzione' ) . ' <a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Gestisci', 'lu3g-manutenzione' ) . '</a></p></div>';
	}

	/* ================================================================ salvataggio */

	public static function save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Non hai i permessi per questa operazione.', 'lu3g-manutenzione' ), 403 );
		}
		check_admin_referer( 'lu3g_manutenzione_save' );
		$old = LU3G_Manutenzione_Settings::get();
		$in  = isset( $_POST['lu3g_m'] ) && is_array( $_POST['lu3g_m'] ) ? wp_unslash( $_POST['lu3g_m'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- pulito in sanitize()
		$new = LU3G_Manutenzione_Settings::sanitize( $in );
		update_option( LU3G_Manutenzione_Settings::OPTION, $new, true );

		// Svuoto la cache quando cambia ciò che vedono i visitatori.
		$changed = $old['enabled'] !== $new['enabled'] || ( $new['enabled'] && $old !== $new );
		$caches  = $changed ? LU3G_Manutenzione_Cache::purge() : array();
		$state   = $old['enabled'] === $new['enabled'] ? 'saved' : ( $new['enabled'] ? 'on' : 'off' );
		wp_safe_redirect(
			self::url(
				array(
					'lm_msg'   => $state,
					'lm_cache' => $changed ? ( $caches ? rawurlencode( implode( ', ', $caches ) ) : 'none' ) : '',
				)
			)
		);
		exit;
	}

	/* ================================================================ pagina */

	private static function toggle( $name, $label, $on, $help = '' ) {
		printf(
			'<label class="lm-toggle"><input type="checkbox" name="lu3g_m[%1$s]" value="1" %2$s><span class="lm-toggle__ui" aria-hidden="true"></span><span><strong>%3$s</strong>%4$s</span></label>',
			esc_attr( $name ),
			checked( $on, true, false ),
			esc_html( $label ),
			$help ? '<small>' . esc_html( $help ) . '</small>' : ''
		);
	}

	public static function render() {
		$s      = LU3G_Manutenzione_Settings::get();
		$active = LU3G_Manutenzione_Gate::is_active();
		$forced = defined( 'LU3G_MANUTENZIONE_OFF' ) && LU3G_MANUTENZIONE_OFF;
		$logo   = (string) file_get_contents( LU3G_MANUTENZIONE_PATH . 'assets/lu3g-logo.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$msg    = isset( $_GET['lm_msg'] ) ? sanitize_key( wp_unslash( $_GET['lm_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cache  = isset( $_GET['lm_cache'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['lm_cache'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$custom = $s['logo_id'] ? wp_get_attachment_image_url( (int) $s['logo_id'], 'medium' ) : '';
		$site   = get_theme_mod( 'custom_logo' ) ? wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'medium' ) : '';

		echo '<div class="wrap lu3g-manutenzione"><h1 class="screen-reader-text">LU3G Manutenzione</h1>';
		echo '<header class="lm-hero"><div class="lm-hero__glow" aria-hidden="true"></div><div class="lm-hero__brand"><div class="lm-hero__logo">' . $logo . '</div><div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG del plugin
		echo '<p class="lm-hero__eyebrow">LU3G Manutenzione</p><h2 class="lm-hero__title">' . esc_html__( 'Sito in manutenzione', 'lu3g-manutenzione' ) . '</h2>';
		echo '<p class="lm-hero__lead">' . esc_html__( 'Con la manutenzione accesa i visitatori vedono una schermata con il logo; dal lucchetto in alto a destra chi ha un account accede con le credenziali del sito e lo vede normalmente.', 'lu3g-manutenzione' ) . '</p></div></div>';
		echo '<dl class="lm-stats lm-stats--2"><div><dt>' . esc_html__( 'Stato', 'lu3g-manutenzione' ) . '</dt><dd class="lm-state ' . ( $active ? 'is-on' : 'is-off' ) . '">' . ( $active ? esc_html__( 'Attiva', 'lu3g-manutenzione' ) : esc_html__( 'Spenta', 'lu3g-manutenzione' ) ) . '</dd></div>';
		echo '<div><dt>' . esc_html__( 'Chi vede il sito', 'lu3g-manutenzione' ) . '</dt><dd class="lm-stats__small">' . esc_html( LU3G_Manutenzione_Settings::access_options()[ $s['access'] ] ) . '</dd></div></dl></header>';

		if ( $msg ) {
			$texts = array(
				'on'    => __( 'Manutenzione accesa: i visitatori ora vedono la schermata.', 'lu3g-manutenzione' ),
				'off'   => __( 'Manutenzione spenta: il sito è di nuovo visibile a tutti.', 'lu3g-manutenzione' ),
				'saved' => __( 'Impostazioni salvate.', 'lu3g-manutenzione' ),
			);
			echo '<div class="lm-alert lm-alert--' . esc_attr( $msg ) . '" role="status"><strong>' . esc_html( $texts[ $msg ] ?? $texts['saved'] ) . '</strong>';
			if ( 'none' === $cache ) {
				echo ' ' . esc_html__( 'Nessun plugin di cache riconosciuto: se il sito usa una cache esterna (Cloudflare, cache del provider), svuotala dal suo pannello.', 'lu3g-manutenzione' );
			} elseif ( '' !== $cache ) {
				/* translators: %s: elenco delle cache */
				echo ' ' . esc_html( sprintf( __( 'Cache svuotata: %s.', 'lu3g-manutenzione' ), $cache ) );
			}
			echo '</div>';
		}
		if ( $forced ) {
			echo '<div class="lm-alert lm-alert--off" role="status"><strong>' . esc_html__( 'Schermata spenta da wp-config.php', 'lu3g-manutenzione' ) . '</strong> ' . esc_html__( '(LU3G_MANUTENZIONE_OFF): finché la costante c\'è, i visitatori vedono il sito anche con la manutenzione accesa.', 'lu3g-manutenzione' ) . '</div>';
		}

		echo '<form class="lm-body" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-lm-form>';
		wp_nonce_field( 'lu3g_manutenzione_save' );
		echo '<input type="hidden" name="action" value="lu3g_manutenzione_save">';

		echo '<div class="lm-card lm-card--switch">';
		self::toggle( 'enabled', __( 'Modalità manutenzione', 'lu3g-manutenzione' ), $s['enabled'], __( 'Accesa: i visitatori vedono la schermata (stato HTTP 503, Google la considera temporanea). La bacheca e la pagina di accesso restano raggiungibili. Al salvataggio la cache delle pagine viene svuotata.', 'lu3g-manutenzione' ) );
		echo '</div>';

		echo '<div class="lm-card"><h3>' . esc_html__( 'Testi della schermata', 'lu3g-manutenzione' ) . '</h3><div class="lm-grid">';
		printf( '<label class="lm-field"><span>%1$s</span><input type="text" class="lm-input" name="lu3g_m[title]" value="%2$s" maxlength="120"></label>', esc_html__( 'Titolo', 'lu3g-manutenzione' ), esc_attr( $s['title'] ) );
		printf( '<label class="lm-field"><span>%1$s</span><input type="text" class="lm-input" name="lu3g_m[note]" value="%2$s" maxlength="120" placeholder="%3$s"><small>%4$s</small></label>', esc_html__( 'Riga in evidenza (facoltativa)', 'lu3g-manutenzione' ), esc_attr( $s['note'] ), esc_attr__( 'Torniamo online lunedì 1 dicembre', 'lu3g-manutenzione' ), esc_html__( 'Compare sotto il messaggio, con un pallino arancione.', 'lu3g-manutenzione' ) );
		printf( '<label class="lm-field lm-field--wide"><span>%1$s</span><textarea class="lm-input" name="lu3g_m[message]" rows="3">%2$s</textarea></label>', esc_html__( 'Messaggio', 'lu3g-manutenzione' ), esc_textarea( $s['message'] ) );
		echo '</div></div>';

		echo '<div class="lm-card"><h3>' . esc_html__( 'Logo', 'lu3g-manutenzione' ) . '</h3><div class="lm-logos" role="radiogroup">';
		$opts = array(
			'lu3g'   => array( __( 'Logo LU3G', 'lu3g-manutenzione' ), '<span class="lm-logos__svg">' . $logo . '</span>' ),
			'site'   => array( __( 'Logo del sito', 'lu3g-manutenzione' ), $site ? '<img src="' . esc_url( $site ) . '" alt="">' : '<em>' . esc_html__( 'Non impostato in Aspetto → Personalizza', 'lu3g-manutenzione' ) . '</em>' ),
			'custom' => array( __( 'Immagine a scelta', 'lu3g-manutenzione' ), '<img src="' . esc_url( $custom ) . '" alt="" data-lm-logo-img' . ( $custom ? '' : ' hidden' ) . '><button type="button" class="lm-btn lm-btn--small" data-lm-pick>' . esc_html__( 'Scegli immagine', 'lu3g-manutenzione' ) . '</button>' ),
		);
		foreach ( $opts as $k => $o ) {
			echo '<label class="lm-logo' . ( $s['logo'] === $k ? ' is-checked' : '' ) . '"><input type="radio" name="lu3g_m[logo]" value="' . esc_attr( $k ) . '" ' . checked( $s['logo'], $k, false ) . '><span class="lm-logo__pic">' . $o[1] . '</span><strong>' . esc_html( $o[0] ) . '</strong></label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML costruito qui con valori già filtrati
		}
		echo '</div><input type="hidden" name="lu3g_m[logo_id]" value="' . (int) $s['logo_id'] . '" data-lm-logo-id><p class="lm-help">' . esc_html__( 'Il logo LU3G è pensato per lo sfondo scuro della schermata. Se il logo del sito o l\'immagine scelta mancano, si vede il logo LU3G.', 'lu3g-manutenzione' ) . '</p></div>';

		echo '<div class="lm-card"><h3>' . esc_html__( 'Accesso dal lucchetto', 'lu3g-manutenzione' ) . '</h3><div class="lm-grid">';
		echo '<label class="lm-field"><span>' . esc_html__( 'Dopo l\'accesso vedono il sito', 'lu3g-manutenzione' ) . '</span><select name="lu3g_m[access]">';
		foreach ( LU3G_Manutenzione_Settings::access_options() as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $s['access'], $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select><small>' . esc_html__( 'Gli altri account, dopo l\'accesso, vedono ancora la schermata con un messaggio e il pulsante Esci.', 'lu3g-manutenzione' ) . '</small></label><div>';
		self::toggle( 'lock', __( 'Mostra il lucchetto', 'lu3g-manutenzione' ), $s['lock'], __( 'In alto a destra nella schermata. Spento: si accede solo dalla pagina di accesso di WordPress.', 'lu3g-manutenzione' ) );
		echo '</div></div><p class="lm-help">' . esc_html__( 'Il modulo invia le credenziali alla normale pagina di accesso di WordPress: funzionano captcha, limiti dei tentativi, verifica in due passaggi e indirizzi di accesso personalizzati. Se un plugin di sicurezza chiede un campo in più, usa il link "Pagina di accesso" nella finestra.', 'lu3g-manutenzione' ) . '</p></div>';

		echo '<div class="lm-savebar"><a class="lm-btn" href="' . esc_url( add_query_arg( LU3G_Manutenzione_Gate::PREVIEW, '1', home_url( '/' ) ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Anteprima della schermata', 'lu3g-manutenzione' ) . '</a><button type="submit" class="lm-btn lm-btn--primary">' . esc_html__( 'Salva', 'lu3g-manutenzione' ) . '</button></div>';
		echo '</form>';

		echo '<details class="lm-card lm-card--soft lm-more"><summary>' . esc_html__( 'Se resti chiuso fuori', 'lu3g-manutenzione' ) . '</summary><p class="lm-help">' . esc_html__( 'La pagina di accesso di WordPress (wp-login.php) funziona sempre. In emergenza aggiungi a wp-config.php questa riga: spegne la schermata senza entrare nella bacheca.', 'lu3g-manutenzione' ) . '</p><p><code>define( \'LU3G_MANUTENZIONE_OFF\', true );</code></p></details>';
		echo '<p class="lm-foot">LU3G Manutenzione ' . esc_html( LU3G_MANUTENZIONE_VERSION ) . '</p></div>';
	}
}
