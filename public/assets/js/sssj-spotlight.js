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

	function init( tile ) {
		if ( tile.getAttribute( 'data-spot-ready' ) === '1' ) { return; }
		tile.setAttribute( 'data-spot-ready', '1' );

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
			if ( e.target.closest( 'a, [data-spot-ctrl]' ) ) { return; }
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
