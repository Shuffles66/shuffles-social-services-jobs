/* Shuffles Social Services Jobs and Engagements — Google Maps integration.
 * Loaded before the Google Maps script, which calls window.sssjInitMaps when ready.
 * - Places autocomplete on inputs[data-sssj-place] → fills the sibling
 *   data-sssj-suburb/state/postcode/lat/lng fields within the [data-sssj-place-group] wrapper.
 * - Renders a results map in [data-sssj-map] from window.SSJ_Maps.points.
 */
( function () {
	'use strict';

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
					if ( p && p.geometry ) { fillFromPlace( input, p ); }
				} );
			} );
		}

		var cfg = window.SSJ_Maps || {};
		var mapEl = document.querySelector( '[data-sssj-map]' );
		if ( mapEl && cfg.points && cfg.points.length ) {
			var map = new google.maps.Map( mapEl, {
				zoom: 5,
				center: { lat: -25.2744, lng: 133.7751 },
				mapTypeControl: false,
				streetViewControl: false,
				fullscreenControl: false
			} );
			var bounds = new google.maps.LatLngBounds();
			var shown = 0;
			cfg.points.forEach( function ( pt ) {
				if ( ! pt.lat || ! pt.lng ) { return; }
				var marker = new google.maps.Marker( {
					position: { lat: parseFloat( pt.lat ), lng: parseFloat( pt.lng ) },
					map: map,
					title: pt.title || ''
				} );
				if ( pt.url ) {
					marker.addListener( 'click', function () { window.location = pt.url; } );
				}
				bounds.extend( marker.getPosition() );
				shown++;
			} );
			if ( shown > 0 ) {
				map.fitBounds( bounds );
				if ( shown === 1 ) { google.maps.event.addListenerOnce( map, 'idle', function () { map.setZoom( 11 ); } ); }
			}
		}
	};
}() );
