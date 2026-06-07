/**
 * Provider swipe deck — Tinder-style browsing for [sssj_swipe].
 * Right (♥ / → / drag right) = save to shortlist; Left (✕ / ← / drag left) = skip; ↺ / U = undo.
 * Pointer events → works with touch and mouse. Buttons + keyboard for accessibility.
 */
( function () {
	'use strict';
	var cfg = window.SSSJ_Swipe || {};

	function ready( fn ) { 'loading' !== document.readyState ? fn() : document.addEventListener( 'DOMContentLoaded', fn ); }
	ready( function () { Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-swipe]' ), initDeck ); } );

	function esc( s ) { var d = document.createElement( 'div' ); d.textContent = String( s == null ? '' : s ); return d.innerHTML; }

	function initDeck( root ) {
		var deck = root.querySelector( '.sssj-swipe__deck' );
		if ( ! deck || deck.dataset.ready ) { return; }
		deck.dataset.ready = '1';
		var cards   = Array.prototype.slice.call( deck.querySelectorAll( '.sssj-swipe__card' ) );
		var total   = cards.length;
		var counter = root.querySelector( '[data-swipe-counter]' );
		var endEl   = root.querySelector( '[data-swipe-end]' );
		var listEl  = root.querySelector( '[data-swipe-shortlist]' );
		var controls = root.querySelector( '.sssj-swipe__controls' );
		var history = [];
		var savedList = [];

		function render() {
			cards.forEach( function ( c, i ) {
				if ( i > 2 ) { c.style.display = 'none'; return; }
				c.style.display = '';
				c.style.zIndex = String( 50 - i );
				c.style.transform = 'translateY(' + ( i * 10 ) + 'px) scale(' + ( 1 - i * 0.04 ) + ')';
				c.style.opacity = '1';
				c.classList.remove( 'is-like', 'is-nope' );
				c.style.transition = 'transform .25s ease';
			} );
			if ( counter ) {
				counter.textContent = cards.length
					? ( cards.length + ' ' + ( cards.length === 1 ? 'provider left' : 'providers left' ) + ( savedList.length ? ' · ♥ ' + savedList.length + ' saved' : '' ) )
					: '';
			}
			bindTop();
		}

		function bindTop() {
			var c = cards[ 0 ];
			if ( ! c || c.dataset.bound ) { return; }
			c.dataset.bound = '1';
			c.style.cursor = 'grab';
			var sx = 0, sy = 0, drag = false;
			c.addEventListener( 'pointerdown', function ( e ) {
				if ( e.target.closest( 'a' ) ) { return; } // let "View profile" work
				drag = true; sx = e.clientX; sy = e.clientY; c.style.transition = 'none';
				try { c.setPointerCapture( e.pointerId ); } catch ( err ) {}
			} );
			c.addEventListener( 'pointermove', function ( e ) {
				if ( ! drag ) { return; }
				var dx = e.clientX - sx, dy = e.clientY - sy;
				c.style.transform = 'translate(' + dx + 'px,' + dy + 'px) rotate(' + ( dx / 22 ) + 'deg)';
				c.classList.toggle( 'is-like', dx > 45 );
				c.classList.toggle( 'is-nope', dx < -45 );
			} );
			function end( e ) {
				if ( ! drag ) { return; }
				drag = false;
				var dx = e.clientX - sx;
				if ( dx > 110 ) { commit( 'right' ); }
				else if ( dx < -110 ) { commit( 'left' ); }
				else { c.style.transition = 'transform .2s ease'; c.style.transform = ''; c.classList.remove( 'is-like', 'is-nope' ); }
			}
			c.addEventListener( 'pointerup', end );
			c.addEventListener( 'pointercancel', end );
		}

		function commit( dir ) {
			var c = cards.shift();
			if ( ! c ) { return; }
			history.push( { card: c, dir: dir } );
			c.style.transition = 'transform .38s ease, opacity .38s';
			c.style.transform = 'translate(' + ( dir === 'right' ? 480 : -480 ) + 'px,40px) rotate(' + ( dir === 'right' ? 26 : -26 ) + 'deg)';
			c.style.opacity = '0';
			setTimeout( function () { if ( c.parentNode ) { c.parentNode.removeChild( c ); } }, 380 );
			if ( dir === 'right' ) { save( c ); }
			render();
			if ( ! cards.length ) { showEnd(); }
		}

		function undo() {
			var h = history.pop();
			if ( ! h ) { return; }
			deck.insertBefore( h.card, deck.firstChild );
			cards.unshift( h.card );
			h.card.style.opacity = '1';
			if ( endEl ) { endEl.hidden = true; }
			if ( controls ) { controls.style.display = ''; }
			render();
		}

		function save( c ) {
			var id = c.getAttribute( 'data-id' );
			var name = c.getAttribute( 'data-name' ), url = c.getAttribute( 'data-url' );
			if ( ! savedList.some( function ( s ) { return s.id === id; } ) ) { savedList.push( { id: id, name: name, url: url } ); }
			if ( ! cfg.logged_in ) {
				toast( 'Log in to keep your shortlist', 'info' );
				return;
			}
			var body = new FormData();
			body.append( 'action', 'sssj_swipe_save' );
			body.append( 'nonce', cfg.nonce || '' );
			body.append( 'id', id );
			fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) { if ( res && res.success ) { toast( '♥ Saved to your shortlist', 'success' ); } } )
				.catch( function () {} );
		}

		function showEnd() {
			if ( controls ) { controls.style.display = 'none'; }
			if ( counter ) { counter.textContent = ''; }
			if ( ! endEl ) { return; }
			endEl.hidden = false;
			if ( listEl ) {
				if ( savedList.length ) {
					listEl.innerHTML = '<p>' + esc( 'Your shortlist (' + savedList.length + '):' ) + '</p><ul>' +
						savedList.map( function ( s ) { return '<li><a href="' + esc( s.url ) + '" target="_blank" rel="noopener">' + esc( s.name ) + '</a></li>'; } ).join( '' ) + '</ul>' +
						( cfg.logged_in ? '' : '<p><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' + esc( cfg.login_url ) + '">' + esc( 'Log in to keep these' ) + '</a></p>' );
				} else {
					listEl.innerHTML = '<p>' + esc( 'You didn’t save any this time.' ) + '</p>';
				}
			}
		}

		// Buttons
		Array.prototype.forEach.call( root.querySelectorAll( '[data-swipe]' ), function ( btn ) {
			btn.addEventListener( 'click', function () {
				var d = btn.getAttribute( 'data-swipe' );
				if ( d === 'undo' ) { undo(); } else if ( cards.length ) { commit( d ); }
			} );
		} );
		// Keyboard
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! cards.length && e.key !== 'u' ) { return; }
			if ( e.key === 'ArrowLeft' ) { commit( 'left' ); }
			else if ( e.key === 'ArrowRight' ) { commit( 'right' ); }
			else if ( e.key === 'u' || e.key === 'ArrowUp' ) { undo(); }
		} );

		function toast( m, t ) { if ( window.sssjToast ) { window.sssjToast( m, t ); } }

		render();
	}
}() );
