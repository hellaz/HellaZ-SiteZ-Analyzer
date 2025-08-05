<?php
/**
 * Template: Enhanced Classic Metadata Display
 *
 * Comprehensive, well-organized view of all analyzed metadata including
 * performance, security, content analysis, structured data, SEO quality,
 * technology stack, and social media presence.
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

// Ensure we have the basic required data
$display_title = $metadata['title'] ?? parse_url( $url ?? '', PHP_URL_HOST );
$display_description = $metadata['description'] ?? '';
$favicon = $metadata['favicon'] ?? '';
?>

<div class="hsz-metadata-display hsz-classic-layout">
    
    <!-- Header Section with Core Metadata -->
    <div class="hsz-metadata-header">
        <div class="hsz-core-info">
            <?php if ( ! empty( $favicon ) ): ?>
                <div class="hsz-favicon-container">
                    <img src="<?php echo esc_url( $favicon ); ?>" 
                         alt="<?php esc_attr_e( 'Website favicon', 'hellaz-sitez-analyzer' ); ?>" 
                         class="hsz-favicon" 
                         width="32" height="32" 
                         loading="lazy">
                </div>
            <?php endif; ?>

            <div class="hsz-primary-metadata">
                <?php if ( ! empty( $display_title ) ): ?>
                    <h3 class="hsz-site-title">
                        <a href="<?php echo esc_url( $url ); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           class="hsz-title-link">
                            <?php echo esc_html( $display_title ); ?>
                            <span class="hsz-external-indicator">↗</span>
                        </a>
                    </h3>
                <?php endif; ?>

                <?php if ( ! empty( $display_description ) ): ?>
                    <p class="hsz-site-description">
                        <?php echo esc_html( $display_description ); ?>
                    </p>
                <?php endif; ?>

                <div class="hsz-analyzed-url">
                    <small class="hsz-url-label"><?php esc_html_e( 'Analyzed URL:', 'hellaz-sitez-analyzer' ); ?></small>
                    <code class="hsz-url-code"><?php echo esc_html( $url ); ?></code>
                </div>
            </div>
        </div>

        <!-- Quick Stats Overview -->
        <div class="hsz-quick-stats">
            <?php if ( isset( $metadata['metadata_quality']['grade'] ) ): ?>
                <div class="hsz-quick-stat hsz-seo-grade">
                    <div class="hsz-stat-label"><?php esc_html_e( 'SEO Grade', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-stat-value hsz-grade-<?php echo esc_attr( strtolower( $metadata['metadata_quality']['grade'] ) ); ?>">
                        <?php echo esc_html( $metadata['metadata_quality']['grade'] ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $social ) ): ?>
                <div class="hsz-quick-stat hsz-social-count">
                    <div class="hsz-stat-label"><?php esc_html_e( 'Social Profiles', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-stat-value"><?php echo esc_html( count( $social ) ); ?></div>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $metadata['structured_data'] ) ): ?>
                <div class="hsz-quick-stat hsz-schema-count">
                    <div class="hsz-stat-label"><?php esc_html_e( 'Schema Items', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-stat-value"><?php echo esc_html( count( $metadata['structured_data'] ) ); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Analysis Sections -->
    <div class="hsz-analysis-sections">
        
        <!-- SEO Quality Section (High Priority) -->
        <?php if ( ! empty( $metadata['metadata_quality'] ) ): ?>
            <div class="hsz-analysis-section hsz-priority-high">
                <?php include HSZ_PLUGIN_PATH . 'templates/partials/seo-quality-section.php'; ?>
            </div>
        <?php endif; ?>

        <!-- Performance Analysis Section -->
        <?php if ( ! empty( $performance ) || ! empty( $metadata['performance'] ) || ! empty( $metadata['performance_hints'] ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="performance">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="true" aria-controls="hsz-performance-content">
                        <span class="hsz-toggle-icon">▼</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Performance Analysis', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-performance-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/performance-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Security Analysis Section -->
        <?php if ( ! empty( $security ) || ! empty( $metadata['security'] ) || ! empty( $api_data['virustotal'] ) || ! empty( $api_data['urlscan'] ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="security">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="true" aria-controls="hsz-security-content">
                        <span class="hsz-toggle-icon">▼</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Security Analysis', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-security-content">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/security-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Analysis Section -->
        <?php if ( ! empty( $metadata['content_analysis'] ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="content">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="false" aria-controls="hsz-content-content">
                        <span class="hsz-toggle-icon">▶</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Content Analysis', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-content-content" style="display: none;">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/content-analysis-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Structured Data Section -->
        <?php if ( ! empty( $metadata['structured_data'] ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="structured-data">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="false" aria-controls="hsz-structured-content">
                        <span class="hsz-toggle-icon">▶</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Structured Data', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-structured-content" style="display: none;">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/structured-data-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Technology Stack Section -->
        <?php if ( ! empty( $api_data['builtwith'] ) || ! empty( $technology ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="technology">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="false" aria-controls="hsz-technology-content">
                        <span class="hsz-toggle-icon">▶</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Technology Stack', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-technology-content" style="display: none;">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/technology-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Media Section -->
        <?php if ( ! empty( $social ) && is_array( $social ) ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="social">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="false" aria-controls="hsz-social-content">
                        <span class="hsz-toggle-icon">▶</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Social Media Presence', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-social-content" style="display: none;">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/social-media-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Additional Metadata Section -->
        <?php 
        $has_additional_metadata = ! empty( $metadata['feeds'] ) || 
                                  ! empty( $metadata['languages'] ) || 
                                  ! empty( $metadata['performance_hints'] ) || 
                                  ! empty( $metadata['images'] ) || 
                                  ! empty( $metadata['links'] ) || 
                                  ! empty( $metadata['meta_tags'] );
        ?>
        <?php if ( $has_additional_metadata ): ?>
            <div class="hsz-analysis-section hsz-collapsible" data-section="additional">
                <div class="hsz-section-header">
                    <button type="button" class="hsz-section-toggle" aria-expanded="false" aria-controls="hsz-additional-content">
                        <span class="hsz-toggle-icon">▶</span>
                        <span class="hsz-toggle-text"><?php esc_html_e( 'Additional Metadata', 'hellaz-sitez-analyzer' ); ?></span>
                    </button>
                </div>
                <div class="hsz-section-content" id="hsz-additional-content" style="display: none;">
                    <?php include HSZ_PLUGIN_PATH . 'templates/partials/additional-metadata-section.php'; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Footer with Analysis Info -->
    <div class="hsz-metadata-footer">
        <div class="hsz-analysis-timestamp">
            <small>
                <?php 
                printf( 
                    esc_html__( 'Analysis completed on %s', 'hellaz-sitez-analyzer' ), 
                    current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) 
                ); 
                ?>
            </small>
        </div>
        <div class="hsz-powered-by">
            <small>
                <?php esc_html_e( 'Powered by HellaZ SiteZ Analyzer', 'hellaz-sitez-analyzer' ); ?>
            </small>
        </div>
    </div>

</div>

<script>
// Collapsible sections functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.hsz-section-toggle');
    
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const content = document.getElementById(this.getAttribute('aria-controls'));
            const icon = this.querySelector('.hsz-toggle-icon');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                content.style.display = 'none';
                this.setAttribute('aria-expanded', 'false');
                icon.textContent = '▶';
                this.closest('.hsz-analysis-section').classList.remove('hsz-expanded');
            } else {
                content.style.display = 'block';
                this.setAttribute('aria-expanded', 'true');
                icon.textContent = '▼';
                this.closest('.hsz-analysis-section').classList.add('hsz-expanded');
            }
        });
    });
});
</script>
