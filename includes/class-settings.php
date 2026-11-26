<?php
/**
 * Impostazioni (un'unica opzione "lu3g_manutenzione").
 * - enabled: modalità manutenzione accesa o spenta.
 * - title, message, note: testi della schermata (note = riga facoltativa, es. "Torniamo online lunedì 1 dicembre").
 * - logo: "lu3g" (logo LU3G, predefinito), "site" (logo del sito da Aspetto → Personalizza), "custom" (immagine scelta).
 * - access: chi, dopo l'accesso, vede il sito normalmente ("logged" tutti gli utenti, "edit_posts" chi lavora sul sito,
 *   "manage_options" solo amministratori). Gli altri vedono la schermata con un messaggio e il pulsante Esci.
 * - lock: mostra il lucchetto per accedere dalla schermata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LU3G_Manutenzione_Settings {

	const OPTION = 'lu3g_manutenzione';

	public static function defaults() {
		return array(
			'enabled' => false,
			'title'   => __( 'Sito in manutenzione', 'lu3g-manutenzione' ),
			'message' => __( 'Stiamo lavorando per migliorare il sito. Torniamo online a breve.', 'lu3g-manutenzione' ),
			'note'    => '',
			'logo'    => 'lu3g',
			'logo_id' => 0,
			'access'  => 'edit_posts',
			'lock'    => true,
		);
	}

	public static function access_options() {
		return array(
			'edit_posts'     => __( 'Chi lavora sul sito (da collaboratore in su)', 'lu3g-manutenzione' ),
			'manage_options' => __( 'Solo gli amministratori', 'lu3g-manutenzione' ),
			'logged'         => __( 'Tutti gli utenti con un account', 'lu3g-manutenzione' ),
		);
	}

	public static function get() {
		$s = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $s ) ? $s : array() );
	}

	/** Pulisce i valori inviati dal pannello. */
	public static function sanitize( array $in ) {
		$d   = self::defaults();
		$out = array(
			'enabled' => ! empty( $in['enabled'] ),
			'title'   => sanitize_text_field( $in['title'] ?? '' ),
			'message' => sanitize_textarea_field( $in['message'] ?? '' ),
			'note'    => sanitize_text_field( $in['note'] ?? '' ),
			'logo'    => in_array( $in['logo'] ?? '', array( 'lu3g', 'site', 'custom' ), true ) ? $in['logo'] : 'lu3g',
			'logo_id' => absint( $in['logo_id'] ?? 0 ),
			'access'  => array_key_exists( $in['access'] ?? '', self::access_options() ) ? $in['access'] : $d['access'],
			'lock'    => ! empty( $in['lock'] ),
		);
		if ( '' === $out['title'] ) {
			$out['title'] = $d['title'];
		}
		return $out;
	}

	/** L'utente collegato può vedere il sito durante la manutenzione? */
	public static function user_can_bypass( $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}
		$access = self::get()['access'];
		$ok     = 'logged' === $access ? true : user_can( $user_id, $access );
		/** Filtro per casi particolari (es. un ruolo specifico). */
		return (bool) apply_filters( 'lu3g_manutenzione_user_can_bypass', $ok, $user_id );
	}
}
