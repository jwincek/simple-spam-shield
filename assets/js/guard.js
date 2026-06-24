/**
 * Simple Spam Shield — front-end guard script.
 *
 * Injects hidden honeypot, nonce, timestamp, and behavioral data fields
 * into comment forms, WooCommerce review forms, and Jetpack contact form blocks.
 *
 * Behavioral tracking (mouse movements, clicks, time on page) ported from
 * Comment & Form Guard's public-scripts.js, rewritten without jQuery.
 */
( function () {
	'use strict';

	if ( typeof simpleSpamShieldGuard === 'undefined' ) {
		return;
	}

	var selectors = [
		'#commentform',                         // WP Comments.
		'#review_form form',                    // WooCommerce Reviews.
		'.wp-block-jetpack-contact-form form',  // Jetpack Contact Form blocks.
		'.jetpack-contact-form form',           // Jetpack legacy class.
	];

	// --- Behavioral analysis tracking ---
	// Ported from Comment & Form Guard.
	var startTime = Date.now();
	var mouseMovements = 0;
	var clicks = 0;

	document.addEventListener( 'mousemove', function () {
		mouseMovements++;
	} );

	document.addEventListener( 'click', function () {
		clicks++;
	} );

	/**
	 * Build the behavioral data JSON string at submission time.
	 */
	function getBehavioralData() {
		var timeSpent = ( Date.now() - startTime ) / 1000;
		return JSON.stringify( {
			time_spent: parseFloat( timeSpent.toFixed( 2 ) ),
			mouse_movements: mouseMovements,
			clicks: clicks
		} );
	}

	/**
	 * Inject hidden fields into a form.
	 */
	function injectFields( form ) {
		if ( form.dataset.simpleSpamShieldProtected ) {
			return;
		}
		form.dataset.simpleSpamShieldProtected = '1';

		// Honeypot field — looks like a legitimate "website" field to bots.
		var hp = document.createElement( 'div' );
		hp.className = 'simple-spam-shield-hp-wrap';
		hp.setAttribute( 'aria-hidden', 'true' );
		hp.innerHTML =
			'<label for="simple_spam_shield_website_url">Website</label>' +
			'<input type="text" name="simple_spam_shield_website_url" id="simple_spam_shield_website_url" value="" tabindex="-1" autocomplete="off">';
		form.appendChild( hp );

		// Nonce field.
		var nonce = document.createElement( 'input' );
		nonce.type  = 'hidden';
		nonce.name  = 'simple_spam_shield_nonce';
		nonce.value = simpleSpamShieldGuard.nonce;
		form.appendChild( nonce );

		// Timestamp field — generated client-side so page caching
		// cannot produce a stale value that breaks the time gate.
		var ts = document.createElement( 'input' );
		ts.type  = 'hidden';
		ts.name  = 'simple_spam_shield_form_loaded';
		ts.value = Math.floor( Date.now() / 1000 );
		form.appendChild( ts );

		// Behavioral data field — populated at submit time.
		var bd = document.createElement( 'input' );
		bd.type  = 'hidden';
		bd.name  = 'simple_spam_shield_behavioral_data';
		bd.value = '';
		form.appendChild( bd );

		// Capture behavioral data just before form submission.
		form.addEventListener( 'submit', function () {
			var field = form.querySelector( 'input[name="simple_spam_shield_behavioral_data"]' );
			if ( field ) {
				field.value = getBehavioralData();
			}
		} );
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
	// The callback fires on every DOM mutation, so coalesce bursts into a
	// single debounced rescan to avoid re-querying the whole document on
	// busy pages (infinite scroll, heavy client-side rendering).
	if ( typeof MutationObserver !== 'undefined' ) {
		var rescanTimer = null;
		var scheduleRescan = function () {
			if ( rescanTimer ) {
				return;
			}
			rescanTimer = window.setTimeout( function () {
				rescanTimer = null;
				protectForms();
			}, 200 );
		};

		var observer = new MutationObserver( function ( mutations ) {
			for ( var i = 0; i < mutations.length; i++ ) {
				if ( mutations[ i ].addedNodes.length ) {
					scheduleRescan();
					return;
				}
			}
		} );
		observer.observe( document.body, { childList: true, subtree: true } );
	}
} )();
