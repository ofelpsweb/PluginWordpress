<?php
/**
 * Dynamic Progress Bar Widget for Elementor.
 *
 * @package DPB
 */

namespace DPB\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Progress_Bar_Widget extends Widget_Base {

    public function get_name(): string {
        return 'dpb_progress_bar';
    }

    public function get_title(): string {
        return __( 'Dynamic Progress Bar', 'dynamic-progress-bar' );
    }

    public function get_icon(): string {
        return 'eicon-skill-bar';
    }

    public function get_categories(): array {
        return [ 'dpb-widgets' ];
    }

    public function get_keywords(): array {
        return [ 'progress', 'bar', 'sheets', 'tickets', 'sales', 'dynamic' ];
    }

    public function get_style_depends(): array {
        return [ 'dpb-progress-bar' ];
    }

    public function get_script_depends(): array {
        return [ 'dpb-progress-bar' ];
    }

    // ── Controls ───────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_sheets_controls();
        $this->register_style_bar_controls();
        $this->register_style_text_controls();
    }

    /**
     * Tab: Content → Section: General
     */
    private function register_content_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'dynamic-progress-bar' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'title', [
            'label'       => __( 'Title', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'Ticket Sales', 'dynamic-progress-bar' ),
            'placeholder' => __( 'e.g., 1st Batch', 'dynamic-progress-bar' ),
            'label_block' => true,
        ] );

        $this->add_control( 'goal', [
            'label'       => __( 'Goal (Total)', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 100,
            'min'         => 1,
            'description' => __( 'Total tickets/items available (100% mark).', 'dynamic-progress-bar' ),
        ] );

        $this->add_control( 'data_source', [
            'label'   => __( 'Data Source', 'dynamic-progress-bar' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'sheets_cell',
            'options' => [
                'manual'      => __( 'Manual (Static)', 'dynamic-progress-bar' ),
                'sheets_cell' => __( 'Google Sheets — Cell Value', 'dynamic-progress-bar' ),
                'sheets_rows' => __( 'Google Sheets — Count Rows', 'dynamic-progress-bar' ),
            ],
        ] );

        $this->add_control( 'manual_value', [
            'label'     => __( 'Current Value', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 45,
            'min'       => 0,
            'condition' => [ 'data_source' => 'manual' ],
        ] );

        $this->add_control( 'display_format', [
            'label'   => __( 'Display Format', 'dynamic-progress-bar' ),
            'type'    => Controls_Manager::SELECT,
            'default' => 'percentage',
            'options' => [
                'percentage' => __( '75%', 'dynamic-progress-bar' ),
                'fraction'   => __( '75 / 100', 'dynamic-progress-bar' ),
                'current'    => __( '75 sold', 'dynamic-progress-bar' ),
                'remaining'  => __( '25 remaining', 'dynamic-progress-bar' ),
                'none'       => __( 'Hide', 'dynamic-progress-bar' ),
            ],
        ] );

        $this->add_control( 'suffix_text', [
            'label'       => __( 'Suffix Text', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __( 'e.g., sold, vendidos', 'dynamic-progress-bar' ),
            'condition'   => [ 'display_format' => 'current' ],
        ] );

        $this->add_control( 'remaining_text', [
            'label'       => __( 'Remaining Text', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => __( 'remaining', 'dynamic-progress-bar' ),
            'condition'   => [ 'display_format' => 'remaining' ],
        ] );

        $this->end_controls_section();
    }

    /**
     * Tab: Content → Section: Google Sheets
     */
    private function register_sheets_controls(): void {
        $this->start_controls_section( 'section_sheets', [
            'label'     => __( 'Google Sheets', 'dynamic-progress-bar' ),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => [
                'data_source' => [ 'sheets_cell', 'sheets_rows' ],
            ],
        ] );

        $api_key = get_option( 'dpb_google_api_key', '' );
        if ( empty( $api_key ) ) {
            $this->add_control( 'api_key_notice', [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => sprintf(
                    '<div style="background:#fef3c7;border:1px solid #f59e0b;padding:10px;border-radius:4px;">⚠️ %s <a href="%s" target="_blank">%s</a></div>',
                    __( 'API Key not configured.', 'dynamic-progress-bar' ),
                    admin_url( 'options-general.php?page=dpb-settings' ),
                    __( 'Configure now →', 'dynamic-progress-bar' )
                ),
                'content_classes' => 'elementor-panel-alert',
            ] );
        }

        $this->add_control( 'spreadsheet_id', [
            'label'       => __( 'Spreadsheet ID', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'placeholder' => '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
            'label_block' => true,
            'description' => __( 'The ID from the spreadsheet URL: docs.google.com/spreadsheets/d/{THIS_PART}/edit', 'dynamic-progress-bar' ),
        ] );

        $this->add_control( 'sheet_name', [
            'label'       => __( 'Sheet Name', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'Sheet1',
            'placeholder' => __( 'Sheet1', 'dynamic-progress-bar' ),
            'description' => __( 'The tab/sheet name at the bottom of your spreadsheet.', 'dynamic-progress-bar' ),
        ] );

        $this->add_control( 'cell_reference', [
            'label'       => __( 'Cell Reference', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'B2',
            'placeholder' => 'B2',
            'description' => __( 'The cell containing the current count (e.g., B2).', 'dynamic-progress-bar' ),
            'condition'   => [ 'data_source' => 'sheets_cell' ],
        ] );

        $this->add_control( 'row_range', [
            'label'       => __( 'Range to Count', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'A2:A',
            'placeholder' => 'A2:A',
            'description' => __( 'Range of rows to count (e.g., "A2:A" counts all filled rows in column A from row 2).', 'dynamic-progress-bar' ),
            'condition'   => [ 'data_source' => 'sheets_rows' ],
        ] );

        $this->add_control( 'cache_minutes', [
            'label'       => __( 'Cache Duration (minutes)', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 5,
            'min'         => 1,
            'max'         => 1440,
            'description' => __( 'How long to cache the Sheets response server-side.', 'dynamic-progress-bar' ),
        ] );

        $this->add_control( 'refresh_interval', [
            'label'       => __( 'Auto-Refresh Interval (seconds)', 'dynamic-progress-bar' ),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 30,
            'min'         => 10,
            'max'         => 3600,
            'description' => __( 'How often the bar refreshes on the frontend (via AJAX). Min: 10s.', 'dynamic-progress-bar' ),
        ] );

        $this->end_controls_section();
    }

    /**
     * Tab: Style → Section: Bar
     */
    private function register_style_bar_controls(): void {
        $this->start_controls_section( 'section_style_bar', [
            'label' => __( 'Bar', 'dynamic-progress-bar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'bar_color', [
            'label'     => __( 'Bar Color', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#8B5CF6',
            'selectors' => [
                '{{WRAPPER}} .dpb-bar-fill' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'bar_use_gradient', [
            'label'        => __( 'Use Gradient', 'dynamic-progress-bar' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'bar_gradient_end', [
            'label'     => __( 'Gradient End Color', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#6D28D9',
            'condition' => [ 'bar_use_gradient' => 'yes' ],
            'selectors' => [
                '{{WRAPPER}} .dpb-bar-fill' => 'background: linear-gradient(90deg, {{bar_color.VALUE}} 0%, {{VALUE}} 100%);',
            ],
        ] );

        $this->add_control( 'bar_bg_color', [
            'label'     => __( 'Track Color', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E5E7EB',
            'selectors' => [
                '{{WRAPPER}} .dpb-bar-track' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_responsive_control( 'bar_height', [
            'label'      => __( 'Height', 'dynamic-progress-bar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 4, 'max' => 60, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 20, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .dpb-bar-track, {{WRAPPER}} .dpb-bar-fill' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_responsive_control( 'bar_border_radius', [
            'label'      => __( 'Border Radius', 'dynamic-progress-bar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 10, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .dpb-bar-track, {{WRAPPER}} .dpb-bar-fill' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'bar_animate', [
            'label'        => __( 'Animate on Scroll', 'dynamic-progress-bar' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
            'description'  => __( 'Bar fills when it enters the viewport.', 'dynamic-progress-bar' ),
        ] );

        $this->add_control( 'bar_animation_duration', [
            'label'     => __( 'Animation Duration (ms)', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 1000,
            'min'       => 200,
            'max'       => 5000,
            'condition' => [ 'bar_animate' => 'yes' ],
        ] );

        $this->add_control( 'bar_stripe', [
            'label'        => __( 'Striped Effect', 'dynamic-progress-bar' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'bar_stripe_animated', [
            'label'        => __( 'Animate Stripes', 'dynamic-progress-bar' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'return_value' => 'yes',
            'condition'    => [ 'bar_stripe' => 'yes' ],
        ] );

        $this->end_controls_section();
    }

    /**
     * Tab: Style → Section: Typography
     */
    private function register_style_text_controls(): void {
        $this->start_controls_section( 'section_style_text', [
            'label' => __( 'Text', 'dynamic-progress-bar' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'title_color', [
            'label'     => __( 'Title Color', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1F2937',
            'selectors' => [
                '{{WRAPPER}} .dpb-title' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __( 'Title Typography', 'dynamic-progress-bar' ),
            'selector' => '{{WRAPPER}} .dpb-title',
        ] );

        $this->add_control( 'percentage_color', [
            'label'     => __( 'Value Color', 'dynamic-progress-bar' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#4B5563',
            'selectors' => [
                '{{WRAPPER}} .dpb-value' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'value_typography',
            'label'    => __( 'Value Typography', 'dynamic-progress-bar' ),
            'selector' => '{{WRAPPER}} .dpb-value',
        ] );

        $this->add_responsive_control( 'text_spacing', [
            'label'      => __( 'Spacing (below text)', 'dynamic-progress-bar' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 30, 'step' => 1 ],
            ],
            'default'    => [ 'size' => 8, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .dpb-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->end_controls_section();
    }

    // ── Render ──────────────────────────────────────────────────────────────────

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $goal       = max( 1, absint( $settings['goal'] ) );
        $current    = 0;
        $percentage = 0;
        $source     = $settings['data_source'];

        if ( $source === 'manual' ) {
            $current    = floatval( $settings['manual_value'] );
            $percentage = min( round( ( $current / $goal ) * 100, 1 ), 100 );
        } else {
            // Fetch from Google Sheets (server-side on initial render).
            $handler       = new \DPB\Sheets_Handler();
            $spreadsheet   = $settings['spreadsheet_id'] ?? '';
            $sheet         = $settings['sheet_name'] ?? 'Sheet1';
            $cache_min     = absint( $settings['cache_minutes'] ?? 5 );

            if ( $source === 'sheets_cell' ) {
                $cell  = $settings['cell_reference'] ?? 'B2';
                $value = $handler->get_cell_value( $spreadsheet, $sheet, $cell, $cache_min );
                $current = $value !== false ? floatval( $value ) : 0;
            } elseif ( $source === 'sheets_rows' ) {
                $range   = $settings['row_range'] ?? 'A2:A';
                $count   = $handler->count_rows( $spreadsheet, $sheet, $range, $cache_min );
                $current = $count !== false ? $count : 0;
            }

            $percentage = min( round( ( $current / $goal ) * 100, 1 ), 100 );
        }

        // Format display value.
        $display_text = '';
        switch ( $settings['display_format'] ) {
            case 'percentage':
                $display_text = $percentage . '%';
                break;
            case 'fraction':
                $display_text = intval( $current ) . ' / ' . $goal;
                break;
            case 'current':
                $suffix       = $settings['suffix_text'] ?? '';
                $display_text = intval( $current ) . ( $suffix ? ' ' . $suffix : '' );
                break;
            case 'remaining':
                $remaining    = max( 0, $goal - intval( $current ) );
                $rem_text     = $settings['remaining_text'] ?? 'remaining';
                $display_text = $remaining . ' ' . $rem_text;
                break;
            case 'none':
                $display_text = '';
                break;
        }

        // Build data attributes for JS.
        $animate  = $settings['bar_animate'] === 'yes';
        $duration = absint( $settings['bar_animation_duration'] ?? 1000 );
        $stripe   = $settings['bar_stripe'] === 'yes';
        $stripe_anim = $settings['bar_stripe_animated'] === 'yes';

        $data_attrs = sprintf(
            'data-percentage="%s" data-animate="%s" data-duration="%d"',
            esc_attr( $percentage ),
            $animate ? 'true' : 'false',
            $duration
        );

        // AJAX refresh data (only for sheets sources).
        if ( $source !== 'manual' ) {
            $refresh = absint( $settings['refresh_interval'] ?? 30 );
            $data_attrs .= sprintf(
                ' data-refresh="%d" data-spreadsheet="%s" data-sheet="%s" data-source="%s" data-goal="%d" data-cache="%d" data-format="%s" data-suffix="%s" data-remaining-text="%s"',
                $refresh,
                esc_attr( $settings['spreadsheet_id'] ?? '' ),
                esc_attr( $settings['sheet_name'] ?? '' ),
                esc_attr( $source ),
                $goal,
                absint( $settings['cache_minutes'] ?? 5 ),
                esc_attr( $settings['display_format'] ),
                esc_attr( $settings['suffix_text'] ?? '' ),
                esc_attr( $settings['remaining_text'] ?? 'remaining' )
            );

            if ( $source === 'sheets_cell' ) {
                $data_attrs .= sprintf( ' data-cell="%s"', esc_attr( $settings['cell_reference'] ?? 'B2' ) );
            } else {
                $data_attrs .= sprintf( ' data-range="%s"', esc_attr( $settings['row_range'] ?? 'A2:A' ) );
            }
        }

        $bar_classes = 'dpb-bar-fill';
        if ( $stripe ) {
            $bar_classes .= ' dpb-striped';
        }
        if ( $stripe && $stripe_anim ) {
            $bar_classes .= ' dpb-striped-animated';
        }

        ?>
        <div class="dpb-progress-bar-widget" <?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all values escaped in sprintf above ?>>
            <?php if ( ! empty( $settings['title'] ) || $display_text !== '' ) : ?>
                <div class="dpb-header">
                    <?php if ( ! empty( $settings['title'] ) ) : ?>
                        <span class="dpb-title"><?php echo esc_html( $settings['title'] ); ?></span>
                    <?php endif; ?>
                    <?php if ( $display_text !== '' ) : ?>
                        <span class="dpb-value"><?php echo esc_html( $display_text ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="dpb-bar-track">
                <div class="<?php echo esc_attr( $bar_classes ); ?>"
                     style="width: <?php echo $animate ? '0%' : esc_attr( $percentage . '%' ); ?>;">
                </div>
            </div>
        </div>
        <?php
    }

    protected function content_template(): void {
        ?>
        <#
        var percentage = 0;
        var current = 0;
        var goal = Math.max(1, parseInt(settings.goal) || 100);

        if (settings.data_source === 'manual') {
            current = parseFloat(settings.manual_value) || 0;
            percentage = Math.min(Math.round((current / goal) * 1000) / 10, 100);
        } else {
            percentage = 50;
            current = Math.round(goal * 0.5);
        }

        var displayText = '';
        switch (settings.display_format) {
            case 'percentage': displayText = percentage + '%'; break;
            case 'fraction':   displayText = Math.floor(current) + ' / ' + goal; break;
            case 'current':    displayText = Math.floor(current) + (settings.suffix_text ? ' ' + settings.suffix_text : ''); break;
            case 'remaining':  displayText = Math.max(0, goal - Math.floor(current)) + ' ' + (settings.remaining_text || 'remaining'); break;
            case 'none':       displayText = ''; break;
        }

        var barClasses = 'dpb-bar-fill';
        if (settings.bar_stripe === 'yes') barClasses += ' dpb-striped';
        if (settings.bar_stripe === 'yes' && settings.bar_stripe_animated === 'yes') barClasses += ' dpb-striped-animated';
        #>

        <div class="dpb-progress-bar-widget">
            <# if (settings.title || displayText) { #>
                <div class="dpb-header">
                    <# if (settings.title) { #>
                        <span class="dpb-title">{{{ settings.title }}}</span>
                    <# } #>
                    <# if (displayText) { #>
                        <span class="dpb-value">{{{ displayText }}}</span>
                    <# } #>
                </div>
            <# } #>
            <div class="dpb-bar-track">
                <div class="{{ barClasses }}" style="width: {{ percentage }}%;"></div>
            </div>
        </div>
        <?php
    }
}
