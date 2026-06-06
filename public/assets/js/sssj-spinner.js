/**
 * Shuffles spinner — the branded busy state for lookups/queries that take a few seconds.
 *
 * Two uses:
 *  1. Automatic: any <form data-sssj-busy="Message…"> shows a full-form overlay on submit
 *     (e.g. the org / worker / participant forms, which do NDIS + ABR + geocode lookups on save).
 *  2. Programmatic: window.SSSJSpinner.show(el, msg) / .hide(el) for AJAX flows (e.g. autofill).
 *
 * Uses the site logo (pulsing) when one is available, else a brand-blue ring — mirrors SPF.
 */
( function () {
	'use strict';

	var cfg = window.SSSJ_Spinner || {};

	// Expose the site logo to CSS once (the logo variant pulses the logo instead of a ring).
	if ( cfg.logo ) {
		try {
			document.documentElement.style.setProperty( '--sssj-spinner-logo', 'url("' + cfg.logo + '")' );
		} catch ( e ) {}
	}

	function buildOverlay( msg ) {
		var ov = document.createElement( 'div' );
		ov.className = 'sssj-busy__overlay';
		if ( cfg.logo ) { ov.setAttribute( 'data-logo', '1' ); }
		var ring = document.createElement( 'div' );
		ring.className = 'sssj-busy__ring';
		ring.setAttribute( 'aria-hidden', 'true' );
		ov.appendChild( ring );
		if ( msg ) {
			var m = document.createElement( 'div' );
			m.className = 'sssj-busy__msg';
			m.textContent = msg;
			ov.appendChild( m );
		}
		ov.setAttribute( 'role', 'status' );
		ov.setAttribute( 'aria-live', 'polite' );
		return ov;
	}

	function show( target, msg ) {
		if ( ! target || target.querySelector( ':scope > .sssj-busy__overlay' ) ) { return; }
		target.classList.add( 'sssj-busy' );
		target.appendChild( buildOverlay( msg || '' ) );
	}

	function hide( target ) {
		if ( ! target ) { return; }
		var ov = target.querySelector( ':scope > .sssj-busy__overlay' );
		if ( ov ) { ov.parentNode.removeChild( ov ); }
		target.classList.remove( 'sssj-busy' );
	}

	window.SSSJSpinner = { show: show, hide: hide };

	// Automatic overlay on submit of any form flagged data-sssj-busy.
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! form || ! form.matches || ! form.matches( 'form[data-sssj-busy]' ) ) { return; }
		// If the browser is about to block submit on native validation, don't show a stuck spinner.
		if ( form.checkValidity && ! form.checkValidity() ) { return; }
		show( form, form.getAttribute( 'data-sssj-busy' ) || '' );
	}, true );

	// Links/buttons flagged data-sssj-busy that navigate (e.g. "Re-check NDIS register now") —
	// overlay the nearest panel before the page reloads.
	document.addEventListener( 'click', function ( e ) {
		var el = e.target.closest ? e.target.closest( 'a[data-sssj-busy], button[data-sssj-busy]' ) : null;
		if ( ! el ) { return; }
		var box = el.closest( '.sssj-ndis, .sssj-panel, .sssj' ) || el.parentNode;
		show( box, el.getAttribute( 'data-sssj-busy' ) || '' );
	}, true );
}() );
