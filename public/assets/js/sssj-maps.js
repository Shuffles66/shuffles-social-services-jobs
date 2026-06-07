/* Shuffles Social Services Jobs and Engagements — Google Maps integration.
 * Loaded before the Google Maps script, which calls window.sssjInitMaps when ready.
 * - Places autocomplete on inputs[data-sssj-place] → fills the sibling
 *   data-sssj-suburb/state/postcode/lat/lng fields within the [data-sssj-place-group] wrapper.
 * - Renders a results map in [data-sssj-map] from window.SSJ_Maps.points.
 *   • Single click a marker  → info box (summary + View link).
 *   • Double click a marker  → scroll to that result's card + a rainbow "tracer" highlight.
 */
( function () {
	'use strict';

	// Shared handle so a place selection can recenter the already-rendered results map.
	window.SSSJMaps = window.SSSJMaps || {};
	window.SSSJMaps.focus = function ( lat, lng, zoom ) {
		var m = window.SSSJMaps.map;
		if ( ! m || lat == null || lng == null ) { return; }
		m.setCenter( { lat: parseFloat( lat ), lng: parseFloat( lng ) } );
		m.setZoom( zoom || 11 );
	};

	/**
	 * Render (or re-render) the results-map markers from a points array. Creates the map on first
	 * use, clears the previous markers, then plots the new ones and fits the view. Safe to call after
	 * an AJAX filter so the pins always match the visible results.
	 */
	window.SSSJMaps.render = function ( points ) {
		if ( ! ( window.google && google.maps ) ) { return; }
		var mapEl = document.querySelector( '[data-sssj-map]' );
		if ( ! mapEl ) { return; }
		points = points || [];

		if ( ! window.SSSJMaps.map ) {
			window.SSSJMaps.map = new google.maps.Map( mapEl, {
				zoom: 5,
				center: { lat: -25.2744, lng: 133.7751 },
				mapTypeControl: false,
				streetViewControl: false,
				fullscreenControl: false
			} );
			window.SSSJMaps.info = new google.maps.InfoWindow();
			window.SSSJMaps.markers = [];
		}
		var map  = window.SSSJMaps.map;
		var info = window.SSSJMaps.info;

		// Clear the previous markers.
		( window.SSSJMaps.markers || [] ).forEach( function ( m ) { m.setMap( null ); } );
		window.SSSJMaps.markers = [];

		var bounds = new google.maps.LatLngBounds();
		var shown  = 0;

		points.forEach( function ( pt ) {
			if ( ! pt.lat || ! pt.lng ) { return; }
			var marker = new google.maps.Marker( {
				position: { lat: parseFloat( pt.lat ), lng: parseFloat( pt.lng ) },
				map: map,
				title: pt.title || ''
			} );
			var clickTimer = null;
			marker.addListener( 'click', function () {
				window.clearTimeout( clickTimer );
				clickTimer = window.setTimeout( function () {
					var html = '<div class="sssj-iw"><strong>' + esc( pt.title ) + '</strong>'
						+ ( pt.sub ? '<br><span class="sssj-iw__sub">📍 ' + esc( pt.sub ) + '</span>' : '' )
						+ ( pt.url ? '<br><a class="sssj-iw__link" href="' + esc( pt.url ) + '">View &rarr;</a>' : '' )
						+ '<br><span class="sssj-iw__hint">Double-click the pin to find this card.</span></div>';
					info.setContent( html );
					info.open( map, marker );
				}, 240 );
			} );
			marker.addListener( 'dblclick', function () {
				window.clearTimeout( clickTimer );
				info.close();
				highlightCard( pt.id );
			} );
			window.SSSJMaps.markers.push( marker );
			bounds.extend( marker.getPosition() );
			shown++;
		} );

		if ( shown > 0 ) {
			map.fitBounds( bounds );
			if ( shown === 1 ) { google.maps.event.addListenerOnce( map, 'idle', function () { map.setZoom( 11 ); } ); }
		}
	};

	function fillFromPlace( input, place ) {
		var group = input.closest( '[data-sssj-place-group]' ) || document;
		var set = function ( sel, val ) {
			var el = group.querySelector( sel );
			if ( el && ( val || val === 0 ) ) { el.value = val; }
		};
		if ( place.geometry && place.geometry.location ) {
			set( '[data-sssj-lat]', place.geometry.location.lat() );
			set( '[data-sssj-lng]', place.geometry.location.lng() );
		}
		var comp = {};
		( place.address_components || [] ).forEach( function ( c ) {
			c.types.forEach( function ( t ) { comp[ t ] = c; } );
		} );
		if ( comp.locality ) { set( '[data-sssj-suburb]', comp.locality.long_name ); }
		else if ( comp.postal_town ) { set( '[data-sssj-suburb]', comp.postal_town.long_name ); }
		if ( comp.administrative_area_level_1 ) { set( '[data-sssj-state]', comp.administrative_area_level_1.short_name ); }
		if ( comp.postal_code ) { set( '[data-sssj-postcode]', comp.postal_code.long_name ); }
	}

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	// Scroll to a result card and run the rainbow tracer highlight.
	function highlightCard( id ) {
		var card = document.querySelector( '[data-sssj-id="' + String( id ).replace( /[^0-9]/g, '' ) + '"]' );
		if ( ! card ) { return; }
		card.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		card.classList.remove( 'sssj-card--tracer' );
		void card.offsetWidth; // force reflow so the animation restarts
		card.classList.add( 'sssj-card--tracer' );
		window.setTimeout( function () { card.classList.remove( 'sssj-card--tracer' ); }, 3400 );
	}

	window.sssjInitMaps = function () {
		if ( ! ( window.google && google.maps ) ) { return; }

		if ( google.maps.places ) {
			document.querySelectorAll( 'input[data-sssj-place]' ).forEach( function ( input ) {
				var ac = new google.maps.places.Autocomplete( input, {
					componentRestrictions: { country: [ 'au' ] },
					fields: [ 'address_components', 'geometry', 'name' ],
					types: [ '(regions)' ]
				} );
				ac.addListener( 'place_changed', function () {
					var p = ac.getPlace();
					if ( ! p || ! p.geometry ) { return; }
					fillFromPlace( input, p );
					var loc = p.geometry.location;
					var lat = loc ? loc.lat() : null;
					var lng = loc ? loc.lng() : null;
					// On a board filter, recenter the results map and trigger the AJAX refresh.
					if ( input.closest( '[data-sssj-filter-form]' ) ) {
						window.SSSJMaps.focus( lat, lng, 11 );
						input.dispatchEvent( new CustomEvent( 'sssj:placechosen', { bubbles: true, detail: { lat: lat, lng: lng } } ) );
					}
				} );
			} );
		}

		window.SSSJMaps.render( ( window.SSJ_Maps || {} ).points || [] );
	};
}() );
