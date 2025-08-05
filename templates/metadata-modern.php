<?php
/**
 * Template: Enhanced Modern Metadata Display
 *
 * Visually rich, card-based layout with background images, interactive elements,
 * comprehensive data visualization, and modern design aesthetics.
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

// Prepare display data
$display_title = $metadata['title'] ?? parse_url( $url ?? '', PHP_URL_HOST );
$display_description = $metadata['description'] ?? '';
$favicon = $metadata['favicon'] ?? '';

// Determine the best background image
$og = $metadata['og'] ?? [];
$twitter = $metadata['twitter'] ?? [];
$bg_image = $og['image'] ?? ( $twitter['image'] ?? '' );

$hero_style = ! empty( $bg_image ) ? 'style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.4)), url(' . esc_url( $bg_image ) . ');"' : '';
?>

<div class="hsz-metadata-display hsz-modern-layout">
    
    <!-- Hero Section with Background -->
    <div class="hsz-modern-hero" <?php echo $hero_style; ?>>
        <div class="hsz-hero-overlay">
            <div class="hsz-hero-content">
                
                <div class="hsz-site-branding">
                    <?php if ( ! empty( $favicon ) ): ?>
                        <div class="hsz-favicon-modern">
                            <img src="<?php echo esc_url( $favicon ); ?>" 
                                 alt="<?php esc_attr_e( 'Website favicon', 'hellaz-sitez-analyzer' ); ?>" 
                                 class="hsz-favicon-large" 
                                 width="48" height="48" 
                                 loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="hsz-site-info">
                        <?php if ( ! empty( $display_title ) ): ?>
                            <h2 class="hsz-modern-title">
                                <a href="<?php echo esc_url( $url ); ?>" 
                                   target="_blank" rel="noopener noreferrer" 
                                   class="hsz-hero-title-link">
                                    <?php echo esc_html( $display_title ); ?>
                                </a>
                            </h2>
                        <?php endif; ?>

                        <?php if ( ! empty( $display_description ) ): ?>
                            <p class="hsz-modern-description">
                                <?php echo esc_html( $display_description ); ?>
                            </p>
                        <?php endif; ?>

                        <div class="hsz-url-display">
                            <code class="hsz-modern-url"><?php echo esc_html( $url ); ?></code>
                        </div>
                    </div>
                </div>

                <!-- Hero Metrics Dashboard -->
                <div class="hsz-hero-metrics">
                    <div class="hsz-metrics-grid">
                        
                        <?php if ( isset( $metadata['metadata_quality']['grade'] ) && isset( $metadata['metadata_quality']['score'] ) ): ?>
                            <div class="hsz-metric-card hsz-seo-card">
                                <div class="hsz-metric-header">
                                    <span class="hsz-metric-icon">🎯</span>
                                    <span class="hsz-metric-title"><?php esc_html_e( 'SEO Quality', 'hellaz-sitez-analyzer' ); ?></span>
                                </div>
                                <div class="hsz-metric-value">
                                    <div class="hsz-grade-display hsz-grade-<?php echo esc_attr( strtolower( $metadata['metadata_quality']['grade'] ) ); ?>">
                                        <?php echo esc_html( $metadata['metadata_quality']['grade'] ); ?>
                                    </div>
                                    <div class="hsz-score-display">
                                        <?php echo esc_html( $metadata['metadata_quality']['score'] ); ?>/100
                                    </div>
                                </div>
                                <div class="hsz-metric-progress">
                                    <div class="hsz-progress-bar">
                                        <div class="hsz-progress-fill" style="width: <?php echo esc_attr( $metadata['metadata_quality']['score'] ); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $performance['overall_grade'] ) || ! empty( $metadata['performance']['overall_grade'] ) ): ?>
                            <?php 
                            $perf_grade = $performance['overall_grade'] ?? $metadata['performance']['overall_grade'];
                            $perf_score = $performance['overall_score'] ?? $metadata['performance']['overall_score'] ?? 0;
                            ?>
                            <div class="hsz-metric-card hsz-performance-card">
                                <div class="hsz-metric-header">
                                    <span class="hsz-metric-icon">⚡</span>
                                    <span class="hsz-metric-title"><?php esc_html_e( 'Performance', 'hellaz-sitez-analyzer' ); ?></span>
                                </div>
                                <div class="hsz-metric-value">
                                    <div class="hsz-grade-display hsz-grade-<?php echo esc_attr( strtolower( $perf_grade ) ); ?>">
                                        <?php echo esc_html( $perf_grade ); ?>
                                    </div>
                                    <div class="hsz-score-display">
                                        <?php echo esc_html( $perf_score ); ?>/100
                                    </div>
                                </div>
                                <div class="hsz-metric-progress">
                                    <div class="hsz-progress-bar">
                                        <div class="hsz-progress-fill" style="width: <?php echo esc_attr( $perf_score ); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $security['overall_grade'] ) || ! empty( $metadata['security']['overall_grade'] ) ): ?>
                            <?php 
                            $sec_grade = $security['overall_grade'] ?? $metadata['security']['overall_grade'];
                            $sec_score = $security['overall_score'] ?? $metadata['security']['overall_score'] ?? 0;
                            ?>
                            <div class="hsz-metric-card hsz-security-card">
                                <div class="hsz-metric-header">
                                    <span class="hsz-metric-icon">🔒</span>
                                    <span class="hsz-metric-title"><?php esc_html_e( 'Security', 'hellaz-sitez-analyzer' ); ?></span>
                                </div>
                                <div class="hsz-metric-value">
                                    <div class="hsz-grade-display hsz-grade-<?php echo esc_attr( strtolower( $sec_grade ) ); ?>">
                                        <?php echo esc_html( $sec_grade ); ?>
                                    </div>
                                    <div class="hsz-score-display">
                                        <?php echo esc_html( $sec_score ); ?>/100
                                    </div>
                                </div>
                                <div class="hsz-metric-progress">
                                    <div class="hsz-progress-bar">
                                        <div class="hsz-progress-fill" style="width: <?php echo esc_attr( $sec_score ); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Additional Quick Stats -->
                        <div class="hsz-metric-card hsz-stats-card">
                            <div class="hsz-metric-header">
                                <span class="hsz-metric-icon">📊</span>
                                <span class="hsz-metric-title"><?php esc_html_e( 'Quick Stats', 'hellaz-sitez-analyzer' ); ?></span>
                            </div>
                            <div class="hsz-quick-stats-modern">
                                <?php if ( ! empty( $social ) ): ?>
                                    <div class="hsz-quick-stat">
                                        <span class="hsz-stat-number"><?php echo esc_html( count( $social ) ); ?></span>
                                        <span class="hsz-stat-label"><?php esc_html_e( 'Social', 'hellaz-sitez-analyzer' ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $metadata['structured_data'] ) ): ?>
                                    <div class="hsz-quick-stat">
                                        <span class="hsz-stat-number"><?php echo esc_html( count( $metadata['structured_data'] ) ); ?></span>
                                        <span class="hsz-stat-label"><?php esc_html_e( 'Schema', 'hellaz-sitez-analyzer' ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $metadata['feeds'] ) ): ?>
                                    <div class="hsz-quick-stat">
                                        <span class="hsz-stat-number"><?php echo esc_html( count( $metadata['feeds'] ) ); ?></span>
                                        <span class="hsz-stat-label"><?php esc_html_e( 'Feeds', 'hellaz-sitez-analyzer' ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Analysis Cards Grid -->
    <div class="hsz-analysis-cards">
        
        <!-- SEO Quality Card (Always Visible) -->
        <?php if ( ! empty( $metadata['metadata_quality'] ) ): ?>
            <div class="hsz-analysis-card hsz-seo-quality-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/seo-quality-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Performance Analysis Card -->
        <?php if ( ! empty( $performance ) || ! empty( $metadata['performance'] ) || ! empty( $metadata['performance_hints'] ) ): ?>
            <div class="hsz-analysis-card hsz-performance-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/performance-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Security Analysis Card -->
        <?php if ( ! empty( $security ) || ! empty( $metadata['security'] ) || ! empty( $api_data['virustotal'] ) || ! empty( $api_data['urlscan'] ) ): ?>
            <div class="hsz-analysis-card hsz-security-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/security-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Analysis Card -->
        <?php if ( ! empty( $metadata['content_analysis'] ) ): ?>
            <div class="hsz-analysis-card hsz-content-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/content-analysis-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Structured Data Card -->
        <?php if ( ! empty( $metadata['structured_data'] ) ): ?>
            <div class="hsz-analysis-card hsz-structured-data-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/structured-data-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Technology Stack Card -->
        <?php if ( ! empty( $api_data['builtwith'] ) || ! empty( $technology ) ): ?>
            <div class="hsz-analysis-card hsz-technology-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/technology-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Media Card -->
        <?php if ( ! empty( $social ) && is_array( $social ) ): ?>
            <div class="hsz-analysis-card hsz-social-media-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/social-media-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Additional Metadata Card -->
        <?php 
        $has_additional_metadata = ! empty( $metadata['feeds'] ) || 
                                  ! empty( $metadata['languages'] ) || 
                                  ! empty( $metadata['performance_hints'] ) || 
                                  ! empty( $metadata['images'] ) || 
                                  ! empty( $metadata['links'] ) || 
                                  ! empty( $metadata['meta_tags'] );
        ?>
        <?php if ( $has_additional_metadata ): ?>
            <div class="hsz-analysis-card hsz-additional-metadata-card">
                <div class="hsz-card-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/additional-metadata-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Modern Footer with Analysis Summary -->
    <div class="hsz-modern-footer">
        <div class="hsz-footer-summary">
            <div class="hsz-summary-stats">
                <h4><?php esc_html_e( 'Analysis Summary', 'hellaz-sitez-analyzer' ); ?></h4>
                <div class="hsz-summary-grid">
                    
                    <div class="hsz-summary-item">
                        <span class="hsz-summary-label"><?php esc_html_e( 'Analysis Date:', 'hellaz-sitez-analyzer' ); ?></span>
                        <span class="hsz-summary-value">
                            <?php echo esc_html( current_time( get_option( 'date_format' ) ) ); ?>
                        </span>
                    </div>
                    
                    <div class="hsz-summary-item">
                        <span class="hsz-summary-label"><?php esc_html_e( 'Analysis Time:', 'hellaz-sitez-analyzer' ); ?></span>
                        <span class="hsz-summary-value">
                            <?php echo esc_html( current_time( get_option( 'time_format' ) ) ); ?>
                        </span>
                    </div>
                    
                    <div class="hsz-summary-item">
                        <span class="hsz-summary-label"><?php esc_html_e( 'Sections Analyzed:', 'hellaz-sitez-analyzer' ); ?></span>
                        <span class="hsz-summary-value">
                            <?php
                            $sections_count = 0;
                            if ( ! empty( $metadata['metadata_quality'] ) ) $sections_count++;
                            if ( ! empty( $performance ) || ! empty( $metadata['performance'] ) ) $sections_count++;
                            if ( ! empty( $security ) || ! empty( $metadata['security'] ) ) $sections_count++;
                            if ( ! empty( $metadata['content_analysis'] ) ) $sections_count++;
                            if ( ! empty( $metadata['structured_data'] ) ) $sections_count++;
                            if ( ! empty( $api_data['builtwith'] ) ) $sections_count++;
                            if ( ! empty( $social ) ) $sections_count++;
                            if ( $has_additional_metadata ) $sections_count++;
                            echo esc_html( $sections_count );
                            ?>
                        </span>
                    </div>
                    
                </div>
            </div>
        </div>

        <div class="hsz-footer-branding">
            <div class="hsz-powered-by-modern">
                <span class="hsz-brand-icon">🚀</span>
                <span class="hsz-brand-text"><?php esc_html_e( 'Powered by HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ); ?></span>
            </div>
        </div>
    </div>

</div>

<script>
// Modern template interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling for internal links
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Add intersection observer for card animations
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('hsz-card-visible');
                }
            });
        }, { threshold: 0.1 });

        const cards = document.querySelectorAll('.hsz-analysis-card');
        cards.forEach(function(card) {
            observer.observe(card);
        });
    }

    // Add hover effects for metric cards
    const metricCards = document.querySelectorAll('.hsz-metric-card');
    metricCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.classList.add('hsz-metric-hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('hsz-metric-hover');
        });
    });
});
</script>
