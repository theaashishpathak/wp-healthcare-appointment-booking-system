# Healthcare Appointment Booking System

An enterprise-grade, WPML-multilingual, fully audit-logged appointment booking system for WordPress, purpose-built for healthcare clinics, hospitals, medical centers, wellness practices, and multi-doctor facilities.

---

## 🚀 Public Booking Wizard Flow

The plugin features a modern, responsive, 7-step AJAX booking wizard (`[appointment_booking]`):

1. **Select Treatment Category**: Choose from active medical specialties (e.g. Cardiology, Dental Care, Orthopedics, General Consultation).
2. **Choose Doctor**: Select assigned doctor(s) featuring photo, qualifications, years of experience, specialization, and bio.
3. **Choose Services**: Pick single or multiple services under the selected category with dynamic cumulative duration calculation in minutes.
4. **Choose Appointment Date**: Interactive date picker that respects doctor weekly working days, single/range holidays, and recurring day exclusions.
5. **Choose Available Time**: Real-time slot generator dynamically calculating open appointment slots according to doctor start time, end time, break interval, slot duration, and existing confirmed bookings.
6. **Personal Information**: Patient details collection (First Name, Last Name, Email, Phone Number, Optional Message) with validation and honeypot spam protection.
7. **Review Appointment**: Comprehensive summary review card displaying all selections, duration, date/time, and patient details before final submission.
8. **Confirmation & Email Dispatch**: Instant confirmation screen featuring a unique Booking ID (`AB-YYYYMMDD-XXXX`), alongside customer confirmation and admin notification emails.

---

## ✨ Key Features & Enhancements

### 🌐 WPML Multilingual & Dynamic i18n System
- **Deep WPML Integration**: Compatible with WPML (`wpml_active_languages`, `wpml_current_language`, `wpml_default_language`).
- **Multi-Language Baseline Dictionary**: Built-in translation dictionaries for **English (`en`)**, **Spanish (`es`)**, **German (`de`)**, **French (`fr`)**, and **Arabic (`ar`)** with full RTL layout support.
- **Dynamic Language Detection**: Automatically detects active WordPress site locales (`get_locale()`) and Polylang/WPML languages.
- **Universal Fallback**: Un-translated languages (e.g. Portuguese `pt`, Italian `it`, Dutch `nl`, Japanese `ja`) seamlessly fall back to English baseline placeholders without breaking UI.
- **Custom Database Translation Table**: Dedicated `wp_ab_translation_map` for translating doctor bios, service names, and category descriptions.

### 🔤 Admin String Translation Suite (`Appointment Booking → String Translations`)
- **Full Visual Editor**: Manage all static text across Frontend Steps, Buttons, Form Labels, Alert Messages, Email Templates, Doctors, Services, Categories, Availability, Appointments, and Settings.
- **Real-Time Live Search Filter**: Includes an inline search box (`#ab_string_search`) to filter string keys or labels instantly as you type.
- **Language Switcher Tabs**: Clean tabbed navigation across all active languages.
- **Sticky Save Bar**: Floating save bar ensures quick updates without scrolling.

### 🛡️ Executive System Activity & Audit Logs (`Appointment Booking → Activity Logs`)
- **Dedicated MySQL Audit Table**: All administrative actions and system events are logged to `wp_ab_activity_logs`.
- **Initiator & Metadata Tracking**: Records User Name, Email, Role, Timestamp, Remote IP Address (`REMOTE_ADDR`), and Browser User Agent (`HTTP_USER_AGENT`).
- **Side-by-Side Diff Tables**: Expandable inline drawer detailing modified field keys with original vs. updated values highlighted in red/green.
- **Raw JSON Payload Viewer**: Detailed payload viewer for deep technical auditability.
- **Tracked Event Types**:
  - **Doctors**: Creation, updates, multi-category assignment, deletions.
  - **Services & Categories**: Creation, edits, duration adjustments, deletions.
  - **Availability & Holidays**: Weekly schedules, break changes, single/range/recurring holiday overrides.
  - **Appointments**: New bookings, status updates (*Pending*, *Confirmed*, *Completed*, *Cancelled*), deletions.
  - **Plugin Lifecycle**: Plugin activation, deactivation, and updates across WordPress.
  - **Security**: User logins, logouts, and administrative setting changes.
  - **Email Delivery**: Tracking customer and admin email dispatches.

