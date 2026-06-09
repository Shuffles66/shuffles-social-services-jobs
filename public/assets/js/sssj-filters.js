/**
 * Shuffles Social Services Jobs, dynamic directory filters.
 *
 * Filters apply automatically (no "Filter" button). On boards that opt in (a form with
 * data-sssj-board + a [data-sssj-results] region), changes are applied via AJAX, the results
 * tiles are swapped in place with the Shuffles spinner, the cursor stays in the search box, and the
 * URL is updated so it's still shareable. Boards that haven't opted in fall back to a GET reload.
 *
 *   • [data-sssj-clear], "Clear all" (resets every filter)
 *   • [data-sssj-here] , "Use my location" (browser geolocation → radius search)
 */
( function () {
	'use strict';

	var DEBOUNCE_MS = 550;
	var DEFAULT_RADIUS = 25;
	var cfg = window.SSSJ_Filter || {};

	function resultsFor( form ) {
		if ( ! form.hasAttribute( 'data-sssj-board' ) || ! cfg.ajax ) { return null; }
		var root = form.closest( '.sssj' );
		return root ? root.querySelector( '[data-sssj-results]' ) : null;
	}

	function eachField( form, fn ) {
		Array.prototype.forEach.call( form.elements, function ( el ) {
			if ( ! el.name || el.disabled ) { return; }
			if ( ( el.type === 'checkbox' || el.type === 'radio' ) && ! el.checked ) { return; }
			if ( el.tagName === 'SELECT' && el.multiple ) {
				Array.prototype.forEach.call( el.selectedOptions, function ( o ) { fn( el.name, o.value ); } );
			} else {
				fn( el.name, el.value );
			}
		} );
	}

	function queryString( form ) {
		var parts = [];
		eachField( form, function ( n, v ) {
			if ( v !== '' && v != null ) { parts.push( encodeURIComponent( n ) + '=' + encodeURIComponent( v ) ); }
		} );
		return parts.join( '&' );
	}

	function bindPagination( form, res ) {
		Array.prototype.forEach.call( res.querySelectorAll( 'a[href*="sssj_paged="]' ), function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var m = a.href.match( /[?&]sssj_paged=(\d+)/ );
				var p = form.querySelector( '[name="sssj_paged"]' );
				if ( ! p ) { p = document.createElement( 'input' ); p.type = 'hidden'; p.name = 'sssj_paged'; form.appendChild( p ); }
				p.value = m ? m[1] : '1';
				runAjax( form, false );
				res.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			} );
		} );
	}

	function runAjax( form, resetPage ) {
		var res = resultsFor( form );
		if ( ! res ) { submitForm( form ); return; }
		if ( resetPage ) { var p = form.querySelector( '[name="sssj_paged"]' ); if ( p ) { p.value = '1'; } }

		var fd = new FormData();
		fd.append( 'action', 'sssj_filter' );
		fd.append( 'board', form.getAttribute( 'data-sssj-board' ) || 'job' );
		eachField( form, function ( n, v ) { fd.append( n, v ); } );

		showSkeleton( res );
		fetch( cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( j ) {
				if ( j && j.success && j.data ) {
					res.innerHTML = j.data.html;
					afterRender( form, res );
					// Refresh the results-map markers so the pins match the filtered results.
					if ( window.SSSJMaps && typeof window.SSSJMaps.render === 'function' ) {
						window.SSSJMaps.render( j.data.points || [] );
					}
				}
				try { var qs = queryString( form ); window.history.replaceState( {}, '', window.location.pathname + ( qs ? ( '?' + qs ) : '' ) ); } catch ( e ) {}
			} )
			.catch( function () { res.innerHTML = '<div class="sssj-panel"><p>' + ( cfg.i18nError || 'Sorry, something went wrong. Please try again.' ) + '</p></div>'; } );
	}

	function submitForm( form ) {
		var paged = form.querySelector( '[name="sssj_paged"]' );
		if ( paged ) { paged.value = '1'; }
		if ( typeof form.requestSubmit === 'function' ) { form.requestSubmit(); } else { form.submit(); }
	}

	/** Apply filters, AJAX if the board opts in, else a GET reload. */
	function apply( form, resetPage ) {
		if ( resultsFor( form ) ) { runAjax( form, resetPage !== false ); } else { submitForm( form ); }
	}

	function setRadius( form, km ) {
		var r = form.querySelector( '[name="sssj_radius"]' );
		if ( ! r ) { return; }
		if ( 'SELECT' === r.tagName ) {
			var best = null;
			Array.prototype.forEach.call( r.options, function ( o ) {
				var v = parseInt( o.value, 10 );
				if ( v && ( best === null || ( v >= km && v < best ) ) ) { best = v; }
			} );
			r.value = String( best || km );
		} else {
			if ( ! parseInt( r.value, 10 ) ) { r.value = String( km ); }
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
				var latEl = form.querySelector( '[data-sssj-lat]' );
				var lngEl = form.querySelector( '[data-sssj-lng]' );
				var locEl = form.querySelector( '[data-sssj-place]' );
				if ( latEl ) { latEl.value = pos.coords.latitude.toFixed( 6 ); }
				if ( lngEl ) { lngEl.value = pos.coords.longitude.toFixed( 6 ); }
				if ( locEl ) { locEl.value = btn.getAttribute( 'data-mylocation' ) || '📍 My location'; }
				setRadius( form, DEFAULT_RADIUS );
				btn.disabled = false;
				btn.textContent = original;
				apply( form, true );
			},
			function () {
				btn.disabled = false;
				btn.textContent = original;
				alert( btn.getAttribute( 'data-denied' ) || 'Could not get your location. Please allow location access or type a suburb.' );
			},
			{ enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
		);
	}

	function clearAll() {
		// Clean reset, navigate to the bare path (works for both AJAX and non-AJAX boards).
		window.location = window.location.pathname;
	}

	function wireForm( form ) {
		var debounceTimer = null;
		var ready = false;
		setTimeout( function () { ready = true; }, 600 );

		var res = resultsFor( form );
		if ( res ) {
			bindPagination( form, res );
			form.addEventListener( 'submit', function ( e ) { e.preventDefault(); apply( form, true ); } );
		}

		// A place chosen from the autocomplete fills lat/lng (see sssj-maps.js) then fires this.
		// Default the radius so the location actually narrows results, then refresh.
		form.addEventListener( 'sssj:placechosen', function () {
			setRadius( form, DEFAULT_RADIUS );
			apply( form, true );
		} );

		form.addEventListener( 'change', function ( e ) {
			if ( ! ready ) { return; }
			var t = e.target;
			if ( ! t || ! t.name ) { return; }
			if ( t.matches( 'input[type="search"], input[type="text"]' ) ) { return; }
			apply( form, true );
		} );

		form.addEventListener( 'input', function ( e ) {
			if ( ! ready ) { return; }
			var t = e.target;
			if ( ! t || ! t.name ) { return; }
			if ( ! t.matches( 'input[type="search"], input[type="text"]' ) ) { return; }
			if ( t.hasAttribute( 'data-sssj-place' ) ) { return; }
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( function () { apply( form, true ); }, DEBOUNCE_MS );
		} );
	}

	/* ---------- Results layout (grid / list), remembered per browser ---------- */
	function currentView() { try { return window.localStorage.getItem( 'sssj_board_view' ) === 'list' ? 'list' : 'grid'; } catch ( e ) { return 'grid'; } }
	function applyView( view ) {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-grid]' ), function ( g ) { g.classList.toggle( 'sssj-grid--list', 'list' === view ); } );
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-view]' ), function ( b ) {
			var on = b.getAttribute( 'data-sssj-view' ) === view;
			b.classList.toggle( 'is-on', on );
			b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}
	function setView( view ) { try { window.localStorage.setItem( 'sssj_board_view', view ); } catch ( e ) {} applyView( view ); }

	/* ---------- Loading skeletons (shown while the AJAX request is in flight) ---------- */
	function showSkeleton( res ) {
		var cards = '';
		for ( var i = 0; i < 6; i++ ) {
			cards += '<div class="sssj-card sssj-skel"><div class="sssj-skel__line sssj-skel__line--title"></div><div class="sssj-skel__row"><span class="sssj-skel__pill"></span><span class="sssj-skel__pill"></span></div><div class="sssj-skel__line"></div><div class="sssj-skel__line sssj-skel__line--short"></div></div>';
		}
		res.innerHTML = '<div class="sssj-grid' + ( 'list' === currentView() ? ' sssj-grid--list' : '' ) + '" data-sssj-grid>' + cards + '</div>';
	}

	/* ---------- Result count (announced via aria-live) ---------- */
	function updateCount( res ) {
		var found = res.querySelector( '[data-sssj-found]' );
		var n = found ? ( parseInt( found.getAttribute( 'data-sssj-found' ), 10 ) || 0 ) : 0;
		var el = document.querySelector( '[data-sssj-count]' );
		if ( ! el ) { return; }
		var tpl = ( ( 1 === n ? el.getAttribute( 'data-one' ) : el.getAttribute( 'data-many' ) ) || '%d' );
		el.textContent = tpl.replace( '%d', n );
	}

	/* ---------- Active-filter chips ---------- */
	var BASIS_LABEL = { tfn: 'Employee (TFN)', abn: 'Contractor (ABN)', vol: 'Volunteer' };
	function chipsBox( form ) { var root = form.closest( '.sssj' ); return root ? root.querySelector( '[data-sssj-chips]' ) : null; }
	function addChip( box, label, onRemove ) {
		var c = document.createElement( 'button' );
		c.type = 'button';
		c.className = 'sssj-chip';
		var t = document.createElement( 'span' ); t.textContent = label; c.appendChild( t );
		var x = document.createElement( 'span' ); x.className = 'sssj-chip__x'; x.setAttribute( 'aria-hidden', 'true' ); x.textContent = '×'; c.appendChild( x );
		c.setAttribute( 'aria-label', 'Remove filter: ' + label );
		c.addEventListener( 'click', onRemove );
		box.appendChild( c );
	}
	function renderChips( form ) {
		var box = chipsBox( form );
		if ( ! box ) { return; }
		box.innerHTML = '';
		var q = form.querySelector( '[name="sssj_q"]' );
		if ( q && q.value.trim() ) { addChip( box, 'Search: ' + q.value.trim(), function () { q.value = ''; apply( form, true ); } ); }
		var loc = form.querySelector( '[name="sssj_loc"]' );
		if ( loc && loc.value.trim() ) { addChip( box, 'Near: ' + loc.value.trim(), function () {
			loc.value = '';
			var la = form.querySelector( '[data-sssj-lat]' ), ln = form.querySelector( '[data-sssj-lng]' );
			if ( la ) { la.value = ''; } if ( ln ) { ln.value = ''; }
			apply( form, true );
		} ); }
		var cat = form.querySelector( 'select[name="sssj_cat[]"]' );
		if ( cat ) {
			Array.prototype.forEach.call( cat.selectedOptions, function ( o ) {
				addChip( box, o.textContent, function () {
					if ( cat.tomselect ) { cat.tomselect.removeItem( o.value, true ); }
					else { o.selected = false; }
					apply( form, true );
				} );
			} );
		}
		Array.prototype.forEach.call( form.querySelectorAll( 'input[name="sssj_funding[]"]:checked' ), function ( cb ) {
			var lbl = cb.closest( 'label' );
			addChip( box, ( lbl ? lbl.textContent.trim() : cb.value ), function () { cb.checked = false; apply( form, true ); } );
		} );
		var basis = form.querySelector( '[data-sssj-basis]' );
		if ( basis && basis.value && BASIS_LABEL[ basis.value ] ) {
			addChip( box, BASIS_LABEL[ basis.value ], function () { setBasis( form, '' ); } );
		}
		var sort = form.querySelector( '[name="sssj_sort"]' );
		if ( sort && sort.value && 'newest' !== sort.value ) {
			var so = sort.options[ sort.selectedIndex ];
			addChip( box, ( so ? so.textContent : sort.value ), function () { sort.value = 'newest'; apply( form, true ); } );
		}
	}

	function setBasis( form, val ) {
		var hid = form.querySelector( '[data-sssj-basis]' );
		if ( hid ) { hid.value = val; }
		var root = form.closest( '.sssj' );
		if ( root ) {
			Array.prototype.forEach.call( root.querySelectorAll( '[data-sssj-basis-pick]' ), function ( b ) {
				var bv = b.getAttribute( 'data-sssj-basis-pick' );
				var on = ( ( 'all' === bv ? '' : bv ) === val );
				b.classList.toggle( 'is-on', on );
				b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
		}
		apply( form, true );
	}

	function afterRender( form, res ) {
		bindPagination( form, res );
		updateCount( res );
		applyView( currentView() );
		renderChips( form );
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-sssj-filter-form]' ), function ( form ) {
			wireForm( form );
			renderChips( form );
			var res = resultsFor( form );
			if ( res ) { updateCount( res ); }
		} );
		applyView( currentView() );
		document.addEventListener( 'click', function ( e ) {
			var here = e.target.closest( '[data-sssj-here]' );
			if ( here ) { e.preventDefault(); useMyLocation( here ); return; }
			var clear = e.target.closest( '[data-sssj-clear]' );
			if ( clear ) { e.preventDefault(); clearAll(); return; }
			var view = e.target.closest( '[data-sssj-view]' );
			if ( view ) { e.preventDefault(); setView( view.getAttribute( 'data-sssj-view' ) ); return; }
			var pick = e.target.closest( '[data-sssj-basis-pick]' );
			if ( pick ) {
				e.preventDefault();
				var root = pick.closest( '.sssj' );
				var form = root ? root.querySelector( '[data-sssj-filter-form]' ) : null;
				if ( form ) { var bv = pick.getAttribute( 'data-sssj-basis-pick' ); setBasis( form, 'all' === bv ? '' : bv ); }
			}
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
