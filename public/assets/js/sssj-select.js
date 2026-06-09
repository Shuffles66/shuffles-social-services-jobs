/* Shuffles SSJ, turn every plugin <select> into a searchable select2-style picker;
 * multi-selects become removable pills. Powered by Tom Select, themed to the .sssj design system. */
( function () {
	'use strict';

	function enhance( root ) {
		if ( 'undefined' === typeof window.TomSelect ) {
			return;
		}
		var selects = ( root || document ).querySelectorAll( '.sssj select:not(.sssj-a11y-lang):not([data-no-enhance])' );
		[].forEach.call( selects, function ( sel ) {
			if ( sel.dataset.tsDone ) {
				return;
			}
			sel.dataset.tsDone = '1';
			var multiple = sel.multiple;
			var firstEmpty = sel.options.length && '' === sel.options[ 0 ].value ? sel.options[ 0 ].text : '';
			try {
				new window.TomSelect( sel, {
					plugins: multiple ? [ 'remove_button' ] : [],
					maxItems: multiple ? null : 1,
					allowEmptyOption: true,
					placeholder: sel.getAttribute( 'data-placeholder' ) || firstEmpty || '',
					hidePlaceholder: false
				} );
			} catch ( e ) { /* leave the native select as-is on any error */ }
		} );
	}

	function init() { enhance( document ); }

	if ( 'loading' !== document.readyState ) {
		init();
	} else {
		document.addEventListener( 'DOMContentLoaded', init );
	}
	// Catch selects added after first paint (AJAX results, etc.).
	window.addEventListener( 'load', init );
}() );
