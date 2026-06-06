/* Shuffles Social Services Jobs and Engagements — accessibility / CALD toolbar.
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

			// Always-visible English escape — shown only when the UI is not English.
			var engWrap = document.createElement( 'div' );
			engWrap.className = 'sssj-a11y-english';
			var eng = makeBtn( 'English Hot Key', 'Switch back to English' );
			eng.addEventListener( 'click', function () { applyLang( 'en' ); } );
			engWrap.appendChild( eng );
			var engNote = document.createElement( 'small' );
			engNote.className = 'sssj-a11y-english-note';
			engNote.textContent = "Have you chosen a language you can't read, and want to go back to English? We got you! Hit the Hot Key";
			engWrap.appendChild( engNote );
			bar.appendChild( engWrap );
		}

		if ( floating ) {
			var wrap = document.createElement( 'div' );
			wrap.className = 'sssj';
			wrap.appendChild( bar );
			document.body.appendChild( wrap );
		} else {
			host.insertBefore( bar, host.firstChild );
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
