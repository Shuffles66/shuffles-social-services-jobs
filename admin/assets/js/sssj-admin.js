/* Shuffles Social Services Jobs and Engagements — admin settings JS.
 * Handles the page-picker "Create page" button (creates a page with the shortcode via AJAX).
 */
( function () {
	'use strict';
	if ( typeof SSJ_Admin === 'undefined' ) { return; }

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.sssj-create-page' ) : null;
		if ( ! btn ) { return; }
		e.preventDefault();

		var wrap = btn.closest( '.sssj-page-picker' );
		if ( ! wrap ) { return; }

		var key   = wrap.getAttribute( 'data-key' );
		var sc    = wrap.getAttribute( 'data-shortcode' );
		var title = btn.getAttribute( 'data-title' ) || 'Jobs';

		btn.disabled = true;
		btn.textContent = SSJ_Admin.creating;

		var body = new URLSearchParams();
		body.append( 'action', 'sssj_create_page' );
		body.append( 'nonce', SSJ_Admin.nonce );
		body.append( 'key', key );
		body.append( 'shortcode', sc );
		body.append( 'title', title );

		fetch( SSJ_Admin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				btn.disabled = false;
				btn.textContent = SSJ_Admin.createLabel;
				if ( ! res || ! res.success || ! res.data ) {
					window.alert( SSJ_Admin.error );
					return;
				}
				var sel = wrap.querySelector( 'select.sssj-page-select' ) || wrap.querySelector( 'select' );
				if ( sel ) {
					var opt = document.createElement( 'option' );
					opt.value = res.data.id;
					opt.textContent = res.data.title;
					opt.selected = true;
					sel.appendChild( opt );
				}
				var links = wrap.querySelector( '.sssj-page-links' );
				if ( links ) {
					links.innerHTML = ' <a href="' + res.data.edit + '">Edit</a> | <a href="' + res.data.view + '" target="_blank" rel="noopener">View</a>';
				}
			} )
			.catch( function () {
				btn.disabled = false;
				btn.textContent = SSJ_Admin.createLabel;
				window.alert( SSJ_Admin.error );
			} );
	} );
}() );
