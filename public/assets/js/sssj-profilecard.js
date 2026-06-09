/**
 * AI Profile Card: client-side canvas compositor for [sssj_profile_card].
 *
 * Asks the server for a TEXT-FREE AI background, then draws the member's location + services (top) and
 * name/tagline (bottom) onto a 1080x1080 canvas in the browser. Works on managed hosting (no server image
 * tools). Uses document-level event delegation so it survives full-page caching and late DOM.
 */
( function () {
	'use strict';

	var CFG = window.SSSJ_Card || {};
	var SIZE = 1080;

	var state = {
		ready: false,    // a composited card is on the canvas
		busy: false,
		scheme: null,
		member: null,
		style: '',
		bgImg: null
	};

	function $( sel, root ) { return ( root || document ).querySelector( sel ); }
	function root() { return $( '[data-sssj-card]' ); }
	function canvas() { return $( '#sssj-card-canvas' ); }

	function msg( text, kind ) {
		var el = $( '[data-card-msg]' );
		if ( ! el ) { return; }
		el.textContent = text || '';
		el.style.color = 'error' === kind ? '#b3261e' : ( 'ok' === kind ? '#1a7a3a' : '' );
	}

	function setBusy( on, label ) {
		state.busy = on;
		var btn = $( '[data-card-generate]' );
		if ( btn ) {
			btn.disabled = on;
			btn.textContent = on ? ( label || 'Creating…' ) : 'Create my card';
		}
	}

	/* ------------------------------------------------------------ drawing helpers */

	function roundRect( ctx, x, y, w, h, r ) {
		r = Math.min( r, h / 2, w / 2 );
		ctx.beginPath();
		ctx.moveTo( x + r, y );
		ctx.arcTo( x + w, y, x + w, y + h, r );
		ctx.arcTo( x + w, y + h, x, y + h, r );
		ctx.arcTo( x, y + h, x, y, r );
		ctx.arcTo( x, y, x + w, y, r );
		ctx.closePath();
	}

	// Draw a pill with auto width to its text; returns the pill width.
	function pill( ctx, text, x, y, font, bg, fg, padX, h, radius ) {
		ctx.font = font;
		var tw = ctx.measureText( text ).width;
		var w = tw + padX * 2;
		if ( bg && 'rgba(0,0,0,0)' !== bg ) {
			ctx.fillStyle = bg;
			roundRect( ctx, x, y, w, h, radius );
			ctx.fill();
		}
		ctx.fillStyle = fg;
		ctx.textBaseline = 'middle';
		ctx.textAlign = 'left';
		ctx.fillText( text, x + padX, y + h / 2 + 1 );
		return w;
	}

	// Lay out tag chips across the top, wrapping to a second row if needed.
	function tags( ctx, list, startX, startY, scheme ) {
		var x = startX, y = startY, gap = 14, h = 58, padX = 26, rowGap = 14, rows = 0;
		var font = '600 30px ' + ( scheme.font || 'Arial, sans-serif' );
		var radius = ( 'number' === typeof scheme.radius ) ? Math.max( scheme.radius, 8 ) : 20;
		for ( var i = 0; i < list.length && rows < 2; i++ ) {
			var t = String( list[ i ] );
			ctx.font = font;
			var w = ctx.measureText( t ).width + padX * 2;
			if ( x + w > SIZE - startX ) {
				x = startX;
				y += h + rowGap;
				rows++;
				if ( rows >= 2 ) { break; }
			}
			if ( scheme.tag_border ) {
				ctx.strokeStyle = scheme.tag_border;
				ctx.lineWidth = 2;
				roundRect( ctx, x, y, w, h, radius );
				ctx.stroke();
			}
			pill( ctx, t, x, y, font, scheme.tag_bg, scheme.tag_fg, padX, h, radius );
			x += w + gap;
		}
		return y + h;
	}

	function wrap( ctx, text, maxW ) {
		var words = String( text ).split( /\s+/ ), lines = [], line = '';
		for ( var i = 0; i < words.length; i++ ) {
			var test = line ? line + ' ' + words[ i ] : words[ i ];
			if ( ctx.measureText( test ).width > maxW && line ) {
				lines.push( line );
				line = words[ i ];
			} else {
				line = test;
			}
		}
		if ( line ) { lines.push( line ); }
		return lines;
	}

	function composite() {
		var cv = canvas();
		if ( ! cv || ! state.bgImg ) { return; }
		var ctx = cv.getContext( '2d' );
		var s = state.scheme || {};
		var m = state.member || {};
		var pad = 56;

		ctx.clearRect( 0, 0, SIZE, SIZE );
		ctx.drawImage( state.bgImg, 0, 0, SIZE, SIZE );

		// Wash for legibility.
		if ( s.overlay ) {
			ctx.fillStyle = s.overlay;
			ctx.fillRect( 0, 0, SIZE, SIZE );
		}

		var radius = ( 'number' === typeof s.radius ) ? s.radius : 8;

		// --- TOP: location pill, then service tags ---
		var topY = pad;
		if ( m.location ) {
			pill( ctx, '📍 ' + m.location, pad, topY, '700 34px ' + ( s.font || 'Arial, sans-serif' ),
				s.pill_bg || 'rgba(0,0,0,0.7)', s.pill_fg || '#fff', 30, 66, Math.max( radius, 10 ) );
			topY += 66 + 20;
		}
		if ( m.services && m.services.length ) {
			tags( ctx, m.services.slice( 0, 6 ), pad, topY, s );
		}

		// --- BOTTOM: name + tagline ---
		ctx.textAlign = 'left';
		ctx.textBaseline = 'alphabetic';
		var by = SIZE - pad;

		if ( m.tagline ) {
			ctx.font = '500 40px ' + ( s.font || 'Arial, sans-serif' );
			var tl = wrap( ctx, m.tagline, SIZE - pad * 2 ).slice( 0, 2 );
			for ( var i = tl.length - 1; i >= 0; i-- ) {
				ctx.fillStyle = s.tagline_fg || '#ddd';
				ctx.fillText( tl[ i ], pad, by );
				by -= 52;
			}
			by -= 8;
		}

		ctx.font = '800 74px ' + ( s.font || 'Arial, sans-serif' );
		ctx.fillStyle = s.name_fg || '#fff';
		var nm = wrap( ctx, m.name || '', SIZE - pad * 2 ).slice( 0, 2 );
		for ( var j = nm.length - 1; j >= 0; j-- ) {
			ctx.fillText( nm[ j ], pad, by );
			by -= 84;
		}

		// Brand stamp, bottom-right.
		if ( CFG.brand ) {
			ctx.font = '600 26px ' + ( s.font || 'Arial, sans-serif' );
			ctx.fillStyle = s.contact_fg || s.tagline_fg || 'rgba(255,255,255,0.85)';
			ctx.textAlign = 'right';
			ctx.fillText( CFG.brand, SIZE - pad, SIZE - pad + 8 );
			ctx.textAlign = 'left';
		}

		state.ready = true;
		var empty = $( '[data-card-empty]' );
		if ( empty ) { empty.style.display = 'none'; }
		var actions = $( '[data-card-actions]' );
		if ( actions ) { actions.style.display = 'flex'; }
	}

	/* ------------------------------------------------------------ network */

	function generate() {
		if ( state.busy ) { return; }
		var sel = $( '[data-card-style]' );
		var style = sel ? sel.value : 'clean_professional';
		var payload = { style: style };
		if ( 'custom' === style ) {
			var cf = $( '[data-card-custom]' );
			var custom = cf ? String( cf.value || '' ).trim() : '';
			if ( ! custom ) {
				msg( 'Describe the look you want, then create your card.', 'error' );
				if ( cf ) { cf.focus(); }
				return;
			}
			payload.custom = custom;
		}
		setBusy( true );
		msg( 'Creating your artwork, this can take up to a minute…' );

		fetch( CFG.generate, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
			credentials: 'same-origin',
			body: JSON.stringify( payload )
		} ).then( function ( r ) {
			return r.json().then( function ( d ) { return { ok: r.ok, d: d }; } );
		} ).then( function ( res ) {
			if ( ! res.ok || ! res.d || ! res.d.success ) {
				setBusy( false );
				msg( ( res.d && res.d.message ) ? res.d.message : 'Could not create the card. Please try again.', 'error' );
				return;
			}
			state.scheme = res.d.scheme || {};
			state.member = res.d.member || {};
			state.style = res.d.style || style;
			if ( res.d.usage ) { updateUsage( res.d.usage ); }

			var img = new Image();
			img.onload = function () {
				state.bgImg = img;
				composite();
				setBusy( false );
				msg( 'Done. Download it or save it to your media library.', 'ok' );
			};
			img.onerror = function () {
				setBusy( false );
				msg( 'The artwork could not be loaded. Please try again.', 'error' );
			};
			img.src = 'data:image/png;base64,' + res.d.background;
		} ).catch( function () {
			setBusy( false );
			msg( 'Network problem. Please try again.', 'error' );
		} );
	}

	function updateUsage( u ) {
		var el = $( '[data-card-usage]' );
		if ( el && u && 'number' === typeof u.used && 'number' === typeof u.limit && u.limit < 999 ) {
			el.textContent = 'Used ' + u.used + ' of ' + u.limit + ' this month.';
		}
	}

	function fileName() {
		return 'profile-card-' + ( state.style || 'card' ) + '.png';
	}

	function download() {
		var cv = canvas();
		if ( ! cv || ! state.ready ) { return; }
		cv.toBlob( function ( blob ) {
			if ( ! blob ) { msg( 'Could not build the image.', 'error' ); return; }
			var url = URL.createObjectURL( blob );
			var a = document.createElement( 'a' );
			a.href = url;
			a.download = fileName();
			document.body.appendChild( a );
			a.click();
			document.body.removeChild( a );
			setTimeout( function () { URL.revokeObjectURL( url ); }, 4000 );
		}, 'image/png' );
	}

	function saveToMedia() {
		var cv = canvas();
		if ( ! cv || ! state.ready ) { return; }
		msg( 'Saving to your media library…' );
		var data = cv.toDataURL( 'image/png' );
		fetch( CFG.save, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': CFG.nonce },
			credentials: 'same-origin',
			body: JSON.stringify( { png: data, style: state.style } )
		} ).then( function ( r ) {
			return r.json().then( function ( d ) { return { ok: r.ok, d: d }; } );
		} ).then( function ( res ) {
			if ( res.ok && res.d && res.d.success ) {
				msg( 'Saved to your media library.', 'ok' );
			} else {
				msg( ( res.d && res.d.message ) ? res.d.message : 'Could not save to media.', 'error' );
			}
		} ).catch( function () {
			msg( 'Network problem while saving.', 'error' );
		} );
	}

	/* ------------------------------------------------------------ delegation */

	document.addEventListener( 'click', function ( e ) {
		if ( ! root() ) { return; }
		var t = e.target;
		if ( t.closest( '[data-card-generate]' ) ) { e.preventDefault(); generate(); }
		else if ( t.closest( '[data-card-download]' ) ) { e.preventDefault(); download(); }
		else if ( t.closest( '[data-card-save]' ) ) { e.preventDefault(); saveToMedia(); }
	}, false );

	// Show the free-text box only when "Custom" is chosen.
	function syncCustom() {
		if ( ! root() ) { return; }
		var sel = $( '[data-card-style]' );
		var wrap = $( '[data-card-custom-wrap]' );
		if ( sel && wrap ) { wrap.style.display = ( 'custom' === sel.value ) ? 'block' : 'none'; }
	}
	document.addEventListener( 'change', function ( e ) {
		if ( e.target && e.target.closest && e.target.closest( '[data-card-style]' ) ) { syncCustom(); }
	}, false );
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', syncCustom );
	} else {
		syncCustom();
	}

}() );
