/* Shuffles Social Services Jobs — self-promotion studio.
 * Cycles the platform "positives", swaps the on-screen card + caption, lets you pick a colour style,
 * and saves a 1080x1080 PNG by drawing it straight to a <canvas> (reliable across browsers — no
 * SVG/foreignObject trick that Chrome refuses to export).
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

	/* Word-wrap text; returns the y after the last line. */
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

		// Brand (top-left)
		ctx.font = '800 50px ' + FONT;
		ctx.fillText( data.brand || '', pad, pad + 44 );

		// Body block
		var x = pad, y = 360;
		if ( item.emoji ) {
			ctx.font = '92px ' + EMOJI_FONT;
			ctx.fillText( item.emoji, x, y );
			y += 56;
		}
		if ( item.eyebrow ) {
			ctx.globalAlpha = 0.9;
			ctx.font = '700 30px ' + FONT;
			y = wrap( ctx, item.eyebrow.toUpperCase(), x, y + 34, W - pad * 2, 40 );
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

		// Footer: URL pill (left) + safety (right)
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

	function init() {
		var root = document.querySelector( '[data-sssj-promo]' );
		if ( ! root || ! data.items || ! data.items.length ) { return; }

		var bodyEl    = root.querySelector( '[data-promo-body]' );
		var card      = root.querySelector( '#sssj-asset' );
		var cap       = root.querySelector( '[data-promo-caption]' );
		var pick      = root.querySelector( '[data-promo-pick]' );
		var stylePick = root.querySelector( '[data-promo-style]' );
		var i         = ( typeof data.start === 'number' ) ? data.start : 0;
		var styleIdx  = ( data.items[ i ] && typeof data.items[ i ].accent !== 'undefined' ) ? data.items[ i ].accent : 0;

		function show( n ) {
			var len = data.items.length;
			i = ( ( n % len ) + len ) % len;
			var it = data.items[ i ];
			if ( bodyEl ) { bodyEl.innerHTML = it.body; }
			if ( card ) { card.setAttribute( 'data-accent', String( styleIdx ) ); }
			if ( cap ) { cap.value = it.caption || ''; }
			if ( pick ) { pick.value = String( i ); }
		}

		function setStyle( s ) {
			var len = STYLES.length;
			styleIdx = ( ( s % len ) + len ) % len;
			if ( card ) { card.setAttribute( 'data-accent', String( styleIdx ) ); }
			if ( stylePick ) { stylePick.value = String( styleIdx ); }
		}

		root.addEventListener( 'click', function ( e ) {
			var nav = e.target.closest && e.target.closest( '[data-promo-nav]' );
			if ( nav ) {
				e.preventDefault();
				show( i + ( 'next' === nav.getAttribute( 'data-promo-nav' ) ? 1 : -1 ) );
				return;
			}
			var save = e.target.closest && e.target.closest( '[data-promo-save]' );
			if ( save ) {
				e.preventDefault();
				msg( root, 'Building image…' );
				try {
					draw( data.items[ i ], styleIdx, function ( blob ) {
						if ( ! blob ) { msg( root, 'Sorry, the image could not be built in this browser.' ); return; }
						var u = URL.createObjectURL( blob );
						var a = document.createElement( 'a' );
						a.href = u;
						a.download = data.items[ i ].filename || 'shuffles-promo.png';
						document.body.appendChild( a ); a.click(); document.body.removeChild( a );
						URL.revokeObjectURL( u );
						msg( root, 'Image saved (1080×1080).' );
					} );
				} catch ( err ) {
					msg( root, 'Sorry, the image could not be built in this browser.' );
				}
				return;
			}
			var copy = e.target.closest && e.target.closest( '[data-promo-copy]' );
			if ( copy ) {
				e.preventDefault();
				var text = cap ? cap.value : ( data.items[ i ].caption || '' );
				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( text ).then(
						function () { msg( root, 'Caption copied. Paste it into your post.' ); },
						function () { msg( root, 'Could not copy automatically — select the caption and copy it.' ); }
					);
				} else {
					msg( root, 'Select the caption above and copy it.' );
				}
			}
		} );

		if ( pick ) { pick.addEventListener( 'change', function () { show( parseInt( pick.value, 10 ) || 0 ); } ); }
		if ( stylePick ) { stylePick.addEventListener( 'change', function () { setStyle( parseInt( stylePick.value, 10 ) || 0 ); } ); }

		setStyle( styleIdx );
		show( i );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
