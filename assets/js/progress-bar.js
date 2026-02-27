/**
 * Dynamic Progress Bar for Elementor — Frontend JS.
 *
 * Handles:
 * 1. Scroll-triggered fill animation (IntersectionObserver)
 * 2. AJAX auto-refresh for Google Sheets data
 */
(function ($) {
    'use strict';

    /**
     * Format the display value based on the chosen format.
     */
    function formatValue(data, format, suffix, remainingText) {
        var pct = data.percentage;
        var cur = Math.floor(data.current);
        var goal = data.goal;

        switch (format) {
            case 'percentage':
                return pct + '%';
            case 'fraction':
                return cur + ' / ' + goal;
            case 'current':
                return cur + (suffix ? ' ' + suffix : '');
            case 'remaining':
                return Math.max(0, goal - cur) + ' ' + (remainingText || 'remaining');
            case 'none':
                return '';
            default:
                return pct + '%';
        }
    }

    /**
     * Animate the bar fill width.
     */
    function animateBar($widget, percentage, duration) {
        var $fill = $widget.find('.dpb-bar-fill');

        $fill.addClass('dpb-animating');
        $fill.css('transition-duration', duration + 'ms');
        $fill.css('width', percentage + '%');

        // Add complete class if 100%.
        if (percentage >= 100) {
            setTimeout(function () {
                $fill.addClass('dpb-complete');
            }, duration);
        } else {
            $fill.removeClass('dpb-complete');
        }
    }

    /**
     * Update the display value text.
     */
    function updateValue($widget, text) {
        var $value = $widget.find('.dpb-value');
        if ($value.length) {
            $value.text(text);
        }
    }

    /**
     * Initialize a single progress bar widget.
     */
    function initWidget($widget) {
        var percentage = parseFloat($widget.data('percentage')) || 0;
        var shouldAnimate = $widget.data('animate') === true || $widget.data('animate') === 'true';
        var duration = parseInt($widget.data('duration'), 10) || 1000;
        var refreshInterval = parseInt($widget.data('refresh'), 10) || 0;

        // ── Scroll Animation ───────────────────────────────────────────────
        if (shouldAnimate && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            animateBar($widget, percentage, duration);
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.2 }
            );

            observer.observe($widget[0]);
        } else {
            // No animation or no IntersectionObserver support — set immediately.
            $widget.find('.dpb-bar-fill').css('width', percentage + '%');
        }

        // ── AJAX Auto-Refresh ──────────────────────────────────────────────
        if (refreshInterval >= 10 && typeof dpbAjax !== 'undefined') {
            var source = $widget.data('source');
            var spreadsheetId = $widget.data('spreadsheet');
            var sheetName = $widget.data('sheet');
            var goal = parseInt($widget.data('goal'), 10) || 100;
            var cacheMinutes = parseInt($widget.data('cache'), 10) || 5;
            var format = $widget.data('format') || 'percentage';
            var suffix = $widget.data('suffix') || '';
            var remainingText = $widget.data('remaining-text') || 'remaining';

            var ajaxData = {
                action: 'dpb_refresh_progress',
                nonce: dpbAjax.nonce,
                spreadsheet_id: spreadsheetId,
                sheet_name: sheetName,
                source: source,
                goal: goal,
                cache_minutes: cacheMinutes,
            };

            if (source === 'sheets_cell') {
                ajaxData.cell = $widget.data('cell') || '';
            }
            if (source === 'sheets_rows') {
                ajaxData.range = $widget.data('range') || '';
            }

            setInterval(function () {
                $.post(dpbAjax.ajaxurl, ajaxData)
                    .done(function (response) {
                        if (response.success && response.data) {
                            var newPct = parseFloat(response.data.percentage);

                            // Guard: ignore invalid or NaN responses.
                            if (isNaN(newPct) || newPct < 0) {
                                return;
                            }

                            // Only update if value actually changed.
                            if (newPct !== percentage) {
                                percentage = newPct;
                                animateBar($widget, percentage, 600);

                                var text = formatValue(response.data, format, suffix, remainingText);
                                updateValue($widget, text);

                                $widget.data('percentage', percentage);
                            }
                        }
                        // On error response: keep current value (don't reset to 0).
                    })
                    .fail(function () {
                        // Network/nonce error: silently keep current value.
                    });
            }, refreshInterval * 1000);
        }
    }

    // ── Elementor Frontend Handler ─────────────────────────────────────────────

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction(
            'frontend/element_ready/dpb_progress_bar.default',
            function ($scope) {
                var $widget = $scope.find('.dpb-progress-bar-widget');
                if ($widget.length) {
                    initWidget($widget);
                }
            }
        );
    });
})(jQuery);
