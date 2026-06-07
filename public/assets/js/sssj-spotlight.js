/**
 * Feature spotlight controls. The orbiting "ball of light" can be paused or reversed:
 *   single tap / click  -> stop or start it
 *   double tap / click  -> reverse its direction
 * A real focusable button provides the same for keyboard and screen-reader users
 * (Space or Enter = stop/start, R = reverse). No dependencies.
 */
( function () {
	'use strict';

	var DBL = 280; // ms window to tell a single tap from a double tap

	/* Refresh today's feature from the REST endpoint so a full-page cache can never freeze it. */
	function refresh( tile ) {
		var ep  = tile.getAttribute( 'data-spot-endpoint' );
		var box = tile.querySelector( '[data-spot-content]' );
		if ( ! ep || ! box ) { return; }
		var url = ep + ( ep.indexOf( '?' ) > -1 ? '&' : '?' ) + 't=' + ( new Date() ).getTime();
		fetch( url, { credentials: 'same-origin', cache: 'no-store' } )
			.then( function ( r ) { return r.ok ? r.json() : null; } )
			.then( function ( d ) { if ( d && typeof d.html === 'string' && d.html.replace( /\s/g, '' ) !== '' ) { box.innerHTML = d.html; } } )
			.catch( function () {} );
	}

	function init( tile ) {
		if ( tile.getAttribute( 'data-spot-ready' ) === '1' ) { return; }
		tile.setAttribute( 'data-spot-ready', '1' );

		refresh( tile );

		var ctrl  = tile.querySelector( '[data-spot-ctrl]' );
		var icon  = tile.querySelector( '[data-spot-icon]' );
		var label = tile.querySelector( '[data-spot-label]' );

		function setPaused( paused ) {
			tile.classList.toggle( 'is-paused', paused );
			if ( ctrl ) { ctrl.setAttribute( 'aria-pressed', paused ? 'true' : 'false' ); }
			if ( icon ) { icon.innerHTML = paused ? '&#9654;' : '&#9208;'; } // play vs pause glyph
			if ( label ) { label.textContent = paused ? 'Start' : 'Pause'; }
		}
		function togglePause() { setPaused( ! tile.classList.contains( 'is-paused' ) ); }
		function reverse() { tile.classList.toggle( 'is-reverse' ); setPaused( false ); }

		// Shared single/double tap resolver.
		var timer = null;
		function tap() {
			if ( timer ) { clearTimeout( timer ); timer = null; reverse(); }
			else { timer = setTimeout( function () { timer = null; togglePause(); }, DBL ); }
		}

		// Tap anywhere on the tile, but never hijack the "Learn more" link or the control button itself.
		tile.addEventListener( 'click', function ( e ) {
			// "Learn more" expands an inline detail panel (it does not link anywhere).
			var more = e.target.closest ? e.target.closest( '[data-spot-more]' ) : null;
			if ( more ) {
				e.preventDefault();
				var box = tile.querySelector( '[data-spot-detail]' );
				if ( box ) {
					var willOpen = box.hasAttribute( 'hidden' );
					if ( willOpen ) { box.removeAttribute( 'hidden' ); } else { box.setAttribute( 'hidden', '' ); }
					more.setAttribute( 'aria-expanded', willOpen ? 'true' : 'false' );
					more.textContent = willOpen ? 'Show less' : 'Learn more about this feature';
				}
				return;
			}
			if ( e.target.closest( 'a, [data-spot-ctrl], [data-spot-detail]' ) ) { return; }
			tap();
		} );

		if ( ctrl ) {
			ctrl.addEventListener( 'click', function ( e ) { e.preventDefault(); e.stopPropagation(); tap(); } );
			ctrl.addEventListener( 'keydown', function ( e ) {
				if ( e.key === ' ' || e.key === 'Enter' ) { e.preventDefault(); togglePause(); }
				else if ( e.key === 'r' || e.key === 'R' ) { e.preventDefault(); reverse(); }
			} );
		}
	}

	function boot() {
		var tiles = document.querySelectorAll( '[data-sssj-spotlight]' );
		Array.prototype.forEach.call( tiles, init );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
