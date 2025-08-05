<?php
/**
 * Template Partial: Performance Analysis Section
 *
 * Displays comprehensive performance analysis including Core Web Vitals,
 * PageSpeed scores, resource analysis, and performance hints.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $performance Performance analysis data
 * @var array $metadata Full metadata array (for performance_hints)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get performance data from either direct variable or metadata array
$perf_data = $performance ?? $metadata['performance'] ?? [];
$perf_hints = $metadata['performance_hints'] ?? [];

// Skip section if no performance data
if ( empty( $perf_data ) && empty( $perf_hints ) ) {
    return;
}
?>

<div class="hsz-performance-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-speed"></span>
        <?php esc_html_e( 'Performance Analysis', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <?php if ( ! empty( $perf_data ) ): ?>
        <!-- Overall Performance Grade -->
        <?php if ( isset( $perf_data['overall_grade'] ) ): ?>
            <div class="hsz-performance-grade">
                <div class="hsz-grade-display">
                    <span class="hsz-grade hsz-grade-<?php echo esc_attr( strtolower( $perf_data['overall_grade'] ) ); ?>">
                        <?php echo esc_html( $perf_data['overall_grade'] ); ?>
                    </span>
                    <span class="hsz-grade-label">
                        <?php esc_html_e( 'Performance Grade', 'hellaz-sitez-analyzer' ); ?>
                    </span>
                </div>
                <?php if ( isset( $perf_data['overall_score'] ) ): ?>
                    <div class="hsz-score-bar">
                        <div class="hsz-score-fill" style="width: <?php echo esc_attr( $perf_data['overall_score'] ); ?>%"></div>
                        <span class="hsz-score-text"><?php echo esc_html( $perf_data['overall_score'] ); ?>/100</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Core Web Vitals -->
        <?php if ( ! empty( $perf_data['core_web_vitals'] ) ): ?>
            <div class="hsz-core-vitals">
                <h5><?php esc_html_e( 'Core Web Vitals', 'hellaz-sitez-analyzer' ); ?></h5>
                <div class="hsz-vitals-grid">
                    
                    <?php if ( isset( $perf_data['core_web_vitals']['lcp'] ) ): ?>
                        <div class="hsz-vital-item">
                            <div class="hsz-vital-value hsz-vital-<?php echo esc_attr( $perf_data['core_web_vitals']['lcp']['status'] ?? 'unknown' ); ?>">
                                <?php echo esc_html( $perf_data['core_web_vitals']['lcp']['value'] ?? 'N/A' ); ?>
                            </div>
                            <div class="hsz-vital-label">
                                <?php esc_html_e( 'LCP', 'hellaz-sitez-analyzer' ); ?>
                                <small><?php esc_html_e( 'Largest Contentful Paint', 'hellaz-sitez-analyzer' ); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['core_web_vitals']['fid'] ) ): ?>
                        <div class="hsz-vital-item">
                            <div class="hsz-vital-value hsz-vital-<?php echo esc_attr( $perf_data['core_web_vitals']['fid']['status'] ?? 'unknown' ); ?>">
                                <?php echo esc_html( $perf_data['core_web_vitals']['fid']['value'] ?? 'N/A' ); ?>
                            </div>
                            <div class="hsz-vital-label">
                                <?php esc_html_e( 'FID', 'hellaz-sitez-analyzer' ); ?>
                                <small><?php esc_html_e( 'First Input Delay', 'hellaz-sitez-analyzer' ); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['core_web_vitals']['cls'] ) ): ?>
                        <div class="hsz-vital-item">
                            <div class="hsz-vital-value hsz-vital-<?php echo esc_attr( $perf_data['core_web_vitals']['cls']['status'] ?? 'unknown' ); ?>">
                                <?php echo esc_html( $perf_data['core_web_vitals']['cls']['value'] ?? 'N/A' ); ?>
                            </div>
                            <div class="hsz-vital-label">
                                <?php esc_html_e( 'CLS', 'hellaz-sitez-analyzer' ); ?>
                                <small><?php esc_html_e( 'Cumulative Layout Shift', 'hellaz-sitez-analyzer' ); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <!-- PageSpeed Scores -->
        <?php if ( ! empty( $perf_data['pagespeed'] ) ): ?>
            <div class="hsz-pagespeed-scores">
                <h5><?php esc_html_e( 'PageSpeed Insights', 'hellaz-sitez-analyzer' ); ?></h5>
                <div class="hsz-pagespeed-grid">
                    
                    <?php if ( isset( $perf_data['pagespeed']['mobile'] ) ): ?>
                        <div class="hsz-pagespeed-item">
                            <div class="hsz-device-icon hsz-mobile-icon"></div>
                            <div class="hsz-pagespeed-score">
                                <span class="hsz-score"><?php echo esc_html( $perf_data['pagespeed']['mobile']['score'] ?? 'N/A' ); ?></span>
                                <span class="hsz-device-label"><?php esc_html_e( 'Mobile', 'hellaz-sitez-analyzer' ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['pagespeed']['desktop'] ) ): ?>
                        <div class="hsz-pagespeed-item">
                            <div class="hsz-device-icon hsz-desktop-icon"></div>
                            <div class="hsz-pagespeed-score">
                                <span class="hsz-score"><?php echo esc_html( $perf_data['pagespeed']['desktop']['score'] ?? 'N/A' ); ?></span>
                                <span class="hsz-device-label"><?php esc_html_e( 'Desktop', 'hellaz-sitez-analyzer' ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <!-- Speed Metrics -->
        <?php if ( ! empty( $perf_data['metrics'] ) ): ?>
            <div class="hsz-speed-metrics">
                <h5><?php esc_html_e( 'Speed Metrics', 'hellaz-sitez-analyzer' ); ?></h5>
                <div class="hsz-metrics-list">
                    
                    <?php if ( isset( $perf_data['metrics']['load_time'] ) ): ?>
                        <div class="hsz-metric-item">
                            <span class="hsz-metric-label"><?php esc_html_e( 'Load Time:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-metric-value"><?php echo esc_html( $perf_data['metrics']['load_time'] ); ?>s</span>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['metrics']['ttfb'] ) ): ?>
                        <div class="hsz-metric-item">
                            <span class="hsz-metric-label"><?php esc_html_e( 'TTFB:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-metric-value"><?php echo esc_html( $perf_data['metrics']['ttfb'] ); ?>ms</span>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['metrics']['page_size'] ) ): ?>
                        <div class="hsz-metric-item">
                            <span class="hsz-metric-label"><?php esc_html_e( 'Page Size:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-metric-value"><?php echo esc_html( size_format( $perf_data['metrics']['page_size'] ) ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['metrics']['requests'] ) ): ?>
                        <div class="hsz-metric-item">
                            <span class="hsz-metric-label"><?php esc_html_e( 'Requests:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-metric-value"><?php echo esc_html( $perf_data['metrics']['requests'] ); ?></span>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <!-- Resource Analysis -->
        <?php if ( ! empty( $perf_data['resources'] ) ): ?>
            <div class="hsz-resource-analysis">
                <h5><?php esc_html_e( 'Resource Analysis', 'hellaz-sitez-analyzer' ); ?></h5>
                <div class="hsz-resources-grid">
                    
                    <?php if ( isset( $perf_data['resources']['images'] ) ): ?>
                        <div class="hsz-resource-item">
                            <span class="hsz-resource-type"><?php esc_html_e( 'Images:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-resource-count"><?php echo esc_html( $perf_data['resources']['images']['count'] ?? 0 ); ?></span>
                            <?php if ( isset( $perf_data['resources']['images']['size'] ) ): ?>
                                <span class="hsz-resource-size">(<?php echo esc_html( size_format( $perf_data['resources']['images']['size'] ) ); ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['resources']['scripts'] ) ): ?>
                        <div class="hsz-resource-item">
                            <span class="hsz-resource-type"><?php esc_html_e( 'Scripts:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-resource-count"><?php echo esc_html( $perf_data['resources']['scripts']['count'] ?? 0 ); ?></span>
                            <?php if ( isset( $perf_data['resources']['scripts']['size'] ) ): ?>
                                <span class="hsz-resource-size">(<?php echo esc_html( size_format( $perf_data['resources']['scripts']['size'] ) ); ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( isset( $perf_data['resources']['stylesheets'] ) ): ?>
                        <div class="hsz-resource-item">
                            <span class="hsz-resource-type"><?php esc_html_e( 'Stylesheets:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-resource-count"><?php echo esc_html( $perf_data['resources']['stylesheets']['count'] ?? 0 ); ?></span>
                            <?php if ( isset( $perf_data['resources']['stylesheets']['size'] ) ): ?>
                                <span class="hsz-resource-size">(<?php echo esc_html( size_format( $perf_data['resources']['stylesheets']['size'] ) ); ?>)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Performance Hints -->
    <?php if ( ! empty( $perf_hints ) ): ?>
        <div class="hsz-performance-hints">
            <h5><?php esc_html_e( 'Performance Optimizations', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-hints-summary">
                <?php if ( isset( $perf_hints['async_scripts'] ) && $perf_hints['async_scripts'] > 0 ): ?>
                    <div class="hsz-hint-item hsz-hint-good">
                        <span class="hsz-hint-icon">✓</span>
                        <span class="hsz-hint-text">
                            <?php printf( 
                                esc_html__( '%d async scripts found', 'hellaz-sitez-analyzer' ), 
                                intval( $perf_hints['async_scripts'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( isset( $perf_hints['defer_scripts'] ) && $perf_hints['defer_scripts'] > 0 ): ?>
                    <div class="hsz-hint-item hsz-hint-good">
                        <span class="hsz-hint-icon">✓</span>
                        <span class="hsz-hint-text">
                            <?php printf( 
                                esc_html__( '%d deferred scripts found', 'hellaz-sitez-analyzer' ), 
                                intval( $perf_hints['defer_scripts'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $perf_hints['preload_links'] ) ): ?>
                    <div class="hsz-hint-item hsz-hint-good">
                        <span class="hsz-hint-icon">✓</span>
                        <span class="hsz-hint-text">
                            <?php printf( 
                                esc_html__( '%d preload links found', 'hellaz-sitez-analyzer' ), 
                                count( $perf_hints['preload_links'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $perf_hints['dns_prefetch'] ) ): ?>
                    <div class="hsz-hint-item hsz-hint-good">
                        <span class="hsz-hint-icon">✓</span>
                        <span class="hsz-hint-text">
                            <?php printf( 
                                esc_html__( '%d DNS prefetch hints found', 'hellaz-sitez-analyzer' ), 
                                count( $perf_hints['dns_prefetch'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $perf_hints['preconnect'] ) ): ?>
                    <div class="hsz-hint-item hsz-hint-good">
                        <span class="hsz-hint-icon">✓</span>
                        <span class="hsz-hint-text">
                            <?php printf( 
                                esc_html__( '%d preconnect hints found', 'hellaz-sitez-analyzer' ), 
                                count( $perf_hints['preconnect'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Detailed hints in expandable section -->
            <?php if ( ! empty( $perf_hints['preload_links'] ) || ! empty( $perf_hints['dns_prefetch'] ) || ! empty( $perf_hints['preconnect'] ) ): ?>
                <details class="hsz-hints-details">
                    <summary><?php esc_html_e( 'View Performance Hints Details', 'hellaz-sitez-analyzer' ); ?></summary>
                    
                    <?php if ( ! empty( $perf_hints['preload_links'] ) ): ?>
                        <div class="hsz-hints-section">
                            <h6><?php esc_html_e( 'Preload Resources', 'hellaz-sitez-analyzer' ); ?></h6>
                            <ul class="hsz-hints-list">
                                <?php foreach ( array_slice( $perf_hints['preload_links'], 0, 5 ) as $preload ): ?>
                                    <li>
                                        <code><?php echo esc_html( $preload['href'] ?? '' ); ?></code>
                                        <?php if ( ! empty( $preload['as'] ) ): ?>
                                            <span class="hsz-hint-type">(<?php echo esc_html( $preload['as'] ); ?>)</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                                <?php if ( count( $perf_hints['preload_links'] ) > 5 ): ?>
                                    <li class="hsz-more-items">
                                        <?php printf( 
                                            esc_html__( '... and %d more', 'hellaz-sitez-analyzer' ), 
                                            count( $perf_hints['preload_links'] ) - 5 
                                        ); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $perf_hints['dns_prefetch'] ) ): ?>
                        <div class="hsz-hints-section">
                            <h6><?php esc_html_e( 'DNS Prefetch', 'hellaz-sitez-analyzer' ); ?></h6>
                            <ul class="hsz-hints-list">
                                <?php foreach ( array_slice( $perf_hints['dns_prefetch'], 0, 5 ) as $dns ): ?>
                                    <li><code><?php echo esc_html( $dns ); ?></code></li>
                                <?php endforeach; ?>
                                <?php if ( count( $perf_hints['dns_prefetch'] ) > 5 ): ?>
                                    <li class="hsz-more-items">
                                        <?php printf( 
                                            esc_html__( '... and %d more', 'hellaz-sitez-analyzer' ), 
                                            count( $perf_hints['dns_prefetch'] ) - 5 
                                        ); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Performance Recommendations -->
    <?php if ( ! empty( $perf_data['recommendations'] ) ): ?>
        <div class="hsz-performance-recommendations">
            <h5><?php esc_html_e( 'Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
            <ul class="hsz-recommendations-list">
                <?php foreach ( array_slice( $perf_data['recommendations'], 0, 5 ) as $recommendation ): ?>
                    <li class="hsz-recommendation-item">
                        <span class="hsz-recommendation-icon">💡</span>
                        <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>
