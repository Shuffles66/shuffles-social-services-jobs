/* Shuffles Social Services Jobs — self-promotion studio.
 * Cycles the platform "positives", swaps the on-screen card + caption, lets you pick a colour style,
 * and saves a 1080x1080 PNG by drawing it straight to a <canvas> (reliable across browsers — no
 * SVG/foreignObject trick that Chrome refuses to export).
 *
 * Robustness: clicks/changes are delegated on `document`, and the current highlight + style are read
 * from the DOM each time, so the buttons work no matter when the studio markup appears.
 */
( function () {
	'use strict';

	var data = window.SSSJ_Promo || {};

	// Colour styles — MUST mirror .sssj .sssj-promo[data-accent="N"] in sssj-assets.css.
	var STYLES = [
		{ bg: [ [ 0, '#1e3a8a' ], [ 0.55, '#2563eb' ], [ 1, '#0ea5e9' ] ] }, // 0 Ocean
		{ bg: [ [ 0, '#0f766e' ], [ 0.60, '#10b981' ], [ 1, '#34d399' ] ] }, // 1 Forest
		{ bg: [ [ 0, '#6d28d9' ], [ 0.55, '#6366f1' ], [ 1, '#3b82f6' ] ] }, // 2 Grape
		{ bg: [ [ 0, '#b91c1c' ], [ 0.70, '#f59e0b' ], [ 1, '#fbbf24' ] ] }, // 3 Sunrise
		{ bg: [ [ 0, '#0f172a' ], [ 0.50, '#334155' ], [ 1, '#475569' ] ] }, // 4 Midnight
		{ bg: [ [ 0, '#9d174d' ], [ 0.55, '#db2777' ], [ 1, '#f472b6' ] ] }  // 5 Rose
	];
	var FONT = '"Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
	var EMOJI_FONT = '"Segoe UI Emoji", "Apple Color Emoji", "Noto Color Emoji", ' + FONT;

	function msg( root, t ) {
		var el = root.querySelector( '[data-promo-msg]' );
		if ( el ) { el.textContent = t; }
	}
	function itemAt( idx ) {
		return ( data.items && data.items[ idx ] ) ? data.items[ idx ] : null;
	}
	function curIdx( root ) {
		var p = root.querySelector( '[data-promo-pick]' );
		return p ? ( parseInt( p.value, 10 ) || 0 ) : 0;
	}
	function curStyle( root ) {
		var s = root.querySelector( '[data-promo-style]' );
		return s ? ( parseInt( s.value, 10 ) || 0 ) : 0;
	}

	function wrap( ctx, text, x, y, maxW, lineH ) {
		var words = String( text || '' ).split( /\s+/ );
		var line  = '';
		for ( var k = 0; k < words.length; k++ ) {
			var test = line ? ( line + ' ' + words[ k ] ) : words[ k ];
			if ( ctx.measureText( test ).width > maxW && line ) {
				ctx.fillText( line, x, y );
				line = words[ k ];
				y += lineH;
			} else {
				line = test;
			}
		}
		if ( line ) { ctx.fillText( line, x, y ); y += lineH; }
		return y;
	}

	function roundRect( ctx, x, y, w, h, r ) {
		ctx.beginPath();
		ctx.moveTo( x + r, y );
		ctx.arcTo( x + w, y, x + w, y + h, r );
		ctx.arcTo( x + w, y + h, x, y + h, r );
		ctx.arcTo( x, y + h, x, y, r );
		ctx.arcTo( x, y, x + w, y, r );
		ctx.closePath();
	}

	/* Render one positive to a square PNG and hand the Blob to cb. */
	function draw( item, styleIdx, cb ) {
		var W = 1080, H = 1080;
		var c = document.createElement( 'canvas' );
		c.width = W; c.height = H;
		var ctx = c.getContext( '2d' );
		if ( ! ctx || ! c.toBlob ) { cb( null ); return; }

		var S  = STYLES[ styleIdx ] || STYLES[ 0 ];
		var fg = '#ffffff';
		var g  = ctx.createLinearGradient( 0, 0, W, H );
		S.bg.forEach( function ( s ) { g.addColorStop( s[ 0 ], s[ 1 ] ); } );
		ctx.fillStyle = g;
		ctx.fillRect( 0, 0, W, H );

		var pad = 88;
		ctx.fillStyle = fg;
		ctx.textBaseline = 'alphabetic';

		ctx.font = '800 50px ' + FONT;
		ctx.fillText( data.brand || '', pad, pad + 44 );

		var x = pad, y = 360;
		if ( item.emoji ) {
			ctx.font = '92px ' + EMOJI_FONT;
			ctx.fillText( item.emoji, x, y );
			y += 56;
		}
		if ( item.eyebrow ) {
			ctx.globalAlpha = 0.9;
			ctx.font = '700 30px ' + FONT;
			y = wrap( ctx, String( item.eyebrow ).toUpperCase(), x, y + 34, W - pad * 2, 40 );
			ctx.globalAlpha = 1;
		}
		if ( 'stat' === item.kind && item.big ) {
			ctx.font = '900 190px ' + FONT;
			ctx.fillText( item.big, x, y + 150 );
			y += 178;
			ctx.font = '700 46px ' + FONT;
			y = wrap( ctx, item.sub, x, y + 8, W - pad * 2, 56 );
		} else {
			ctx.font = '900 72px ' + FONT;
			y = wrap( ctx, item.headline, x, y + 64, W - pad * 2, 82 );
			ctx.globalAlpha = 0.96;
			ctx.font = '600 40px ' + FONT;
			y = wrap( ctx, item.sub, x, y + 26, W - pad * 2, 52 );
			ctx.globalAlpha = 1;
		}

		var fy = H - pad + 6;
		ctx.font = '700 30px ' + FONT;
		var url = data.host || '';
		var uw  = ctx.measureText( url ).width;
		ctx.globalAlpha = 0.18;
		ctx.fillStyle = '#ffffff';
		roundRect( ctx, pad - 16, fy - 38, uw + 32, 54, 27 );
		ctx.fill();
		ctx.globalAlpha = 1;
		ctx.fillStyle = fg;
		ctx.fillText( url, pad, fy );
		var safe = '🛡 ' + ( data.safety || '' );
		var sw   = ctx.measureText( safe ).width;
		ctx.fillText( safe, W - pad - sw, fy );

		c.toBlob( function ( b ) { cb( b ); }, 'image/png' );
	}

	function show( root, idx, styleIdx ) {
		var it = itemAt( idx );
		if ( ! it ) { return; }
		var body = root.querySelector( '[data-promo-body]' );
		if ( body ) { body.innerHTML = it.body; }
		var card = root.querySelector( '#sssj-asset' );
		if ( card ) { card.setAttribute( 'data-accent', String( styleIdx ) ); }
		var cap = root.querySelector( '[data-promo-caption]' );
		if ( cap ) { cap.value = it.caption || ''; }
		var pick = root.querySelector( '[data-promo-pick]' );
		if ( pick ) { pick.value = String( idx ); }
	}

	function saveImage( root ) {
		var it = itemAt( curIdx( root ) );
		if ( ! it ) { msg( root, 'Nothing to save yet.' ); return; }
		msg( root, 'Building image…' );
		try {
			draw( it, curStyle( root ), function ( blob ) {
				if ( ! blob ) { msg( root, 'Sorry, the image could not be built in this browser.' ); return; }
				var u = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = u;
				a.download = it.filename || 'shuffles-promo.png';
				document.body.appendChild( a ); a.click(); document.body.removeChild( a );
				URL.revokeObjectURL( u );
				msg( root, 'Image saved (1080×1080).' );
			} );
		} catch ( err ) {
			msg( root, 'Sorry, the image could not be built in this browser.' );
		}
	}

	function copyCaption( root ) {
		var cap = root.querySelector( '[data-promo-caption]' );
		var text = cap ? cap.value : '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then(
				function () { msg( root, 'Caption copied. Paste it into your post.' ); },
				function () { msg( root, 'Could not copy automatically — select the caption and copy it.' ); }
			);
		} else {
			msg( root, 'Select the caption above and copy it.' );
		}
	}

	function onClick( e ) {
		var el  = e.target;
		var hit = el.closest ? el.closest( '[data-promo-nav],[data-promo-save],[data-promo-copy]' ) : null;
		if ( ! hit ) { return; }
		var root = hit.closest( '[data-sssj-promo]' );
		if ( ! root ) { return; }
		e.preventDefault();
		if ( hit.hasAttribute( 'data-promo-nav' ) ) {
			var len = ( data.items || [] ).length;
			if ( ! len ) { return; }
			var step = ( 'next' === hit.getAttribute( 'data-promo-nav' ) ) ? 1 : -1;
			var idx  = ( ( curIdx( root ) + step ) % len + len ) % len;
			show( root, idx, curStyle( root ) );
		} else if ( hit.hasAttribute( 'data-promo-save' ) ) {
			saveImage( root );
		} else if ( hit.hasAttribute( 'data-promo-copy' ) ) {
			copyCaption( root );
		}
	}

	function onChange( e ) {
		var el   = e.target;
		var root = el.closest ? el.closest( '[data-sssj-promo]' ) : null;
		if ( ! root ) { return; }
		if ( el.hasAttribute( 'data-promo-pick' ) ) {
			show( root, parseInt( el.value, 10 ) || 0, curStyle( root ) );
		} else if ( el.hasAttribute( 'data-promo-style' ) ) {
			var card = root.querySelector( '#sssj-asset' );
			if ( card ) { card.setAttribute( 'data-accent', String( parseInt( el.value, 10 ) || 0 ) ); }
		}
	}

	function boot() {
		if ( ! data.items || ! data.items.length ) {
			if ( window.console && console.warn ) { console.warn( 'SSSJ_Promo: no data — self-promo studio inactive.' ); }
			return;
		}
		document.addEventListener( 'click', onClick );
		document.addEventListener( 'change', onChange );
		var root = document.querySelector( '[data-sssj-promo]' );
		if ( root ) {
			var i  = ( typeof data.start === 'number' ) ? data.start : 0;
			var st = ( data.items[ i ] && typeof data.items[ i ].accent !== 'undefined' ) ? data.items[ i ].accent : 0;
			var sp = root.querySelector( '[data-promo-style]' );
			if ( sp ) { sp.value = String( st ); }
			show( root, i, st );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
