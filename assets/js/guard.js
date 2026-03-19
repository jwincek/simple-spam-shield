/**
 * Simple Spam Shield — front-end guard script.
 *
 * Injects hidden honeypot, nonce, and timestamp fields into comment forms,
 * WooCommerce review forms, and Jetpack contact form blocks.
 */
( function () {
	'use strict';

	if ( typeof sssGuard === 'undefined' ) {
		return;
	}

	var selectors = [
		'#commentform',                         // WP Comments.
		'#review_form form',                    // WooCommerce Reviews.
		'.wp-block-jetpack-contact-form form',  // Jetpack Contact Form blocks.
		'.jetpack-contact-form form',           // Jetpack legacy class.
	];

	/**
	 * Inject hidden fields into a form.
	 */
	function injectFields( form ) {
		if ( form.dataset.sssProtected ) {
			return;
		}
		form.dataset.sssProtected = '1';

		// Honeypot field — looks like a legitimate "website" field to bots.
		var hp = document.createElement( 'div' );
		hp.className = 'sss-hp-wrap';
		hp.setAttribute( 'aria-hidden', 'true' );
		hp.innerHTML =
			'<label for="sss_website_url">Website</label>' +
			'<input type="text" name="sss_website_url" id="sss_website_url" value="" tabindex="-1" autocomplete="off">';
		form.appendChild( hp );

		// Nonce field.
		var nonce = document.createElement( 'input' );
		nonce.type  = 'hidden';
		nonce.name  = 'sss_nonce';
		nonce.value = sssGuard.nonce;
		form.appendChild( nonce );

		// Timestamp field — generated client-side so page caching
		// cannot produce a stale value that breaks the time gate.
		var ts = document.createElement( 'input' );
		ts.type  = 'hidden';
		ts.name  = 'sss_form_loaded';
		ts.value = Math.floor( Date.now() / 1000 );
		form.appendChild( ts );
	}

	/**
	 * Find and protect all matching forms.
	 */
	function protectForms() {
		selectors.forEach( function ( selector ) {
			var forms = document.querySelectorAll( selector );
			forms.forEach( injectFields );
		} );
	}

	// Run on DOMContentLoaded.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', protectForms );
	} else {
		protectForms();
	}

	// Also observe for dynamically-inserted forms (e.g. AJAX-loaded reviews).
	if ( typeof MutationObserver !== 'undefined' ) {
		var observer = new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				if ( mutation.addedNodes.length ) {
					protectForms();
				}
			} );
		} );
		observer.observe( document.body, { childList: true, subtree: true } );
	}
} )();
