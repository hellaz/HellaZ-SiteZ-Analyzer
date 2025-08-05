<?php
/**
 * Template Partial: Additional Metadata Section
 *
 * Displays additional metadata including RSS feeds, language information,
 * performance hints, images analysis, links analysis, and other miscellaneous data.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $metadata Full metadata array
 * @var string $url The analyzed URL
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get various metadata sections
$feeds = $metadata['feeds'] ?? [];
$languages = $metadata['languages'] ?? [];
$performance_hints = $metadata['performance_hints'] ?? [];
$images = $metadata['images'] ?? [];
$links = $metadata['links'] ?? [];
$meta_tags = $metadata['meta_tags'] ?? [];
$robots = $metadata['robots'] ?? [];

// Skip section if no additional metadata
if ( empty( $feeds ) && empty( $languages ) && empty( $performance_hints ) && 
     empty( $images ) && empty( $links ) && empty( $meta_tags ) && empty( $robots ) ) {
    return;
}
?>

<div class="hsz-additional-metadata-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-metadata"></span>
        <?php esc_html_e( 'Additional Metadata', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- RSS/Atom Feeds -->
    <?php if ( ! empty( $feeds ) ): ?>
        <div class="hsz-feeds-analysis">
            <h5>
                <span class="hsz-feeds-icon">📡</span>
                <?php esc_html_e( 'RSS & Atom Feeds', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-feeds-overview">
                <div class="hsz-feeds-count">
                    <span class="hsz-count-number"><?php echo esc_html( count( $feeds ) ); ?></span>
                    <span class="hsz-count-label">
                        <?php echo count( $feeds ) === 1 ? 
                            esc_html__( 'Feed Found', 'hellaz-sitez-analyzer' ) : 
                            esc_html__( 'Feeds Found', 'hellaz-sitez-analyzer' ); ?>
                    </span>
                </div>
            </div>

            <div class="hsz-feeds-list">
                <?php foreach ( array_slice( $feeds, 0, 10 ) as $feed ): ?>
                    <div class="hsz-feed-item">
                        <div class="hsz-feed-header">
                            <div class="hsz-feed-type hsz-feed-<?php echo esc_attr( strtolower( str_replace( [ 'application/', '+xml' ], '', $feed['type'] ?? 'rss' ) ) ); ?>">
                                <?php 
                                $feed_type = $feed['type'] ?? 'application/rss+xml';
                                if ( strpos( $feed_type, 'rss' ) !== false ) {
                                    echo 'RSS';
                                } elseif ( strpos( $feed_type, 'atom' ) !== false ) {
                                    echo 'ATOM';
                                } else {
                                    echo 'FEED';
                                }
                                ?>
                            </div>
                            <div class="hsz-feed-title">
                                <?php echo esc_html( $feed['title'] ?: __( 'Untitled Feed', 'hellaz-sitez-analyzer' ) ); ?>
                            </div>
                        </div>
                        
                        <div class="hsz-feed-url">
                            <code><?php echo esc_html( $feed['url'] ); ?></code>
                            <a href="<?php echo esc_url( $feed['url'] ); ?>" 
                               target="_blank" rel="noopener noreferrer" 
                               class="hsz-feed-link"
                               title="<?php esc_attr_e( 'Open feed in new tab', 'hellaz-sitez-analyzer' ); ?>">
                                <span class="hsz-external-icon">↗</span>
                            </a>
                        </div>
                        
                        <div class="hsz-feed-type-full">
                            <small><?php echo esc_html( $feed['type'] ); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ( count( $feeds ) > 10 ): ?>
                    <div class="hsz-more-feeds">
                        <small>
                            <?php printf( 
                                esc_html__( '... and %d more feeds', 'hellaz-sitez-analyzer' ), 
                                count( $feeds ) - 10 
                            ); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Language Information -->
    <?php if ( ! empty( $languages ) ): ?>
        <div class="hsz-languages-analysis">
            <h5>
                <span class="hsz-languages-icon">🌐</span>
                <?php esc_html_e( 'Language Information', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-languages-details">
                
                <!-- Primary Language -->
                <?php if ( ! empty( $languages['html_lang'] ) ): ?>
                    <div class="hsz-language-item hsz-primary-language">
                        <div class="hsz-language-label"><?php esc_html_e( 'Primary Language:', 'hellaz-sitez-analyzer' ); ?></div>
                        <div class="hsz-language-value">
                            <span class="hsz-language-code"><?php echo esc_html( strtoupper( $languages['html_lang'] ) ); ?></span>
                            <span class="hsz-language-name">
                                <?php 
                                $language_names = [
                                    'en' => __( 'English', 'hellaz-sitez-analyzer' ),
                                    'es' => __( 'Spanish', 'hellaz-sitez-analyzer' ),
                                    'fr' => __( 'French', 'hellaz-sitez-analyzer' ),
                                    'de' => __( 'German', 'hellaz-sitez-analyzer' ),
                                    'it' => __( 'Italian', 'hellaz-sitez-analyzer' ),
                                    'pt' => __( 'Portuguese', 'hellaz-sitez-analyzer' ),
                                    'ru' => __( 'Russian', 'hellaz-sitez-analyzer' ),
                                    'ja' => __( 'Japanese', 'hellaz-sitez-analyzer' ),
                                    'ko' => __( 'Korean', 'hellaz-sitez-analyzer' ),
                                    'zh' => __( 'Chinese', 'hellaz-sitez-analyzer' ),
                                    'ar' => __( 'Arabic', 'hellaz-sitez-analyzer' ),
                                    'hi' => __( 'Hindi', 'hellaz-sitez-analyzer' ),
                                ];
                                $lang_code = strtolower( substr( $languages['html_lang'], 0, 2 ) );
                                echo esc_html( $language_names[ $lang_code ] ?? $languages['html_lang'] );
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Content Language -->
                <?php if ( ! empty( $languages['content_language'] ) && $languages['content_language'] !== $languages['html_lang'] ): ?>
                    <div class="hsz-language-item">
                        <div class="hsz-language-label"><?php esc_html_e( 'Content Language:', 'hellaz-sitez-analyzer' ); ?></div>
                        <div class="hsz-language-value">
                            <code><?php echo esc_html( $languages['content_language'] ); ?></code>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Hreflang Alternatives -->
                <?php if ( ! empty( $languages['hreflang'] ) ): ?>
                    <div class="hsz-language-item hsz-hreflang-section">
                        <div class="hsz-language-label"><?php esc_html_e( 'Language Alternatives:', 'hellaz-sitez-analyzer' ); ?></div>
                        <div class="hsz-hreflang-list">
                            <?php foreach ( array_slice( $languages['hreflang'], 0, 8 ) as $lang_code => $lang_url ): ?>
                                <div class="hsz-hreflang-item">
                                    <span class="hsz-hreflang-code"><?php echo esc_html( $lang_code ); ?></span>
                                    <a href="<?php echo esc_url( $lang_url ); ?>" 
                                       target="_blank" rel="noopener noreferrer" 
                                       class="hsz-hreflang-url"
                                       title="<?php esc_attr_e( 'View language version', 'hellaz-sitez-analyzer' ); ?>">
                                        <?php echo esc_html( parse_url( $lang_url, PHP_URL_PATH ) ?: '/' ); ?>
                                        <span class="hsz-external-icon">↗</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ( count( $languages['hreflang'] ) > 8 ): ?>
                                <div class="hsz-more-languages">
                                    <small>
                                        <?php printf( 
                                            esc_html__( '... and %d more languages', 'hellaz-sitez-analyzer' ), 
                                            count( $languages['hreflang'] ) - 8 
                                        ); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

    <!-- Performance Hints -->
    <?php if ( ! empty( $performance_hints ) ): ?>
        <div class="hsz-performance-hints-analysis">
            <h5>
                <span class="hsz-hints-icon">⚡</span>
                <?php esc_html_e( 'Performance Optimization Hints', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-hints-overview">
                <div class="hsz-hints-stats">
                    
                    <?php if ( ! empty( $performance_hints['preload_links'] ) ): ?>
                        <div class="hsz-hint-stat">
                            <div class="hsz-hint-number"><?php echo esc_html( count( $performance_hints['preload_links'] ) ); ?></div>
                            <div class="hsz-hint-label"><?php esc_html_e( 'Preload', 'hellaz-sitez-analyzer' ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $performance_hints['prefetch_links'] ) ): ?>
                        <div class="hsz-hint-stat">
                            <div class="hsz-hint-number"><?php echo esc_html( count( $performance_hints['prefetch_links'] ) ); ?></div>
                            <div class="hsz-hint-label"><?php esc_html_e( 'Prefetch', 'hellaz-sitez-analyzer' ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $performance_hints['dns_prefetch'] ) ): ?>
                        <div class="hsz-hint-stat">
                            <div class="hsz-hint-number"><?php echo esc_html( count( $performance_hints['dns_prefetch'] ) ); ?></div>
                            <div class="hsz-hint-label"><?php esc_html_e( 'DNS Prefetch', 'hellaz-sitez-analyzer' ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $performance_hints['preconnect'] ) ): ?>
                        <div class="hsz-hint-stat">
                            <div class="hsz-hint-number"><?php echo esc_html( count( $performance_hints['preconnect'] ) ); ?></div>
                            <div class="hsz-hint-label"><?php esc_html_e( 'Preconnect', 'hellaz-sitez-analyzer' ); ?></div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Script Loading Optimization -->
            <?php if ( isset( $performance_hints['async_scripts'] ) || isset( $performance_hints['defer_scripts'] ) ): ?>
                <div class="hsz-script-optimization">
                    <h6><?php esc_html_e( 'Script Loading Optimization', 'hellaz-sitez-analyzer' ); ?></h6>
                    
                    <div class="hsz-script-stats">
                        <?php if ( isset( $performance_hints['async_scripts'] ) ): ?>
                            <div class="hsz-script-stat hsz-script-async">
                                <span class="hsz-script-icon">⚡</span>
                                <span class="hsz-script-count"><?php echo esc_html( $performance_hints['async_scripts'] ); ?></span>
                                <span class="hsz-script-label"><?php esc_html_e( 'Async Scripts', 'hellaz-sitez-analyzer' ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $performance_hints['defer_scripts'] ) ): ?>
                            <div class="hsz-script-stat hsz-script-defer">
                                <span class="hsz-script-icon">⏳</span>
                                <span class="hsz-script-count"><?php echo esc_html( $performance_hints['defer_scripts'] ); ?></span>
                                <span class="hsz-script-label"><?php esc_html_e( 'Deferred Scripts', 'hellaz-sitez-analyzer' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Style Loading Information -->
            <?php if ( isset( $performance_hints['inline_styles'] ) || isset( $performance_hints['external_styles'] ) ): ?>
                <div class="hsz-style-optimization">
                    <h6><?php esc_html_e( 'Stylesheet Loading', 'hellaz-sitez-analyzer' ); ?></h6>
                    
                    <div class="hsz-style-stats">
                        <?php if ( isset( $performance_hints['external_styles'] ) ): ?>
                            <div class="hsz-style-stat">
                                <span class="hsz-style-label"><?php esc_html_e( 'External Stylesheets:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-style-count"><?php echo esc_html( $performance_hints['external_styles'] ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( isset( $performance_hints['inline_styles'] ) ): ?>
                            <div class="hsz-style-stat">
                                <span class="hsz-style-label"><?php esc_html_e( 'Inline Styles:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-style-count"><?php echo esc_html( $performance_hints['inline_styles'] ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detailed Resource Hints -->
            <?php if ( ! empty( $performance_hints['preload_links'] ) || ! empty( $performance_hints['dns_prefetch'] ) ): ?>
                <details class="hsz-hints-details">
                    <summary><?php esc_html_e( 'View Performance Hints Details', 'hellaz-sitez-analyzer' ); ?></summary>
                    
                    <?php if ( ! empty( $performance_hints['preload_links'] ) ): ?>
                        <div class="hsz-hints-section">
                            <h6><?php esc_html_e( 'Preload Resources', 'hellaz-sitez-analyzer' ); ?></h6>
                            <ul class="hsz-resource-list">
                                <?php foreach ( array_slice( $performance_hints['preload_links'], 0, 8 ) as $preload ): ?>
                                    <li class="hsz-resource-item">
                                        <code class="hsz-resource-url"><?php echo esc_html( $preload['href'] ?? '' ); ?></code>
                                        <?php if ( ! empty( $preload['as'] ) ): ?>
                                            <span class="hsz-resource-type"><?php echo esc_html( $preload['as'] ); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $performance_hints['dns_prefetch'] ) ): ?>
                        <div class="hsz-hints-section">
                            <h6><?php esc_html_e( 'DNS Prefetch Domains', 'hellaz-sitez-analyzer' ); ?></h6>
                            <ul class="hsz-domain-list">
                                <?php foreach ( array_slice( $performance_hints['dns_prefetch'], 0, 8 ) as $domain ): ?>
                                    <li class="hsz-domain-item">
                                        <code><?php echo esc_html( $domain ); ?></code>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Images Analysis Summary -->
    <?php if ( ! empty( $images ) ): ?>
        <div class="hsz-images-analysis">
            <h5>
                <span class="hsz-images-icon">🖼️</span>
                <?php esc_html_e( 'Images Analysis Summary', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-images-overview">
                <div class="hsz-images-stats">
                    <div class="hsz-image-stat">
                        <div class="hsz-stat-number"><?php echo esc_html( count( $images ) ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'Images Found', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                    
                    <?php
                    $images_with_alt = 0;
                    $images_with_title = 0;
                    $images_with_dimensions = 0;
                    
                    foreach ( $images as $image ) {
                        if ( ! empty( $image['alt'] ) ) $images_with_alt++;
                        if ( ! empty( $image['title'] ) ) $images_with_title++;
                        if ( ! empty( $image['width'] ) && ! empty( $image['height'] ) ) $images_with_dimensions++;
                    }
                    ?>
                    
                    <div class="hsz-image-stat">
                        <div class="hsz-stat-number"><?php echo esc_html( $images_with_alt ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'With Alt Text', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                    
                    <div class="hsz-image-stat">
                        <div class="hsz-stat-number"><?php echo esc_html( $images_with_dimensions ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'With Dimensions', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                </div>

                <!-- Image SEO Assessment -->
                <div class="hsz-images-seo">
                    <?php
                    $alt_percentage = count( $images ) > 0 ? round( ( $images_with_alt / count( $images ) ) * 100 ) : 0;
                    
                    if ( $alt_percentage >= 90 ) {
                        $seo_status = 'excellent';
                        $seo_message = __( 'Excellent image SEO', 'hellaz-sitez-analyzer' );
                    } elseif ( $alt_percentage >= 70 ) {
                        $seo_status = 'good';
                        $seo_message = __( 'Good image SEO', 'hellaz-sitez-analyzer' );
                    } elseif ( $alt_percentage >= 50 ) {
                        $seo_status = 'average';
                        $seo_message = __( 'Average image SEO', 'hellaz-sitez-analyzer' );
                    } else {
                        $seo_status = 'poor';
                        $seo_message = __( 'Poor image SEO', 'hellaz-sitez-analyzer' );
                    }
                    ?>
                    
                    <div class="hsz-seo-indicator hsz-seo-<?php echo esc_attr( $seo_status ); ?>">
                        <span class="hsz-seo-percentage"><?php echo esc_html( $alt_percentage ); ?>%</span>
                        <span class="hsz-seo-label"><?php echo esc_html( $seo_message ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Image Issues -->
            <?php if ( $images_with_alt < count( $images ) ): ?>
                <div class="hsz-image-issues">
                    <div class="hsz-issue-item">
                        <span class="hsz-issue-icon">⚠️</span>
                        <span class="hsz-issue-text">
                            <?php printf( 
                                esc_html__( '%d images missing alt text for accessibility', 'hellaz-sitez-analyzer' ), 
                                count( $images ) - $images_with_alt 
                            ); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Links Analysis Summary -->
    <?php if ( ! empty( $links ) ): ?>
        <div class="hsz-links-analysis">
            <h5>
                <span class="hsz-links-icon">🔗</span>
                <?php esc_html_e( 'Links Analysis Summary', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-links-overview">
                <?php
                $internal_links = 0;
                $external_links = 0;
                $nofollow_links = 0;
                
                foreach ( $links as $link ) {
                    if ( ( $link['type'] ?? '' ) === 'internal' ) {
                        $internal_links++;
                    } else {
                        $external_links++;
                    }
                    
                    if ( strpos( $link['rel'] ?? '', 'nofollow' ) !== false ) {
                        $nofollow_links++;
                    }
                }
                ?>
                
                <div class="hsz-links-stats">
                    <div class="hsz-link-stat">
                        <div class="hsz-stat-number"><?php echo esc_html( count( $links ) ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'Total Links', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                    
                    <div class="hsz-link-stat hsz-internal-links">
                        <div class="hsz-stat-number"><?php echo esc_html( $internal_links ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'Internal', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                    
                    <div class="hsz-link-stat hsz-external-links">
                        <div class="hsz-stat-number"><?php echo esc_html( $external_links ); ?></div>
                        <div class="hsz-stat-label"><?php esc_html_e( 'External', 'hellaz-sitez-analyzer' ); ?></div>
                    </div>
                    
                    <?php if ( $nofollow_links > 0 ): ?>
                        <div class="hsz-link-stat hsz-nofollow-links">
                            <div class="hsz-stat-number"><?php echo esc_html( $nofollow_links ); ?></div>
                            <div class="hsz-stat-label"><?php esc_html_e( 'Nofollow', 'hellaz-sitez-analyzer' ); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Link Quality Assessment -->
                <div class="hsz-links-quality">
                    <?php
                    $internal_ratio = count( $links ) > 0 ? ( $internal_links / count( $links ) ) * 100 : 0;
                    
                    if ( $internal_ratio >= 70 ) {
                        $quality_status = 'good';
                        $quality_message = __( 'Good internal linking', 'hellaz-sitez-analyzer' );
                    } elseif ( $internal_ratio >= 40 ) {
                        $quality_status = 'average';
                        $quality_message = __( 'Balanced link structure', 'hellaz-sitez-analyzer' );
                    } else {
                        $quality_status = 'external-heavy';
                        $quality_message = __( 'Heavy external linking', 'hellaz-sitez-analyzer' );
                    }
                    ?>
                    
                    <div class="hsz-quality-indicator hsz-quality-<?php echo esc_attr( $quality_status ); ?>">
                        <span class="hsz-quality-ratio"><?php echo esc_html( round( $internal_ratio ) ); ?>% internal</span>
                        <span class="hsz-quality-label"><?php echo esc_html( $quality_message ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Robots Directives -->
    <?php if ( ! empty( $robots ) ): ?>
        <div class="hsz-robots-analysis">
            <h5>
                <span class="hsz-robots-icon">🤖</span>
                <?php esc_html_e( 'Robots Directives', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-robots-directives">
                <?php foreach ( $robots as $directive ): ?>
                    <span class="hsz-robot-directive hsz-directive-<?php echo esc_attr( str_replace( [ ' ', ',' ], [ '-', '' ], strtolower( $directive ) ) ); ?>">
                        <?php echo esc_html( trim( $directive ) ); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Robots Assessment -->
            <div class="hsz-robots-assessment">
                <?php if ( in_array( 'noindex', $robots ) ): ?>
                    <div class="hsz-robots-warning">
                        <span class="hsz-warning-icon">⚠️</span>
                        <span class="hsz-warning-text"><?php esc_html_e( 'Page is set to NOINDEX - search engines will not index this page', 'hellaz-sitez-analyzer' ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'nofollow', $robots ) ): ?>
                    <div class="hsz-robots-info">
                        <span class="hsz-info-icon">ℹ️</span>
                        <span class="hsz-info-text"><?php esc_html_e( 'Page is set to NOFOLLOW - search engines will not follow links', 'hellaz-sitez-analyzer' ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( empty( array_intersect( [ 'noindex', 'nofollow' ], $robots ) ) ): ?>
                    <div class="hsz-robots-good">
                        <span class="hsz-good-icon">✅</span>
                        <span class="hsz-good-text"><?php esc_html_e( 'Search engine friendly robots directives', 'hellaz-sitez-analyzer' ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Meta Tags Overview -->
    <?php if ( ! empty( $meta_tags ) ): ?>
        <div class="hsz-meta-tags-analysis">
            <h5>
                <span class="hsz-meta-icon">🏷️</span>
                <?php esc_html_e( 'Meta Tags Overview', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-meta-overview">
                <div class="hsz-meta-count">
                    <span class="hsz-count-number"><?php echo esc_html( count( $meta_tags ) ); ?></span>
                    <span class="hsz-count-label"><?php esc_html_e( 'Meta Tags Found', 'hellaz-sitez-analyzer' ); ?></span>
                </div>
            </div>

            <!-- Important Meta Tags -->
            <div class="hsz-important-meta">
                <?php
                $important_meta = [
                    'viewport' => __( 'Viewport', 'hellaz-sitez-analyzer' ),
                    'author' => __( 'Author', 'hellaz-sitez-analyzer' ),
                    'keywords' => __( 'Keywords', 'hellaz-sitez-analyzer' ),
                    'generator' => __( 'Generator', 'hellaz-sitez-analyzer' ),
                    'theme-color' => __( 'Theme Color', 'hellaz-sitez-analyzer' ),
                ];
                ?>
                
                <h6><?php esc_html_e( 'Key Meta Tags', 'hellaz-sitez-analyzer' ); ?></h6>
                <div class="hsz-meta-grid">
                    <?php foreach ( $important_meta as $meta_key => $meta_label ): ?>
                        <?php if ( isset( $meta_tags[ $meta_key ] ) ): ?>
                            <div class="hsz-meta-item hsz-meta-present">
                                <div class="hsz-meta-label"><?php echo esc_html( $meta_label ); ?>:</div>
                                <div class="hsz-meta-value">
                                    <code><?php echo esc_html( wp_trim_words( $meta_tags[ $meta_key ], 8 ) ); ?></code>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- All Meta Tags (Expandable) -->
            <?php if ( count( $meta_tags ) > 5 ): ?>
                <details class="hsz-all-meta-details">
                    <summary><?php esc_html_e( 'View All Meta Tags', 'hellaz-sitez-analyzer' ); ?></summary>
                    <div class="hsz-all-meta-list">
                        <?php foreach ( array_slice( $meta_tags, 0, 20 ) as $meta_name => $meta_content ): ?>
                            <div class="hsz-meta-full-item">
                                <div class="hsz-meta-name"><code><?php echo esc_html( $meta_name ); ?></code></div>
                                <div class="hsz-meta-content"><code><?php echo esc_html( wp_trim_words( $meta_content, 15 ) ); ?></code></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ( count( $meta_tags ) > 20 ): ?>
                            <div class="hsz-more-meta">
                                <small>
                                    <?php printf( 
                                        esc_html__( '... and %d more meta tags', 'hellaz-sitez-analyzer' ), 
                                        count( $meta_tags ) - 20 
                                    ); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Additional Metadata Recommendations -->
    <div class="hsz-additional-recommendations">
        <h5><?php esc_html_e( 'Additional Metadata Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <ul class="hsz-recommendations-list">
            <?php
            $recommendations = [];
            
            // Feed recommendations
            if ( empty( $feeds ) ) {
                $recommendations[] = __( 'Consider adding RSS/Atom feeds to help users subscribe to your content', 'hellaz-sitez-analyzer' );
            }
            
            // Language recommendations
            if ( empty( $languages['html_lang'] ) ) {
                $recommendations[] = __( 'Add a lang attribute to your HTML tag to specify the page language', 'hellaz-sitez-analyzer' );
            }
            
            // Image SEO recommendations
            if ( ! empty( $images ) && $images_with_alt < count( $images ) ) {
                $recommendations[] = __( 'Add alt text to all images for better accessibility and SEO', 'hellaz-sitez-analyzer' );
            }
            
            // Performance hints recommendations
            if ( empty( $performance_hints['preload_links'] ) && empty( $performance_hints['dns_prefetch'] ) ) {
                $recommendations[] = __( 'Consider adding resource hints (preload, dns-prefetch) to improve loading performance', 'hellaz-sitez-analyzer' );
            }
            
            // Link structure recommendations
            if ( ! empty( $links ) && $internal_links < ( count( $links ) * 0.3 ) ) {
                $recommendations[] = __( 'Increase internal linking to improve site navigation and SEO', 'hellaz-sitez-analyzer' );
            }
            
            // Meta tags recommendations
            if ( ! isset( $meta_tags['viewport'] ) ) {
                $recommendations[] = __( 'Add a viewport meta tag for better mobile responsiveness', 'hellaz-sitez-analyzer' );
            }
            
            // Positive feedback if well implemented
            if ( empty( $recommendations ) ) {
                $recommendations[] = __( 'Your additional metadata implementation looks comprehensive!', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <?php foreach ( array_slice( $recommendations, 0, 6 ) as $recommendation ): ?>
                <li class="hsz-recommendation-item">
                    <span class="hsz-recommendation-icon">🔧</span>
                    <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
