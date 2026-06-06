/**
 * Shuffles Social Services Jobs — dynamic directory filters.
 *
 * Filters apply automatically (no "Filter" button needed): changing a select, checkbox,
 * radius or search box re-runs the search. Adds:
 *   • [data-sssj-clear] — a "Clear all" button (strips every filter)
 *   • [data-sssj-here]  — a "Use my location" button (browser geolocation → radius search)
 *
 * Works on any <form data-sssj-filter-form> that GET-submits to the same page.
 * Degrades gracefully: without JS the <noscript> submit button still filters.
 */
( function () {
	'use strict';

	var DEBOUNCE_MS = 650;
	var DEFAULT_RADIUS = 25; // km, applied by "Use my location" when no radius is set

	function submitForm( form ) {
		// Drop the page number so a new filter starts on page 1.
		var paged = form.querySelector( '[name="sssj_paged"]' );
		if ( paged ) { paged.value = '1'; }
		if ( typeof form.requestSubmit === 'function' ) {
			form.requestSubmit();
		} else {
			form.submit();
		}
	}

	function setRadius( form, km ) {
		var r = form.querySelector( '[name="sssj_radius"]' );
		if ( ! r ) { return; }
		if ( 'SELECT' === r.tagName ) {
			// Pick the option whose value is >= km (closest sensible band), else the largest.
			var best = null;
			Array.prototype.forEach.call( r.options, function ( o ) {
				var v = parseInt( o.value, 10 );
				if ( v && ( best === null || ( v >= km && v < best ) ) ) { best = v; }
			} );
			r.value = String( best || km );
		} else {
			if ( ! parseInt( r.value, 10 ) ) { r.value = String( km ); }
			// Refresh the visible "x km" output next to the slider.
			var out = r.nextElementSibling;
			if ( out && 'OUTPUT' === out.tagName ) { out.value = r.value + ' km'; }
		}
	}

	function useMyLocation( btn ) {
		var form = btn.closest( '[data-sssj-filter-form]' );
		if ( ! form || ! navigator.geolocation ) {
			if ( ! navigator.geolocation ) { alert( btn.getAttribute( 'data-unsupported' ) || 'Location is not available in this browser.' ); }
			return;
		}
		var original = btn.textContent;
		btn.disabled = true;
		btn.textContent = btn.getAttribute( 'data-locating' ) || 'Locating…';

		navigator.geolocation.getCurrentPosition(
			function ( pos ) {
				var lat = pos.coords.latitude;
				var lng = pos.coords.longitude;
				var latEl = form.querySelector( '[data-sssj-lat]' );
				var lngEl = form.querySelector( '[data-sssj-lng]' );
				var locEl = form.querySelector( '[data-sssj-place]' );
				if ( latEl ) { latEl.value = lat.toFixed( 6 ); }
				if ( lngEl ) { lngEl.value = lng.toFixed( 6 ); }
				if ( locEl ) { locEl.value = btn.getAttribute( 'data-mylocation' ) || '📍 My location'; }
				setRadius( form, DEFAULT_RADIUS );
				submitForm( form );
			},
			function () {
				btn.disabled = false;
				btn.textContent = original;
				alert( btn.getAttribute( 'data-denied' ) || 'Could not get your location. Please allow location access or type a suburb.' );
			},
			{ enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
		);
	}

	function clearAll( btn ) {
		// Every filter lives in the query string — navigating to the bare path resets them all.
		window.location = window.location.pathname;
	}

	function wireForm( form ) {
		var debounceTimer = null;
		var ready = false;
		// Ignore change/input events fired while enhancers (Tom Select) initialise.
		setTimeout( function () { ready = true; }, 600 );

		form.addEventListener( 'change', function ( e ) {
			if ( ! ready ) { return; }
			var t = e.target;
			if ( ! t || ! t.name ) { return; }
			// Text-like inputs are handled by the debounced 'input' path below.
			if ( t.matches( 'input[type="search"], input[type="text"]' ) ) { return; }
			submitForm( form );
		} );

		form.addEventListener( 'input', function ( e ) {
			if ( ! ready ) { return; }
			var t = e.target;
			if ( ! t || ! t.name ) { return; }
			if ( ! t.matches( 'input[type="search"], input[type="text"]' ) ) { return; }
			if ( t.hasAttribute( 'data-sssj-place' ) ) { return; } // location is applied on submit/geolocation, not mid-type
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( function () { submitForm( form ); }, DEBOUNCE_MS );
		} );
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-filter-form]' ), wireForm );
		document.addEventListener( 'click', function ( e ) {
			var here = e.target.closest( '[data-sssj-here]' );
			if ( here ) { e.preventDefault(); useMyLocation( here ); return; }
			var clear = e.target.closest( '[data-sssj-clear]' );
			if ( clear ) { e.preventDefault(); clearAll( clear ); }
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
