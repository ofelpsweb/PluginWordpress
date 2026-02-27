<?php
/**
 * Plugin Name: Dynamic Progress Bar for Elementor
 * Description: Custom progress bar widget with Google Sheets integration for real-time tracking.
 * Version: 1.0.2
 * Author: Felipe
 * Requires Plugins: elementor
 * Text Domain: dynamic-progress-bar
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DPB_VERSION', '1.0.2' );
define( 'DPB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DPB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Auto-Update via GitHub Releases ────────────────────────────────────────────

require_once DPB_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$dpb_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/ofelpsweb/PluginWordpress/',
    __FILE__,
    'elementor-progress-bar'
);

// Use releases (tags) for version checking.
$dpb_update_checker->getVcsApi()->enableReleaseAssets();

/**
 * Check if Elementor is active and loaded.
 */
function dpb_check_elementor() {
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Dynamic Progress Bar</strong> requires <strong>Elementor</strong> to be installed and active.</p></div>';
        } );
        return false;
    }
    return true;
}

/**
 * Initialize the plugin.
 */
function dpb_init() {
    if ( ! dpb_check_elementor() ) {
        return;
    }

    require_once DPB_PLUGIN_DIR . 'includes/sheets-handler.php';

    // Register widget
    add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
        require_once DPB_PLUGIN_DIR . 'widgets/progress-bar-widget.php';
        $widgets_manager->register( new \DPB\Widgets\Progress_Bar_Widget() );
    } );

    // Register widget category
    add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
        $elements_manager->add_category( 'dpb-widgets', [
            'title' => __( 'Dynamic Progress Bar', 'dynamic-progress-bar' ),
            'icon'  => 'eicon-progress-tracker',
        ] );
    } );

    // Enqueue frontend assets
    add_action( 'elementor/frontend/after_enqueue_styles', function () {
        wp_enqueue_style( 'dpb-progress-bar', DPB_PLUGIN_URL . 'assets/css/progress-bar.css', [], DPB_VERSION );
    } );

    add_action( 'elementor/frontend/after_enqueue_scripts', function () {
        wp_enqueue_script( 'dpb-progress-bar', DPB_PLUGIN_URL . 'assets/js/progress-bar.js', [ 'jquery' ], DPB_VERSION, true );
        wp_localize_script( 'dpb-progress-bar', 'dpbAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'dpb_refresh_nonce' ),
        ] );
    } );

    // AJAX endpoints (logged-in and guest)
    add_action( 'wp_ajax_dpb_refresh_progress', 'dpb_ajax_refresh_progress' );
    add_action( 'wp_ajax_nopriv_dpb_refresh_progress', 'dpb_ajax_refresh_progress' );
}
add_action( 'plugins_loaded', 'dpb_init' );

/**
 * AJAX handler: fetch fresh progress value from Google Sheets.
 */
function dpb_ajax_refresh_progress() {
    check_ajax_referer( 'dpb_refresh_nonce', 'nonce' );

    $spreadsheet_id = sanitize_text_field( wp_unslash( $_POST['spreadsheet_id'] ?? '' ) );
    $sheet_name     = sanitize_text_field( wp_unslash( $_POST['sheet_name'] ?? '' ) );
    $cell           = sanitize_text_field( wp_unslash( $_POST['cell'] ?? '' ) );
    $range          = sanitize_text_field( wp_unslash( $_POST['range'] ?? '' ) );
    $source         = sanitize_text_field( wp_unslash( $_POST['source'] ?? 'sheets_cell' ) );
    $goal           = absint( $_POST['goal'] ?? 100 );
    $cache_minutes  = absint( $_POST['cache_minutes'] ?? 5 );

    if ( empty( $spreadsheet_id ) ) {
        wp_send_json_error( [ 'message' => 'Missing spreadsheet ID.' ] );
    }

    $handler = new \DPB\Sheets_Handler();
    $current = false;

    if ( $source === 'sheets_rows' && ! empty( $range ) ) {
        $current = $handler->count_rows( $spreadsheet_id, $sheet_name, $range, $cache_minutes );
    } elseif ( ! empty( $cell ) ) {
        $value   = $handler->get_cell_value( $spreadsheet_id, $sheet_name, $cell, $cache_minutes );
        $current = $value !== false ? floatval( $value ) : false;
    }

    if ( $current === false ) {
        wp_send_json_error( [ 'message' => 'Could not fetch data from Google Sheets.' ] );
    }

    $current    = floatval( $current );
    $percentage = $goal > 0 ? min( round( ( $current / $goal ) * 100, 1 ), 100 ) : 0;

    wp_send_json_success( [
        'current'    => $current,
        'goal'       => $goal,
        'percentage' => $percentage,
    ] );
}

// ── Settings Page ──────────────────────────────────────────────────────────────

/**
 * Register settings page under Settings menu.
 */
function dpb_register_settings_page() {
    add_options_page(
        __( 'Dynamic Progress Bar', 'dynamic-progress-bar' ),
        __( 'Dynamic Progress Bar', 'dynamic-progress-bar' ),
        'manage_options',
        'dpb-settings',
        'dpb_render_settings_page'
    );
}
add_action( 'admin_menu', 'dpb_register_settings_page' );

/**
 * Register plugin settings.
 */
function dpb_register_settings() {
    register_setting( 'dpb_settings_group', 'dpb_google_api_key', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ] );
}
add_action( 'admin_init', 'dpb_register_settings' );

/**
 * Render the settings page.
 */
function dpb_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'dpb_settings_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="dpb_google_api_key"><?php esc_html_e( 'Google Sheets API Key', 'dynamic-progress-bar' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="dpb_google_api_key" name="dpb_google_api_key"
                               value="<?php echo esc_attr( get_option( 'dpb_google_api_key', '' ) ); ?>"
                               class="regular-text" />
                        <p class="description">
                            <?php esc_html_e( 'Create an API key in Google Cloud Console with Google Sheets API enabled.', 'dynamic-progress-bar' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Setup Guide', 'dynamic-progress-bar' ); ?></h2>
        <ol>
            <li><?php esc_html_e( 'Go to Google Cloud Console → APIs & Services → Credentials.', 'dynamic-progress-bar' ); ?></li>
            <li><?php esc_html_e( 'Create an API Key (restrict to Google Sheets API only).', 'dynamic-progress-bar' ); ?></li>
            <li><?php esc_html_e( 'Enable the Google Sheets API in your project.', 'dynamic-progress-bar' ); ?></li>
            <li><?php esc_html_e( 'Share your spreadsheet with "Anyone with the link" (Viewer).', 'dynamic-progress-bar' ); ?></li>
            <li><?php esc_html_e( 'Paste the API Key above and save.', 'dynamic-progress-bar' ); ?></li>
        </ol>
    </div>
    <?php
}
