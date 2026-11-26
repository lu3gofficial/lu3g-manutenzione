/**
 * LU3G Manutenzione — pannello: scelta del logo (selezione evidenziata, immagine dalla Libreria media).
 */
( function () {
	'use strict';
	var form = document.querySelector( '[data-lm-form]' );
	if ( ! form ) {
		return;
	}
	var cards = form.querySelectorAll( '.lm-logo' );
	function mark() {
		cards.forEach( function ( c ) {
			c.classList.toggle( 'is-checked', c.querySelector( 'input' ).checked );
		} );
	}
	form.addEventListener( 'change', function ( e ) {
		if ( e.target.name === 'lu3g_m[logo]' ) {
			mark();
		}
	} );

	var pick = form.querySelector( '[data-lm-pick]' );
	var idField = form.querySelector( '[data-lm-logo-id]' );
	var img = form.querySelector( '[data-lm-logo-img]' );
	var frame = null;
	if ( pick && window.wp && wp.media ) {
		pick.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( { title: 'Logo della schermata di manutenzione', library: { type: 'image' }, multiple: false, button: { text: 'Usa questa immagine' } } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					idField.value = a.id;
					img.src = ( a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url );
					img.hidden = false;
					var radio = form.querySelector( 'input[name="lu3g_m[logo]"][value="custom"]' );
					radio.checked = true;
					mark();
				} );
			}
			frame.open();
		} );
	}
}() );
