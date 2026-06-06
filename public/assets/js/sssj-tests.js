/* Shuffles SSJ — testing worksheet: mark Pass/Fail per case, progress saved per browser. */
( function () {
	'use strict';
	var root = document.querySelector( '[data-sssj-tests]' );
	if ( ! root ) {
		return;
	}
	var KEY = 'sssj_tests_' + ( root.getAttribute( 'data-tests-version' ) || '' );
	function load() { try { return JSON.parse( localStorage.getItem( KEY ) || '{}' ) || {}; } catch ( e ) { return {}; } }
	function store( s ) { try { localStorage.setItem( KEY, JSON.stringify( s ) ); } catch ( e ) {} }
	var state = load();
	var rows = [].slice.call( root.querySelectorAll( 'tr[data-test-id]' ) );
	var total = rows.length;

	function applyRow( tr ) {
		var v = state[ tr.getAttribute( 'data-test-id' ) ];
		tr.classList.toggle( 'is-pass', 'pass' === v );
		tr.classList.toggle( 'is-fail', 'fail' === v );
		[].forEach.call( tr.querySelectorAll( '[data-mark]' ), function ( b ) {
			b.classList.toggle( 'is-on', b.getAttribute( 'data-mark' ) === v );
		} );
	}
	function updateProgress() {
		var done = 0;
		rows.forEach( function ( tr ) { if ( state[ tr.getAttribute( 'data-test-id' ) ] ) { done++; } } );
		var pct = total ? Math.round( done / total * 100 ) : 0;
		var p = root.querySelector( '[data-tests-progress]' );
		if ( p ) { p.textContent = done + ' / ' + total; }
		var f = root.querySelector( '[data-tests-fill]' );
		if ( f ) { f.style.width = pct + '%'; }
	}

	rows.forEach( function ( tr ) {
		applyRow( tr );
		[].forEach.call( tr.querySelectorAll( '[data-mark]' ), function ( b ) {
			b.addEventListener( 'click', function () {
				var id = tr.getAttribute( 'data-test-id' ), m = b.getAttribute( 'data-mark' );
				if ( state[ id ] === m ) { delete state[ id ]; } else { state[ id ] = m; }
				store( state ); applyRow( tr ); updateProgress();
			} );
		} );
	} );
	var reset = root.querySelector( '[data-tests-reset]' );
	if ( reset ) {
		reset.addEventListener( 'click', function () {
			if ( window.confirm( 'Clear all test results?' ) ) { state = {}; store( state ); rows.forEach( applyRow ); updateProgress(); }
		} );
	}
	updateProgress();
}() );
