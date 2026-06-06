/**
 * "Scan now" on the NDIS Registration No field — looks the number up against the public NDIS
 * Commission register and shows the live status / groups / expiry inline, before saving.
 * Used on the organisation and worker (sole-trader) profile forms.
 */
( function () {
	'use strict';

	var cfg = window.SSSJ_NDIS || {};

	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = String( s == null ? '' : s );
		return d.innerHTML;
	}

	function digits( s ) { return String( s == null ? '' : s ).replace( /\D+/g, '' ); }

	function render( box, d, form ) {
		var groups = ( d.groups && d.groups.length )
			? '<ul class="sssj-ndis__groups">' + d.groups.map( function ( g ) { return '<li>' + esc( g ) + '</li>'; } ).join( '' ) + '</ul>'
			: '';
		var force = d.in_force_until ? '<div><strong>' + esc( cfg.i18n_inforce || 'In force until' ) + ':</strong> ' + esc( d.in_force_until ) + '</div>' : '';
		var name  = d.legal_name ? '<div>' + esc( d.legal_name ) + '</div>' : '';

		// ABN row + mismatch check against the ABN typed on this form (org_abn or worker_abn).
		var abnHtml = '';
		if ( d.abn ) {
			abnHtml = '<div><strong>' + esc( cfg.i18n_abn || 'ABN (register)' ) + ':</strong> <code>' + esc( d.abn ) + '</code>';
			var ownEl = form ? ( form.querySelector( '[name="org_abn"]' ) || form.querySelector( '[name="worker_abn"]' ) ) : null;
			var own   = ownEl ? digits( ownEl.value ) : '';
			if ( own && own !== digits( d.abn ) ) {
				abnHtml += '<div class="sssj-ndis__abnwarn">' + esc( ( cfg.i18n_abnwarn || '⚠ This differs from the ABN you entered (%s) — please check.' ).replace( '%s', own ) ) + '</div>';
			}
			abnHtml += '</div>';
		}
		var addr = d.address ? '<div><strong>' + esc( cfg.i18n_addr || 'Head office (register)' ) + ':</strong> ' + esc( d.address ) + '</div>' : '';
		var web  = d.website ? '<div><strong>' + esc( cfg.i18n_web || 'Website (register)' ) + ':</strong> <a href="' + esc( d.website ) + '" target="_blank" rel="noopener nofollow">' + esc( d.website.replace( /^https?:\/\//, '' ) ) + '</a></div>' : '';
		var phone = d.phone ? '<div><strong>' + esc( cfg.i18n_phone || 'Phone (register)' ) + ':</strong> ' + esc( d.phone ) + '</div>' : '';
		var outlets = ( d.outlets && d.outlets.length )
			? '<div style="margin-top:4px"><strong>' + esc( cfg.i18n_outlets || 'Outlets' ) + '</strong><ul class="sssj-ndis__outlets">' + d.outlets.map( function ( o ) {
				return '<li>' + esc( o.name || '' ) + ( o.phone ? ' — ' + esc( o.phone ) : '' ) + '</li>';
			} ).join( '' ) + '</ul></div>'
			: '';

		// Status badge colour reflects the actual status (red for revoked/banned, green for approved).
		var toneCls = d.tone === 'rejected' ? 'sssj-badge--rejected' : ( d.tone === 'verified' ? 'sssj-badge--verified' : '' );

		box.innerHTML =
			'<div class="sssj-ndis sssj-ndis--preview">'
			+ name
			+ '<div><strong>' + esc( cfg.i18n_status || 'Registration status' ) + ':</strong> <span class="sssj-badge ' + toneCls + '">' + esc( d.status ) + '</span></div>'
			+ force
			+ abnHtml
			+ addr
			+ web
			+ phone
			+ outlets
			+ ( groups ? '<div style="margin-top:6px"><strong>' + esc( cfg.i18n_groups || 'Approved registration groups' ) + '</strong>' + groups + '</div>' : '' )
			+ '</div>';
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '[data-sssj-ndis-scan]' ) : null;
		if ( ! btn ) { return; }
		e.preventDefault();
		var form  = btn.closest( 'form' );
		var input = form ? form.querySelector( '[name="ndis_register_id"]' ) : null;
		var box   = form ? form.querySelector( '[data-sssj-ndis-result]' ) : null;
		var id    = input ? String( input.value ).replace( /\D+/g, '' ) : '';
		if ( ! box ) { return; }
		if ( ! id ) { box.innerHTML = '<p class="sssj-ndis__note">' + esc( cfg.i18n_empty || 'Enter your NDIS Registration No first.' ) + '</p>'; return; }
		if ( ! cfg.ajax ) { return; }

		btn.disabled = true;
		if ( window.SSSJSpinner ) { window.SSSJSpinner.show( box.parentNode || box, cfg.i18n_loading || 'Checking the NDIS register…' ); }
		box.innerHTML = '';

		var body = new FormData();
		body.append( 'action', 'sssj_ndis_scan_preview' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'id', id );

		fetch( cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				btn.disabled = false;
				if ( window.SSSJSpinner ) { window.SSSJSpinner.hide( box.parentNode || box ); }
				if ( ! res || ! res.success ) {
					box.innerHTML = '<p class="sssj-ndis__note">' + esc( ( res && res.data && res.data.msg ) || 'Could not check that number.' ) + '</p>';
					return;
				}
				render( box, res.data || {}, form );
			} )
			.catch( function () {
				btn.disabled = false;
				if ( window.SSSJSpinner ) { window.SSSJSpinner.hide( box.parentNode || box ); }
				box.innerHTML = '<p class="sssj-ndis__note">' + esc( 'Could not check that number.' ) + '</p>';
			} );
	} );
}() );
