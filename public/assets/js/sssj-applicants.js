/**
 * sssj-applicants.js - advertiser applicants list: sort + filter, grouped by job, remembered per browser.
 * Controls: [data-app-sort] (select), [data-app-filter] (select). Each job's applicants live in a
 * [data-applicants] group; rows are .sssj-applicant with data-app-status / -applied / -score / -name.
 * Progressive enhancement: with no JS the rows still render (server-ordered).
 */
( function () {
	'use strict';

	var STATUS_ORDER = [ 'new', 'viewed', 'shortlisted', 'interview', 'offer', 'hired', 'declined', 'rejected', 'withdrawn' ];
	function statusRank( s ) { var i = STATUS_ORDER.indexOf( s ); return i < 0 ? 99 : i; }
	function attr( el, a ) { return el.getAttribute( a ) || ''; }
	function numAttr( el, a ) { var n = parseInt( el.getAttribute( a ), 10 ); return isNaN( n ) ? 0 : n; }
	function getPref( k, d ) { try { return window.localStorage.getItem( 'sssj_app_' + k ) || d; } catch ( e ) { return d; } }
	function setPref( k, v ) { try { window.localStorage.setItem( 'sssj_app_' + k, v ); } catch ( e ) {} }

	function apply() {
		var sortSel = document.querySelector( '[data-app-sort]' );
		var filtSel = document.querySelector( '[data-app-filter]' );
		var sort = sortSel ? sortSel.value : 'newest';
		var filt = filtSel ? filtSel.value : 'all';
		Array.prototype.forEach.call( document.querySelectorAll( '[data-applicants]' ), function ( group ) {
			var rows = Array.prototype.slice.call( group.querySelectorAll( '.sssj-applicant' ) );
			var shown = 0;
			rows.forEach( function ( r ) {
				var ok = ( 'all' === filt || attr( r, 'data-app-status' ) === filt );
				r.style.display = ok ? '' : 'none';
				if ( ok ) { shown++; }
			} );
			rows.sort( function ( a, b ) {
				if ( 'score' === sort ) { return numAttr( b, 'data-app-score' ) - numAttr( a, 'data-app-score' ); }
				if ( 'newest' === sort ) { return numAttr( b, 'data-app-applied' ) - numAttr( a, 'data-app-applied' ); }
				if ( 'oldest' === sort ) { return numAttr( a, 'data-app-applied' ) - numAttr( b, 'data-app-applied' ); }
				if ( 'status' === sort ) { return statusRank( attr( a, 'data-app-status' ) ) - statusRank( attr( b, 'data-app-status' ) ); }
				if ( 'name' === sort ) { return attr( a, 'data-app-name' ).localeCompare( attr( b, 'data-app-name' ) ); }
				return 0;
			} );
			rows.forEach( function ( r ) { group.appendChild( r ); } );
			// Empty-when-filtered note per group.
			var note = group.querySelector( '[data-app-empty]' );
			if ( ! note ) {
				note = document.createElement( 'p' );
				note.className = 'description';
				note.setAttribute( 'data-app-empty', '1' );
				note.textContent = 'No applicants match this filter.';
				group.appendChild( note );
			}
			note.style.display = ( rows.length && 0 === shown ) ? '' : 'none';
		} );
	}

	function init() {
		var sortSel = document.querySelector( '[data-app-sort]' );
		var filtSel = document.querySelector( '[data-app-filter]' );
		if ( ! sortSel && ! filtSel ) { return; }
		if ( sortSel && ! sortSel.dataset.sssjBound ) {
			sortSel.dataset.sssjBound = '1';
			sortSel.value = getPref( 'sort', sortSel.value );
			sortSel.addEventListener( 'change', function () { setPref( 'sort', sortSel.value ); apply(); } );
		}
		if ( filtSel && ! filtSel.dataset.sssjBound ) {
			filtSel.dataset.sssjBound = '1';
			filtSel.value = getPref( 'filter', filtSel.value );
			filtSel.addEventListener( 'change', function () { setPref( 'filter', filtSel.value ); apply(); } );
		}
		apply();
	}

	if ( 'loading' !== document.readyState ) { init(); } else { document.addEventListener( 'DOMContentLoaded', init ); }
	window.addEventListener( 'load', init );
}() );
