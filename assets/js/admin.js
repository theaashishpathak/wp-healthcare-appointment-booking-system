( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		// Confirm before any delete link is followed.
		document.querySelectorAll( '.ab-delete-link' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				var message = ( window.AB_ADMIN && AB_ADMIN.i18n && AB_ADMIN.i18n.confirmDelete )
					? AB_ADMIN.i18n.confirmDelete
					: 'Are you sure you want to delete this item?';
				if ( ! window.confirm( message ) ) {
					e.preventDefault();
				}
			} );
		} );

		// WordPress Media Uploader for image fields (icon / doctor photo).
		document.querySelectorAll( '.ab-media-upload' ).forEach( function ( button ) {
			button.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var targetSelector = button.getAttribute( 'data-target' );
				var targetField    = document.querySelector( targetSelector );

				if ( ! window.wp || ! wp.media ) {
					return;
				}

				var frame = wp.media( {
					title: 'Select or Upload Image',
					button: { text: 'Use this image' },
					multiple: false,
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					targetField.value = attachment.url;
				} );

				frame.open();
			} );
		} );

		// Basic color input enhancement (native color input fallback).
		document.querySelectorAll( '.ab-color-field' ).forEach( function ( field ) {
			var swatch = document.createElement( 'span' );
			swatch.style.display = 'inline-block';
			swatch.style.width = '20px';
			swatch.style.height = '20px';
			swatch.style.marginLeft = '8px';
			swatch.style.verticalAlign = 'middle';
			swatch.style.border = '1px solid #ccc';
			swatch.style.borderRadius = '3px';
			swatch.style.background = field.value || '#000000';
			field.insertAdjacentElement( 'afterend', swatch );
			field.addEventListener( 'input', function () {
				swatch.style.background = field.value;
			} );
		} );

	} );
} )();
