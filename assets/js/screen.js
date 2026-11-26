/**
 * LU3G Manutenzione — finestra di accesso dal lucchetto.
 * Apertura/chiusura (pulsante, X, clic fuori, Esc), focus intrappolato nella finestra e restituito al lucchetto.
 */
( function () {
	'use strict';
	var dialog = document.getElementById( 'lu3g-m-dialog' );
	var overlay = document.querySelector( '[data-overlay]' );
	var opener = document.querySelector( '[data-open]' );
	if ( ! dialog || ! overlay ) {
		return;
	}
	var last = null;

	function focusables() {
		return Array.prototype.filter.call(
			dialog.querySelectorAll( 'a[href], button, input:not([type="hidden"]), [tabindex]:not([tabindex="-1"])' ),
			function ( el ) {
				return ! el.disabled && el.offsetParent !== null;
			}
		);
	}
	function first() {
		var input = dialog.querySelector( 'input[name="log"]' );
		var list = focusables();
		return input || list[ 1 ] || list[ 0 ];
	}
	function open() {
		last = document.activeElement;
		overlay.hidden = false;
		dialog.hidden = false;
		document.body.style.overflow = 'hidden';
		var el = first();
		if ( el ) {
			el.focus();
		}
	}
	function close() {
		overlay.hidden = true;
		dialog.hidden = true;
		document.body.style.overflow = '';
		( last && last.focus ? last : opener || document.body ).focus();
	}

	if ( opener ) {
		opener.addEventListener( 'click', open );
	}
	overlay.addEventListener( 'click', close );
	dialog.querySelector( '[data-close]' ).addEventListener( 'click', close );
	document.addEventListener( 'keydown', function ( e ) {
		if ( dialog.hidden ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			close();
			return;
		}
		if ( e.key === 'Tab' ) {
			var list = focusables();
			if ( ! list.length ) {
				return;
			}
			var a = list[ 0 ];
			var z = list[ list.length - 1 ];
			if ( e.shiftKey && document.activeElement === a ) {
				e.preventDefault();
				z.focus();
			} else if ( ! e.shiftKey && document.activeElement === z ) {
				e.preventDefault();
				a.focus();
			}
		}
	} );
	// Finestra già aperta dal server (errore di accesso o account senza permesso): porta lì il focus.
	if ( ! dialog.hidden ) {
		last = opener;
		document.body.style.overflow = 'hidden';
		var el = first();
		if ( el ) {
			el.focus();
		}
	}
}() );
