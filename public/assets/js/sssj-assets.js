/**
 * Shareable-asset wizard (résumé / flyer / social). $0 browser path:
 *   live preview, theme picker, Download PDF (browser print), Save image (themed canvas PNG), Copy caption.
 *
 * "Save image" draws the asset onto a <canvas> from a server-provided data blob (#sssj-asset-data) using
 * the chosen colour theme, so it works reliably in every browser (the old SVG/foreignObject export taints
 * the canvas in Chrome). No dependencies.
 */
( function () {
	'use strict';

	var FONT = 'Arial, "Segoe UI", Helvetica, sans-serif';

	var THEMES = {
		teal:    { ac: '#0e6e8c', deep: '#0b566f', soft: '#e8f1f4', h1: '#e7f3f6', h2: '#eef4fb' },
		indigo:  { ac: '#4f46e5', deep: '#3730a3', soft: '#eef2ff', h1: '#eef2ff', h2: '#f5f3ff' },
		plum:    { ac: '#7c3aed', deep: '#5b21b6', soft: '#f3e8ff', h1: '#f5f3ff', h2: '#faf5ff' },
		rose:    { ac: '#be185d', deep: '#9d174d', soft: '#fce7f0', h1: '#fdf2f8', h2: '#fff1f2' },
		emerald: { ac: '#047857', deep: '#065f46', soft: '#dcfce7', h1: '#ecfdf5', h2: '#f0fdf4' },
		amber:   { ac: '#b45309', deep: '#92400e', soft: '#fef3c7', h1: '#fffbeb', h2: '#fff7ed' },
		slate:   { ac: '#334155', deep: '#1e293b', soft: '#e2e8f0', h1: '#f1f5f9', h2: '#f8fafc' }
	};
	var THEME_KEYS = [ 'teal', 'indigo', 'plum', 'rose', 'emerald', 'amber', 'slate' ];

	function msg( root, text ) {
		var el = root.querySelector( '[data-asset-msg]' );
		if ( el ) { el.textContent = text; }
	}

	/* ---------- live preview editing ---------- */
	function bindEditing( root ) {
		var inputs = root.querySelectorAll( '[data-edit]' );
		Array.prototype.forEach.call( inputs, function ( input ) {
			var key = input.getAttribute( 'data-edit' );
			input.addEventListener( 'input', function () {
				var target = root.querySelector( '[data-bind="' + key + '"]' );
				if ( target ) { target.textContent = input.value; }
				if ( 'blurb' === key ) {
					var wrapEl = root.querySelector( '[data-block="about"]' );
					if ( wrapEl ) { if ( input.value.trim() === '' ) { wrapEl.setAttribute( 'hidden', '' ); } else { wrapEl.removeAttribute( 'hidden' ); } }
					var c = root.querySelector( '[data-blurb-count]' );
					if ( c ) { var n = input.value.trim() ? input.value.trim().split( /\s+/ ).length : 0; c.textContent = n + ' words' + ( n > 70 ? ' (try to keep it under 70)' : '' ); }
				}
			} );
		} );
		var c = root.querySelector( '[data-blurb-count]' );
		var b = root.querySelector( '[data-edit="blurb"]' );
		if ( c && b ) { var n = b.value.trim() ? b.value.trim().split( /\s+/ ).length : 0; c.textContent = n + ' words' + ( n > 70 ? ' (try to keep it under 70)' : '' ); }
	}

	/* ---------- theme picker ---------- */
	function applyTheme( root, key ) {
		var node = root.querySelector( '#sssj-asset' );
		if ( ! node ) { return; }
		THEME_KEYS.forEach( function ( k ) { node.classList.remove( 'sssj-asset--theme-' + k ); } );
		if ( key && 'teal' !== key ) { node.classList.add( 'sssj-asset--theme-' + key ); }
	}
	function currentTheme( root ) {
		var sel = root.querySelector( '[data-asset-theme]' );
		return sel ? sel.value : 'teal';
	}
	function initTheme( root ) {
		var sel = root.querySelector( '[data-asset-theme]' );
		if ( ! sel ) { return; }
		var saved = null;
		try { saved = window.localStorage.getItem( 'sssj_asset_theme' ); } catch ( e ) {}
		if ( saved ) {
			for ( var i = 0; i < sel.options.length; i++ ) { if ( sel.options[ i ].value === saved ) { sel.value = saved; } }
		}
		applyTheme( root, sel.value );
		sel.addEventListener( 'change', function () {
			applyTheme( root, sel.value );
			try { window.localStorage.setItem( 'sssj_asset_theme', sel.value ); } catch ( e ) {}
		} );
	}

	/* ---------- canvas helpers ---------- */
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
	function wrap( ctx, text, maxW ) {
		var words = String( text || '' ).split( /\s+/ ), lines = [], line = '';
		for ( var i = 0; i < words.length; i++ ) {
			var test = line ? line + ' ' + words[ i ] : words[ i ];
			if ( ctx.measureText( test ).width > maxW && line ) { lines.push( line ); line = words[ i ]; }
			else { line = test; }
		}
		if ( line ) { lines.push( line ); }
		return lines;
	}
	function initials( name ) {
		name = String( name || '' ).trim();
		if ( ! name ) { return '★'; }
		var p = name.split( /\s+/ );
		var s = p[ 0 ].charAt( 0 );
		if ( p.length > 1 ) { s += p[ p.length - 1 ].charAt( 0 ); }
		return s.toUpperCase();
	}
	function heading( ctx, text, T, y, pad, draw ) {
		y += 28;
		if ( draw ) {
			ctx.fillStyle = T.ac; ctx.fillRect( pad, y - 14, 4, 18 );
			ctx.fillStyle = '#64748b'; ctx.font = '700 13px ' + FONT; ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
			ctx.fillText( String( text ).toUpperCase(), pad + 12, y );
		}
		return y + 6;
	}
	function drawChips( ctx, list, T, y, pad, cw, draw ) {
		var x = pad, gap = 10, h = 40, padX = 16, rowGap = 10;
		y += 18;
		ctx.font = '700 16px ' + FONT;
		for ( var i = 0; i < list.length; i++ ) {
			var t = String( list[ i ] );
			var w = ctx.measureText( t ).width + padX * 2;
			if ( x + w > pad + cw && x > pad ) { x = pad; y += h + rowGap; }
			if ( draw ) {
				ctx.fillStyle = T.soft; roundRect( ctx, x, y, w, h, 999 ); ctx.fill();
				ctx.fillStyle = T.deep; ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
				ctx.fillText( t, x + padX, y + h / 2 + 1 );
			}
			x += w + gap;
		}
		return y + h;
	}
	function drawChecks( ctx, list, y, pad, cw, draw ) {
		var x = pad, gap = 22, lineH = 30;
		y += 26;
		ctx.font = '700 16px ' + FONT;
		for ( var i = 0; i < list.length; i++ ) {
			var label = String( list[ i ] );
			var w = 22 + ctx.measureText( label ).width;
			if ( x + w > pad + cw && x > pad ) { x = pad; y += lineH; }
			if ( draw ) {
				ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
				ctx.fillStyle = '#15803d'; ctx.font = '800 16px ' + FONT; ctx.fillText( '✓', x, y );
				ctx.fillStyle = '#0f172a'; ctx.font = '700 16px ' + FONT; ctx.fillText( label, x + 22, y );
			}
			x += w + gap;
		}
		return y;
	}

	/* ---------- portrait (résumé / flyer) ---------- */
	function layoutPortrait( ctx, d, T, photo, draw, W, pad, cw ) {
		var y = 0, headH = 184;
		if ( draw ) {
			var g = ctx.createLinearGradient( 0, 0, W, headH );
			g.addColorStop( 0, T.h1 ); g.addColorStop( 1, T.h2 );
			ctx.fillStyle = g; ctx.fillRect( 0, 0, W, headH );
		}
		var av = 112, ax = pad, ayc = headH / 2, round = ( false !== d.avatarRound );
		if ( draw ) {
			ctx.save();
			ctx.beginPath();
			if ( round ) { ctx.arc( ax + av / 2, ayc, av / 2, 0, 6.2832 ); } else { roundRect( ctx, ax, ayc - av / 2, av, av, 16 ); }
			ctx.closePath(); ctx.fillStyle = '#fff'; ctx.fill();
			ctx.beginPath();
			if ( round ) { ctx.arc( ax + av / 2, ayc, av / 2 - 5, 0, 6.2832 ); } else { roundRect( ctx, ax + 5, ayc - av / 2 + 5, av - 10, av - 10, 12 ); }
			ctx.closePath();
			if ( photo ) { ctx.clip(); ctx.drawImage( photo, ax + 5, ayc - av / 2 + 5, av - 10, av - 10 ); }
			else { ctx.fillStyle = T.ac; ctx.fill(); ctx.fillStyle = '#fff'; ctx.font = '700 44px ' + FONT; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText( initials( d.name ), ax + av / 2, ayc + 2 ); }
			ctx.restore();
		}
		var tx = ax + av + 26, tw = W - tx - pad;
		ctx.font = '800 40px ' + FONT;
		var nameLines = wrap( ctx, d.name, tw ).slice( 0, 2 );
		ctx.font = '700 22px ' + FONT;
		var roleLines = d.role ? wrap( ctx, d.role, tw ).slice( 0, 2 ) : [];
		var blockH = nameLines.length * 46 + roleLines.length * 28 + ( d.location ? 30 : 0 );
		var ty = ayc - blockH / 2 + 34;
		if ( draw ) {
			ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
			ctx.fillStyle = '#0f172a'; ctx.font = '800 40px ' + FONT;
			for ( var i = 0; i < nameLines.length; i++ ) { ctx.fillText( nameLines[ i ], tx, ty ); ty += 46; }
			if ( roleLines.length ) { ctx.fillStyle = T.ac; ctx.font = '700 22px ' + FONT; for ( var r = 0; r < roleLines.length; r++ ) { ctx.fillText( roleLines[ r ], tx, ty ); ty += 28; } }
			if ( d.location ) { ctx.fillStyle = '#0f172a'; ctx.font = '700 20px ' + FONT; ctx.fillText( '📍 ' + d.location, tx, ty + 2 ); }
		}
		y = headH;

		if ( d.facts && d.facts.length ) {
			y += 22;
			var fh = 72, n = Math.min( d.facts.length, 3 ), colW = cw / n;
			if ( draw ) {
				ctx.strokeStyle = '#e2e8f0'; ctx.lineWidth = 1; roundRect( ctx, pad, y, cw, fh, 12 ); ctx.stroke();
				for ( var fi = 0; fi < n; fi++ ) {
					var cxx = pad + colW * fi;
					if ( fi > 0 ) { ctx.beginPath(); ctx.moveTo( cxx, y + 12 ); ctx.lineTo( cxx, y + fh - 12 ); ctx.strokeStyle = '#e2e8f0'; ctx.stroke(); }
					ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic';
					ctx.fillStyle = T.ac; ctx.font = '700 12px ' + FONT;
					ctx.fillText( String( d.facts[ fi ].label ).toUpperCase(), cxx + 16, y + 28 );
					ctx.fillStyle = '#0f172a'; ctx.font = '700 17px ' + FONT;
					ctx.fillText( ( wrap( ctx, String( d.facts[ fi ].value ), colW - 28 )[ 0 ] || '' ), cxx + 16, y + 52 );
				}
			}
			y += fh;
		}

		if ( d.about && d.about.trim() ) {
			y = heading( ctx, d.aboutLabel || 'About me', T, y, pad, draw );
			ctx.font = '400 18px ' + FONT;
			var lines = wrap( ctx, d.about.trim(), cw );
			for ( var ai = 0; ai < lines.length; ai++ ) { y += 26; if ( draw ) { ctx.fillStyle = '#1f2937'; ctx.textAlign = 'left'; ctx.textBaseline = 'alphabetic'; ctx.fillText( lines[ ai ], pad, y ); } }
			y += 6;
		}

		if ( d.chips && d.chips.length ) {
			y = heading( ctx, d.chipsLabel || 'What I help with', T, y, pad, draw );
			y = drawChips( ctx, d.chips, T, y, pad, cw, draw );
		}

		if ( d.checks && d.checks.length ) {
			y = heading( ctx, 'Verified checks', T, y, pad, draw );
			y = drawChecks( ctx, d.checks, y, pad, cw, draw );
		}

		if ( d.cta ) {
			y += 28;
			var ch = 64;
			if ( draw ) {
				ctx.fillStyle = T.ac; roundRect( ctx, pad, y, cw, ch, 12 ); ctx.fill();
				ctx.fillStyle = '#fff'; ctx.font = '800 19px ' + FONT; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
				ctx.fillText( ( wrap( ctx, d.cta, cw - 40 )[ 0 ] || d.cta ), W / 2, y + ch / 2 + 1 );
			}
			y += ch;
		}
		return y + 38;
	}

	function renderPortrait( d, T, photo ) {
		var W = 900, pad = 44, cw = W - pad * 2;
		var m = document.createElement( 'canvas' ); m.width = W; m.height = 10;
		var H = layoutPortrait( m.getContext( '2d' ), d, T, null, false, W, pad, cw );
		var cv = document.createElement( 'canvas' ); cv.width = W; cv.height = Math.max( 300, Math.ceil( H ) );
		var ctx = cv.getContext( '2d' );
		ctx.fillStyle = '#ffffff'; ctx.fillRect( 0, 0, W, cv.height );
		layoutPortrait( ctx, d, T, photo, true, W, pad, cw );
		return cv;
	}

	/* ---------- square (social) ---------- */
	function renderSquare( d, T, photo ) {
		var S = 900, pad = 60, cx = S / 2;
		var cv = document.createElement( 'canvas' ); cv.width = S; cv.height = S;
		var ctx = cv.getContext( '2d' );
		var g = ctx.createLinearGradient( 0, 0, S, S ); g.addColorStop( 0, T.h1 ); g.addColorStop( 0.6, '#ffffff' ); g.addColorStop( 1, T.h2 );
		ctx.fillStyle = g; ctx.fillRect( 0, 0, S, S );

		var av = 156, ay = 110;
		ctx.save();
		ctx.beginPath(); ctx.arc( cx, ay + av / 2, av / 2, 0, 6.2832 ); ctx.closePath(); ctx.fillStyle = '#fff'; ctx.fill();
		ctx.beginPath(); ctx.arc( cx, ay + av / 2, av / 2 - 6, 0, 6.2832 ); ctx.closePath();
		if ( photo ) { ctx.clip(); ctx.drawImage( photo, cx - av / 2 + 6, ay + 6, av - 12, av - 12 ); }
		else { ctx.fillStyle = T.ac; ctx.fill(); ctx.fillStyle = '#fff'; ctx.font = '700 58px ' + FONT; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText( initials( d.name ), cx, ay + av / 2 + 2 ); }
		ctx.restore();

		var y = ay + av + 56;
		ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic';
		ctx.fillStyle = '#0f172a'; ctx.font = '800 46px ' + FONT;
		var nl = wrap( ctx, d.name, S - pad * 2 ).slice( 0, 2 );
		for ( var i = 0; i < nl.length; i++ ) { ctx.fillText( nl[ i ], cx, y ); y += 52; }
		if ( d.role ) { ctx.fillStyle = T.ac; ctx.font = '700 27px ' + FONT; var rl = wrap( ctx, d.role, S - pad * 2 ).slice( 0, 2 ); y += 6; for ( var r = 0; r < rl.length; r++ ) { ctx.fillText( rl[ r ], cx, y ); y += 36; } }
		if ( d.location ) { y += 8; ctx.fillStyle = '#0f172a'; ctx.font = '700 24px ' + FONT; ctx.fillText( '📍 ' + d.location, cx, y ); y += 8; }

		if ( d.chips && d.chips.length ) {
			y += 34;
			var chips = d.chips.slice( 0, 5 ), gap = 12, h = 46, padX = 20, rows = [], row = [], rowW = 0;
			ctx.font = '700 18px ' + FONT;
			for ( var ci = 0; ci < chips.length; ci++ ) {
				var w = ctx.measureText( chips[ ci ] ).width + padX * 2;
				if ( rowW + w + ( row.length ? gap : 0 ) > S - pad * 2 && row.length ) { rows.push( { items: row, w: rowW } ); row = []; rowW = 0; }
				row.push( { t: chips[ ci ], w: w } ); rowW += w + ( row.length > 1 ? gap : 0 );
			}
			if ( row.length ) { rows.push( { items: row, w: rowW } ); }
			for ( var ri = 0; ri < rows.length && ri < 2; ri++ ) {
				var rx = cx - rows[ ri ].w / 2;
				for ( var k = 0; k < rows[ ri ].items.length; k++ ) {
					var it = rows[ ri ].items[ k ];
					ctx.fillStyle = T.soft; roundRect( ctx, rx, y, it.w, h, 999 ); ctx.fill();
					ctx.fillStyle = T.deep; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText( it.t, rx + it.w / 2, y + h / 2 + 1 );
					rx += it.w + gap;
				}
				y += h + gap;
			}
		}

		if ( d.cta ) {
			var chh = 72;
			ctx.fillStyle = T.ac; roundRect( ctx, pad, S - pad - chh, S - pad * 2, chh, 14 ); ctx.fill();
			ctx.fillStyle = '#fff'; ctx.font = '800 22px ' + FONT; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
			ctx.fillText( ( wrap( ctx, d.cta, S - pad * 2 - 40 )[ 0 ] || d.cta ), cx, S - pad - chh / 2 + 1 );
		}
		return cv;
	}

	/* ---------- save image ---------- */
	function getData( root ) {
		var el = root.querySelector( '#sssj-asset-data' );
		if ( ! el ) { return null; }
		try { return JSON.parse( el.textContent ); } catch ( e ) { return null; }
	}
	function download( canvas, name, root ) {
		try {
			canvas.toBlob( function ( out ) {
				if ( ! out ) { msg( root, 'Could not build the image. Use Download PDF.' ); return; }
				var a = document.createElement( 'a' );
				a.href = URL.createObjectURL( out ); a.download = name;
				document.body.appendChild( a ); a.click(); document.body.removeChild( a );
				setTimeout( function () { URL.revokeObjectURL( a.href ); }, 4000 );
				msg( root, 'Image saved.' );
			}, 'image/png' );
		} catch ( e ) { msg( root, 'Could not build the image. Use Download PDF.' ); }
	}
	function saveImage( root ) {
		var d = getData( root );
		if ( ! d ) { msg( root, 'Image export is not ready here. Use Download PDF.' ); return; }
		var tg = root.querySelector( '[data-edit="tagline"]' ); if ( tg ) { d.role = tg.value; }
		var bl = root.querySelector( '[data-edit="blurb"]' ); if ( bl && ( 'about' in d ) ) { d.about = bl.value; }
		var T = THEMES[ currentTheme( root ) ] || THEMES.teal;
		var name = root.getAttribute( 'data-filename' ) || ( ( 'square' === d.shape ) ? 'social.png' : ( ( d.kind || 'resume' ) + '.png' ) );
		msg( root, 'Building image…' );
		var build = function ( photo ) {
			var cv = ( 'square' === d.shape ) ? renderSquare( d, T, photo ) : renderPortrait( d, T, photo );
			download( cv, name, root );
		};
		if ( d.photo ) {
			var img = new Image();
			img.onload = function () { build( img ); };
			img.onerror = function () { build( null ); };
			img.src = d.photo;
		} else { build( null ); }
	}

	/* ---------- server (Gotenberg) PDF, optional ---------- */
	function serverPdf( root ) {
		var cfg = window.SSSJ_AssetRender || {};
		if ( ! cfg.url || ! cfg.nonce ) { msg( root, 'High-quality rendering is not available. Use Quick PDF.' ); return; }
		var type = root.getAttribute( 'data-asset-type' ) || 'resume';
		msg( root, 'Building your print-quality PDF…' );
		var fd = new FormData();
		fd.append( 'action', 'sssj_asset_render' );
		fd.append( '_wpnonce', cfg.nonce );
		fd.append( 'type', type );
		var job = root.getAttribute( 'data-asset-job' );
		if ( job ) { fd.append( 'job_id', job ); }
		var tg = root.querySelector( '[data-edit="tagline"]' );
		if ( tg ) { fd.append( 'tagline', tg.value ); }
		var bl = root.querySelector( '[data-edit="blurb"]' );
		if ( bl ) { fd.append( 'blurb', bl.value ); }
		fetch( cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) { if ( ! r.ok ) { return r.text().then( function ( t ) { throw new Error( t || ( 'HTTP ' + r.status ) ); } ); } return r.blob(); } )
			.then( function ( blob ) {
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url; a.download = type + '.pdf';
				document.body.appendChild( a ); a.click(); document.body.removeChild( a );
				URL.revokeObjectURL( url );
				msg( root, 'Saved a print-quality PDF.' );
			} )
			.catch( function ( e ) { msg( root, 'Could not build the print-quality PDF (' + ( e.message || 'error' ) + '). You can still use Quick PDF.' ); } );
	}

	function saveResume( root ) {
		var cfg = window.SSSJ_AssetRender || {};
		if ( ! cfg.ajax || ! cfg.nonce ) { msg( root, 'Saving to My résumés is not available right now. You can still download a PDF.' ); return; }
		var asset = root.querySelector( '#sssj-asset' );
		var fmt = ( asset && asset.classList.contains( 'sssj-asset--styled' ) ) ? 'styled' : 'ats';
		var tg = root.querySelector( '[data-edit="tagline"]' );
		var bl = root.querySelector( '[data-edit="blurb"]' );
		msg( root, 'Saving to My résumés' + String.fromCharCode(8230) );
		var body = 'action=sssj_asset_save_resume&nonce=' + encodeURIComponent( cfg.nonce )
			+ '&format=' + encodeURIComponent( fmt )
			+ '&tagline=' + encodeURIComponent( tg ? tg.value : '' )
			+ '&blurb=' + encodeURIComponent( bl ? bl.value : '' );
		fetch( cfg.ajax, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) {
				if ( d && d.success ) { msg( root, ( d.data && d.data.msg ) ? d.data.msg : 'Saved to My résumés.' ); }
				else { msg( root, ( d && d.data && d.data.msg ) ? d.data.msg : 'Could not save to My résumés.' ); }
			} )
			.catch( function () { msg( root, 'Could not save right now. You can still download a PDF above.' ); } );
	}

	function copyCaption( root ) {
		var text = root.getAttribute( 'data-caption' ) || '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () { msg( root, 'Caption copied. Paste it into your post.' ); }, function () { msg( root, 'Could not copy automatically. Select the caption and copy it.' ); } );
		} else { msg( root, text ); }
	}

	function hrefParams( a ) {
		var href = a.getAttribute( 'href' ) || '';
		return {
			type: ( /[?&]sssj_asset=([a-z]+)/.exec( href ) || [] )[ 1 ] || '',
			job:  ( /[?&]sssj_job_id=([0-9]+)/.exec( href ) || [] )[ 1 ] || ''
		};
	}

	// Swap the Create-an-asset tool to another type in place (no full reload), so it never bounces
	// out of the dashboard tab and the controls (incl. the colour theme) stay put.
	function swapPanel( type, job ) {
		var cfg = window.SSSJ_AssetRender || {};
		var current = document.querySelector( '.sssj--create-asset' );
		if ( ! cfg.ajax || ! cfg.nonce || ! current ) { return; }
		current.setAttribute( 'aria-busy', 'true' );
		current.style.opacity = '0.55';
		var body = 'action=sssj_asset_panel&nonce=' + encodeURIComponent( cfg.nonce )
			+ '&type=' + encodeURIComponent( type ) + ( job ? '&job_id=' + encodeURIComponent( job ) : '' );
		fetch( cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		} ).then( function ( r ) { return r.json(); } ).then( function ( d ) {
			if ( d && d.success && d.data && d.data.html ) {
				var tmp = document.createElement( 'div' );
				tmp.innerHTML = d.data.html;
				var fresh = tmp.querySelector( '.sssj--create-asset' );
				if ( fresh && current.parentNode ) {
					current.parentNode.replaceChild( fresh, current );
					init( fresh );
					try {
						if ( window.history && window.history.pushState ) {
							window.history.pushState( null, '', '?sssj_asset=' + encodeURIComponent( type ) + ( job ? '&sssj_job_id=' + encodeURIComponent( job ) : '' ) + '#dash-create-asset' );
						}
					} catch ( e ) {}
					return;
				}
			}
			current.style.opacity = ''; current.removeAttribute( 'aria-busy' );
		} ).catch( function () { current.style.opacity = ''; current.removeAttribute( 'aria-busy' ); } );
	}

	// Résumé layout toggle (ATS vs styled): a CSS skin on the same DOM, remembered per browser.
	function initFormat( root ) {
		var picks = root.querySelectorAll( '[data-asset-format-pick]' );
		if ( ! picks.length ) { return; }
		var asset = root.querySelector( '#sssj-asset' );
		var saved = 'ats';
		try { saved = window.localStorage.getItem( 'sssj_resume_format' ) || 'ats'; } catch ( e ) {}
		function apply( fmt ) {
			if ( asset ) {
				asset.classList.toggle( 'sssj-asset--ats', 'ats' === fmt );
				asset.classList.toggle( 'sssj-asset--styled', 'styled' === fmt );
				asset.setAttribute( 'data-asset-format', fmt );
			}
			Array.prototype.forEach.call( picks, function ( b ) {
				var on = b.getAttribute( 'data-asset-format-pick' ) === fmt;
				b.classList.toggle( 'sssj-btn--primary', on );
				b.classList.toggle( 'sssj-btn--ghost', ! on );
			} );
			try { window.localStorage.setItem( 'sssj_resume_format', fmt ); } catch ( e ) {}
		}
		Array.prototype.forEach.call( picks, function ( b ) {
			b.addEventListener( 'click', function () { apply( b.getAttribute( 'data-asset-format-pick' ) ); } );
		} );
		apply( 'styled' === saved ? 'styled' : 'ats' );
	}

	function init( root ) {
		bindEditing( root );
		initTheme( root );
		initFormat( root );
		root.addEventListener( 'click', function ( e ) {
			// Asset-type tabs + job pick-list links: swap in place instead of reloading the whole page.
			var link = e.target.closest ? e.target.closest( 'a[href*="sssj_asset="]' ) : null;
			if ( link && root.contains( link ) ) {
				var cfg = window.SSSJ_AssetRender || {};
				if ( cfg.ajax && cfg.nonce ) {
					e.preventDefault();
					var p = hrefParams( link );
					if ( p.type ) { swapPanel( p.type, p.job ); }
					return;
				}
			}
			var btn = e.target.closest ? e.target.closest( '[data-action]' ) : null;
			if ( ! btn ) { return; }
			e.preventDefault();
			var act = btn.getAttribute( 'data-action' );
			if ( 'pdf' === act ) { window.print(); }
			else if ( 'server-pdf' === act ) { serverPdf( root ); }
			else if ( 'save-resume' === act ) { saveResume( root ); }
			else if ( 'png' === act ) { saveImage( root ); }
			else if ( 'caption' === act ) { copyCaption( root ); }
		} );
	}

	function boot() {
		var roots = document.querySelectorAll( '[data-sssj-asset-wizard]' );
		Array.prototype.forEach.call( roots, init );
	}

	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', boot ); } else { boot(); }
}() );
