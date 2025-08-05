<?php
/**
 * Template Partial: Technology Stack Section
 *
 * Displays comprehensive technology analysis including CMS detection,
 * hosting information, JavaScript libraries, analytics tools, and server details.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $api_data API data containing BuiltWith results
 * @var array $technology Direct technology data (alternative)
 * @var array $metadata Full metadata array
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get technology data from multiple possible sources
$tech_data = $technology ?? $api_data['builtwith'] ?? $metadata['technology'] ?? [];

// Skip section if no technology data
if ( empty( $tech_data ) ) {
    return;
}
?>

<div class="hsz-technology-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-tech"></span>
        <?php esc_html_e( 'Technology Stack Analysis', 'hellaz-sitez-analyzer' ); ?>
        <span class="hsz-api-badge"><?php esc_html_e( 'API', 'hellaz-sitez-analyzer' ); ?></span>
    </h4>

    <!-- Technology Overview -->
    <?php if ( isset( $tech_data['technologies'] ) ): ?>
        <div class="hsz-tech-overview">
            <div class="hsz-tech-stats">
                <?php
                $technologies = $tech_data['technologies'];
                $total_tech = 0;
                foreach ( $technologies as $category => $items ) {
                    if ( is_array( $items ) ) {
                        $total_tech += count( $items );
                    }
                }
                ?>
                
                <div class="hsz-stat-card">
                    <div class="hsz-stat-number"><?php echo esc_html( $total_tech ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Technologies Detected', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
                
                <div class="hsz-stat-card">
                    <div class="hsz-stat-number"><?php echo esc_html( count( $technologies ) ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Technology Categories', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            </div>
        </div>

        <!-- Core Technologies -->
        <div class="hsz-core-technologies">
            <h5><?php esc_html_e( 'Core Technologies', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-core-tech-grid">
                
                <!-- Content Management System -->
                <?php if ( ! empty( $technologies['cms'] ) ): ?>
                    <div class="hsz-tech-card hsz-tech-primary">
                        <div class="hsz-tech-icon">🖥️</div>
                        <div class="hsz-tech-content">
                            <div class="hsz-tech-category"><?php esc_html_e( 'Content Management', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-tech-items">
                                <?php foreach ( array_slice( $technologies['cms'], 0, 3 ) as $cms ): ?>
                                    <span class="hsz-tech-item hsz-tech-cms"><?php echo esc_html( $cms ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Web Server -->
                <?php if ( ! empty( $technologies['web_server'] ) || ! empty( $tech_data['server_info']['server'] ) ): ?>
                    <div class="hsz-tech-card hsz-tech-primary">
                        <div class="hsz-tech-icon">🌐</div>
                        <div class="hsz-tech-content">
                            <div class="hsz-tech-category"><?php esc_html_e( 'Web Server', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-tech-items">
                                <?php 
                                $servers = $technologies['web_server'] ?? [ $tech_data['server_info']['server'] ?? '' ];
                                foreach ( array_slice( array_filter( $servers ), 0, 2 ) as $server ): 
                                ?>
                                    <span class="hsz-tech-item hsz-tech-server"><?php echo esc_html( $server ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Programming Language -->
                <?php if ( ! empty( $technologies['programming_language'] ) ): ?>
                    <div class="hsz-tech-card hsz-tech-primary">
                        <div class="hsz-tech-icon">💻</div>
                        <div class="hsz-tech-content">
                            <div class="hsz-tech-category"><?php esc_html_e( 'Programming Language', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-tech-items">
                                <?php foreach ( array_slice( $technologies['programming_language'], 0, 3 ) as $language ): ?>
                                    <span class="hsz-tech-item hsz-tech-language"><?php echo esc_html( $language ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Hosting/CDN -->
                <?php if ( ! empty( $technologies['hosting'] ) || ! empty( $technologies['cdn'] ) ): ?>
                    <div class="hsz-tech-card hsz-tech-primary">
                        <div class="hsz-tech-icon">☁️</div>
                        <div class="hsz-tech-content">
                            <div class="hsz-tech-category"><?php esc_html_e( 'Hosting & CDN', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-tech-items">
                                <?php 
                                $hosting_items = array_merge( 
                                    $technologies['hosting'] ?? [], 
                                    $technologies['cdn'] ?? [] 
                                );
                                foreach ( array_slice( $hosting_items, 0, 3 ) as $hosting ): 
                                ?>
                                    <span class="hsz-tech-item hsz-tech-hosting"><?php echo esc_html( $hosting ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Frontend Technologies -->
        <div class="hsz-frontend-technologies">
            <h5><?php esc_html_e( 'Frontend Technologies', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-frontend-grid">
                
                <!-- JavaScript Libraries -->
                <?php if ( ! empty( $technologies['javascript'] ) || ! empty( $technologies['javascript_library'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">📚</span>
                            <?php esc_html_e( 'JavaScript Libraries', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-tech-tags">
                            <?php 
                            $js_libraries = array_merge( 
                                $technologies['javascript'] ?? [], 
                                $technologies['javascript_library'] ?? [] 
                            );
                            foreach ( array_slice( array_unique( $js_libraries ), 0, 8 ) as $library ): 
                            ?>
                                <span class="hsz-tech-tag hsz-tech-js"><?php echo esc_html( $library ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- CSS Frameworks -->
                <?php if ( ! empty( $technologies['css_framework'] ) || ! empty( $technologies['css'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">🎨</span>
                            <?php esc_html_e( 'CSS Frameworks', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-tech-tags">
                            <?php 
                            $css_frameworks = array_merge( 
                                $technologies['css_framework'] ?? [], 
                                $technologies['css'] ?? [] 
                            );
                            foreach ( array_slice( array_unique( $css_frameworks ), 0, 6 ) as $framework ): 
                            ?>
                                <span class="hsz-tech-tag hsz-tech-css"><?php echo esc_html( $framework ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- UI Frameworks -->
                <?php if ( ! empty( $technologies['ui_framework'] ) || ! empty( $technologies['frontend_framework'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">🖼️</span>
                            <?php esc_html_e( 'UI Frameworks', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-tech-tags">
                            <?php 
                            $ui_frameworks = array_merge( 
                                $technologies['ui_framework'] ?? [], 
                                $technologies['frontend_framework'] ?? [] 
                            );
                            foreach ( array_slice( array_unique( $ui_frameworks ), 0, 6 ) as $framework ): 
                            ?>
                                <span class="hsz-tech-tag hsz-tech-ui"><?php echo esc_html( $framework ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Marketing & Analytics -->
        <div class="hsz-marketing-technologies">
            <h5><?php esc_html_e( 'Marketing & Analytics', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-marketing-grid">
                
                <!-- Analytics Tools -->
                <?php if ( ! empty( $technologies['analytics'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">📊</span>
                            <?php esc_html_e( 'Analytics', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-analytics-items">
                            <?php foreach ( array_slice( $technologies['analytics'], 0, 5 ) as $analytics ): ?>
                                <div class="hsz-analytics-item">
                                    <span class="hsz-analytics-icon">📈</span>
                                    <span class="hsz-analytics-name"><?php echo esc_html( $analytics ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Advertising -->
                <?php if ( ! empty( $technologies['advertising'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">📢</span>
                            <?php esc_html_e( 'Advertising', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-advertising-items">
                            <?php foreach ( array_slice( $technologies['advertising'], 0, 5 ) as $advertising ): ?>
                                <div class="hsz-advertising-item">
                                    <span class="hsz-advertising-icon">💰</span>
                                    <span class="hsz-advertising-name"><?php echo esc_html( $advertising ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tag Management -->
                <?php if ( ! empty( $technologies['tag_manager'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">🏷️</span>
                            <?php esc_html_e( 'Tag Management', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-tech-tags">
                            <?php foreach ( array_slice( $technologies['tag_manager'], 0, 4 ) as $tag_manager ): ?>
                                <span class="hsz-tech-tag hsz-tech-tag-manager"><?php echo esc_html( $tag_manager ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Marketing Automation -->
                <?php if ( ! empty( $technologies['marketing_automation'] ) ): ?>
                    <div class="hsz-tech-category-section">
                        <h6 class="hsz-tech-subtitle">
                            <span class="hsz-subtitle-icon">🤖</span>
                            <?php esc_html_e( 'Marketing Automation', 'hellaz-sitez-analyzer' ); ?>
                        </h6>
                        <div class="hsz-tech-tags">
                            <?php foreach ( array_slice( $technologies['marketing_automation'], 0, 4 ) as $automation ): ?>
                                <span class="hsz-tech-tag hsz-tech-automation"><?php echo esc_html( $automation ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- E-commerce & Business Tools -->
        <?php if ( ! empty( $technologies['ecommerce'] ) || ! empty( $technologies['payment'] ) || ! empty( $technologies['crm'] ) ): ?>
            <div class="hsz-business-technologies">
                <h5><?php esc_html_e( 'E-commerce & Business Tools', 'hellaz-sitez-analyzer' ); ?></h5>
                
                <div class="hsz-business-grid">
                    
                    <!-- E-commerce Platforms -->
                    <?php if ( ! empty( $technologies['ecommerce'] ) ): ?>
                        <div class="hsz-tech-category-section">
                            <h6 class="hsz-tech-subtitle">
                                <span class="hsz-subtitle-icon">🛒</span>
                                <?php esc_html_e( 'E-commerce', 'hellaz-sitez-analyzer' ); ?>
                            </h6>
                            <div class="hsz-ecommerce-items">
                                <?php foreach ( array_slice( $technologies['ecommerce'], 0, 4 ) as $ecommerce ): ?>
                                    <div class="hsz-ecommerce-item">
                                        <span class="hsz-ecommerce-icon">🏪</span>
                                        <span class="hsz-ecommerce-name"><?php echo esc_html( $ecommerce ); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Payment Systems -->
                    <?php if ( ! empty( $technologies['payment'] ) ): ?>
                        <div class="hsz-tech-category-section">
                            <h6 class="hsz-tech-subtitle">
                                <span class="hsz-subtitle-icon">💳</span>
                                <?php esc_html_e( 'Payment Systems', 'hellaz-sitez-analyzer' ); ?>
                            </h6>
                            <div class="hsz-tech-tags">
                                <?php foreach ( array_slice( $technologies['payment'], 0, 5 ) as $payment ): ?>
                                    <span class="hsz-tech-tag hsz-tech-payment"><?php echo esc_html( $payment ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- CRM Systems -->
                    <?php if ( ! empty( $technologies['crm'] ) ): ?>
                        <div class="hsz-tech-category-section">
                            <h6 class="hsz-tech-subtitle">
                                <span class="hsz-subtitle-icon">👥</span>
                                <?php esc_html_e( 'CRM Systems', 'hellaz-sitez-analyzer' ); ?>
                            </h6>
                            <div class="hsz-tech-tags">
                                <?php foreach ( array_slice( $technologies['crm'], 0, 4 ) as $crm ): ?>
                                    <span class="hsz-tech-tag hsz-tech-crm"><?php echo esc_html( $crm ); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Server Information -->
    <?php if ( ! empty( $tech_data['server_info'] ) ): ?>
        <div class="hsz-server-information">
            <h5><?php esc_html_e( 'Server Information', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-server-details">
                <?php $server_info = $tech_data['server_info']; ?>
                
                <div class="hsz-server-grid">
                    
                    <?php if ( ! empty( $server_info['ip'] ) ): ?>
                        <div class="hsz-server-item">
                            <div class="hsz-server-label"><?php esc_html_e( 'IP Address:', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-server-value">
                                <code><?php echo esc_html( $server_info['ip'] ); ?></code>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $server_info['location'] ) ): ?>
                        <div class="hsz-server-item">
                            <div class="hsz-server-label"><?php esc_html_e( 'Server Location:', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-server-value"><?php echo esc_html( $server_info['location'] ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $server_info['hosting_provider'] ) ): ?>
                        <div class="hsz-server-item">
                            <div class="hsz-server-label"><?php esc_html_e( 'Hosting Provider:', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-server-value"><?php echo esc_html( $server_info['hosting_provider'] ); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $server_info['server'] ) ): ?>
                        <div class="hsz-server-item">
                            <div class="hsz-server-label"><?php esc_html_e( 'Server Software:', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-server-value">
                                <code><?php echo esc_html( $server_info['server'] ); ?></code>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $server_info['response_time'] ) ): ?>
                        <div class="hsz-server-item">
                            <div class="hsz-server-label"><?php esc_html_e( 'Response Time:', 'hellaz-sitez-analyzer' ); ?></div>
                            <div class="hsz-server-value"><?php echo esc_html( $server_info['response_time'] ); ?>ms</div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Technology Recommendations -->
    <div class="hsz-tech-recommendations">
        <h5><?php esc_html_e( 'Technology Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <ul class="hsz-recommendations-list">
            <?php
            $recommendations = [];
            
            // CMS recommendations
            if ( empty( $technologies['cms'] ?? [] ) ) {
                $recommendations[] = __( 'Consider implementing a Content Management System for easier content updates', 'hellaz-sitez-analyzer' );
            }
            
            // Analytics recommendations
            if ( empty( $technologies['analytics'] ?? [] ) ) {
                $recommendations[] = __( 'Add web analytics tools like Google Analytics to track visitor behavior', 'hellaz-sitez-analyzer' );
            }
            
            // Performance recommendations
            if ( empty( $technologies['cdn'] ?? [] ) && empty( $technologies['hosting'] ?? [] ) ) {
                $recommendations[] = __( 'Consider using a Content Delivery Network (CDN) to improve loading speeds', 'hellaz-sitez-analyzer' );
            }
            
            // Security recommendations
            if ( empty( $technologies['security'] ?? [] ) ) {
                $recommendations[] = __( 'Implement security tools and monitoring to protect your website', 'hellaz-sitez-analyzer' );
            }
            
            // Modern framework recommendations
            $has_modern_js = false;
            $modern_frameworks = [ 'React', 'Vue.js', 'Angular', 'Svelte' ];
            foreach ( $modern_frameworks as $framework ) {
                if ( in_array( $framework, $technologies['javascript'] ?? [] ) ) {
                    $has_modern_js = true;
                    break;
                }
            }
            
            if ( ! $has_modern_js && ! empty( $technologies['javascript'] ?? [] ) ) {
                $recommendations[] = __( 'Consider modernizing JavaScript stack with contemporary frameworks', 'hellaz-sitez-analyzer' );
            }
            
            // SEO recommendations
            if ( empty( $technologies['tag_manager'] ?? [] ) ) {
                $recommendations[] = __( 'Implement Google Tag Manager for better marketing tag management', 'hellaz-sitez-analyzer' );
            }
            
            // Default positive message
            if ( empty( $recommendations ) ) {
                $recommendations[] = __( 'Your technology stack looks comprehensive and well-implemented!', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <?php foreach ( array_slice( $recommendations, 0, 5 ) as $recommendation ): ?>
                <li class="hsz-recommendation-item">
                    <span class="hsz-recommendation-icon">⚙️</span>
                    <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Technology Insights -->
    <?php if ( ! empty( $technologies ) ): ?>
        <div class="hsz-tech-insights">
            <h5><?php esc_html_e( 'Technology Insights', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-insights-grid">
                
                <!-- Technology Maturity -->
                <div class="hsz-insight-card">
                    <div class="hsz-insight-title"><?php esc_html_e( 'Technology Maturity', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-insight-content">
                        <?php
                        $modern_count = 0;
                        $total_count = 0;
                        
                        $modern_techs = [ 'React', 'Vue.js', 'Angular', 'Node.js', 'Webpack', 'TypeScript' ];
                        foreach ( $technologies as $category => $items ) {
                            if ( is_array( $items ) ) {
                                $total_count += count( $items );
                                foreach ( $items as $item ) {
                                    if ( in_array( $item, $modern_techs ) ) {
                                        $modern_count++;
                                    }
                                }
                            }
                        }
                        
                        $maturity_percentage = $total_count > 0 ? round( ( $modern_count / $total_count ) * 100 ) : 0;
                        
                        if ( $maturity_percentage >= 70 ) {
                            $maturity_status = 'modern';
                            $maturity_message = __( 'Modern technology stack', 'hellaz-sitez-analyzer' );
                        } elseif ( $maturity_percentage >= 40 ) {
                            $maturity_status = 'mixed';
                            $maturity_message = __( 'Mixed modern and legacy technologies', 'hellaz-sitez-analyzer' );
                        } else {
                            $maturity_status = 'legacy';
                            $maturity_message = __( 'Primarily legacy technologies', 'hellaz-sitez-analyzer' );
                        }
                        ?>
                        <div class="hsz-maturity-indicator hsz-maturity-<?php echo esc_attr( $maturity_status ); ?>">
                            <span class="hsz-maturity-percentage"><?php echo esc_html( $maturity_percentage ); ?>%</span>
                            <span class="hsz-maturity-label"><?php echo esc_html( $maturity_message ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Performance Impact -->
                <div class="hsz-insight-card">
                    <div class="hsz-insight-title"><?php esc_html_e( 'Performance Impact', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-insight-content">
                        <?php
                        $heavy_techs = [ 'jQuery UI', 'Bootstrap 3', 'Font Awesome', 'Slick Slider' ];
                        $light_techs = [ 'Alpine.js', 'Tailwind CSS', 'Preact' ];
                        
                        $heavy_count = 0;
                        $light_count = 0;
                        
                        foreach ( $technologies as $category => $items ) {
                            if ( is_array( $items ) ) {
                                foreach ( $items as $item ) {
                                    if ( in_array( $item, $heavy_techs ) ) {
                                        $heavy_count++;
                                    } elseif ( in_array( $item, $light_techs ) ) {
                                        $light_count++;
                                    }
                                }
                            }
                        }
                        
                        if ( $light_count > $heavy_count ) {
                            $perf_status = 'optimized';
                            $perf_message = __( 'Performance-optimized stack', 'hellaz-sitez-analyzer' );
                        } elseif ( $heavy_count > 0 ) {
                            $perf_status = 'heavy';
                            $perf_message = __( 'May impact performance', 'hellaz-sitez-analyzer' );
                        } else {
                            $perf_status = 'neutral';
                            $perf_message = __( 'Balanced technology choices', 'hellaz-sitez-analyzer' );
                        }
                        ?>
                        <div class="hsz-performance-indicator hsz-performance-<?php echo esc_attr( $perf_status ); ?>">
                            <span class="hsz-performance-icon">
                                <?php echo $perf_status === 'optimized' ? '⚡' : ( $perf_status === 'heavy' ? '⏳' : '⚖️' ); ?>
                            </span>
                            <span class="hsz-performance-label"><?php echo esc_html( $perf_message ); ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <!-- External Analysis Tools -->
    <div class="hsz-tech-tools">
        <h5><?php esc_html_e( 'Technology Analysis Tools', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-tools-grid">
            <div class="hsz-tool-card">
                <div class="hsz-tool-name"><?php esc_html_e( 'BuiltWith', 'hellaz-sitez-analyzer' ); ?></div>
                <div class="hsz-tool-description"><?php esc_html_e( 'Comprehensive technology profiling', 'hellaz-sitez-analyzer' ); ?></div>
                <a href="<?php echo esc_url( 'https://builtwith.com/' . parse_url( $url ?? '', PHP_URL_HOST ) ); ?>" 
                   target="_blank" rel="noopener noreferrer" class="hsz-tool-link">
                    <?php esc_html_e( 'View Report', 'hellaz-sitez-analyzer' ); ?>
                    <span class="hsz-external-icon">↗</span>
                </a>
            </div>
            
            <div class="hsz-tool-card">
                <div class="hsz-tool-name"><?php esc_html_e( 'Wappalyzer', 'hellaz-sitez-analyzer' ); ?></div>
                <div class="hsz-tool-description"><?php esc_html_e( 'Technology stack identification', 'hellaz-sitez-analyzer' ); ?></div>
                <a href="<?php echo esc_url( 'https://www.wappalyzer.com/lookup/' . urlencode( $url ?? '' ) ); ?>" 
                   target="_blank" rel="noopener noreferrer" class="hsz-tool-link">
                    <?php esc_html_e( 'Analyze', 'hellaz-sitez-analyzer' ); ?>
                    <span class="hsz-external-icon">↗</span>
                </a>
            </div>
        </div>
    </div>

</div>
