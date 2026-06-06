/**
 * Guides — collapsible panels. Click a header to open/close. No dependencies.
 * The first guide renders open (is-open) server-side; this just wires the toggles.
 */
( function () {
	'use strict';

	function init( root ) {
		var toggles = root.querySelectorAll( '[data-guide-toggle]' );
		Array.prototype.forEach.call( toggles, function ( btn ) {
			btn.addEventListener( 'click', function () {
				var panel = btn.closest( '[data-sssj-guide]' );
				if ( ! panel ) {
					return;
				}
				var open = panel.classList.toggle( 'is-open' );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
		} );
	}

	function boot() {
		var roots = document.querySelectorAll( '[data-sssj-guides]' );
		Array.prototype.forEach.call( roots, init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