### 📧 Email Delivery & Failure Tracking
- Logs every customer confirmation and admin notification email attempt.
- Captures delivery status (`Sent Successfully` or `Failed / Mailer Rejected`), recipient address, subject, booking ID, and active language.
- Listens to WordPress core `wp_mail_failed` to log exact `WP_Error` codes and diagnostic messages (e.g. SMTP auth failure, port blocks).

### 📅 Advanced Doctor Availability & Holiday Engine
- Weekly working hours per doctor with custom start time, end time, break start, break end, and slot intervals (15, 20, 30, 45, 60 mins).
- Flexible holiday overrides: Single Date, Date Range, Weekly Recurring Holidays, or Special Working Days (open on public holidays).

### 📊 Admin Dashboard & Appointments Management
- **Dashboard**: High-level stat cards, today's schedule, pending bookings, and quick action shortcuts.
- **Appointments Manager**: Searchable and filterable table by doctor, category, status, date, or patient info.
- **CSV Export**: One-click CSV export of appointment records.

---

## 🛠️ Installation & Usage

1. Upload the `appointment-booking-system` folder to `/wp-content/plugins/`.
2. Activate the plugin through **Plugins** in WordPress.
3. Go to **Appointment Booking → Treatment Categories** and create your first category.
4. Add **Doctors** and assign them to categories.
5. Add **Services** under each category.
6. Configure doctor **Availability** (working hours, breaks, slot interval, holidays).
7. Place the shortcode on any page, post, or builder module (Divi, Elementor, Gutenberg, Beaver Builder):

```
[appointment_booking]
```

---

## 🎨 Page Builder Compatibility (Divi & Elementor)

- **Zero External Dependencies**: The `[appointment_booking]` shortcode works natively inside Divi Text, Code, or Shortcode modules, Elementor Shortcode widgets, Gutenberg, and classic editor.
- **Clean Styling**: Embedded CSS ensures smooth responsive layouts across desktop, tablet, and mobile.

---

## 🔒 Security & Data Integrity

- **Nonces & Capabilities**: Nonces protect all form submissions; `manage_options` capability is enforced on all admin endpoints.
- **Prepared Queries**: All database operations execute via `$wpdb->prepare()`.
- **Sanitization & Escaping**: Full input sanitization (`sanitize_text_field`, `sanitize_email`, `absint`) and output escaping (`esc_html`, `esc_attr`, `esc_url`).
- **Anti-Spam & Collision Prevention**: Public form features honeypot protection and real-time slot availability re-verification at submission time.

---

## 🗄️ Database Architecture

The plugin automatically installs and maintains 10 custom database tables:

- `wp_ab_categories`: Treatment categories and display order.
- `wp_ab_doctors`: Doctor profiles, qualifications, experience, bio, email, status.
- `wp_ab_doctor_categories`: Pivot table mapping doctors to multiple categories.
- `wp_ab_services`: Services, duration (hours/minutes), and category links.
- `wp_ab_availability`: Doctor weekly working hours, breaks, and slot durations.
- `wp_ab_holidays`: Doctor holidays, ranges, recurring days, and special working days.
- `wp_ab_appointments`: Master appointment bookings, status, and patient data.
- `wp_ab_appointment_services`: Pivot table linking appointments to selected services.
- `wp_ab_translation_map`: WPML translation mapping for custom table items.
- `wp_ab_activity_logs`: Complete system audit trail with diffs, user metadata, IP, and user agents.

---

## 🗑️ Uninstall & Data Retention

By default, all custom database tables and plugin options are removed upon deletion. To preserve data upon plugin deletion, enable **"Keep all appointment data when the plugin is deleted"** under **Appointment Booking → Settings → Advanced**.

---

## 🔮 Future Roadmap

- 🗓️ **Google Calendar & Outlook 365 Sync**: 2-way real-time calendar synchronization for doctors.
- 📹 **Telehealth Integration**: Automated Zoom & Google Meet video link generation for online consultations.
- 💬 **WhatsApp & SMS Notifications**: Twilio and WhatsApp Business API reminders for upcoming appointments.
- 💳 **Payment Gateways**: Stripe, PayPal, Razorpay, and WooCommerce checkout integration.
- 👤 **Patient Portal**: Self-service appointment management, rescheduling, and medical history.
- 🧩 **Native Builders**: Dedicated Elementor widgets and Divi modules.
