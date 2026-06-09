/**
 * sssj-capture.js - "Take a photo" (mobile camera) + client-side downscale/normalise + preview,
 * for any file input tagged data-sssj-photo (credential evidence, profile photo, gallery, logos).
 *
 * Why: phone photos are often over 8MB and may be HEIC (which the server rejects). When a photo comes
 * from the camera, or a picked image is oversized or HEIC, we redraw it to a canvas and re-encode
 * (JPEG, or PNG when the source is PNG so transparency survives) so it fits the limit and uploads
 * quickly. Small, already-safe images (jpeg/png/webp under the limit) are left untouched; PDF/SVG
 * pass through. Progressive enhancement: needs DataTransfer; older browsers keep the plain input.
 */
( function () {
	'use strict';
	var CFG = window.SSSJ_Capture || {};
	var MAX_EDGE = CFG.maxEdge || 2000;
	var QUALITY = CFG.quality || 0.85;
	var MAX_BYTES = CFG.maxBytes || ( 8 * 1024 * 1024 );
	var T = {
		take: CFG.i18nTake || 'Take a photo',
		remove: CFG.i18nRemove || 'Remove',
		busy: CFG.i18nBusy || 'Preparing photo...',
		big: CFG.i18nBig || 'That image is still too large after shrinking. Please try a smaller one.'
	};

	function dtSupported() { try { new DataTransfer(); return true; } catch ( e ) { return false; } }
	function rasterImage( f ) { return !! f && /^image\//.test( f.type || '' ) && 'image/svg+xml' !== f.type; }
	function isHeic( f ) { var t = ( f.type || '' ).toLowerCase(); return /heic|heif/.test( t ) || /\.(heic|heif)$/i.test( f.name || '' ); }

	// Output mime for a file, or null to leave it untouched.
	function decideMime( f, fromCamera ) {
		if ( fromCamera && rasterImage( f ) ) { return 'image/jpeg'; }
		if ( ! rasterImage( f ) ) { return null; }
		if ( isHeic( f ) ) { return 'image/jpeg'; }
		if ( f.size > MAX_BYTES ) { return ( 'image/png' === f.type ) ? 'image/png' : 'image/jpeg'; }
		return null;
	}

	function reencode( file, mime ) {
		return new Promise( function ( resolve ) {
			function draw( src, w, h, cleanup ) {
				var scale = Math.min( 1, MAX_EDGE / Math.max( w, h ) );
				var cw = Math.max( 1, Math.round( w * scale ) );
				var ch = Math.max( 1, Math.round( h * scale ) );
				var canvas = document.createElement( 'canvas' );
				canvas.width = cw; canvas.height = ch;
				var ctx = canvas.getContext( '2d' );
				if ( ! ctx ) { if ( cleanup ) { cleanup(); } resolve( file ); return; }
				if ( 'image/jpeg' === mime ) { ctx.fillStyle = '#ffffff'; ctx.fillRect( 0, 0, cw, ch ); }
				try { ctx.drawImage( src, 0, 0, cw, ch ); } catch ( e ) { if ( cleanup ) { cleanup(); } resolve( file ); return; }
				if ( cleanup ) { cleanup(); }
				if ( ! canvas.toBlob ) { resolve( file ); return; }
				canvas.toBlob( function ( blob ) {
					if ( ! blob ) { resolve( file ); return; }
					var ext = ( 'image/png' === mime ) ? '.png' : '.jpg';
					var base = ( file.name || 'photo' ).replace( /\.[a-z0-9]+$/i, '' );
					var out;
					try { out = new File( [ blob ], base + ext, { type: mime, lastModified: Date.now() } ); }
					catch ( e ) { out = blob; try { out.name = base + ext; } catch ( e2 ) {} }
					resolve( out );
				}, mime, ( 'image/png' === mime ) ? undefined : QUALITY );
			}
			if ( window.createImageBitmap ) {
				var p = null;
				try { p = createImageBitmap( file, { imageOrientation: 'from-image' } ); } catch ( e ) { p = null; }
				if ( p && p.then ) {
					p.then( function ( bmp ) { draw( bmp, bmp.width, bmp.height, function () { if ( bmp.close ) { bmp.close(); } } ); } )
						.catch( function () { viaImg(); } );
					return;
				}
			}
			viaImg();
			function viaImg() {
				var url = URL.createObjectURL( file );
				var img = new Image();
				img.onload = function () { draw( img, img.naturalWidth || img.width, img.naturalHeight || img.height, function () { URL.revokeObjectURL( url ); } ); };
				img.onerror = function () { URL.revokeObjectURL( url ); resolve( file ); };
				img.src = url;
			}
		} );
	}

	function process( file, fromCamera ) {
		var mime = decideMime( file, fromCamera );
		if ( ! mime ) { return Promise.resolve( file ); }
		return reencode( file, mime );
	}

	function fmtSize( n ) { return n >= 1048576 ? ( n / 1048576 ).toFixed( 1 ) + ' MB' : Math.max( 1, Math.round( n / 1024 ) ) + ' KB'; }

	function enhance( input ) {
		if ( input.__sssjCapture ) { return; }
		input.__sssjCapture = 1;
		if ( ! dtSupported() ) { return; } // graceful: the plain file input still works
		var multiple = input.multiple;
		var coarse = window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches;

		var wrap = document.createElement( 'div' );
		wrap.className = 'sssj-capture';
		input.parentNode.insertBefore( wrap, input.nextSibling );

		var capInput = null, btn = null;
		if ( coarse ) {
			capInput = document.createElement( 'input' );
			capInput.type = 'file';
			capInput.accept = 'image/*';
			capInput.setAttribute( 'capture', 'environment' );
			capInput.className = 'sssj-capture__cam';
			wrap.appendChild( capInput );
			btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'sssj-btn sssj-btn--secondary sssj-btn--sm sssj-capture__btn';
			btn.innerHTML = '📷 <span></span>';
			btn.querySelector( 'span' ).textContent = T.take;
			btn.addEventListener( 'click', function () { capInput.click(); } );
			wrap.appendChild( btn );
		}
		var statusEl = document.createElement( 'p' );
		statusEl.className = 'sssj-capture__status description';
		wrap.appendChild( statusEl );
		var preview = document.createElement( 'div' );
		preview.className = 'sssj-capture__preview';
		wrap.appendChild( preview );

		var state = [];

		function setBusy( b ) {
			statusEl.textContent = b ? T.busy : '';
			if ( btn ) { btn.disabled = !! b; }
		}
		function applyToInput() {
			var dt = new DataTransfer();
			state.forEach( function ( f ) { dt.items.add( f ); } );
			input.files = dt.files;
			render();
		}
		function render() {
			preview.innerHTML = '';
			state.forEach( function ( f, idx ) {
				var row = document.createElement( 'div' );
				row.className = 'sssj-capture__item';
				if ( /^image\//.test( f.type ) ) {
					var im = document.createElement( 'img' );
					im.className = 'sssj-capture__thumb';
					im.alt = '';
					im.src = URL.createObjectURL( f );
					row.appendChild( im );
				} else {
					var doc = document.createElement( 'span' );
					doc.className = 'sssj-capture__doc';
					doc.textContent = '📄';
					row.appendChild( doc );
				}
				var meta = document.createElement( 'span' );
				meta.className = 'sssj-capture__meta';
				meta.textContent = ( f.name || 'file' ) + ' (' + fmtSize( f.size ) + ')';
				row.appendChild( meta );
				var rm = document.createElement( 'button' );
				rm.type = 'button';
				rm.className = 'sssj-btn sssj-btn--ghost sssj-btn--sm';
				rm.textContent = T.remove;
				rm.addEventListener( 'click', function () { state.splice( idx, 1 ); applyToInput(); } );
				row.appendChild( rm );
				preview.appendChild( row );
			} );
		}
		function ingest( list, fromCamera, append ) {
			var files = Array.prototype.slice.call( list || [] );
			if ( ! files.length ) { return; }
			setBusy( true );
			Promise.all( files.map( function ( f ) { return process( f, fromCamera ); } ) ).then( function ( res ) {
				if ( append && multiple ) { state = state.concat( res ); }
				else { state = multiple ? res : res.slice( 0, 1 ); }
				applyToInput();
				setBusy( false );
				var big = state.filter( function ( f ) { return f.size > MAX_BYTES; } );
				if ( big.length ) { statusEl.textContent = T.big; }
			} );
		}

		input.addEventListener( 'change', function () {
			if ( input.__sssjProg ) { return; }
			input.__sssjProg = 1;
			ingest( input.files, false, false );
			input.__sssjProg = 0;
		} );
		if ( capInput ) {
			capInput.addEventListener( 'change', function () {
				ingest( capInput.files, true, true );
				capInput.value = '';
			} );
		}
	}

	function init() {
		var inputs = document.querySelectorAll( 'input[type="file"][data-sssj-photo]' );
		Array.prototype.forEach.call( inputs, enhance );
	}
	if ( 'loading' === document.readyState ) { document.addEventListener( 'DOMContentLoaded', init ); }
	else { init(); }
} )();
