/**
 * Shareable-asset wizard (Phase 1, worker résumé). $0 browser path:
 *   live preview, Download PDF (browser print), Save image (best-effort PNG), Copy caption.
 * No dependencies. Graceful fallbacks throughout.
 */
( function () {
	'use strict';

	function msg( root, text ) {
		var el = root.querySelector( '[data-asset-msg]' );
		if ( el ) { el.textContent = text; }
	}

	/* Live preview: bind the wording inputs to the preview nodes. */
	function bindEditing( root ) {
		var inputs = root.querySelectorAll( '[data-edit]' );
		Array.prototype.forEach.call( inputs, function ( input ) {
			var key = input.getAttribute( 'data-edit' );
			input.addEventListener( 'input', function () {
				var target = root.querySelector( '[data-bind="' + key + '"]' );
				if ( target ) { target.textContent = input.value; }
				if ( 'blurb' === key ) {
					var wrap = root.querySelector( '[data-block="about"]' );
					if ( wrap ) { if ( input.value.trim() === '' ) { wrap.setAttribute( 'hidden', '' ); } else { wrap.removeAttribute( 'hidden' ); } }
					var c = root.querySelector( '[data-blurb-count]' );
					if ( c ) { var n = input.value.trim() ? input.value.trim().split( /\s+/ ).length : 0; c.textContent = n + ' words' + ( n > 70 ? ' (try to keep it under 70)' : '' ); }
				}
			} );
		} );
		var c = root.querySelector( '[data-blurb-count]' );
		var b = root.querySelector( '[data-edit="blurb"]' );
		if ( c && b ) { var n = b.value.trim() ? b.value.trim().split( /\s+/ ).length : 0; c.textContent = n + ' words' + ( n > 70 ? ' (try to keep it under 70)' : '' ); }
	}

	/* Collect CSS text from our own same-origin stylesheets (for the image export). */
	function collectCss() {
		var css = '';
		Array.prototype.forEach.call( document.styleSheets, function ( sheet ) {
			var href = sheet.href || '';
			if ( href && href.indexOf( 'shuffles-social-services-jobs' ) === -1 ) { return; }
			try {
				var rules = sheet.cssRules;
				if ( ! rules ) { return; }
				for ( var i = 0; i < rules.length; i++ ) { css += rules[ i ].cssText + '\n'; }
			} catch ( e ) { /* cross-origin sheet, skip */ }
		} );
		return css;
	}

	/* Turn any same-origin <img> in the clone into a data URI so it renders inside the SVG. */
	function inlinePhoto( original, clone ) {
		var src = original.querySelector( 'img' );
		var dst = clone.querySelector( 'img' );
		if ( ! src || ! dst ) { return; }
		try {
			var c = document.createElement( 'canvas' );
			c.width = src.naturalWidth || src.width; c.height = src.naturalHeight || src.height;
			if ( ! c.width || ! c.height ) { return; }
			c.getContext( '2d' ).drawImage( src, 0, 0, c.width, c.height );
			dst.setAttribute( 'src', c.toDataURL( 'image/png' ) );
		} catch ( e ) { /* if it taints or fails, drop the image so the rest still renders */ dst.parentNode.removeChild( dst ); }
	}

	function savePng( root ) {
		var node = root.querySelector( '#sssj-asset' );
		if ( ! node || ! window.XMLSerializer ) { msg( root, 'Image export is not supported here. Use Download PDF.' ); return; }
		msg( root, 'Building image…' );
		try {
			var w = Math.ceil( node.getBoundingClientRect().width );
			var h = Math.ceil( node.scrollHeight );
			var clone = node.cloneNode( true );
			inlinePhoto( node, clone );
			var xhtml = new XMLSerializer().serializeToString( clone );
			var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '">'
				+ '<foreignObject width="100%" height="100%"><div xmlns="http://www.w3.org/1999/xhtml" class="sssj">'
				+ '<style>' + collectCss() + '</style>' + xhtml + '</div></foreignObject></svg>';
			var blob = new Blob( [ svg ], { type: 'image/svg+xml;charset=utf-8' } );
			var url  = URL.createObjectURL( blob );
			var img  = new Image();
			img.onload = function () {
				try {
					var scale = 2;
					var canvas = document.createElement( 'canvas' );
					canvas.width = w * scale; canvas.height = h * scale;
					var ctx = canvas.getContext( '2d' );
					ctx.fillStyle = '#ffffff'; ctx.fillRect( 0, 0, canvas.width, canvas.height );
					ctx.scale( scale, scale ); ctx.drawImage( img, 0, 0 );
					URL.revokeObjectURL( url );
					canvas.toBlob( function ( out ) {
						if ( ! out ) { msg( root, 'Could not build the image. Use Download PDF.' ); return; }
						var a = document.createElement( 'a' );
						a.href = URL.createObjectURL( out ); a.download = ( root.getAttribute( 'data-filename' ) || 'resume.png' );
						document.body.appendChild( a ); a.click(); document.body.removeChild( a );
						msg( root, 'Image saved.' );
					}, 'image/png' );
				} catch ( e ) { msg( root, 'Could not build the image. Use Download PDF.' ); }
			};
			img.onerror = function () { URL.revokeObjectURL( url ); msg( root, 'Could not build the image. Use Download PDF.' ); };
			img.src = url;
		} catch ( e ) { msg( root, 'Could not build the image. Use Download PDF.' ); }
	}

	/* Phase 2: ask the server to render a print-quality PDF (Gotenberg). Falls back to the browser
	 * path on any error. Sends the asset type + the member's edited wording; the server rebuilds the
	 * locked template so output is pixel-identical for everyone. */
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
			.then( function ( r ) {
				if ( ! r.ok ) { return r.text().then( function ( t ) { throw new Error( t || ( 'HTTP ' + r.status ) ); } ); }
				return r.blob();
			} )
			.then( function ( blob ) {
				var url = URL.createObjectURL( blob );
				var a   = document.createElement( 'a' );
				a.href = url; a.download = type + '.pdf';
				document.body.appendChild( a ); a.click(); document.body.removeChild( a );
				URL.revokeObjectURL( url );
				msg( root, 'Saved a print-quality PDF.' );
			} )
			.catch( function ( e ) { msg( root, 'Could not build the print-quality PDF (' + ( e.message || 'error' ) + '). You can still use Quick PDF.' ); } );
	}

	function copyCaption( root ) {
		var text = root.getAttribute( 'data-caption' ) || '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () { msg( root, 'Caption copied. Paste it into your post.' ); }, function () { msg( root, 'Could not copy automatically. Select the caption and copy it.' ); } );
		} else {
			msg( root, text );
		}
	}

	function init( root ) {
		bindEditing( root );
		root.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '[data-action]' ) : null;
			if ( ! btn ) { return; }
			e.preventDefault();
			var act = btn.getAttribute( 'data-action' );
			if ( 'pdf' === act ) { window.print(); }
			else if ( 'server-pdf' === act ) { serverPdf( root ); }
			else if ( 'png' === act ) { savePng( root ); }
			else if ( 'caption' === act ) { copyCaption( root ); }
		} );
	}

	function boot() {
		var roots = document.querySelectorAll( '[data-sssj-asset-wizard]' );
		Array.prototype.forEach.call( roots, init );
	}

	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', boot ); } else { boot(); }
}() );
