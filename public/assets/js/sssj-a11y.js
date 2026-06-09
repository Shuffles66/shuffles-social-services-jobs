/* Shuffles Social Services Jobs and Engagements, accessibility / CALD toolbar.
 * Browser-side, $0 to run. Injects a toolbar into the first .sssj surface and provides:
 * larger text, high-contrast, no-colour, Easy-Read, read-aloud (SpeechSynthesis), and
 * voice input (Web Speech API) on search fields. Preferences persist in localStorage.
 * Mode classes toggle on <html>; the no-colour FILTER targets .sssj content blocks
 * (never the root) so it can't trap a fixed modal.
 */
( function () {
	'use strict';

	var cfg = window.SSJ_A11y || {};
	var L   = cfg.labels || {};
	var KEY = 'sssj_a11y_prefs';
	var html = document.documentElement;

	function load() {
		try { return JSON.parse( localStorage.getItem( KEY ) ) || {}; } catch ( e ) { return {}; }
	}
	function save() {
		try { localStorage.setItem( KEY, JSON.stringify( prefs ) ); } catch ( e ) {}
	}
	var prefs = load();

	function applyPrefs() {
		html.classList.toggle( 'sssj-a11y-contrast', !! prefs.contrast );
		html.classList.toggle( 'sssj-a11y-mono', !! prefs.mono );
		html.classList.toggle( 'sssj-a11y-easyread', !! prefs.easyread );
		html.classList.remove( 'sssj-a11y-text-lg', 'sssj-a11y-text-xl' );
		if ( 'lg' === prefs.textsize ) { html.classList.add( 'sssj-a11y-text-lg' ); }
		else if ( 'xl' === prefs.textsize ) { html.classList.add( 'sssj-a11y-text-xl' ); }
	}

	function makeBtn( label, title ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'sssj-a11y-btn';
		b.textContent = label;
		b.setAttribute( 'aria-label', title || label );
		b.title = title || label;
		return b;
	}

	function readAloud() {
		if ( ! ( 'speechSynthesis' in window ) ) { return; }
		if ( window.speechSynthesis.speaking ) { window.speechSynthesis.cancel(); return; }
		var root = document.querySelector( '.sssj' );
		if ( ! root ) { return; }
		var text = ( root.innerText || root.textContent || '' ).replace( /\s+/g, ' ' ).trim();
		if ( ! text ) { return; }
		var u = new SpeechSynthesisUtterance( text.slice( 0, 5000 ) );
		u.lang = cfg.lang || 'en-AU';
		window.speechSynthesis.speak( u );
	}

	function attachVoice() {
		var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
		if ( ! SR ) { return; }
		document.querySelectorAll( 'input[name="sssj_q"], input[name="sssj_loc"], input[data-sssj-place]' ).forEach( function ( inp ) {
			if ( inp.dataset.sssjMic ) { return; }
			inp.dataset.sssjMic = '1';
			var mic = makeBtn( '🎤', L.voice || 'Voice input' );
			mic.classList.add( 'sssj-a11y-mic' );
			inp.insertAdjacentElement( 'afterend', mic );
			mic.addEventListener( 'click', function () {
				var r = new SR();
				r.lang = cfg.lang || 'en-AU';
				r.interimResults = false;
				r.maxAlternatives = 1;
				mic.classList.add( 'is-listening' );
				r.onresult = function ( e ) {
					inp.value = e.results[ 0 ][ 0 ].transcript;
					inp.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				};
				r.onerror = function () { mic.classList.remove( 'is-listening' ); };
				r.onend = function () { mic.classList.remove( 'is-listening' ); };
				try { r.start(); } catch ( e ) {}
			} );
		} );
	}

	var rtlLangs = cfg.rtl || { ar: 1 };

	function updateEnglishBtn() {
		var el = document.querySelector( '.sssj-a11y-english' );
		if ( el ) { el.style.display = ( prefs.lang && 'en' !== prefs.lang ) ? '' : 'none'; }
	}

	function applyLang( code ) {
		var dict = ( cfg.i18n || {} )[ code ] || {};
		document.querySelectorAll( '[data-i18n]' ).forEach( function ( el ) {
			if ( ! el.dataset.i18nEn ) { el.dataset.i18nEn = el.textContent; }
			var k = el.getAttribute( 'data-i18n' );
			el.textContent = ( null != dict[ k ] ) ? dict[ k ] : el.dataset.i18nEn;
		} );
		// Translate placeholders too (data-i18n-placeholder).
		document.querySelectorAll( '[data-i18n-placeholder]' ).forEach( function ( el ) {
			if ( null == el.dataset.i18nPhEn ) { el.dataset.i18nPhEn = el.getAttribute( 'placeholder' ) || ''; }
			var pk = el.getAttribute( 'data-i18n-placeholder' );
			el.setAttribute( 'placeholder', ( null != dict[ pk ] ) ? dict[ pk ] : el.dataset.i18nPhEn );
		} );
		// Site-wide: stamp the document language; RTL is applied to plugin surfaces (incl. the
		// floating toolbar wrapper) so we don't flip a theme that wasn't built for it.
		document.documentElement.setAttribute( 'lang', code );
		var rtl = !! rtlLangs[ code ];
		document.querySelectorAll( '.sssj' ).forEach( function ( s ) { s.setAttribute( 'dir', rtl ? 'rtl' : 'ltr' ); } );
		prefs.lang = code;
		save();
		var sel = document.querySelector( '.sssj-a11y-lang' );
		if ( sel && sel.value !== code ) { sel.value = code; }
		updateEnglishBtn();

		// Whole-site machine translation (Option 1). Only when configured and DeepL supports the language.
		if ( cfg.translate && 'en' !== code && ( cfg.translate_langs || [] ).indexOf( code ) !== -1 ) {
			translatePage( code );
		} else if ( 'en' === code && pageTranslated ) {
			window.location.reload(); // restore originals (they are English)
		}
	}

	var pageTranslated = false, translatedLang = '';

	function collectTextNodes() {
		var nodes = [];
		if ( ! document.body || ! document.createTreeWalker ) { return nodes; }
		var walker = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT, {
			acceptNode: function ( n ) {
				if ( ! n.nodeValue || ! n.nodeValue.trim() ) { return NodeFilter.FILTER_REJECT; }
				var p = n.parentNode;
				if ( ! p || ! p.closest ) { return NodeFilter.FILTER_REJECT; }
				if ( p.closest( 'script,style,noscript,textarea,code,pre,[translate="no"],[data-no-translate],.sssj-a11y' ) ) { return NodeFilter.FILTER_REJECT; }
				return NodeFilter.FILTER_ACCEPT;
			}
		} );
		var node;
		while ( ( node = walker.nextNode() ) ) { nodes.push( node ); }
		return nodes;
	}

	function chunkArr( arr, n ) { var out = []; for ( var i = 0; i < arr.length; i += n ) { out.push( arr.slice( i, i + n ) ); } return out; }

	function applyMap( nodes, map ) {
		nodes.forEach( function ( nd ) {
			var t = nd.nodeValue, s = t.trim();
			if ( s && map[ s ] ) { nd.nodeValue = t.replace( s, map[ s ] ); }
		} );
		document.querySelectorAll( 'input[placeholder],textarea[placeholder]' ).forEach( function ( el ) {
			if ( el.closest( '.sssj-a11y' ) ) { return; }
			var ph = ( el.getAttribute( 'placeholder' ) || '' ).trim();
			if ( ph && map[ ph ] ) { el.setAttribute( 'placeholder', map[ ph ] ); }
		} );
	}

	function translatePage( code ) {
		if ( translatedLang === code ) { return; }
		var nodes = collectTextNodes();
		var uniq = {}, list = [];
		nodes.forEach( function ( nd ) { var s = nd.nodeValue.trim(); if ( s && ! uniq[ s ] ) { uniq[ s ] = 1; list.push( s ); } } );
		document.querySelectorAll( 'input[placeholder],textarea[placeholder]' ).forEach( function ( el ) {
			if ( el.closest( '.sssj-a11y' ) ) { return; }
			var ph = ( el.getAttribute( 'placeholder' ) || '' ).trim();
			if ( ph && ! uniq[ ph ] ) { uniq[ ph ] = 1; list.push( ph ); }
		} );
		if ( ! list.length ) { return; }
		list = list.slice( 0, 600 );
		var map = {}, batches = chunkArr( list, 100 ), pending = batches.length;
		batches.forEach( function ( b ) {
			fetch( cfg.translate_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.translate_nonce },
				credentials: 'same-origin',
				body: JSON.stringify( { texts: b, lang: code } )
			} ).then( function ( r ) { return r.json(); } ).then( function ( d ) {
				if ( d && d.map ) { Object.keys( d.map ).forEach( function ( k ) { map[ k ] = d.map[ k ]; } ); }
			} ).catch( function () {} ).then( function () {
				pending--;
				if ( 0 === pending ) { applyMap( nodes, map ); translatedLang = code; pageTranslated = true; }
			} );
		} );
	}

	function buildToolbar() {
		if ( document.querySelector( '.sssj-a11y-bar' ) ) { return; }
		var host = document.querySelector( '.sssj' );
		var floating = ! host; // site-wide: float on pages that have no plugin surface
		var bar = document.createElement( 'div' );
		bar.className = 'sssj-a11y-bar' + ( floating ? ' sssj-a11y-bar--floating' : '' );
		bar.setAttribute( 'role', 'region' );
		bar.setAttribute( 'aria-label', L.region || 'Accessibility tools' );

		function add( label, title, fn, key ) {
			var b = makeBtn( label, title );
			if ( key ) { b.setAttribute( 'data-i18n', key ); }
			b.addEventListener( 'click', fn );
			bar.appendChild( b );
		}

		add( 'A+', L.bigger || 'Larger text', function () {
			prefs.textsize = 'lg' === prefs.textsize ? 'xl' : ( 'xl' === prefs.textsize ? '' : 'lg' );
			save(); applyPrefs();
		} );
		add( L.contrast || 'High contrast', L.contrast || 'High contrast', function () { prefs.contrast = ! prefs.contrast; save(); applyPrefs(); }, 'a11y_contrast' );
		add( L.mono || 'No colour', L.mono || 'No colour', function () { prefs.mono = ! prefs.mono; save(); applyPrefs(); }, 'a11y_mono' );
		add( L.easyread || 'Easy read', L.easyread || 'Easy read', function () { prefs.easyread = ! prefs.easyread; save(); applyPrefs(); }, 'a11y_easyread' );
		if ( 'speechSynthesis' in window ) {
			add( '🔊', L.read || 'Read aloud', readAloud );
		}
		add( L.reset || 'Reset', L.reset || 'Reset', function () { prefs = {}; save(); applyPrefs(); }, 'a11y_reset' );

		var langs = cfg.langs || {};
		if ( Object.keys( langs ).length > 1 ) {
			var sel = document.createElement( 'select' );
			sel.className = 'sssj-select sssj-a11y-lang';
			sel.setAttribute( 'aria-label', L.language || 'Language' );
			Object.keys( langs ).forEach( function ( code ) {
				var o = document.createElement( 'option' );
				o.value = code;
				o.textContent = langs[ code ];
				if ( code === ( prefs.lang || 'en' ) ) { o.selected = true; }
				sel.appendChild( o );
			} );
			sel.addEventListener( 'change', function () { applyLang( sel.value ); } );
			bar.appendChild( sel );

			// Always-visible English escape, shown only when the UI is not English.
			var engWrap = document.createElement( 'div' );
			engWrap.className = 'sssj-a11y-english';
			var eng = makeBtn( 'English Hot Key', 'Switch back to English' );
			eng.addEventListener( 'click', function () { applyLang( 'en' ); } );
			engWrap.appendChild( eng );
			var engNote = document.createElement( 'small' );
			engNote.className = 'sssj-a11y-english-note';
			engNote.textContent = "Have you chosen a language you can't read, and want to go back to English? We got you! Hit the Hot Key";
			engWrap.appendChild( engNote );
			// NB: engWrap is appended to the OUTER container (next to the Accessibility button), not the bar.
		}

		// Hide the toolbar behind a single "Accessibility" toggle, in the same position.
		var outer = document.createElement( 'div' );
		outer.className = 'sssj-a11y' + ( floating ? ' sssj-a11y--floating' : '' );
		// Open by default (admin setting cfg.bar_open, default on); a saved user choice always wins.
		var startOpen = ( 'boolean' === typeof prefs.a11yOpen ) ? prefs.a11yOpen : ( '0' !== cfg.bar_open );
		if ( startOpen ) { outer.classList.add( 'is-open' ); }
		var toggle = makeBtn( '♿ ' + ( L.region || 'Accessibility' ), L.region || 'Accessibility tools' );
		toggle.className += ' sssj-a11y-toggle';
		toggle.setAttribute( 'aria-expanded', startOpen ? 'true' : 'false' );
		toggle.addEventListener( 'click', function () {
			var open = ! outer.classList.contains( 'is-open' );
			outer.classList.toggle( 'is-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			prefs.a11yOpen = open;
			save();
		} );
		outer.appendChild( toggle );
		// English hot-key sits OUTSIDE the collapsible bar, next to the Accessibility button.
		if ( typeof engWrap !== 'undefined' && engWrap ) { outer.appendChild( engWrap ); }
		outer.appendChild( bar );

		if ( floating ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'sssj';
			wrap.appendChild( outer );
			document.body.appendChild( wrap );
		} else {
			host.insertBefore( outer, host.firstChild );
		}
	}

	function init() {
		applyPrefs();
		buildToolbar();
		attachVoice();
		applyLang( prefs.lang || 'en' );
	}

	if ( 'loading' !== document.readyState ) { init(); }
	else { document.addEventListener( 'DOMContentLoaded', init ); }
}() );
