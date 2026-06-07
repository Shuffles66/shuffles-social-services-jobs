/**
 * Form enhancements for the sssj profile forms (worker / org / need). Additive + idempotent +
 * defensive: it groups existing field blocks into section cards and adds a completeness meter,
 * availability toggle, character counter, styled file inputs + photo preview, a sticky save bar,
 * toast + Ctrl+S — WITHOUT changing any field names, and without disturbing Tom Select, the suburb
 * autocomplete, the NDIS "Scan now" button or the existing submit spinner. Brand-aligned (uses the
 * sssj design tokens). Targets form.sssj-stack only (never the admin-bar search form).
 */
( function () {
	'use strict';

	var ICONS = {
		person: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>',
		camera: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8h4l2-3h6l2 3h4v11H3z"/><circle cx="12" cy="13" r="3.5"/></svg>',
		clock:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
		star:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg>',
		pin:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.5 7-11a7 7 0 10-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
		dollar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M16 7c0-2-2-3-4-3s-4 1-4 3 2 3 4 3 4 1 4 3-2 3-4 3-4-1-4-3"/></svg>',
		shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>',
		eye:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>'
	};

	// Section definitions: an anchor (the start field's leading label text) + title + icon.
	var SECTIONS = [
		{ a: 'Display name',  t: 'Basic information',        i: 'person' },
		{ a: 'Profile photo', t: 'Photos',                   i: 'camera' },
		{ a: 'I am available',t: 'Availability & status',    i: 'clock'  },
		{ a: 'Services you offer', t: 'Skills & services',   i: 'star'   },
		{ a: 'Your location', t: 'Location & travel',        i: 'pin'    },
		{ a: 'Years experience', t: 'Experience & rates',    i: 'dollar' },
		{ a: 'Your ABN',      t: 'Business & credentials',   i: 'shield' },
		{ a: 'Who can see',   t: 'Visibility & notifications', i: 'eye'  }
	];

	function ready( fn ) {
		if ( 'complete' === document.readyState ) { fn(); }
		else { window.addEventListener( 'load', fn ); }
	}

	function starts( el, text ) {
		return el && ( el.textContent || '' ).trim().toLowerCase().indexOf( text.toLowerCase() ) === 0;
	}

	ready( function () {
		var form = document.querySelector( 'form.sssj-stack' );
		if ( ! form || form.dataset.sssjEnhanced ) { return; }
		form.dataset.sssjEnhanced = '1';
		[ buildSections, completeness, availabilityToggle, charCounter, fileInputs, stickyBar, shortcuts, titleIndicator ].forEach( function ( fn ) {
			try { fn( form ); } catch ( e ) { /* never let one feature break the rest */ }
		} );
		setTimeout( function () { window.sssjToast( 'Tip: press Ctrl+S to save at any time', 'info', 4000 ); }, 1200 );
	} );

	/* ---- Section cards: wrap existing top-level field blocks into labelled cards ---- */
	function buildSections( form ) {
		var kids = Array.prototype.filter.call( form.children, function ( el ) { return el.nodeType === 1; } );
		// Find the start element for each section (first child block whose text starts with the anchor).
		var starts_ = [];
		SECTIONS.forEach( function ( sec ) {
			for ( var i = 0; i < kids.length; i++ ) {
				if ( starts( kids[ i ], sec.a ) ) { starts_.push( { sec: sec, el: kids[ i ], idx: i } ); break; }
			}
		} );
		if ( starts_.length < 2 ) { return; } // not the expected form — leave it alone
		starts_.forEach( function ( s, n ) {
			var stop = ( n + 1 < starts_.length ) ? starts_[ n + 1 ].el : null;
			// collect blocks from s.el up to (not incl) stop
			var nodes = [];
			var cur = s.el;
			while ( cur && cur !== stop ) { nodes.push( cur ); cur = cur.nextElementSibling; }
			var card = document.createElement( 'div' );
			card.className = 'sssj-section';
			card.style.animationDelay = ( n * 0.05 ) + 's';
			var h = document.createElement( 'h3' );
			h.className = 'sssj-section__title';
			h.innerHTML = ( ICONS[ s.sec.i ] || '' ) + '<span>' + esc( s.sec.t ) + '</span>';
			card.appendChild( h );
			s.el.parentNode.insertBefore( card, s.el );
			nodes.forEach( function ( nd ) { card.appendChild( nd ); } );
		} );
	}

	/* ---- Profile completeness meter (worker form) ---- */
	function completeness( form ) {
		if ( ! form.querySelector( '[name="display_name"]' ) ) { return; } // worker form only
		var box = document.createElement( 'div' );
		box.className = 'sssj-compl';
		box.innerHTML = '<div class="sssj-compl__head"><span>' + esc( 'Profile completeness' ) + '</span><span class="sssj-compl__pct">0%</span></div>'
			+ '<div class="sssj-compl__track"><div class="sssj-compl__fill"></div></div>'
			+ '<div class="sssj-compl__msg"></div>';
		var first = form.querySelector( '.sssj-section' );
		if ( first ) { first.parentNode.insertBefore( box, first ); } else { form.insertBefore( box, form.firstChild ); }
		var fill = box.querySelector( '.sssj-compl__fill' ), pct = box.querySelector( '.sssj-compl__pct' ), msg = box.querySelector( '.sssj-compl__msg' );

		function val( sel ) { var e = form.querySelector( sel ); return e ? String( e.value || '' ).trim() : ''; }
		function num( sel ) { return parseFloat( val( sel ) ) || 0; }
		function recompute() {
			var checks = [
				val( '[name="display_name"]' ) !== '',
				val( '[name="bio"]' ) !== '',
				!! ( form.querySelector( '[name="services[]"]' ) && form.querySelector( '[name="services[]"]' ).selectedOptions && form.querySelector( '[name="services[]"]' ).selectedOptions.length ),
				val( '[name="location_suburb"]' ) !== '',
				num( '[name="travel_radius_km"]' ) > 0,
				num( '[name="years_experience"]' ) > 0,
				num( '[name="rate_min"]' ) > 0,
				!! ( ( form.querySelector( '[name="worker_photo"]' ) && form.querySelector( '[name="worker_photo"]' ).files && form.querySelector( '[name="worker_photo"]' ).files.length ) || form.querySelector( 'img.sssj-worker-photo' ) )
			];
			var done = checks.filter( Boolean ).length;
			var p = Math.round( done / checks.length * 100 );
			fill.style.width = p + '%';
			pct.textContent = p + '%';
			msg.textContent = p >= 100 ? 'Great profile! You’re ready to be found. ✓'
				: p >= 75 ? 'Almost complete — just a few more fields!'
				: p >= 50 ? 'Halfway there! Add location and rates.'
				: p >= 25 ? 'Good start — add more details to stand out.'
				: 'Start filling in your profile to get found.';
		}
		form.addEventListener( 'input', recompute );
		form.addEventListener( 'change', recompute );
		recompute();
	}

	/* ---- Availability checkbox → pill toggle (keeps the real checkbox for submission) ---- */
	function availabilityToggle( form ) {
		var kids = Array.prototype.filter.call( form.querySelectorAll( '.sssj-field' ), function () { return true; } );
		var block = null;
		kids.forEach( function ( k ) { if ( ! block && starts( k, 'I am available' ) ) { block = k; } } );
		if ( ! block ) { return; }
		var cb = block.querySelector( 'input[type="checkbox"]' );
		var lbl = block.querySelector( 'label' );
		if ( ! cb || ! lbl ) { return; }
		lbl.style.display = 'none'; // keep in DOM (checkbox still submits)
		var t = document.createElement( 'div' );
		t.className = 'sssj-toggle' + ( cb.checked ? ' is-on' : '' );
		t.setAttribute( 'role', 'switch' );
		t.setAttribute( 'tabindex', '0' );
		t.innerHTML = '<span class="sssj-toggle__track"><span class="sssj-toggle__knob"></span></span>'
			+ '<span class="sssj-toggle__txt">' + esc( 'Available for work now' ) + '</span>'
			+ '<span class="sssj-toggle__badge">' + ( cb.checked ? 'Available' : 'Unavailable' ) + '</span>';
		block.appendChild( t );
		function sync() {
			t.classList.toggle( 'is-on', cb.checked );
			t.setAttribute( 'aria-checked', cb.checked ? 'true' : 'false' );
			t.querySelector( '.sssj-toggle__badge' ).textContent = cb.checked ? 'Available' : 'Unavailable';
		}
		function flip() { cb.checked = ! cb.checked; cb.dispatchEvent( new Event( 'change', { bubbles: true } ) ); sync(); }
		t.addEventListener( 'click', flip );
		t.addEventListener( 'keydown', function ( e ) { if ( e.key === ' ' || e.key === 'Enter' ) { e.preventDefault(); flip(); } } );
		sync();
	}

	/* ---- Character counter on the About textarea ---- */
	function charCounter( form ) {
		var ta = form.querySelector( 'textarea[name="bio"]' ) || form.querySelector( 'textarea' );
		if ( ! ta ) { return; }
		var limit = 600;
		if ( ! ta.getAttribute( 'placeholder' ) ) {
			ta.setAttribute( 'placeholder', 'Tell employers and participants about your background, skills, and approach to support work…' );
		}
		var c = document.createElement( 'div' );
		c.className = 'sssj-charcount';
		ta.parentNode.insertBefore( c, ta.nextSibling );
		function upd() {
			var n = ( ta.value || '' ).length;
			c.textContent = n + ' / ' + limit;
			c.classList.toggle( 'warn', n >= limit * 0.85 && n < limit );
			c.classList.toggle( 'over', n >= limit );
		}
		ta.addEventListener( 'input', upd );
		upd();
	}

	/* ---- Styled file inputs + profile-photo preview ---- */
	function fileInputs( form ) {
		var inputs = form.querySelectorAll( 'input[type="file"]' );
		Array.prototype.forEach.call( inputs, function ( inp, idx ) {
			if ( inp.dataset.sssjFile ) { return; }
			inp.dataset.sssjFile = '1';
			if ( ! inp.id ) { inp.id = 'sssj-file-' + idx; }
			inp.style.display = 'none';
			var label = document.createElement( 'label' );
			label.className = 'sssj-filelabel';
			label.setAttribute( 'for', inp.id );
			var isPhoto = idx === 0;
			label.innerHTML = ( isPhoto ? ICONS.person : ICONS.camera ) + '<span>' + esc( isPhoto ? 'Choose profile photo' : 'Add photos' ) + '</span>';
			inp.parentNode.insertBefore( label, inp );
			if ( isPhoto ) {
				var img = document.createElement( 'img' );
				img.className = 'sssj-photoprev';
				img.alt = '';
				inp.parentNode.insertBefore( img, inp.nextSibling );
				inp.addEventListener( 'change', function () {
					if ( inp.files && inp.files[ 0 ] ) {
						var r = new FileReader();
						r.onload = function ( e ) { img.src = e.target.result; img.classList.add( 'is-visible' ); };
						r.readAsDataURL( inp.files[ 0 ] );
						label.querySelector( 'span' ).textContent = inp.files[ 0 ].name;
					}
				} );
			} else {
				inp.addEventListener( 'change', function () {
					var n = inp.files ? inp.files.length : 0;
					label.querySelector( 'span' ).textContent = n ? ( n + ' photo' + ( n > 1 ? 's' : '' ) + ' selected' ) : 'Add photos';
				} );
			}
		} );
	}

	/* ---- Sticky save bar ---- */
	function stickyBar( form ) {
		if ( document.getElementById( 'sssj-stickybar' ) ) { return; }
		var submit = form.querySelector( 'button[type="submit"]' );
		if ( ! submit ) { return; }
		var bar = document.createElement( 'div' );
		bar.id = 'sssj-stickybar';
		bar.className = 'sssj-stickybar';
		bar.innerHTML = '<div class="sssj-stickybar__txt"><strong>' + esc( 'Your profile' ) + '</strong> · ' + esc( 'unsaved changes won’t be kept' ) + '</div>';
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'sssj-btn sssj-btn--primary';
		btn.textContent = 'Save now';
		btn.addEventListener( 'click', function () { submit.click(); } );
		bar.appendChild( btn );
		document.body.appendChild( bar );
		var touched = false;
		function upd() { bar.classList.toggle( 'is-visible', touched || window.scrollY > 300 ); }
		form.addEventListener( 'input', function () { touched = true; upd(); }, { once: false } );
		window.addEventListener( 'scroll', upd, { passive: true } );
		upd();
	}

	/* ---- Ctrl/Cmd+S to save ---- */
	function shortcuts( form ) {
		document.addEventListener( 'keydown', function ( e ) {
			if ( ( e.ctrlKey || e.metaKey ) && ( e.key === 's' || e.key === 'S' ) ) {
				e.preventDefault();
				var s = form.querySelector( 'button[type="submit"]' );
				if ( s ) { window.sssjToast( 'Saving your profile…', 'info' ); s.click(); }
			}
		} );
	}

	/* ---- Unsaved-changes title indicator ---- */
	function titleIndicator( form ) {
		form.addEventListener( 'input', function () {
			if ( document.title.indexOf( '● ' ) !== 0 ) { document.title = '● ' + document.title; }
		} );
		form.addEventListener( 'submit', function () {
			document.title = document.title.replace( /^● /, '' );
		} );
	}

	/* ---- Toast ---- */
	window.sssjToast = function ( message, type, duration ) {
		type = type || 'info';
		duration = duration || 3500;
		var t = document.getElementById( 'sssj-toast' );
		if ( ! t ) { t = document.createElement( 'div' ); t.id = 'sssj-toast'; document.body.appendChild( t ); }
		t.className = 'sssj-toast is-' + type;
		t.textContent = message;
		// force reflow so the transition runs even on rapid repeats
		void t.offsetWidth;
		t.classList.add( 'is-show' );
		clearTimeout( t._sssjTimer );
		t._sssjTimer = setTimeout( function () { t.classList.remove( 'is-show' ); }, duration );
	};

	function esc( s ) { var d = document.createElement( 'div' ); d.textContent = String( s == null ? '' : s ); return d.innerHTML; }
}() );
