/**
 * Shuffles Social Services Jobs, front-page display animations.
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

	// Auto-scrolling carousels (e.g. [sssj_why_us layout="carousel"]). Steps one card on an
	// interval, loops back to the start at the end, and pauses on hover / focus / touch.
	function carousels() {
		var els = document.querySelectorAll( '[data-sssj-autoscroll]' );
		Array.prototype.forEach.call( els, function ( el ) {
			if ( reduce ) { return; }
			var delay = parseInt( el.getAttribute( 'data-sssj-autoscroll' ), 10 ) || 4000;
			if ( delay < 1500 ) { delay = 1500; }
			var paused = false;

			function step() {
				if ( paused ) { return; }
				if ( el.scrollWidth - el.clientWidth <= 4 ) { return; } // nothing to scroll
				var card = el.querySelector( '.sssj-whyus__item, *' );
				var by = card ? Math.round( card.getBoundingClientRect().width + 16 ) : el.clientWidth;
				if ( el.scrollLeft + el.clientWidth >= el.scrollWidth - 8 ) {
					el.scrollTo( { left: 0, behavior: 'smooth' } ); // loop back to the start
				} else {
					el.scrollBy( { left: by, behavior: 'smooth' } );
				}
			}

			window.setInterval( step, delay );
			var pause = function () { paused = true; };
			var resume = function () { paused = false; };
			el.addEventListener( 'mouseenter', pause );
			el.addEventListener( 'mouseleave', resume );
			el.addEventListener( 'focusin', pause );
			el.addEventListener( 'focusout', resume );
			el.addEventListener( 'touchstart', pause, { passive: true } );
			el.addEventListener( 'touchend', function () { window.setTimeout( resume, 4000 ); }, { passive: true } );
		} );
	}

	// Demo tour "Which are you?" filter: show only personas in the chosen hat-group.
	function demoFilter() {
		var bar = document.querySelector( '[data-demo-filterbar]' );
		if ( ! bar ) { return; }
		var btns = bar.querySelectorAll( '[data-demo-filter]' );
		var items = document.querySelectorAll( '[data-demo-group]' );
		function apply( group ) {
			Array.prototype.forEach.call( items, function ( el ) {
				var show = ( 'all' === group || el.getAttribute( 'data-demo-group' ) === group );
				el.style.display = show ? '' : 'none';
			} );
			Array.prototype.forEach.call( btns, function ( b ) {
				var on = ( b.getAttribute( 'data-demo-filter' ) === group );
				b.classList.toggle( 'is-active', on );
				b.classList.toggle( 'sssj-btn--primary', on );
				b.classList.toggle( 'sssj-btn--ghost', ! on );
			} );
		}
		Array.prototype.forEach.call( btns, function ( b ) {
			b.addEventListener( 'click', function () { apply( b.getAttribute( 'data-demo-filter' ) ); } );
		} );
	}

	function start() { boot(); carousels(); demoFilter(); }

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );
