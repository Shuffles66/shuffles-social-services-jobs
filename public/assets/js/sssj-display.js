/**
 * Shuffles Social Services Jobs — front-page display animations.
 *
 * 1) Reveal-on-scroll: any [data-sssj-reveal] container (and its .sssj-reveal children) fades/slides
 *    in when it scrolls into view (adds .is-in).
 * 2) Count-up: [data-sssj-count="N"] numbers animate 0 → N the first time they appear.
 *
 * Respects prefers-reduced-motion (shows everything immediately, sets final numbers).
 */
( function () {
	'use strict';

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function countUp( el ) {
		var target = parseInt( el.getAttribute( 'data-sssj-count' ), 10 ) || 0;
		if ( reduce || target <= 0 ) { el.textContent = String( target ); return; }
		var dur = Math.min( 1600, 400 + target * 12 );
		var start = null;
		function step( ts ) {
			if ( start === null ) { start = ts; }
			var p = Math.min( 1, ( ts - start ) / dur );
			// easeOutCubic
			var eased = 1 - Math.pow( 1 - p, 3 );
			el.textContent = String( Math.round( eased * target ) );
			if ( p < 1 ) { window.requestAnimationFrame( step ); }
			else { el.textContent = String( target ); }
		}
		window.requestAnimationFrame( step );
	}

	function reveal( container ) {
		container.classList.add( 'is-in' );
		// Stagger any explicit reveal children.
		var kids = container.querySelectorAll( '.sssj-reveal' );
		Array.prototype.forEach.call( kids, function ( k ) { k.classList.add( 'is-in' ); } );
		// Fire any counters inside.
		Array.prototype.forEach.call( container.querySelectorAll( '[data-sssj-count]' ), countUp );
	}

	function boot() {
		var containers = document.querySelectorAll( '[data-sssj-reveal]' );
		if ( ! containers.length ) { return; }

		if ( reduce || ! ( 'IntersectionObserver' in window ) ) {
			Array.prototype.forEach.call( containers, reveal );
			return;
		}

		var io = new IntersectionObserver( function ( entries, obs ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					reveal( entry.target );
					obs.unobserve( entry.target );
				}
			} );
		}, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' } );

		Array.prototype.forEach.call( containers, function ( c ) { io.observe( c ); } );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
