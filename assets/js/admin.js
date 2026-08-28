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

/**
 * Theme Options page: the Google Fonts repeater. Each row is a plain
 * text input for one embed link/URL; on every change they're joined
 * back into the single hidden field the PHP side has always expected
 * (hex_sanitize_google_fonts_urls() still receives one newline-joined
 * string), and the resolved family names are mirrored live into the
 * shared datalist every 'font' field points at and into the chip list
 * — no save/reload needed to see a newly pasted font in the pickers.
 *
 * @package Hex
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-hex-google-fonts]' );
	if ( ! root ) {
		return;
	}

	var rows      = root.querySelector( '[data-hex-google-fonts-rows]' );
	var hidden    = root.querySelector( '[data-hex-google-fonts-hidden]' );
	var chips     = root.querySelector( '[data-hex-google-fonts-chips]' );
	var empty     = root.querySelector( '[data-hex-google-fonts-empty]' );
	var addButton = root.querySelector( '[data-hex-add-font-row]' );
	var datalist  = document.getElementById( 'hex-google-fonts-list' );

	var urlPattern = /https:\/\/fonts\.googleapis\.com\/css2\?[^\s"'<>]+/g;

	function extractFamilies( text ) {
		var families = [];
		var urlMatches = String( text ).match( urlPattern ) || [];

		urlMatches.forEach( function ( url ) {
			var query = url.split( '?' )[ 1 ] || '';
			var familyParams = query.match( /family=[^&]+/g ) || [];

			familyParams.forEach( function ( param ) {
				var raw = param.slice( 'family='.length );
				var decoded = decodeURIComponent( raw.replace( /\+/g, ' ' ) );
				var name = decoded.split( ':' )[ 0 ].trim();

				if ( name && families.indexOf( name ) === -1 ) {
					families.push( name );
				}
			} );
		} );

		return families;
	}

	function rowInputs() {
		return Array.prototype.slice.call( rows.querySelectorAll( '[data-hex-google-fonts-url]' ) );
	}

	function sync() {
		var values = rowInputs().map( function ( input ) {
			return input.value.trim();
		} );

		hidden.value = values.filter( Boolean ).join( '\n' );

		var families = [];
		values.forEach( function ( value ) {
			extractFamilies( value ).forEach( function ( family ) {
				if ( families.indexOf( family ) === -1 ) {
					families.push( family );
				}
			} );
		} );

		if ( datalist ) {
			datalist.innerHTML = '';
			families.forEach( function ( family ) {
				var option = document.createElement( 'option' );
				option.value = family;
				datalist.appendChild( option );
			} );
		}

		if ( chips ) {
			chips.innerHTML = '';
			families.forEach( function ( family ) {
				var chip = document.createElement( 'span' );
				chip.className = 'rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-medium text-indigo-300!';
				chip.textContent = family;
				chips.appendChild( chip );
			} );
		}

		if ( empty ) {
			empty.classList.toggle( 'hidden', families.length > 0 );
		}
	}

	function addRow( value ) {
		var row = document.createElement( 'div' );
		row.className = 'hex-google-fonts-row flex items-center gap-2';
		row.innerHTML = '<input type="text" class="hex-field font-mono text-xs" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" data-hex-google-fonts-url>' +
			'<button type="button" class="hex-btn hex-btn-secondary shrink-0 px-3!" data-hex-remove-font-row aria-label="Remove font">&times;</button>';
		row.querySelector( '[data-hex-google-fonts-url]' ).value = value || '';
		rows.appendChild( row );
		return row;
	}

	rows.addEventListener( 'input', function ( event ) {
		if ( event.target.matches( '[data-hex-google-fonts-url]' ) ) {
			sync();
		}
	} );

	rows.addEventListener( 'click', function ( event ) {
		var removeButton = event.target.closest( '[data-hex-remove-font-row]' );
		if ( ! removeButton ) {
			return;
		}

		removeButton.closest( '.hex-google-fonts-row' ).remove();

		if ( ! rows.querySelector( '.hex-google-fonts-row' ) ) {
			addRow( '' );
		}

		sync();
	} );

	if ( addButton ) {
		addButton.addEventListener( 'click', function () {
			var row = addRow( '' );
			row.querySelector( '[data-hex-google-fonts-url]' ).focus();
		} );
	}

	sync();
} )();
