/**
 * Accessible primary navigation: toggles the mobile menu button,
 * swapping the Tailwind `hidden`/`flex` classes on the menu list and
 * keeping aria-expanded in sync.
 *
 * @package Hex
 */
( function () {
	'use strict';

	var container = document.getElementById( 'site-navigation' );
	if ( ! container ) {
		return;
	}

	var button = container.querySelector( '.menu-toggle' );
	var menu = container.querySelector( 'ul' );

	if ( ! button || ! menu ) {
		return;
	}

	button.addEventListener( 'click', function () {
		var isHidden = menu.classList.contains( 'hidden' );
		menu.classList.toggle( 'hidden', ! isHidden );
		menu.classList.toggle( 'flex', isHidden );
		button.setAttribute( 'aria-expanded', isHidden ? 'true' : 'false' );
	} );
} )();
