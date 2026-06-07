/* Shuffles Social Services Jobs — navigation menu.
 * Mobile hamburger toggle for the [sssj_menu] nav. On desktop the toggle is hidden (CSS),
 * so this is a no-op there. Tapping the toggle shows/hides the list; tapping a real link closes it.
 */
( function () {
	'use strict';

	function init( nav ) {
		var btn  = nav.querySelector( '.sssj-nav__toggle' );
		var list = nav.querySelector( '.sssj-nav__list' );
		if ( ! btn || ! list ) { return; }

		btn.addEventListener( 'click', function () {
			var open = nav.classList.toggle( 'is-open' );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );

		// Tapping a real menu link closes the menu on mobile (a dropdown parent with href="#" keeps it open).
		list.addEventListener( 'click', function ( e ) {
			var a = e.target.closest( 'a' );
			if ( ! a ) { return; }
			var href = a.getAttribute( 'href' ) || '';
			if ( '#' === href || '' === href ) { return; }
			if ( window.matchMedia && window.matchMedia( '(max-width:640px)' ).matches ) {
				nav.classList.remove( 'is-open' );
				btn.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '.sssj-nav' ), init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
