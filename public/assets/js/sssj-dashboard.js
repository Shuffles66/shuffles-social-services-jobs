/**
 * Member dashboard tabs. Clicking a tab shows its panel and hides the others.
 * Progressive enhancement: without JS, all panels are visible (each has its own heading);
 * adding .is-enhanced on the container is what hides the inactive panels.
 */
( function () {
	'use strict';

	function init( root ) {
		root.classList.add( 'is-enhanced' );
		var tabs   = root.querySelectorAll( '[data-dash-tab]' );
		var panels = root.querySelectorAll( '[data-dash-panel]' );

		function show( slug ) {
			Array.prototype.forEach.call( panels, function ( p ) {
				p.classList.toggle( 'is-active', p.getAttribute( 'data-dash-panel' ) === slug );
			} );
			Array.prototype.forEach.call( tabs, function ( t ) {
				t.classList.toggle( 'is-active', t.getAttribute( 'data-dash-tab' ) === slug );
			} );
			try {
				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', '#dash-' + slug );
				}
			} catch ( e ) {}
		}

		Array.prototype.forEach.call( tabs, function ( t ) {
			t.addEventListener( 'click', function () { show( t.getAttribute( 'data-dash-tab' ) ); } );
		} );

		// Deep-link support: #dash-<slug>
		var hash = ( window.location.hash || '' ).replace( '#dash-', '' );
		var valid = false;
		Array.prototype.forEach.call( tabs, function ( t ) { if ( t.getAttribute( 'data-dash-tab' ) === hash ) { valid = true; } } );
		show( valid ? hash : ( tabs.length ? tabs[ 0 ].getAttribute( 'data-dash-tab' ) : '' ) );
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-dash]' ), init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
