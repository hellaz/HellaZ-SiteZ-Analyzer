<?php
/**
 * Template: Enhanced Compact Metadata Display
 *
 * Streamlined view showing essential information with key metrics
 * and most important analysis results in a compact format.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var string $url The URL being analyzed
 * @var array $metadata Complete metadata analysis results
 * @var array $social Social media profiles array
 * @var array $performance Performance analysis data (optional)
 * @var array $security Security analysis data (optional)
 * @var array $api_data API data from third-party services (optional)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prepare essential data
$display_title = $metadata['title'] ?? parse_url( $url ?? '', PHP_URL_HOST );
$display_description = $metadata['description'] ?? '';
$favicon = $metadata['favicon'] ?? '';
?>

<div class="hsz-metadata-display hsz-compact-layout">
    
    <!-- Compact Header -->
    <div class="hsz-compact-header">
        <div class="hsz-site-info">
            <?php if ( ! empty( $favicon ) ): ?>
                <img src="<?php echo esc_url( $favicon ); ?>" 
                     alt="<?php esc_attr_e( 'Website favicon', 'hellaz-sitez-analyzer' ); ?>" 
                     class="hsz-favicon-small" 
                     width="20" height="20" 
                     loading="lazy">
            <?php endif; ?>

            <?php if ( ! empty( $display_title ) ): ?>
                <h4 class="hsz-compact-title">
                    <a href="<?php echo esc_url( $url ); ?>" 
                       target="_blank" rel="noopener noreferrer" 
                       class="hsz-title-link">
                        <?php echo esc_html( wp_trim_words( $display_title, 8 ) ); ?>
                    </a>
                </h4>
            <?php endif; ?>
        </div>

        <!-- Key Metrics -->
        <div class="hsz-key-metrics">
            <?php if ( isset( $metadata['metadata_quality']['grade'] ) ): ?>
                <div class="hsz-metric hsz-seo-metric">
                    <span class="hsz-metric-label"><?php esc_html_e( 'SEO:', 'hellaz-sitez-analyzer' ); ?></span>
                    <span class="hsz-metric-value hsz-grade-<?php echo esc_attr( strtolower( $metadata['metadata_quality']['grade'] ) ); ?>">
                        <?php echo esc_html( $metadata['metadata_quality']['grade'] ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $performance['overall_grade'] ) || ! empty( $metadata['performance']['overall_grade'] ) ): ?>
                <?php $perf_grade = $performance['overall_grade'] ?? $metadata['performance']['overall_grade']; ?>
                <div class="hsz-metric hsz-performance-metric">
                    <span class="hsz-metric-label"><?php esc_html_e( 'Performance:', 'hellaz-sitez-analyzer' ); ?></span>
                    <span class="hsz-metric-value hsz-grade-<?php echo esc_attr( strtolower( $perf_grade ) ); ?>">
                        <?php echo esc_html( $perf_grade ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $security['overall_grade'] ) || ! empty( $metadata['security']['overall_grade'] ) ): ?>
                <?php $sec_grade = $security['overall_grade'] ?? $metadata['security']['overall_grade']; ?>
                <div class="hsz-metric hsz-security-metric">
                    <span class="hsz-metric-label"><?php esc_html_e( 'Security:', 'hellaz-sitez-analyzer' ); ?></span>
                    <span class="hsz-metric-value hsz-grade-<?php echo esc_attr( strtolower( $sec_grade ) ); ?>">
                        <?php echo esc_html( $sec_grade ); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Essential Information -->
    <div class="hsz-compact-content">
        
        <?php if ( ! empty( $display_description ) ): ?>
            <div class="hsz-compact-section">
                <p class="hsz-compact-description">
                    <?php echo esc_html( wp_trim_words( $display_description, 25 ) ); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Critical Issues (if any) -->
        <?php if ( ! empty( $metadata['metadata_quality']['issues'] ) ): ?>
            <?php $critical_issues = array_slice( $metadata['metadata_quality']['issues'], 0, 3 ); ?>
            <div class="hsz-compact-section hsz-issues-section">
                <h5 class="hsz-compact-heading">
                    <span class="hsz-warning-icon">⚠️</span>
                    <?php esc_html_e( 'Issues Found', 'hellaz-sitez-analyzer' ); ?>
                </h5>
                <ul class="hsz-compact-issues">
                    <?php foreach ( $critical_issues as $issue ): ?>
                        <li class="hsz-compact-issue"><?php echo esc_html( $issue ); ?></li>
                    <?php endforeach; ?>
                    <?php if ( count( $metadata['metadata_quality']['issues'] ) > 3 ): ?>
                        <li class="hsz-more-issues">
                            <?php printf( 
                                esc_html__( '... and %d more issues', 'hellaz-sitez-analyzer' ), 
                                count( $metadata['metadata_quality']['issues'] ) - 3 
                            ); ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Key Statistics -->
        <div class="hsz-compact-section hsz-stats-section">
            <div class="hsz-compact-stats">
                
                <?php if ( ! empty( $social ) ): ?>
                    <div class="hsz-compact-stat">
                        <span class="hsz-stat-icon">📱</span>
                        <span class="hsz-stat-text">
                            <?php printf( 
                                esc_html( _n( '%d Social Profile', '%d Social Profiles', count( $social ), 'hellaz-sitez-analyzer' ) ), 
                                count( $social ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $metadata['structured_data'] ) ): ?>
                    <div class="hsz-compact-stat">
                        <span class="hsz-stat-icon">📊</span>
                        <span class="hsz-stat-text">
                            <?php printf( 
                                esc_html( _n( '%d Schema Item', '%d Schema Items', count( $metadata['structured_data'] ), 'hellaz-sitez-analyzer' ) ), 
                                count( $metadata['structured_data'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $metadata['content_analysis']['word_count'] ) ): ?>
                    <div class="hsz-compact-stat">
                        <span class="hsz-stat-icon">📝</span>
                        <span class="hsz-stat-text">
                            <?php printf( 
                                esc_html__( '%s Words', 'hellaz-sitez-analyzer' ), 
                                number_format( $metadata['content_analysis']['word_count'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $metadata['feeds'] ) ): ?>
                    <div class="hsz-compact-stat">
                        <span class="hsz-stat-icon">📡</span>
                        <span class="hsz-stat-text">
                            <?php printf( 
                                esc_html( _n( '%d Feed', '%d Feeds', count( $metadata['feeds'] ), 'hellaz-sitez-analyzer' ) ), 
                                count( $metadata['feeds'] ) 
                            ); ?>
                        </span>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Top Social Media Platforms (if available) -->
        <?php if ( ! empty( $social ) ): ?>
            <div class="hsz-compact-section hsz-social-compact">
                <h5 class="hsz-compact-heading">
                    <span class="hsz-social-icon">🌐</span>
                    <?php esc_html_e( 'Social Presence', 'hellaz-sitez-analyzer' ); ?>
                </h5>
                <div class="hsz-social-compact-list">
                    <?php 
                    // Include just the essential social media functionality
                    $social_icons = [
                        'facebook.com' => 'Facebook',
                        'twitter.com' => 'Twitter', 
                        'x.com' => 'X',
                        'linkedin.com' => 'LinkedIn',
                        'instagram.com' => 'Instagram',
                        'youtube.com' => 'YouTube',
                        'github.com' => 'GitHub'
                    ];
                    
                    $displayed_count = 0;
                    foreach ( array_slice( $social, 0, 6 ) as $social_url ):
                        $host = wp_parse_url( $social_url, PHP_URL_HOST );
                        if ( $host ) {
                            $normalized_host = preg_replace( '/^www\./i', '', $host );
                            $platform_name = $social_icons[ $normalized_host ] ?? ucfirst( str_replace( '.com', '', $normalized_host ) );
                            $displayed_count++;
                    ?>
                        <a href="<?php echo esc_url( $social_url ); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           class="hsz-social-compact-link"
                           title="<?php echo esc_attr( sprintf( __( 'Visit %s profile', 'hellaz-sitez-analyzer' ), $platform_name ) ); ?>">
                            <?php echo esc_html( $platform_name ); ?>
                        </a>
                    <?php 
                        }
                    endforeach; 
                    
                    if ( count( $social ) > 6 ): ?>
                        <span class="hsz-more-social">
                            <?php printf( esc_html__( '+%d more', 'hellaz-sitez-analyzer' ), count( $social ) - 6 ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Recommendations -->
        <?php if ( ! empty( $metadata['metadata_quality']['recommendations'] ) ): ?>
            <?php $top_recommendations = array_slice( $metadata['metadata_quality']['recommendations'], 0, 2 ); ?>
            <div class="hsz-compact-section hsz-recommendations-compact">
                <h5 class="hsz-compact-heading">
                    <span class="hsz-recommendations-icon">💡</span>
                    <?php esc_html_e( 'Top Recommendations', 'hellaz-sitez-analyzer' ); ?>
                </h5>
                <ul class="hsz-compact-recommendations">
                    <?php foreach ( $top_recommendations as $recommendation ): ?>
                        <li class="hsz-compact-recommendation">
                            <?php echo esc_html( $recommendation ); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

    </div>

    <!-- Compact Footer -->
    <div class="hsz-compact-footer">
        <small class="hsz-analyzed-timestamp">
            <?php printf( 
                esc_html__( 'Analyzed: %s', 'hellaz-sitez-analyzer' ), 
                current_time( get_option( 'time_format' ) ) 
            ); ?>
        </small>
        
        <?php if ( defined( 'HSZ_SHOW_FULL_ANALYSIS_LINK' ) && HSZ_SHOW_FULL_ANALYSIS_LINK ): ?>
            <a href="#" class="hsz-view-full-analysis" onclick="return false;">
                <?php esc_html_e( 'View Full Analysis', 'hellaz-sitez-analyzer' ); ?>
            </a>
        <?php endif; ?>
    </div>

</div>
