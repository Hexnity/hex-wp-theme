/**
 * Confirms before submitting the "Check & Update Now" form, since
 * it can overwrite live theme files.
 *
 * @package Hex
 */
( function () {
	'use strict';

	var form = document.querySelector( '.hex-confirm-update' );
	if ( ! form ) {
		return;
	}

	form.addEventListener( 'submit', function ( event ) {
		var message = 'This will replace the active theme\'s files with the latest version from GitHub. Continue?';
		if ( ! window.confirm( message ) ) {
			event.preventDefault();
		}
	} );
} )();

/**
 * Theme Options page: left-category tab switching, and syncing each
 * native color-swatch input with its paired hex text field.
 *
 * @package Hex
 */
( function () {
	'use strict';

	var tabsRoot = document.querySelector( '[data-hex-tabs]' );
	if ( tabsRoot ) {
		var buttons = tabsRoot.querySelectorAll( '.hex-tab-btn' );
		var panels = tabsRoot.querySelectorAll( '.hex-tab-panel' );

		var activate = function ( target ) {
			buttons.forEach( function ( btn ) {
				var isActive = btn.getAttribute( 'data-hex-tab-target' ) === target;
				btn.classList.toggle( 'bg-indigo-600!', isActive );
			} );
			panels.forEach( function ( panel ) {
				panel.classList.toggle( 'hidden', panel.getAttribute( 'data-hex-tab-panel' ) !== target );
			} );
		};

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				activate( btn.getAttribute( 'data-hex-tab-target' ) );
			} );
		} );

		if ( buttons.length ) {
			activate( buttons[ 0 ].getAttribute( 'data-hex-tab-target' ) );
		}
	}

	document.querySelectorAll( '.hex-color-swatch' ).forEach( function ( swatch ) {
		var textInput = document.getElementById( swatch.getAttribute( 'data-hex-color-for' ) );
		if ( ! textInput ) {
			return;
		}

		swatch.addEventListener( 'input', function () {
			textInput.value = swatch.value;
		} );

		textInput.addEventListener( 'input', function () {
			if ( /^#[0-9a-fA-F]{6}$/.test( textInput.value ) ) {
				swatch.value = textInput.value;
			}
		} );
	} );
} )();
