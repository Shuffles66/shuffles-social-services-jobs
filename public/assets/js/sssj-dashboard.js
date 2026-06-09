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

		// Modals (e.g. the "Edit my roles" hat picker). Open via [data-sssj-modal-open="name"],
		// close via any [data-sssj-modal-close], the backdrop, or the Escape key.
		function closeModals() {
			Array.prototype.forEach.call( root.querySelectorAll( '[data-sssj-modal]' ), function ( m ) { m.hidden = true; } );
			document.body.style.overflow = '';
		}
		root.addEventListener( 'click', function ( e ) {
			var opener = e.target.closest ? e.target.closest( '[data-sssj-modal-open]' ) : null;
			if ( opener ) {
				e.preventDefault();
				var m = root.querySelector( '[data-sssj-modal="' + opener.getAttribute( 'data-sssj-modal-open' ) + '"]' );
				if ( m ) { m.hidden = false; document.body.style.overflow = 'hidden'; }
				return;
			}
			if ( e.target.closest && e.target.closest( '[data-sssj-modal-close]' ) ) { e.preventDefault(); closeModals(); }
		} );
		document.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key ) { closeModals(); } } );

		// Deep-link support: #dash-<slug>. Otherwise open the member's primary-role tab
		// (data-sssj-dash-default), falling back to the first tab.
		var hash = ( window.location.hash || '' ).replace( '#dash-', '' );
		var def  = root.getAttribute( 'data-sssj-dash-default' ) || '';
		var hashValid = false, defValid = false;
		Array.prototype.forEach.call( tabs, function ( t ) {
			var s = t.getAttribute( 'data-dash-tab' );
			if ( s === hash ) { hashValid = true; }
			if ( s === def ) { defValid = true; }
		} );
		show( hashValid ? hash : ( defValid ? def : ( tabs.length ? tabs[ 0 ].getAttribute( 'data-dash-tab' ) : '' ) ) );
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
