<?php
/**
 * Schermata di manutenzione: pagina HTML autonoma (non usa il tema, così nessun page builder o plugin la altera).
 * Variabili da LU3G_Manutenzione_Gate::render(): $s, $logo, $url, $user, $logged, $no_entry, $error, $action, $preview.
 *
 * @package lu3g-manutenzione
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ver     = LU3G_MANUTENZIONE_VERSION;
$site    = get_bloginfo( 'name' );
$open    = $error || $no_entry; // dialogo aperto subito dopo un errore o per chi è collegato senza accesso
$charset = get_bloginfo( 'charset' );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( $charset ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $s['title'] . ( $site ? ' – ' . $site : '' ) ); ?></title>
<?php if ( has_site_icon() ) : ?>
<link rel="icon" href="<?php echo esc_url( get_site_icon_url( 32 ) ); ?>" sizes="32x32">
<?php endif; ?>
<style>@font-face{font-family:"LU3G Manrope";src:url(<?php echo esc_url( LU3G_MANUTENZIONE_URL . 'assets/fonts/manrope-latin-wght.woff2' ); ?>) format("woff2");font-weight:200 800;font-display:swap}</style>
<link rel="stylesheet" href="<?php echo esc_url( LU3G_MANUTENZIONE_URL . 'assets/css/screen.css?ver=' . $ver ); ?>">
</head>
<body class="lu3g-m">
<div class="lu3g-m__bg" aria-hidden="true"><span class="lu3g-m__glow"></span><span class="lu3g-m__glow lu3g-m__glow--2"></span></div>

<?php if ( $preview ) : ?>
<p class="lu3g-m__preview"><?php esc_html_e( 'Anteprima: così vedono il sito i visitatori.', 'lu3g-manutenzione' ); ?></p>
<?php endif; ?>

<?php if ( $s['lock'] || $no_entry ) : ?>
<button type="button" class="lu3g-m__lock" data-open aria-haspopup="dialog" aria-controls="lu3g-m-dialog" aria-label="<?php esc_attr_e( 'Accedi al sito', 'lu3g-manutenzione' ); ?>">
	<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M6.5 10.5V8a5.5 5.5 0 0 1 11 0v2.5M5.5 10.5h13a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-13a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1ZM12 14.5v2.5"/></svg>
</button>
<?php endif; ?>

<main class="lu3g-m__main">
	<div class="lu3g-m__logo">
		<?php if ( 'img' === $logo['type'] ) : ?>
			<img src="<?php echo esc_url( $logo['value'] ); ?>" alt="<?php echo esc_attr( $site ); ?>">
		<?php else : ?>
			<?php echo $logo['value']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG del plugin ?>
		<?php endif; ?>
	</div>
	<?php if ( $site ) : ?>
		<p class="lu3g-m__eyebrow"><?php echo esc_html( $site ); ?></p>
	<?php endif; ?>
	<h1 class="lu3g-m__title"><?php echo esc_html( $s['title'] ); ?></h1>
	<?php if ( '' !== $s['message'] ) : ?>
		<p class="lu3g-m__text"><?php echo nl2br( esc_html( $s['message'] ) ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $s['note'] ) : ?>
		<p class="lu3g-m__note"><span class="lu3g-m__dot" aria-hidden="true"></span><?php echo esc_html( $s['note'] ); ?></p>
	<?php endif; ?>
</main>

<footer class="lu3g-m__foot"><?php esc_html_e( 'Sito realizzato da', 'lu3g-manutenzione' ); ?> <a href="https://lu3g.it" rel="noopener" target="_blank">LU3G Agenzia Web</a></footer>

<?php if ( $s['lock'] || $no_entry ) : ?>
<div class="lu3g-m__overlay" data-overlay <?php echo $open ? '' : 'hidden'; ?>></div>
<div class="lu3g-m__dialog" id="lu3g-m-dialog" role="dialog" aria-modal="true" aria-labelledby="lu3g-m-dialog-title" <?php echo $open ? '' : 'hidden'; ?>>
	<button type="button" class="lu3g-m__close" data-close aria-label="<?php esc_attr_e( 'Chiudi', 'lu3g-manutenzione' ); ?>">
		<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
	</button>
	<?php if ( $no_entry ) : ?>
		<h2 class="lu3g-m__dtitle" id="lu3g-m-dialog-title"><?php esc_html_e( 'Accesso non consentito', 'lu3g-manutenzione' ); ?></h2>
		<p class="lu3g-m__dtext">
			<?php
			/* translators: %s: nome dell'utente */
			echo esc_html( sprintf( __( 'Sei collegato come %s, ma il tuo account non può vedere il sito durante la manutenzione.', 'lu3g-manutenzione' ), $user->display_name ) );
			?>
		</p>
		<a class="lu3g-m__btn" href="<?php echo esc_url( wp_logout_url( $url ) ); ?>"><?php esc_html_e( 'Esci e accedi con un altro account', 'lu3g-manutenzione' ); ?></a>
	<?php else : ?>
		<h2 class="lu3g-m__dtitle" id="lu3g-m-dialog-title"><?php esc_html_e( 'Accedi al sito', 'lu3g-manutenzione' ); ?></h2>
		<p class="lu3g-m__dtext"><?php esc_html_e( 'Usa le credenziali del sito per vederlo durante la manutenzione.', 'lu3g-manutenzione' ); ?></p>
		<?php if ( $error ) : ?>
			<p class="lu3g-m__error" role="alert"><?php esc_html_e( 'Nome utente o password non corretti. Riprova.', 'lu3g-manutenzione' ); ?></p>
		<?php endif; ?>
		<form class="lu3g-m__form" method="post" action="<?php echo esc_url( $action ); ?>">
			<label class="lu3g-m__field"><span><?php esc_html_e( 'Nome utente o email', 'lu3g-manutenzione' ); ?></span>
				<input type="text" name="log" autocomplete="username" autocapitalize="off" spellcheck="false" required>
			</label>
			<label class="lu3g-m__field"><span><?php esc_html_e( 'Password', 'lu3g-manutenzione' ); ?></span>
				<input type="password" name="pwd" autocomplete="current-password" required>
			</label>
			<label class="lu3g-m__check"><input type="checkbox" name="rememberme" value="forever"> <?php esc_html_e( 'Ricordami', 'lu3g-manutenzione' ); ?></label>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $url ); ?>">
			<input type="hidden" name="lu3g_manutenzione" value="1">
			<button type="submit" class="lu3g-m__btn" name="wp-submit"><?php esc_html_e( 'Accedi', 'lu3g-manutenzione' ); ?></button>
		</form>
		<p class="lu3g-m__links">
			<a href="<?php echo esc_url( wp_lostpassword_url( $url ) ); ?>"><?php esc_html_e( 'Password dimenticata?', 'lu3g-manutenzione' ); ?></a>
			<a href="<?php echo esc_url( wp_login_url( $url ) ); ?>"><?php esc_html_e( 'Pagina di accesso', 'lu3g-manutenzione' ); ?></a>
		</p>
	<?php endif; ?>
</div>
<script src="<?php echo esc_url( LU3G_MANUTENZIONE_URL . 'assets/js/screen.js?ver=' . $ver ); ?>"></script>
<?php endif; ?>
</body>
</html>
