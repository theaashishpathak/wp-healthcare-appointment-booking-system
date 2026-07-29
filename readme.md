# Appointment Booking System

A complete appointment booking plugin for WordPress, purpose-built for healthcare clinics, hospitals, wellness centres, and treatment providers.

## Requirements

- WordPress 6.0+
- PHP 8.1+
- MySQL / MariaDB

## Installation

1. Upload the `appointment-booking-system` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Appointment Booking → Treatment Categories** and add your first category.
4. Add **Doctors** and assign them to categories.
5. Add **Services** under each category.
6. Configure each doctor's **Availability** (working hours, breaks, slot interval, holidays).
7. Place the shortcode below on any page:

```
[appointment_booking]
```

## Divi Builder

The shortcode has zero dependency on Divi modules and can be dropped into a Text, Code, or Shortcode module.

## Admin Menu

- Dashboard — booking statistics, recent appointments, upcoming schedule
- Treatment Categories — CRUD
- Doctors — CRUD, multi-category assignment
- Services — CRUD, duration in hours/minutes
- Availability — weekly hours, breaks, slot interval, holidays (single date, range, recurring), special working days
- Appointments — searchable/filterable table, status management, CSV export
- Settings — clinic info, email, appearance, advanced
- Help — shortcode reference & quick start

## Security

Every admin and AJAX request is protected with WordPress nonces, capability checks (`manage_options` for admin actions), sanitization on input, escaping on output, and `$wpdb->prepare()` for all database queries. The public booking form includes a honeypot field for basic spam prevention and re-validates slot availability at submission time to prevent double booking.

## Database Tables

`wp_ab_categories`, `wp_ab_doctors`, `wp_ab_doctor_categories`, `wp_ab_services`, `wp_ab_availability`, `wp_ab_holidays`, `wp_ab_appointments`, `wp_ab_appointment_services`.

## Uninstall

By default, all plugin tables and data are removed when the plugin is deleted. Enable **"Keep all appointment data when the plugin is deleted"** under Settings → Advanced to preserve your data.

## Future Enhancements

Google/Outlook Calendar sync, Zoom integration, WhatsApp/SMS notifications, Stripe/Razorpay/PayPal payments, WooCommerce integration, patient/doctor login, rescheduling, recurring appointments, analytics dashboard, REST API, WPML support, Elementor widget, Divi module.
