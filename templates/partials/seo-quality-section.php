<?php
/**
 * Template Partial: SEO Quality Section
 *
 * Displays comprehensive SEO quality analysis including metadata quality score,
 * grade assessment, SEO issues, and actionable recommendations.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $metadata Full metadata array containing metadata_quality
 * @var array $seo_quality Direct SEO quality data (alternative)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get SEO quality data from metadata or direct variable
$seo_data = $seo_quality ?? $metadata['metadata_quality'] ?? [];

// Skip section if no SEO quality data
if ( empty( $seo_data ) ) {
    return;
}
?>

<div class="hsz-seo-quality-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-seo"></span>
        <?php esc_html_e( 'SEO Quality Analysis', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- Overall SEO Grade Display -->
    <?php if ( isset( $seo_data['grade'] ) && isset( $seo_data['score'] ) ): ?>
        <div class="hsz-seo-grade-display">
            <div class="hsz-grade-container">
                <div class="hsz-grade-circle hsz-grade-<?php echo esc_attr( strtolower( $seo_data['grade'] ) ); ?>">
                    <div class="hsz-grade-letter"><?php echo esc_html( $seo_data['grade'] ); ?></div>
                    <div class="hsz-grade-score"><?php echo esc_html( $seo_data['score'] ); ?>/<?php echo esc_html( $seo_data['max_score'] ?? 100 ); ?></div>
                </div>
                
                <div class="hsz-grade-details">
                    <div class="hsz-grade-title"><?php esc_html_e( 'SEO Quality Grade', 'hellaz-sitez-analyzer' ); ?></div>
                    
                    <?php if ( isset( $seo_data['completeness'] ) ): ?>
                        <div class="hsz-completeness">
                            <span class="hsz-completeness-label"><?php esc_html_e( 'Completeness:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-completeness-value"><?php echo esc_html( $seo_data['completeness'] ); ?>%</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hsz-grade-description">
                        <?php
                        $grade = $seo_data['grade'];
                        $descriptions = [
                            'A+' => __( 'Outstanding SEO implementation with excellent metadata', 'hellaz-sitez-analyzer' ),
                            'A'  => __( 'Excellent SEO quality with comprehensive metadata', 'hellaz-sitez-analyzer' ),
                            'A-' => __( 'Very good SEO implementation with minor improvements needed', 'hellaz-sitez-analyzer' ),
                            'B+' => __( 'Good SEO foundation with room for enhancement', 'hellaz-sitez-analyzer' ),
                            'B'  => __( 'Decent SEO quality but needs significant improvements', 'hellaz-sitez-analyzer' ),
                            'B-' => __( 'Below average SEO quality requiring attention', 'hellaz-sitez-analyzer' ),
                            'C+' => __( 'Poor SEO implementation needs major improvements', 'hellaz-sitez-analyzer' ),
                            'C'  => __( 'Very poor SEO quality with critical issues', 'hellaz-sitez-analyzer' ),
                            'C-' => __( 'Extremely poor SEO implementation', 'hellaz-sitez-analyzer' ),
                            'D'  => __( 'Failing SEO quality - immediate action required', 'hellaz-sitez-analyzer' ),
                            'F'  => __( 'Critical SEO failure - complete overhaul needed', 'hellaz-sitez-analyzer' )
                        ];
                        echo esc_html( $descriptions[ $grade ] ?? __( 'SEO quality assessment completed', 'hellaz-sitez-analyzer' ) );
                        ?>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="hsz-progress-container">
                <div class="hsz-progress-bar">
                    <div class="hsz-progress-fill hsz-progress-<?php echo esc_attr( strtolower( $seo_data['grade'] ) ); ?>" 
                         style="width: <?php echo esc_attr( $seo_data['completeness'] ?? $seo_data['score'] ); ?>%">
                    </div>
                </div>
                <div class="hsz-progress-labels">
                    <span class="hsz-progress-start">0</span>
                    <span class="hsz-progress-end">100</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEO Components Breakdown -->
    <div class="hsz-seo-components">
        <h5><?php esc_html_e( 'SEO Components Analysis', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-components-grid">
            
            <!-- Title Analysis -->
            <?php 
            $title = $metadata['title'] ?? '';
            $title_length = strlen( $title );
            $title_status = '';
            $title_message = '';
            
            if ( empty( $title ) ) {
                $title_status = 'critical';
                $title_message = __( 'Missing title tag', 'hellaz-sitez-analyzer' );
                $title_score = 0;
            } elseif ( $title_length < 30 ) {
                $title_status = 'warning';
                $title_message = __( 'Title too short', 'hellaz-sitez-analyzer' );
                $title_score = 50;
            } elseif ( $title_length > 60 ) {
                $title_status = 'warning';
                $title_message = __( 'Title too long', 'hellaz-sitez-analyzer' );
                $title_score = 70;
            } else {
                $title_status = 'good';
                $title_message = __( 'Optimal title length', 'hellaz-sitez-analyzer' );
                $title_score = 100;
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $title_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">📝</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Title Tag', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( $title_score ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $title_message ); ?></div>
                <?php if ( ! empty( $title ) ): ?>
                    <div class="hsz-component-details">
                        <small><?php printf( esc_html__( '%d characters', 'hellaz-sitez-analyzer' ), $title_length ); ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Description Analysis -->
            <?php 
            $description = $metadata['description'] ?? '';
            $desc_length = strlen( $description );
            $desc_status = '';
            $desc_message = '';
            
            if ( empty( $description ) ) {
                $desc_status = 'critical';
                $desc_message = __( 'Missing meta description', 'hellaz-sitez-analyzer' );
                $desc_score = 0;
            } elseif ( $desc_length < 120 ) {
                $desc_status = 'warning';
                $desc_message = __( 'Description too short', 'hellaz-sitez-analyzer' );
                $desc_score = 60;
            } elseif ( $desc_length > 160 ) {
                $desc_status = 'warning';
                $desc_message = __( 'Description too long', 'hellaz-sitez-analyzer' );
                $desc_score = 70;
            } else {
                $desc_status = 'good';
                $desc_message = __( 'Optimal description length', 'hellaz-sitez-analyzer' );
                $desc_score = 100;
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $desc_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">📄</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Meta Description', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( $desc_score ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $desc_message ); ?></div>
                <?php if ( ! empty( $description ) ): ?>
                    <div class="hsz-component-details">
                        <small><?php printf( esc_html__( '%d characters', 'hellaz-sitez-analyzer' ), $desc_length ); ?></small>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Open Graph Analysis -->
            <?php 
            $og_tags = $metadata['og'] ?? [];
            $og_score = 0;
            $og_status = '';
            $og_message = '';
            
            $required_og = [ 'title', 'description', 'image', 'url' ];
            $present_og = array_intersect( $required_og, array_keys( $og_tags ) );
            $og_score = ( count( $present_og ) / count( $required_og ) ) * 100;
            
            if ( $og_score >= 75 ) {
                $og_status = 'good';
                $og_message = __( 'Good Open Graph implementation', 'hellaz-sitez-analyzer' );
            } elseif ( $og_score >= 50 ) {
                $og_status = 'warning';
                $og_message = __( 'Incomplete Open Graph tags', 'hellaz-sitez-analyzer' );
            } else {
                $og_status = 'critical';
                $og_message = __( 'Poor Open Graph implementation', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $og_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">📱</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Open Graph', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( round( $og_score ) ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $og_message ); ?></div>
                <div class="hsz-component-details">
                    <small><?php printf( esc_html__( '%d of %d tags present', 'hellaz-sitez-analyzer' ), count( $present_og ), count( $required_og ) ); ?></small>
                </div>
            </div>

            <!-- Twitter Card Analysis -->
            <?php 
            $twitter_tags = $metadata['twitter'] ?? [];
            $twitter_score = 0;
            $twitter_status = '';
            $twitter_message = '';
            
            $required_twitter = [ 'card', 'title', 'description' ];
            $present_twitter = array_intersect( $required_twitter, array_keys( $twitter_tags ) );
            $twitter_score = ( count( $present_twitter ) / count( $required_twitter ) ) * 100;
            
            if ( $twitter_score >= 75 ) {
                $twitter_status = 'good';
                $twitter_message = __( 'Good Twitter Card setup', 'hellaz-sitez-analyzer' );
            } elseif ( $twitter_score >= 50 ) {
                $twitter_status = 'warning';
                $twitter_message = __( 'Incomplete Twitter Cards', 'hellaz-sitez-analyzer' );
            } else {
                $twitter_status = 'critical';
                $twitter_message = __( 'Missing Twitter Card tags', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $twitter_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">🐦</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Twitter Cards', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( round( $twitter_score ) ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $twitter_message ); ?></div>
                <div class="hsz-component-details">
                    <small><?php printf( esc_html__( '%d of %d tags present', 'hellaz-sitez-analyzer' ), count( $present_twitter ), count( $required_twitter ) ); ?></small>
                </div>
            </div>

            <!-- Canonical URL Analysis -->
            <?php 
            $canonical = $metadata['canonical_url'] ?? '';
            $canonical_status = '';
            $canonical_message = '';
            $canonical_score = 0;
            
            if ( empty( $canonical ) ) {
                $canonical_status = 'warning';
                $canonical_message = __( 'Missing canonical URL', 'hellaz-sitez-analyzer' );
                $canonical_score = 0;
            } else {
                $canonical_status = 'good';
                $canonical_message = __( 'Canonical URL present', 'hellaz-sitez-analyzer' );
                $canonical_score = 100;
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $canonical_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">🔗</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Canonical URL', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( $canonical_score ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $canonical_message ); ?></div>
            </div>

            <!-- Structured Data Analysis -->
            <?php 
            $structured_data = $metadata['structured_data'] ?? [];
            $structured_count = count( $structured_data );
            $structured_status = '';
            $structured_message = '';
            $structured_score = 0;
            
            if ( $structured_count === 0 ) {
                $structured_status = 'warning';
                $structured_message = __( 'No structured data found', 'hellaz-sitez-analyzer' );
                $structured_score = 0;
            } elseif ( $structured_count < 3 ) {
                $structured_status = 'average';
                $structured_message = __( 'Limited structured data', 'hellaz-sitez-analyzer' );
                $structured_score = 60;
            } else {
                $structured_status = 'good';
                $structured_message = __( 'Good structured data coverage', 'hellaz-sitez-analyzer' );
                $structured_score = 100;
            }
            ?>
            
            <div class="hsz-component-card hsz-component-<?php echo esc_attr( $structured_status ); ?>">
                <div class="hsz-component-header">
                    <div class="hsz-component-icon">📊</div>
                    <div class="hsz-component-title"><?php esc_html_e( 'Structured Data', 'hellaz-sitez-analyzer' ); ?></div>
                    <div class="hsz-component-score"><?php echo esc_html( $structured_score ); ?>%</div>
                </div>
                <div class="hsz-component-status"><?php echo esc_html( $structured_message ); ?></div>
                <div class="hsz-component-details">
                    <small><?php printf( esc_html__( '%d items found', 'hellaz-sitez-analyzer' ), $structured_count ); ?></small>
                </div>
            </div>

        </div>
    </div>

    <!-- SEO Issues -->
    <?php if ( ! empty( $seo_data['issues'] ) ): ?>
        <div class="hsz-seo-issues">
            <h5>
                <span class="hsz-issues-icon">⚠️</span>
                <?php esc_html_e( 'SEO Issues Found', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-issues-list">
                <?php foreach ( array_slice( $seo_data['issues'], 0, 8 ) as $issue ): ?>
                    <div class="hsz-issue-item">
                        <div class="hsz-issue-indicator">
                            <span class="hsz-issue-dot"></span>
                        </div>
                        <div class="hsz-issue-content">
                            <div class="hsz-issue-text"><?php echo esc_html( $issue ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ( count( $seo_data['issues'] ) > 8 ): ?>
                    <div class="hsz-more-issues">
                        <button type="button" class="hsz-show-more-issues">
                            <?php printf( esc_html__( 'Show %d more issues', 'hellaz-sitez-analyzer' ), count( $seo_data['issues'] ) - 8 ); ?>
                        </button>
                        <div class="hsz-additional-issues" style="display: none;">
                            <?php foreach ( array_slice( $seo_data['issues'], 8 ) as $issue ): ?>
                                <div class="hsz-issue-item">
                                    <div class="hsz-issue-indicator">
                                        <span class="hsz-issue-dot"></span>
                                    </div>
                                    <div class="hsz-issue-content">
                                        <div class="hsz-issue-text"><?php echo esc_html( $issue ); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEO Recommendations -->
    <?php if ( ! empty( $seo_data['recommendations'] ) ): ?>
        <div class="hsz-seo-recommendations">
            <h5>
                <span class="hsz-recommendations-icon">💡</span>
                <?php esc_html_e( 'SEO Recommendations', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-recommendations-list">
                <?php foreach ( array_slice( $seo_data['recommendations'], 0, 6 ) as $index => $recommendation ): ?>
                    <div class="hsz-recommendation-item">
                        <div class="hsz-recommendation-priority">
                            <span class="hsz-priority-number"><?php echo esc_html( $index + 1 ); ?></span>
                        </div>
                        <div class="hsz-recommendation-content">
                            <div class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ( count( $seo_data['recommendations'] ) > 6 ): ?>
                    <div class="hsz-more-recommendations">
                        <button type="button" class="hsz-show-more-recommendations">
                            <?php printf( esc_html__( 'Show %d more recommendations', 'hellaz-sitez-analyzer' ), count( $seo_data['recommendations'] ) - 6 ); ?>
                        </button>
                        <div class="hsz-additional-recommendations" style="display: none;">
                            <?php foreach ( array_slice( $seo_data['recommendations'], 6 ) as $index => $recommendation ): ?>
                                <div class="hsz-recommendation-item">
                                    <div class="hsz-recommendation-priority">
                                        <span class="hsz-priority-number"><?php echo esc_html( $index + 7 ); ?></span>
                                    </div>
                                    <div class="hsz-recommendation-content">
                                        <div class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- SEO Action Plan -->
    <div class="hsz-seo-action-plan">
        <h5><?php esc_html_e( 'Recommended Action Plan', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-action-steps">
            <?php
            $current_score = $seo_data['score'] ?? 0;
            $action_plan = [];
            
            if ( $current_score < 40 ) {
                $action_plan[] = [
                    'priority' => 'high',
                    'title' => __( 'Fix Critical Issues First', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Address missing title tags, meta descriptions, and basic metadata', 'hellaz-sitez-analyzer' ),
                    'timeframe' => __( 'Immediate (1-2 hours)', 'hellaz-sitez-analyzer' )
                ];
            }
            
            if ( $current_score < 70 ) {
                $action_plan[] = [
                    'priority' => 'medium',
                    'title' => __( 'Implement Social Media Tags', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Add Open Graph and Twitter Card tags for better social sharing', 'hellaz-sitez-analyzer' ),
                    'timeframe' => __( 'Short term (1-2 days)', 'hellaz-sitez-analyzer' )
                ];
            }
            
            if ( empty( $metadata['structured_data'] ) ) {
                $action_plan[] = [
                    'priority' => 'medium',
                    'title' => __( 'Add Structured Data', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Implement JSON-LD structured data for better search understanding', 'hellaz-sitez-analyzer' ),
                    'timeframe' => __( 'Medium term (1 week)', 'hellaz-sitez-analyzer' )
                ];
            }
            
            $action_plan[] = [
                'priority' => 'low',
                'title' => __( 'Monitor and Optimize', 'hellaz-sitez-analyzer' ),
                'description' => __( 'Regular monitoring and continuous optimization of SEO elements', 'hellaz-sitez-analyzer' ),
                'timeframe' => __( 'Ongoing', 'hellaz-sitez-analyzer' )
            ];
            ?>
            
            <?php foreach ( $action_plan as $index => $step ): ?>
                <div class="hsz-action-step hsz-priority-<?php echo esc_attr( $step['priority'] ); ?>">
                    <div class="hsz-step-number"><?php echo esc_html( $index + 1 ); ?></div>
                    <div class="hsz-step-content">
                        <div class="hsz-step-title"><?php echo esc_html( $step['title'] ); ?></div>
                        <div class="hsz-step-description"><?php echo esc_html( $step['description'] ); ?></div>
                        <div class="hsz-step-timeframe">
                            <span class="hsz-timeframe-label"><?php esc_html_e( 'Timeframe:', 'hellaz-sitez-analyzer' ); ?></span>
                            <span class="hsz-timeframe-value"><?php echo esc_html( $step['timeframe'] ); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
// Show more issues functionality
document.addEventListener('DOMContentLoaded', function() {
    const showMoreIssuesBtn = document.querySelector('.hsz-show-more-issues');
    if (showMoreIssuesBtn) {
        showMoreIssuesBtn.addEventListener('click', function() {
            const additionalIssues = document.querySelector('.hsz-additional-issues');
            if (additionalIssues.style.display === 'none') {
                additionalIssues.style.display = 'block';
                this.textContent = '<?php esc_html_e( 'Show fewer issues', 'hellaz-sitez-analyzer' ); ?>';
            } else {
                additionalIssues.style.display = 'none';
                this.textContent = this.textContent.replace('<?php esc_html_e( 'Show fewer issues', 'hellaz-sitez-analyzer' ); ?>', '<?php printf( esc_html__( 'Show %d more issues', 'hellaz-sitez-analyzer' ), count( $seo_data['issues'] ) - 8 ); ?>');
            }
        });
    }

    // Show more recommendations functionality
    const showMoreRecsBtn = document.querySelector('.hsz-show-more-recommendations');
    if (showMoreRecsBtn) {
        showMoreRecsBtn.addEventListener('click', function() {
            const additionalRecs = document.querySelector('.hsz-additional-recommendations');
            if (additionalRecs.style.display === 'none') {
                additionalRecs.style.display = 'block';
                this.textContent = '<?php esc_html_e( 'Show fewer recommendations', 'hellaz-sitez-analyzer' ); ?>';
            } else {
                additionalRecs.style.display = 'none';
                this.textContent = this.textContent.replace('<?php esc_html_e( 'Show fewer recommendations', 'hellaz-sitez-analyzer' ); ?>', '<?php printf( esc_html__( 'Show %d more recommendations', 'hellaz-sitez-analyzer' ), count( $seo_data['recommendations'] ) - 6 ); ?>');
            }
        });
    }
});
</script>
