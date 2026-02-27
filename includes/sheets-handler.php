<?php
/**
 * Google Sheets API Handler.
 *
 * Fetches cell values from Google Sheets and caches them using WP transients.
 *
 * @package DPB
 */

namespace DPB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Sheets_Handler {

    /**
     * Google Sheets API v4 base URL.
     */
    private const API_BASE = 'https://sheets.googleapis.com/v4/spreadsheets';

    /**
     * Get the stored Google API key.
     */
    private function get_api_key(): string {
        return get_option( 'dpb_google_api_key', '' );
    }

    /**
     * Build the transient cache key for a specific cell request.
     */
    private function cache_key( string $spreadsheet_id, string $sheet_name, string $cell ): string {
        return 'dpb_' . md5( $spreadsheet_id . $sheet_name . $cell );
    }

    /**
     * Fetch a single cell value from Google Sheets.
     *
     * @param string $spreadsheet_id The spreadsheet ID from the URL.
     * @param string $sheet_name     The sheet/tab name (e.g., "Sheet1").
     * @param string $cell           The cell reference (e.g., "B2").
     * @param int    $cache_minutes  How long to cache the result (in minutes).
     *
     * @return string|false The cell value, or false on failure.
     */
    public function get_cell_value( string $spreadsheet_id, string $sheet_name, string $cell, int $cache_minutes = 5 ) {
        $transient_key = $this->cache_key( $spreadsheet_id, $sheet_name, $cell );

        // Check cache first.
        $cached = get_transient( $transient_key );
        if ( $cached !== false ) {
            return $cached;
        }

        // Build the range (e.g., "Sheet1!B2").
        $range = ! empty( $sheet_name ) ? $sheet_name . '!' . $cell : $cell;

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return false;
        }

        $url = sprintf(
            '%s/%s/values/%s?key=%s&valueRenderOption=UNFORMATTED_VALUE',
            self::API_BASE,
            urlencode( $spreadsheet_id ),
            urlencode( $range ),
            urlencode( $api_key )
        );

        $response = wp_remote_get( $url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status !== 200 ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['values'][0][0] ) && $body['values'][0][0] !== 0 && $body['values'][0][0] !== '0' ) {
            // Cell is empty — cache "0" to avoid repeated API calls.
            set_transient( $transient_key, '0', $cache_minutes * MINUTE_IN_SECONDS );
            return '0';
        }

        $value = (string) $body['values'][0][0];
        set_transient( $transient_key, $value, $cache_minutes * MINUTE_IN_SECONDS );

        return $value;
    }

    /**
     * Fetch a range and count non-empty rows.
     *
     * Useful when each sale is a row and you want to count total rows.
     *
     * @param string $spreadsheet_id The spreadsheet ID.
     * @param string $sheet_name     The sheet/tab name.
     * @param string $range          The range (e.g., "A2:A" for all rows in column A from row 2).
     * @param int    $cache_minutes  Cache duration.
     *
     * @return int|false Row count, or false on failure.
     */
    public function count_rows( string $spreadsheet_id, string $sheet_name, string $range, int $cache_minutes = 5 ) {
        $transient_key = 'dpb_count_' . md5( $spreadsheet_id . $sheet_name . $range );

        $cached = get_transient( $transient_key );
        if ( $cached !== false ) {
            return (int) $cached;
        }

        $full_range = ! empty( $sheet_name ) ? $sheet_name . '!' . $range : $range;

        $api_key = $this->get_api_key();
        if ( empty( $api_key ) ) {
            return false;
        }

        $url = sprintf(
            '%s/%s/values/%s?key=%s&majorDimension=ROWS',
            self::API_BASE,
            urlencode( $spreadsheet_id ),
            urlencode( $full_range ),
            urlencode( $api_key )
        );

        $response = wp_remote_get( $url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $body  = json_decode( wp_remote_retrieve_body( $response ), true );
        $count = isset( $body['values'] ) ? count( $body['values'] ) : 0;

        set_transient( $transient_key, (string) $count, $cache_minutes * MINUTE_IN_SECONDS );

        return $count;
    }
}
