/* Shuffles Social Services Jobs — self-promotion studio.
 * Cycles through the platform "positives", swapping the graphic body + the caption.
 * The actual Save-image / Copy-caption buttons are handled by sssj-assets.js (shared
 * rasteriser): this wrapper just keeps #sssj-asset's body + the wizard's data-caption in sync.
 */
( function () {
	'use strict';

	var data = window.SSSJ_Promo || {};

	function init() {
		var root = document.querySelector( '[data-sssj-promo]' );
		if ( ! root || ! data.items || ! data.items.length ) { return; }

		var card = root.querySelector( '#sssj-asset' );
		var body = root.querySelector( '[data-promo-body]' );
		var cap  = root.querySelector( '[data-promo-caption]' );
		var pick = root.querySelector( '[data-promo-pick]' );
		var i    = ( typeof data.start === 'number' ) ? data.start : 0;

		function show( n ) {
			var len = data.items.length;
			i = ( ( n % len ) + len ) % len;
			var it = data.items[ i ];
			if ( body ) { body.innerHTML = it.body; }
			if ( card && typeof it.accent !== 'undefined' ) { card.setAttribute( 'data-accent', String( it.accent ) ); }
			root.setAttribute( 'data-caption', it.caption || '' );
			root.setAttribute( 'data-filename', it.filename || 'shuffles-promo.png' );
			if ( cap ) { cap.value = it.caption || ''; }
			if ( pick ) { pick.value = String( i ); }
		}

		root.addEventListener( 'click', function ( e ) {
			var b = e.target.closest ? e.target.closest( '[data-promo-nav]' ) : null;
			if ( ! b ) { return; }
			e.preventDefault();
			show( i + ( 'next' === b.getAttribute( 'data-promo-nav' ) ? 1 : -1 ) );
		} );

		if ( pick ) {
			pick.addEventListener( 'change', function () { show( parseInt( pick.value, 10 ) || 0 ); } );
		}

		show( i );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
