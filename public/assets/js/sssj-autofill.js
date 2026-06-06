/**
 * Organisation profile "Fetch details from my website" — reads the entered website URL and
 * pre-fills empty fields (name, description, phone). Powered server-side; an AI/Tavily integration
 * can enrich the result. Only fills fields the user left blank — never clobbers their input.
 */
( function () {
	'use strict';

	function fill( form, name, value ) {
		if ( ! value ) { return; }
		var el = form.querySelector( '[name="' + name + '"]' );
		if ( el && '' === String( el.value ).trim() ) { el.value = value; }
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-sssj-autofill]' );
		if ( ! btn ) { return; }
		e.preventDefault();
		var cfg = window.SSJ_Autofill || {};
		var form = btn.closest( 'form' );
		if ( ! form || ! cfg.ajax ) { return; }
		var urlEl = form.querySelector( '[name="org_website"]' );
		var url = urlEl ? String( urlEl.value ).trim() : '';
		if ( ! url ) { window.alert( btn.getAttribute( 'data-empty' ) || 'Enter your website URL first.' ); return; }

		var original = btn.textContent;
		btn.disabled = true;
		btn.textContent = btn.getAttribute( 'data-loading' ) || 'Reading…';

		var body = new FormData();
		body.append( 'action', 'sssj_autofill' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'url', url );

		fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				btn.disabled = false;
				btn.textContent = original;
				if ( ! res || ! res.success ) {
					window.alert( ( res && res.data && res.data.msg ) || 'Could not read that website.' );
					return;
				}
				var d = res.data || {};
				fill( form, 'org_name', d.org_name );
				fill( form, 'description', d.description );
				fill( form, 'org_phone', d.org_phone );
			} )
			.catch( function () {
				btn.disabled = false;
				btn.textContent = original;
				window.alert( 'Could not read that website.' );
			} );
	} );
}() );
