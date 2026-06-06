/* Shuffles SSJ — Appearance "Style Studio": live preview + presets + saved looks.
 * Binds to the existing Appearance controls by id; nothing here saves until the form is submitted. */
( function () {
	'use strict';

	var PREVIEW_ID = 'sssj-studio-preview';
	var preview = document.getElementById( PREVIEW_ID );
	if ( ! preview ) {
		return;
	}

	// control key -> CSS variable (array = [var, suffix]).
	var TOKEN_MAP = {
		color_primary:      '--sssj-blue',
		color_primary_deep: '--sssj-blue-deep',
		color_ink:          '--sssj-ink',
		color_text:         '--sssj-text',
		color_line:         '--sssj-line',
		color_bg:           '--sssj-bg',
		color_bg_soft:      '--sssj-bg-soft',
		color_abn:          '--sssj-abn',
		color_tfn:          '--sssj-tfn',
		color_need:         '--sssj-need',
		ui_radius:          [ '--sssj-radius', 'px' ],
		font_family:        '--sssj-font',
		font_size:          '--sssj-fs',
		heading_weight:     '--sssj-weight-heading'
	};
	// every control we manage (for collect/apply of presets + themes).
	var KEYS = Object.keys( TOKEN_MAP ).concat( [ 'ui_density', 'custom_css' ] );

	function el( key ) { return document.getElementById( 'sssj-' + key ); }
	function getVal( key ) { var e = el( key ); return e ? e.value : ''; }
	function setVal( key, v ) { var e = el( key ); if ( e ) { e.value = ( null == v ? '' : v ); } }

	var styleEl = document.createElement( 'style' );
	styleEl.id = 'sssj-studio-style';
	document.head.appendChild( styleEl );

	function build() {
		var vars = '';
		for ( var key in TOKEN_MAP ) {
			if ( ! TOKEN_MAP.hasOwnProperty( key ) ) { continue; }
			var def = TOKEN_MAP[ key ], token, suffix = '';
			if ( Array.isArray( def ) ) { token = def[ 0 ]; suffix = def[ 1 ]; } else { token = def; }
			var v = ( getVal( key ) || '' ).trim();
			if ( '' !== v ) { vars += token + ':' + v + suffix + ';'; }
		}
		var density = getVal( 'ui_density' );
		var scale = 'compact' === density ? 0.75 : ( 'comfortable' === density ? 1.3 : 0 );
		if ( scale ) {
			var base = { '--sssj-s2': 8, '--sssj-s3': 12, '--sssj-s4': 16, '--sssj-s5': 24 };
			for ( var t in base ) { if ( base.hasOwnProperty( t ) ) { vars += t + ':' + Math.round( base[ t ] * scale ) + 'px;'; } }
		}
		var css = '#' + PREVIEW_ID + '{' + vars + '}';
		var raw = ( getVal( 'custom_css' ) || '' ).trim();
		if ( raw ) {
			// Scope the root ".sssj" to the preview (leave compound classes like .sssj-btn intact).
			css += '\n' + raw.replace( /\.sssj(?![\w-])/g, '#' + PREVIEW_ID );
		}
		styleEl.textContent = css;
	}

	KEYS.forEach( function ( key ) {
		var e = el( key );
		if ( e ) { e.addEventListener( 'input', build ); e.addEventListener( 'change', build ); }
	} );

	// Presets.
	var PRESETS = {
		soft: { color_primary: '#0ea5e9', color_primary_deep: '#0369a1', ui_radius: '18', heading_weight: '700', ui_density: 'comfortable' },
		bold: { color_primary: '#111827', color_primary_deep: '#000000', color_ink: '#000000', ui_radius: '4', heading_weight: '800', ui_density: 'normal' },
		calm: { color_primary: '#0d9488', color_primary_deep: '#0f766e', color_bg_soft: '#f1f5f9', ui_radius: '12', heading_weight: '600', ui_density: 'normal' }
	};
	[].forEach.call( document.querySelectorAll( '[data-sssj-preset]' ), function ( b ) {
		b.addEventListener( 'click', function () {
			var p = PRESETS[ b.getAttribute( 'data-sssj-preset' ) ];
			if ( ! p ) { return; }
			for ( var k in p ) { if ( p.hasOwnProperty( k ) ) { setVal( k, p[ k ] ); } }
			build();
		} );
	} );

	// Preview width toggle.
	[].forEach.call( document.querySelectorAll( '[data-sssj-width]' ), function ( b ) {
		b.addEventListener( 'click', function () {
			var w = parseInt( b.getAttribute( 'data-sssj-width' ), 10 );
			preview.style.maxWidth = w > 0 ? ( w + 'px' ) : '';
		} );
	} );

	// Saved looks (persist via the hidden field on form submit).
	var hidden = document.getElementById( 'sssj-appearance_themes' );
	var sel = document.querySelector( '[data-sssj-themes]' );
	function readThemes() { try { var a = JSON.parse( ( hidden && hidden.value ) || '[]' ); return Array.isArray( a ) ? a : []; } catch ( e ) { return []; } }
	function writeThemes( a ) { if ( hidden ) { hidden.value = JSON.stringify( a ); } }
	function refreshList() {
		if ( ! sel ) { return; }
		var a = readThemes();
		sel.innerHTML = '<option value="">— select —</option>';
		a.forEach( function ( t, i ) { var o = document.createElement( 'option' ); o.value = String( i ); o.textContent = t.name; sel.appendChild( o ); } );
	}
	function collect() { var v = {}; KEYS.forEach( function ( k ) { v[ k ] = getVal( k ); } ); return v; }
	function applyVals( v ) { KEYS.forEach( function ( k ) { if ( k in v ) { setVal( k, v[ k ] ); } } ); build(); }

	function bind( selector, fn ) { var b = document.querySelector( selector ); if ( b ) { b.addEventListener( 'click', fn ); } }
	bind( '[data-sssj-theme-save]', function () {
		var name = window.prompt( 'Name this look:' );
		if ( ! name ) { return; }
		var a = readThemes(), item = { name: name, values: collect() }, idx = -1;
		a.forEach( function ( t, i ) { if ( t.name === name ) { idx = i; } } );
		if ( idx >= 0 ) { a[ idx ] = item; } else { a.push( item ); }
		writeThemes( a ); refreshList();
	} );
	bind( '[data-sssj-theme-load]', function () {
		if ( ! sel || '' === sel.value ) { return; }
		var t = readThemes()[ parseInt( sel.value, 10 ) ];
		if ( t && t.values ) { applyVals( t.values ); }
	} );
	bind( '[data-sssj-theme-del]', function () {
		if ( ! sel || '' === sel.value ) { return; }
		var a = readThemes(); a.splice( parseInt( sel.value, 10 ), 1 ); writeThemes( a ); refreshList();
	} );

	refreshList();
	build();
}() );
