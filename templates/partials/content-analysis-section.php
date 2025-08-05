<?php
/**
 * Template Partial: Content Analysis Section
 *
 * Displays comprehensive content analysis including word count, heading structure,
 * readability score, content quality metrics, and SEO content recommendations.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $metadata Full metadata array containing content_analysis
 * @var array $content_analysis Direct content analysis data (alternative)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get content analysis data from metadata or direct variable
$content_data = $content_analysis ?? $metadata['content_analysis'] ?? [];

// Skip section if no content analysis data
if ( empty( $content_data ) ) {
    return;
}
?>

<div class="hsz-content-analysis-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-content"></span>
        <?php esc_html_e( 'Content Analysis', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- Content Overview Stats -->
    <div class="hsz-content-overview">
        <div class="hsz-content-stats-grid">
            
            <!-- Word Count -->
            <?php if ( isset( $content_data['word_count'] ) ): ?>
                <div class="hsz-stat-item">
                    <div class="hsz-stat-number"><?php echo esc_html( number_format( $content_data['word_count'] ) ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Words', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-stat-indicator">
                        <?php 
                        $word_status = '';
                        if ( $content_data['word_count'] < 300 ) {
                            $word_status = 'low';
                            $word_message = __( 'Consider adding more content', 'hellaz-sitez-analyzer' );
                        } elseif ( $content_data['word_count'] > 2000 ) {
                            $word_status = 'high';
                            $word_message = __( 'Very comprehensive content', 'hellaz-sitez-analyzer' );
                        } else {
                            $word_status = 'good';
                            $word_message = __( 'Good content length', 'hellaz-sitez-analyzer' );
                        }
                        ?>
                        <span class="hsz-indicator hsz-indicator-<?php echo esc_attr( $word_status ); ?>">
                            <?php echo esc_html( $word_message ); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Paragraph Count -->
            <?php if ( isset( $content_data['paragraph_count'] ) ): ?>
                <div class="hsz-stat-item">
                    <div class="hsz-stat-number"><?php echo esc_html( number_format( $content_data['paragraph_count'] ) ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Paragraphs', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            <?php endif; ?>

            <!-- List Count -->
            <?php if ( isset( $content_data['list_count'] ) ): ?>
                <div class="hsz-stat-item">
                    <div class="hsz-stat-number"><?php echo esc_html( number_format( $content_data['list_count'] ) ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Lists', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            <?php endif; ?>

            <!-- Table Count -->
            <?php if ( isset( $content_data['table_count'] ) ): ?>
                <div class="hsz-stat-item">
                    <div class="hsz-stat-number"><?php echo esc_html( number_format( $content_data['table_count'] ) ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Tables', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Heading Structure Analysis -->
    <?php if ( ! empty( $content_data['heading_structure'] ) ): ?>
        <div class="hsz-heading-analysis">
            <h5><?php esc_html_e( 'Heading Structure', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-heading-structure">
                <?php 
                $heading_levels = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
                $total_headings = array_sum( $content_data['heading_structure'] );
                ?>
                
                <div class="hsz-heading-overview">
                    <div class="hsz-total-headings">
                        <span class="hsz-heading-count"><?php echo esc_html( $total_headings ); ?></span>
                        <span class="hsz-heading-label"><?php esc_html_e( 'Total Headings', 'hellaz-sitez-analyzer' ); ?></span>
                    </div>
                </div>

                <div class="hsz-heading-breakdown">
                    <?php foreach ( $heading_levels as $level ): ?>
                        <?php $count = $content_data['heading_structure'][ $level ] ?? 0; ?>
                        <div class="hsz-heading-level <?php echo $count > 0 ? 'hsz-heading-present' : 'hsz-heading-missing'; ?>">
                            <div class="hsz-heading-tag"><?php echo esc_html( strtoupper( $level ) ); ?></div>
                            <div class="hsz-heading-count"><?php echo esc_html( $count ); ?></div>
                            <div class="hsz-heading-bar">
                                <?php if ( $total_headings > 0 ): ?>
                                    <div class="hsz-heading-fill" style="width: <?php echo esc_attr( ( $count / $total_headings ) * 100 ); ?>%"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Heading Structure Recommendations -->
                <div class="hsz-heading-recommendations">
                    <?php
                    $h1_count = $content_data['heading_structure']['h1'] ?? 0;
                    $h2_count = $content_data['heading_structure']['h2'] ?? 0;
                    ?>
                    
                    <?php if ( $h1_count === 0 ): ?>
                        <div class="hsz-heading-issue">
                            <span class="hsz-issue-icon">⚠️</span>
                            <span class="hsz-issue-text"><?php esc_html_e( 'Missing H1 tag - add a main heading', 'hellaz-sitez-analyzer' ); ?></span>
                        </div>
                    <?php elseif ( $h1_count > 1 ): ?>
                        <div class="hsz-heading-issue">
                            <span class="hsz-issue-icon">⚠️</span>
                            <span class="hsz-issue-text"><?php esc_html_e( 'Multiple H1 tags found - use only one H1 per page', 'hellaz-sitez-analyzer' ); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="hsz-heading-good">
                            <span class="hsz-good-icon">✅</span>
                            <span class="hsz-good-text"><?php esc_html_e( 'Proper H1 usage detected', 'hellaz-sitez-analyzer' ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $h2_count === 0 && $total_headings > 1 ): ?>
                        <div class="hsz-heading-suggestion">
                            <span class="hsz-suggestion-icon">💡</span>
                            <span class="hsz-suggestion-text"><?php esc_html_e( 'Consider using H2 tags to structure your content', 'hellaz-sitez-analyzer' ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Readability Analysis -->
    <?php if ( isset( $content_data['readability_score'] ) ): ?>
        <div class="hsz-readability-analysis">
            <h5><?php esc_html_e( 'Readability Analysis', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-readability-score">
                <div class="hsz-score-circle">
                    <div class="hsz-score-value"><?php echo esc_html( $content_data['readability_score'] ); ?></div>
                    <div class="hsz-score-max">/100</div>
                </div>
                
                <div class="hsz-readability-details">
                    <?php
                    $score = $content_data['readability_score'];
                    if ( $score >= 90 ) {
                        $level = __( 'Very Easy', 'hellaz-sitez-analyzer' );
                        $description = __( '5th grade level', 'hellaz-sitez-analyzer' );
                        $status = 'excellent';
                    } elseif ( $score >= 80 ) {
                        $level = __( 'Easy', 'hellaz-sitez-analyzer' );
                        $description = __( '6th grade level', 'hellaz-sitez-analyzer' );
                        $status = 'good';
                    } elseif ( $score >= 70 ) {
                        $level = __( 'Fairly Easy', 'hellaz-sitez-analyzer' );
                        $description = __( '7th grade level', 'hellaz-sitez-analyzer' );
                        $status = 'good';
                    } elseif ( $score >= 60 ) {
                        $level = __( 'Standard', 'hellaz-sitez-analyzer' );
                        $description = __( '8th-9th grade level', 'hellaz-sitez-analyzer' );
                        $status = 'average';
                    } elseif ( $score >= 50 ) {
                        $level = __( 'Fairly Difficult', 'hellaz-sitez-analyzer' );
                        $description = __( '10th-12th grade level', 'hellaz-sitez-analyzer' );
                        $status = 'average';
                    } elseif ( $score >= 30 ) {
                        $level = __( 'Difficult', 'hellaz-sitez-analyzer' );
                        $description = __( 'College level', 'hellaz-sitez-analyzer' );
                        $status = 'difficult';
                    } else {
                        $level = __( 'Very Difficult', 'hellaz-sitez-analyzer' );
                        $description = __( 'Graduate level', 'hellaz-sitez-analyzer' );
                        $status = 'difficult';
                    }
                    ?>
                    
                    <div class="hsz-readability-level hsz-readability-<?php echo esc_attr( $status ); ?>">
                        <div class="hsz-level-name"><?php echo esc_html( $level ); ?></div>
                        <div class="hsz-level-description"><?php echo esc_html( $description ); ?></div>
                    </div>

                    <div class="hsz-readability-bar">
                        <div class="hsz-readability-fill hsz-fill-<?php echo esc_attr( $status ); ?>" style="width: <?php echo esc_attr( $score ); ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Readability Recommendations -->
            <div class="hsz-readability-tips">
                <?php if ( $score < 60 ): ?>
                    <div class="hsz-readability-suggestions">
                        <h6><?php esc_html_e( 'Improve Readability:', 'hellaz-sitez-analyzer' ); ?></h6>
                        <ul class="hsz-suggestions-list">
                            <li><?php esc_html_e( 'Use shorter sentences (aim for 15-20 words)', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Break up long paragraphs', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Use simpler words when possible', 'hellaz-sitez-analyzer' ); ?></li>
                            <li><?php esc_html_e( 'Add more headings to structure content', 'hellaz-sitez-analyzer' ); ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Content Quality Metrics -->
    <div class="hsz-content-quality">
        <h5><?php esc_html_e( 'Content Quality Indicators', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-quality-indicators">
            
            <!-- Content Length Assessment -->
            <?php if ( isset( $content_data['word_count'] ) ): ?>
                <div class="hsz-quality-item">
                    <div class="hsz-quality-metric">
                        <span class="hsz-metric-name"><?php esc_html_e( 'Content Length', 'hellaz-sitez-analyzer' ); ?></span>
                        <?php
                        $word_count = $content_data['word_count'];
                        if ( $word_count < 300 ) {
                            $length_status = 'poor';
                            $length_message = __( 'Too short for SEO', 'hellaz-sitez-analyzer' );
                        } elseif ( $word_count >= 300 && $word_count < 600 ) {
                            $length_status = 'average';
                            $length_message = __( 'Acceptable length', 'hellaz-sitez-analyzer' );
                        } elseif ( $word_count >= 600 && $word_count < 2000 ) {
                            $length_status = 'good';
                            $length_message = __( 'Good content length', 'hellaz-sitez-analyzer' );
                        } else {
                            $length_status = 'excellent';
                            $length_message = __( 'Comprehensive content', 'hellaz-sitez-analyzer' );
                        }
                        ?>
                        <span class="hsz-metric-status hsz-status-<?php echo esc_attr( $length_status ); ?>">
                            <?php echo esc_html( $length_message ); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Structure Assessment -->
            <?php if ( ! empty( $content_data['heading_structure'] ) ): ?>
                <div class="hsz-quality-item">
                    <div class="hsz-quality-metric">
                        <span class="hsz-metric-name"><?php esc_html_e( 'Content Structure', 'hellaz-sitez-analyzer' ); ?></span>
                        <?php
                        $has_h1 = ( $content_data['heading_structure']['h1'] ?? 0 ) === 1;
                        $has_h2 = ( $content_data['heading_structure']['h2'] ?? 0 ) > 0;
                        
                        if ( $has_h1 && $has_h2 ) {
                            $structure_status = 'good';
                            $structure_message = __( 'Well structured', 'hellaz-sitez-analyzer' );
                        } elseif ( $has_h1 ) {
                            $structure_status = 'average';
                            $structure_message = __( 'Basic structure', 'hellaz-sitez-analyzer' );
                        } else {
                            $structure_status = 'poor';
                            $structure_message = __( 'Poor structure', 'hellaz-sitez-analyzer' );
                        }
                        ?>
                        <span class="hsz-metric-status hsz-status-<?php echo esc_attr( $structure_status ); ?>">
                            <?php echo esc_html( $structure_message ); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Content Variety -->
            <?php 
            $content_variety_score = 0;
            $variety_elements = [];
            
            if ( ( $content_data['list_count'] ?? 0 ) > 0 ) {
                $content_variety_score += 25;
                $variety_elements[] = __( 'Lists', 'hellaz-sitez-analyzer' );
            }
            if ( ( $content_data['table_count'] ?? 0 ) > 0 ) {
                $content_variety_score += 25;
                $variety_elements[] = __( 'Tables', 'hellaz-sitez-analyzer' );
            }
            if ( ( $content_data['paragraph_count'] ?? 0 ) > 3 ) {
                $content_variety_score += 25;
                $variety_elements[] = __( 'Multiple paragraphs', 'hellaz-sitez-analyzer' );
            }
            if ( count( $content_data['heading_structure'] ?? [] ) > 2 ) {
                $content_variety_score += 25;
                $variety_elements[] = __( 'Multiple heading levels', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <div class="hsz-quality-item">
                <div class="hsz-quality-metric">
                    <span class="hsz-metric-name"><?php esc_html_e( 'Content Variety', 'hellaz-sitez-analyzer' ); ?></span>
                    <?php
                    if ( $content_variety_score >= 75 ) {
                        $variety_status = 'excellent';
                        $variety_message = __( 'Rich content variety', 'hellaz-sitez-analyzer' );
                    } elseif ( $content_variety_score >= 50 ) {
                        $variety_status = 'good';
                        $variety_message = __( 'Good content variety', 'hellaz-sitez-analyzer' );
                    } elseif ( $content_variety_score >= 25 ) {
                        $variety_status = 'average';
                        $variety_message = __( 'Limited variety', 'hellaz-sitez-analyzer' );
                    } else {
                        $variety_status = 'poor';
                        $variety_message = __( 'Lacks variety', 'hellaz-sitez-analyzer' );
                    }
                    ?>
                    <span class="hsz-metric-status hsz-status-<?php echo esc_attr( $variety_status ); ?>">
                        <?php echo esc_html( $variety_message ); ?>
                    </span>
                </div>
                
                <?php if ( ! empty( $variety_elements ) ): ?>
                    <div class="hsz-variety-elements">
                        <small><?php echo esc_html( implode( ', ', $variety_elements ) ); ?></small>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Content Recommendations -->
    <div class="hsz-content-recommendations">
        <h5><?php esc_html_e( 'Content Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <ul class="hsz-recommendations-list">
            <?php
            $recommendations = [];
            
            // Word count recommendations
            if ( ( $content_data['word_count'] ?? 0 ) < 300 ) {
                $recommendations[] = __( 'Increase content length to at least 300 words for better SEO', 'hellaz-sitez-analyzer' );
            }
            
            // Heading recommendations
            if ( ( $content_data['heading_structure']['h1'] ?? 0 ) === 0 ) {
                $recommendations[] = __( 'Add an H1 heading as your main page title', 'hellaz-sitez-analyzer' );
            }
            
            if ( ( $content_data['heading_structure']['h2'] ?? 0 ) === 0 && ( $content_data['word_count'] ?? 0 ) > 300 ) {
                $recommendations[] = __( 'Use H2 headings to break up your content into sections', 'hellaz-sitez-analyzer' );
            }
            
            // Structure recommendations
            if ( ( $content_data['paragraph_count'] ?? 0 ) < 3 && ( $content_data['word_count'] ?? 0 ) > 200 ) {
                $recommendations[] = __( 'Break up long text into multiple paragraphs', 'hellaz-sitez-analyzer' );
            }
            
            // Readability recommendations
            if ( ( $content_data['readability_score'] ?? 100 ) < 60 ) {
                $recommendations[] = __( 'Improve readability by using shorter sentences and simpler words', 'hellaz-sitez-analyzer' );
            }
            
            // Variety recommendations
            if ( ( $content_data['list_count'] ?? 0 ) === 0 && ( $content_data['word_count'] ?? 0 ) > 500 ) {
                $recommendations[] = __( 'Consider adding lists to make content more scannable', 'hellaz-sitez-analyzer' );
            }
            
            // Default positive message if no issues
            if ( empty( $recommendations ) ) {
                $recommendations[] = __( 'Your content structure and quality look good!', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <?php foreach ( array_slice( $recommendations, 0, 5 ) as $recommendation ): ?>
                <li class="hsz-recommendation-item">
                    <span class="hsz-recommendation-icon">💡</span>
                    <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
