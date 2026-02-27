=== Dynamic Progress Bar for Elementor ===
Contributors: felipe
Tags: elementor, progress bar, google sheets, tickets, dynamic
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

A dynamic progress bar widget for Elementor with Google Sheets integration. Track ticket sales, goals, and more in real time.

== Description ==

**Dynamic Progress Bar** adds a custom progress bar widget to Elementor that connects to Google Sheets for live data.

**Use cases:**
- Track ticket sales for events (1st batch, 2nd batch, etc.)
- Crowdfunding/donation goal tracking
- Any goal that updates in a spreadsheet

**Features:**
- Google Sheets API integration (reads cell value or counts rows)
- Auto-refresh via AJAX (configurable interval)
- Server-side caching (WordPress transients)
- Full Elementor Style tab (colors, gradient, height, border radius, typography)
- Scroll-triggered animation
- Striped/animated stripe effects
- Multiple display formats: percentage, fraction, current count, remaining
- Manual (static) mode for non-Sheets usage

== Installation ==

1. Upload the `elementor-progress-bar` folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Go to **Settings → Dynamic Progress Bar** and enter your Google Sheets API Key.
4. In Elementor, search for "Dynamic Progress Bar" in the widget panel.

== Setup Google Sheets API ==

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a project (or select existing).
3. Enable the **Google Sheets API**.
4. Go to **Credentials → Create Credentials → API Key**.
5. Restrict the key to Google Sheets API only (recommended).
6. Share your spreadsheet with "Anyone with the link" as Viewer.
7. Paste the API Key in the plugin settings.

== Changelog ==

= 1.0.0 =
* Initial release.
* Google Sheets cell value and row count modes.
* AJAX auto-refresh with configurable interval.
* Full Elementor Content/Style/Advanced tabs.
* Scroll animation, striped bar, gradient support.
