( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var root = document.querySelector( '.ab-booking-wizard' );
		if ( ! root || typeof AB_FRONTEND === 'undefined' ) {
			return;
		}

		var form      = root.querySelector( '#ab-booking-form' );
		var alertBox  = root.querySelector( '.ab-alert' );
		var nonce     = root.getAttribute( 'data-nonce' ) || AB_FRONTEND.nonce;

		var state = {
			category: null, // {id, name}
			doctor: null,   // {id, name, ...}
			services: [],   // [{id, name, duration, minutes}]
			totalMinutes: 0,
			date: null,     // 'Y-m-d'
			time: null,     // {time: 'HH:MM:SS', label}
			calendarYear: null,
			calendarMonth: null,
		};

		/* ---------- helpers ---------- */

		function ajax( action, data ) {
			var body = new FormData();
			body.append( 'action', action );
			body.append( 'nonce', nonce );
			if ( AB_FRONTEND.lang ) {
				body.append( 'lang', AB_FRONTEND.lang );
			}
			for ( var key in data ) {
				if ( Object.prototype.hasOwnProperty.call( data, key ) ) {
					var val = data[ key ];
					if ( Array.isArray( val ) ) {
						val.forEach( function ( v ) { body.append( key + '[]', v ); } );
					} else {
						body.append( key, val );
					}
				}
			}
			return fetch( AB_FRONTEND.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( res ) { return res.json(); } );
		}

		function showAlert( message, type ) {
			alertBox.textContent = message;
			alertBox.className = 'ab-alert' + ( 'success' === type ? ' ab-alert-success' : '' );
			alertBox.style.display = 'block';
			alertBox.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		}

		function hideAlert() {
			alertBox.style.display = 'none';
		}

		function setLoading( container ) {
			container.innerHTML = '<div class="ab-loading">' + ( container.getAttribute( 'data-loading-text' ) || 'Loading…' ) + '</div>';
		}

		function escapeHtml( str ) {
			var div = document.createElement( 'div' );
			div.textContent = str == null ? '' : String( str );
			return div.innerHTML;
		}

		/* ---------- step navigation ---------- */

		function goToStep( n ) {
			hideAlert();
			root.querySelectorAll( '.ab-step' ).forEach( function ( section ) {
				section.style.display = ( parseInt( section.getAttribute( 'data-step' ), 10 ) === n ) ? '' : 'none';
			} );
			root.querySelectorAll( '.ab-step-dot' ).forEach( function ( dot ) {
				var dotStep = parseInt( dot.getAttribute( 'data-step' ), 10 );
				dot.classList.remove( 'ab-active', 'ab-complete' );
				if ( dotStep === n ) {
					dot.classList.add( 'ab-active' );
				} else if ( dotStep < n ) {
					dot.classList.add( 'ab-complete' );
				}
			} );
			root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}

		root.querySelectorAll( '[data-back]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				goToStep( parseInt( btn.getAttribute( 'data-back' ), 10 ) );
			} );
		} );

		/* ---------- Step 1: Category ---------- */

		root.querySelectorAll( '.ab-category-card' ).forEach( function ( card ) {
			card.addEventListener( 'click', function () {
				root.querySelectorAll( '.ab-category-card' ).forEach( function ( c ) { c.classList.remove( 'ab-selected' ); } );
				card.classList.add( 'ab-selected' );
				state.category = { id: card.getAttribute( 'data-id' ), name: card.getAttribute( 'data-name' ) };
				state.doctor = null;
				state.services = [];
				goToStep( 2 );
				loadDoctors();
			} );
		} );

		/* ---------- Step 2: Doctor ---------- */

		function loadDoctors() {
			var container = root.querySelector( '.ab-doctor-grid' );
			setLoading( container );

			ajax( 'ab_get_doctors', { category_id: state.category.id } ).then( function ( res ) {
				if ( ! res.success ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( res.data && res.data.message ) + '</div>';
					return;
				}
				var doctors = res.data.doctors;
				if ( ! doctors.length ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( 'No doctors available under this category yet.' ) + '</div>';
					return;
				}

				container.innerHTML = '';
				doctors.forEach( function ( doc ) {
					var card = document.createElement( 'button' );
					card.type = 'button';
					card.className = 'ab-card ab-doctor-card';
					card.setAttribute( 'data-id', doc.id );
					card.innerHTML =
						( doc.image ? '<img class="ab-doctor-photo" src="' + escapeHtml( doc.image ) + '" alt="" />' : '<div class="ab-doctor-photo"></div>' ) +
						'<span class="ab-doctor-name">' + escapeHtml( doc.name ) + '</span>' +
						( doc.qualification ? '<span class="ab-doctor-meta">' + escapeHtml( doc.qualification ) + '</span>' : '' ) +
						( doc.experience ? '<span class="ab-doctor-meta">' + escapeHtml( doc.experience ) + '</span>' : '' ) +
						( doc.specialization ? '<span class="ab-doctor-meta">' + escapeHtml( doc.specialization ) + '</span>' : '' ) +
						'<span class="ab-doctor-select-btn">Select</span>';

					card.addEventListener( 'click', function () {
						container.querySelectorAll( '.ab-doctor-card' ).forEach( function ( c ) { c.classList.remove( 'ab-selected' ); } );
						card.classList.add( 'ab-selected' );
						state.doctor = doc;
						state.services = [];
						goToStep( 3 );
						loadServices();
					} );

					container.appendChild( card );
				} );
			} ).catch( function () {
				container.innerHTML = '<div class="ab-notice">' + escapeHtml( AB_FRONTEND.i18n.genericError ) + '</div>';
			} );
		}

		/* ---------- Step 3: Services ---------- */

		function loadServices() {
			var container = root.querySelector( '.ab-service-list' );
			setLoading( container );

			ajax( 'ab_get_services', { category_id: state.category.id } ).then( function ( res ) {
				if ( ! res.success ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( res.data && res.data.message ) + '</div>';
					return;
				}
				var services = res.data.services;
				if ( ! services.length ) {
					container.innerHTML = '<div class="ab-notice">No services available under this category yet.</div>';
					return;
				}

				container.innerHTML = '';
				services.forEach( function ( svc ) {
					var item = document.createElement( 'div' );
					item.className = 'ab-service-item';
					item.innerHTML =
						'<label>' +
						'<input type="checkbox" value="' + svc.id + '" />' +
						'<span class="ab-service-name">' + escapeHtml( svc.name ) + '</span>' +
						'</label>' +
						'<span class="ab-service-duration">' + escapeHtml( svc.duration ) + '</span>';

					var checkbox = item.querySelector( 'input' );
					checkbox.addEventListener( 'change', function () {
						item.classList.toggle( 'ab-selected', checkbox.checked );
						if ( checkbox.checked ) {
							state.services.push( svc );
						} else {
							state.services = state.services.filter( function ( s ) { return String( s.id ) !== String( svc.id ); } );
						}
						updateTotalDuration();
					} );

					container.appendChild( item );
				} );
			} ).catch( function () {
				container.innerHTML = '<div class="ab-notice">' + escapeHtml( AB_FRONTEND.i18n.genericError ) + '</div>';
			} );
		}

		function updateTotalDuration() {
			var totalMinutes = state.services.reduce( function ( sum, s ) { return sum + parseInt( s.minutes, 10 ); }, 0 );
			state.totalMinutes = totalMinutes;
			var hours = Math.floor( totalMinutes / 60 );
			var mins  = totalMinutes % 60;
			var label = [];
			if ( hours > 0 ) { label.push( hours + ( hours === 1 ? ' Hour' : ' Hours' ) ); }
			if ( mins > 0 || ! hours ) { label.push( mins + ' Minutes' ); }
			root.querySelector( '.ab-total-duration-value' ).textContent = label.join( ' ' );
		}

		root.querySelector( '.ab-btn-next[data-next="4"]' ).addEventListener( 'click', function () {
			if ( ! state.services.length ) {
				showAlert( AB_FRONTEND.i18n.selectService );
				return;
			}
			goToStep( 4 );
			var now = new Date();
			state.calendarYear = now.getFullYear();
			state.calendarMonth = now.getMonth() + 1;
			loadCalendar();
		} );

		/* ---------- Step 4: Calendar ---------- */

		var monthNames = [ 'January','February','March','April','May','June','July','August','September','October','November','December' ];

		function loadCalendar() {
			var container = root.querySelector( '.ab-calendar' );
			setLoading( container );

			ajax( 'ab_get_available_dates', {
				doctor_id: state.doctor.id,
				year: state.calendarYear,
				month: state.calendarMonth,
			} ).then( function ( res ) {
				if ( ! res.success ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( res.data && res.data.message ) + '</div>';
					return;
				}
				renderCalendar( container, res.data.available_dates );
			} ).catch( function () {
				container.innerHTML = '<div class="ab-notice">' + escapeHtml( AB_FRONTEND.i18n.genericError ) + '</div>';
			} );
		}

		function renderCalendar( container, availableDates ) {
			var year = state.calendarYear;
			var month = state.calendarMonth;
			var firstWeekday = new Date( year, month - 1, 1 ).getDay();
			var daysInMonth = new Date( year, month, 0 ).getDate();

			var html = '<div class="ab-calendar-header">' +
				'<button type="button" class="ab-cal-prev">&larr;</button>' +
				'<strong>' + monthNames[ month - 1 ] + ' ' + year + '</strong>' +
				'<button type="button" class="ab-cal-next">&rarr;</button>' +
				'</div><div class="ab-calendar-grid">';

			[ 'Su','Mo','Tu','We','Th','Fr','Sa' ].forEach( function ( d ) { html += '<div class="ab-cal-dow">' + d + '</div>'; } );

			for ( var i = 0; i < firstWeekday; i++ ) { html += '<div class="ab-cal-day ab-empty"></div>'; }

			for ( var d = 1; d <= daysInMonth; d++ ) {
				var dateStr = year + '-' + String( month ).padStart( 2, '0' ) + '-' + String( d ).padStart( 2, '0' );
				var available = availableDates.indexOf( dateStr ) !== -1;
				html += '<div class="ab-cal-day ' + ( available ? 'ab-available' : 'ab-disabled' ) + '" data-date="' + dateStr + '">' + d + '</div>';
			}
			html += '</div>';
			container.innerHTML = html;

			container.querySelector( '.ab-cal-prev' ).addEventListener( 'click', function () {
				state.calendarMonth--;
				if ( state.calendarMonth < 1 ) { state.calendarMonth = 12; state.calendarYear--; }
				loadCalendar();
			} );
			container.querySelector( '.ab-cal-next' ).addEventListener( 'click', function () {
				state.calendarMonth++;
				if ( state.calendarMonth > 12 ) { state.calendarMonth = 1; state.calendarYear++; }
				loadCalendar();
			} );

			container.querySelectorAll( '.ab-cal-day.ab-available' ).forEach( function ( dayEl ) {
				dayEl.addEventListener( 'click', function () {
					container.querySelectorAll( '.ab-cal-day' ).forEach( function ( d ) { d.classList.remove( 'ab-selected' ); } );
					dayEl.classList.add( 'ab-selected' );
					state.date = dayEl.getAttribute( 'data-date' );
					goToStep( 5 );
					loadSlots();
				} );
			} );
		}

		/* ---------- Step 5: Time slots ---------- */

		function loadSlots() {
			var container = root.querySelector( '.ab-slot-grid' );
			setLoading( container );

			ajax( 'ab_get_time_slots', { doctor_id: state.doctor.id, date: state.date } ).then( function ( res ) {
				if ( ! res.success ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( res.data && res.data.message ) + '</div>';
					return;
				}
				var slots = res.data.slots;
				if ( ! slots.length ) {
					container.innerHTML = '<div class="ab-notice">' + escapeHtml( AB_FRONTEND.i18n.noSlots ) + '</div>';
					return;
				}
				container.innerHTML = '';
				slots.forEach( function ( slot ) {
					var el = document.createElement( 'div' );
					el.className = 'ab-slot ' + ( slot.booked ? 'ab-slot-booked' : 'ab-slot-available' );
					el.innerHTML = '<span class="ab-slot-dot"></span><span class="ab-slot-label">' + escapeHtml( slot.label ) + '</span>' +
						( slot.booked ? '<span class="ab-slot-status">Booked</span>' : '' );

					if ( slot.booked ) {
						el.setAttribute( 'aria-disabled', 'true' );
					} else {
						el.addEventListener( 'click', function () {
							container.querySelectorAll( '.ab-slot' ).forEach( function ( s ) { s.classList.remove( 'ab-selected' ); } );
							el.classList.add( 'ab-selected' );
							state.time = slot;
							goToStep( 6 );
						} );
					}

					container.appendChild( el );
				} );
			} ).catch( function () {
				container.innerHTML = '<div class="ab-notice">' + escapeHtml( AB_FRONTEND.i18n.genericError ) + '</div>';
			} );
		}

		/* ---------- Step 6: Patient details ---------- */

		function clearFieldErrors() {
			root.querySelectorAll( '.ab-field' ).forEach( function ( f ) {
				f.classList.remove( 'ab-has-error' );
				var err = f.querySelector( '.ab-field-error' );
				if ( err ) { err.remove(); }
			} );
		}

		function setFieldError( fieldId, message ) {
			var input = root.querySelector( '#' + fieldId );
			if ( ! input ) { return; }
			var field = input.closest( '.ab-field' );
			field.classList.add( 'ab-has-error' );
			var span = document.createElement( 'span' );
			span.className = 'ab-field-error';
			span.textContent = message;
			field.appendChild( span );
		}

		function validateDetails() {
			clearFieldErrors();
			var valid = true;
			var firstName = root.querySelector( '#ab_first_name' ).value.trim();
			var lastName  = root.querySelector( '#ab_last_name' ).value.trim();
			var email     = root.querySelector( '#ab_email' ).value.trim();
			var phone     = root.querySelector( '#ab_phone' ).value.trim();

			if ( ! firstName ) { setFieldError( 'ab_first_name', AB_FRONTEND.i18n.requiredField ); valid = false; }
			if ( ! lastName ) { setFieldError( 'ab_last_name', AB_FRONTEND.i18n.requiredField ); valid = false; }
			if ( ! email ) {
				setFieldError( 'ab_email', AB_FRONTEND.i18n.requiredField ); valid = false;
			} else if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) {
				setFieldError( 'ab_email', AB_FRONTEND.i18n.invalidEmail ); valid = false;
			}
			if ( ! phone ) {
				setFieldError( 'ab_phone', AB_FRONTEND.i18n.requiredField ); valid = false;
			} else if ( ! /^[0-9+\-\s()]{6,20}$/.test( phone ) ) {
				setFieldError( 'ab_phone', AB_FRONTEND.i18n.invalidPhone ); valid = false;
			}

			return valid;
		}

		root.querySelector( '.ab-btn-next[data-next="7"]' ).addEventListener( 'click', function () {
			if ( ! validateDetails() ) {
				return;
			}
			renderReview();
			goToStep( 7 );
		} );

		/* ---------- Step 7: Review ---------- */

		function renderReview() {
			var card = root.querySelector( '.ab-review-card' );
			var serviceNames = state.services.map( function ( s ) { return s.name; } ).join( ', ' );
			var firstName = root.querySelector( '#ab_first_name' ).value.trim();
			var lastName  = root.querySelector( '#ab_last_name' ).value.trim();
			var email     = root.querySelector( '#ab_email' ).value.trim();
			var phone     = root.querySelector( '#ab_phone' ).value.trim();

			var hours = Math.floor( state.totalMinutes / 60 );
			var mins  = state.totalMinutes % 60;
			var durationLabel = ( hours ? hours + 'h ' : '' ) + mins + 'm';

			var rows = [
				[ 'Treatment', state.category.name ],
				[ 'Doctor', state.doctor.name ],
				[ 'Services', serviceNames ],
				[ 'Appointment Date', state.date ],
				[ 'Appointment Time', state.time.label ],
				[ 'Total Duration', durationLabel ],
				[ 'Patient Name', firstName + ' ' + lastName ],
				[ 'Email', email ],
				[ 'Phone', phone ],
			];

			card.innerHTML = rows.map( function ( r ) {
				return '<div class="ab-review-row"><span class="ab-review-label">' + escapeHtml( r[ 0 ] ) + '</span><span class="ab-review-value">' + escapeHtml( r[ 1 ] ) + '</span></div>';
			} ).join( '' );
		}

		/* ---------- Submit ---------- */

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			hideAlert();

			var submitBtn = form.querySelector( '.ab-btn-submit' );
			submitBtn.disabled = true;
			var originalText = submitBtn.textContent;
			submitBtn.textContent = 'Submitting…';

			var payload = {
				category_id: state.category.id,
				doctor_id: state.doctor.id,
				service_ids: state.services.map( function ( s ) { return s.id; } ),
				date: state.date,
				time: state.time.time,
				first_name: root.querySelector( '#ab_first_name' ).value.trim(),
				last_name: root.querySelector( '#ab_last_name' ).value.trim(),
				email: root.querySelector( '#ab_email' ).value.trim(),
				phone: root.querySelector( '#ab_phone' ).value.trim(),
				country_code: root.querySelector( '#ab_country_code' ).value.trim(),
				message: root.querySelector( '#ab_message' ).value.trim(),
				ab_website: root.querySelector( '[name="ab_website"]' ).value,
			};

			ajax( 'ab_submit_booking', payload ).then( function ( res ) {
				submitBtn.disabled = false;
				submitBtn.textContent = originalText;

				if ( ! res.success ) {
					showAlert( ( res.data && res.data.message ) || AB_FRONTEND.i18n.genericError );
					return;
				}

				root.querySelector( '.ab-booking-id-display' ).textContent = 'Booking ID: ' + res.data.booking_id;
				goToStep( 8 );
			} ).catch( function () {
				submitBtn.disabled = false;
				submitBtn.textContent = originalText;
				showAlert( AB_FRONTEND.i18n.genericError );
			} );
		} );

		/* ---------- Restart ---------- */

		root.querySelector( '.ab-btn-restart' ).addEventListener( 'click', function () {
			window.location.reload();
		} );

	} );
} )();