<?php
/**
 * Template Partial: Structured Data Section
 *
 * Displays structured data analysis including JSON-LD, Microdata, Schema.org markup,
 * and rich snippets potential with detailed breakdown of found structured data.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $metadata Full metadata array containing structured_data
 * @var array $structured_data Direct structured data (alternative)
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get structured data from metadata or direct variable
$structured_data = $structured_data ?? $metadata['structured_data'] ?? [];

// Skip section if no structured data
if ( empty( $structured_data ) ) {
    return;
}
?>

<div class="hsz-structured-data-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-schema"></span>
        <?php esc_html_e( 'Structured Data Analysis', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- Structured Data Overview -->
    <div class="hsz-structured-overview">
        <div class="hsz-structured-stats">
            <div class="hsz-stat-card">
                <div class="hsz-stat-number"><?php echo esc_html( count( $structured_data ) ); ?></div>
                <div class="hsz-stat-label"><?php esc_html_e( 'Structured Data Items', 'hellaz-sitez-analyzer' ); ?></div>
            </div>
            
            <?php
            // Count different types
            $json_ld_count = 0;
            $microdata_count = 0;
            $schema_types = [];
            
            foreach ( $structured_data as $item ) {
                if ( isset( $item['type'] ) ) {
                    if ( $item['type'] === 'json-ld' ) {
                        $json_ld_count++;
                        // Extract schema types from JSON-LD
                        if ( isset( $item['data']['@type'] ) ) {
                            $type = is_array( $item['data']['@type'] ) ? $item['data']['@type'][0] : $item['data']['@type'];
                            $schema_types[] = $type;
                        }
                    } elseif ( $item['type'] === 'microdata' ) {
                        $microdata_count++;
                        // Extract schema types from Microdata
                        if ( isset( $item['itemtype'] ) ) {
                            $type = basename( $item['itemtype'] );
                            $schema_types[] = $type;
                        }
                    }
                }
            }
            
            $schema_types = array_unique( $schema_types );
            ?>
            
            <?php if ( $json_ld_count > 0 ): ?>
                <div class="hsz-stat-card hsz-stat-good">
                    <div class="hsz-stat-number"><?php echo esc_html( $json_ld_count ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'JSON-LD Items', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if ( $microdata_count > 0 ): ?>
                <div class="hsz-stat-card hsz-stat-average">
                    <div class="hsz-stat-number"><?php echo esc_html( $microdata_count ); ?></div>
                    <div class="hsz-stat-label"><?php esc_html_e( 'Microdata Items', 'hellaz-sitez-analyzer' ); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="hsz-stat-card">
                <div class="hsz-stat-number"><?php echo esc_html( count( $schema_types ) ); ?></div>
                <div class="hsz-stat-label"><?php esc_html_e( 'Schema Types', 'hellaz-sitez-analyzer' ); ?></div>
            </div>
        </div>

        <!-- Schema Types Overview -->
        <?php if ( ! empty( $schema_types ) ): ?>
            <div class="hsz-schema-types-overview">
                <h5><?php esc_html_e( 'Schema.org Types Found', 'hellaz-sitez-analyzer' ); ?></h5>
                <div class="hsz-schema-types-list">
                    <?php foreach ( $schema_types as $type ): ?>
                        <span class="hsz-schema-type-tag"><?php echo esc_html( $type ); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rich Snippets Potential -->
    <div class="hsz-rich-snippets-potential">
        <h5><?php esc_html_e( 'Rich Snippets Potential', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-rich-snippets-analysis">
            <?php
            // Analyze rich snippet potential based on schema types
            $rich_snippet_types = [
                'Article' => [
                    'name' => __( 'Article Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Enhanced search results with publication date, author, and headline', 'hellaz-sitez-analyzer' ),
                    'icon' => '📰'
                ],
                'Product' => [
                    'name' => __( 'Product Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Product details with price, availability, and ratings', 'hellaz-sitez-analyzer' ),
                    'icon' => '🛍️'
                ],
                'Review' => [
                    'name' => __( 'Review Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Star ratings and review information in search results', 'hellaz-sitez-analyzer' ),
                    'icon' => '⭐'
                ],
                'Recipe' => [
                    'name' => __( 'Recipe Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Cooking time, ratings, and ingredient information', 'hellaz-sitez-analyzer' ),
                    'icon' => '🍳'
                ],
                'Event' => [
                    'name' => __( 'Event Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Event dates, location, and ticket information', 'hellaz-sitez-analyzer' ),
                    'icon' => '📅'
                ],
                'Organization' => [
                    'name' => __( 'Organization Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Company information and contact details', 'hellaz-sitez-analyzer' ),
                    'icon' => '🏢'
                ],
                'Person' => [
                    'name' => __( 'Person Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Personal information and social profiles', 'hellaz-sitez-analyzer' ),
                    'icon' => '👤'
                ],
                'LocalBusiness' => [
                    'name' => __( 'Local Business Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Business hours, location, and contact information', 'hellaz-sitez-analyzer' ),
                    'icon' => '🏪'
                ],
                'FAQ' => [
                    'name' => __( 'FAQ Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Frequently asked questions in search results', 'hellaz-sitez-analyzer' ),
                    'icon' => '❓'
                ],
                'BreadcrumbList' => [
                    'name' => __( 'Breadcrumb Rich Snippets', 'hellaz-sitez-analyzer' ),
                    'description' => __( 'Navigation breadcrumbs in search results', 'hellaz-sitez-analyzer' ),
                    'icon' => '🧭'
                ]
            ];
            
            $found_types = array_intersect( $schema_types, array_keys( $rich_snippet_types ) );
            $potential_types = array_diff( array_keys( $rich_snippet_types ), $schema_types );
            ?>
            
            <!-- Currently Enabled Rich Snippets -->
            <?php if ( ! empty( $found_types ) ): ?>
                <div class="hsz-rich-snippets-enabled">
                    <h6 class="hsz-snippets-subtitle">
                        <span class="hsz-subtitle-icon">✅</span>
                        <?php esc_html_e( 'Enabled Rich Snippets', 'hellaz-sitez-analyzer' ); ?>
                    </h6>
                    <div class="hsz-snippets-grid">
                        <?php foreach ( $found_types as $type ): ?>
                            <div class="hsz-snippet-card hsz-snippet-enabled">
                                <div class="hsz-snippet-icon"><?php echo esc_html( $rich_snippet_types[ $type ]['icon'] ); ?></div>
                                <div class="hsz-snippet-content">
                                    <div class="hsz-snippet-name"><?php echo esc_html( $rich_snippet_types[ $type ]['name'] ); ?></div>
                                    <div class="hsz-snippet-description"><?php echo esc_html( $rich_snippet_types[ $type ]['description'] ); ?></div>
                                </div>
                                <div class="hsz-snippet-status hsz-status-enabled">
                                    <?php esc_html_e( 'Active', 'hellaz-sitez-analyzer' ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Potential Rich Snippets -->
            <?php if ( ! empty( $potential_types ) ): ?>
                <div class="hsz-rich-snippets-potential">
                    <h6 class="hsz-snippets-subtitle">
                        <span class="hsz-subtitle-icon">💡</span>
                        <?php esc_html_e( 'Potential Rich Snippets', 'hellaz-sitez-analyzer' ); ?>
                    </h6>
                    <div class="hsz-snippets-grid">
                        <?php foreach ( array_slice( $potential_types, 0, 6 ) as $type ): ?>
                            <div class="hsz-snippet-card hsz-snippet-potential">
                                <div class="hsz-snippet-icon"><?php echo esc_html( $rich_snippet_types[ $type ]['icon'] ); ?></div>
                                <div class="hsz-snippet-content">
                                    <div class="hsz-snippet-name"><?php echo esc_html( $rich_snippet_types[ $type ]['name'] ); ?></div>
                                    <div class="hsz-snippet-description"><?php echo esc_html( $rich_snippet_types[ $type ]['description'] ); ?></div>
                                </div>
                                <div class="hsz-snippet-status hsz-status-potential">
                                    <?php esc_html_e( 'Available', 'hellaz-sitez-analyzer' ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Structured Data Analysis -->
    <div class="hsz-structured-details">
        <h5><?php esc_html_e( 'Structured Data Details', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-structured-items">
            <?php foreach ( array_slice( $structured_data, 0, 10 ) as $index => $item ): ?>
                <div class="hsz-structured-item">
                    <div class="hsz-item-header">
                        <div class="hsz-item-type">
                            <span class="hsz-type-badge hsz-type-<?php echo esc_attr( $item['type'] ?? 'unknown' ); ?>">
                                <?php echo esc_html( strtoupper( $item['type'] ?? 'Unknown' ) ); ?>
                            </span>
                            
                            <?php if ( isset( $item['data']['@type'] ) ): ?>
                                <span class="hsz-schema-type">
                                    <?php 
                                    $schema_type = is_array( $item['data']['@type'] ) ? $item['data']['@type'][0] : $item['data']['@type'];
                                    echo esc_html( $schema_type );
                                    ?>
                                </span>
                            <?php elseif ( isset( $item['itemtype'] ) ): ?>
                                <span class="hsz-schema-type">
                                    <?php echo esc_html( basename( $item['itemtype'] ) ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <button class="hsz-toggle-details" type="button" 
                                aria-expanded="false" 
                                aria-controls="hsz-details-<?php echo esc_attr( $index ); ?>">
                            <?php esc_html_e( 'View Details', 'hellaz-sitez-analyzer' ); ?>
                            <span class="hsz-toggle-icon">▼</span>
                        </button>
                    </div>
                    
                    <div class="hsz-item-summary">
                        <?php if ( $item['type'] === 'json-ld' && isset( $item['data'] ) ): ?>
                            <?php $data = $item['data']; ?>
                            
                            <!-- Common JSON-LD properties -->
                            <?php if ( isset( $data['name'] ) ): ?>
                                <div class="hsz-summary-property">
                                    <strong><?php esc_html_e( 'Name:', 'hellaz-sitez-analyzer' ); ?></strong>
                                    <?php echo esc_html( wp_trim_words( $data['name'], 10 ) ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( isset( $data['headline'] ) ): ?>
                                <div class="hsz-summary-property">
                                    <strong><?php esc_html_e( 'Headline:', 'hellaz-sitez-analyzer' ); ?></strong>
                                    <?php echo esc_html( wp_trim_words( $data['headline'], 10 ) ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( isset( $data['description'] ) ): ?>
                                <div class="hsz-summary-property">
                                    <strong><?php esc_html_e( 'Description:', 'hellaz-sitez-analyzer' ); ?></strong>
                                    <?php echo esc_html( wp_trim_words( $data['description'], 15 ) ); ?>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ( $item['type'] === 'microdata' && isset( $item['data'] ) ): ?>
                            <?php $data = $item['data']; ?>
                            
                            <div class="hsz-summary-property">
                                <strong><?php esc_html_e( 'Properties:', 'hellaz-sitez-analyzer' ); ?></strong>
                                <?php echo esc_html( implode( ', ', array_slice( array_keys( $data ), 0, 5 ) ) ); ?>
                                <?php if ( count( $data ) > 5 ): ?>
                                    <span class="hsz-more-properties">
                                        <?php printf( esc_html__( '... and %d more', 'hellaz-sitez-analyzer' ), count( $data ) - 5 ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Detailed view (initially hidden) -->
                    <div class="hsz-item-details" id="hsz-details-<?php echo esc_attr( $index ); ?>" style="display: none;">
                        <div class="hsz-details-content">
                            <?php if ( $item['type'] === 'json-ld' && isset( $item['data'] ) ): ?>
                                <pre class="hsz-json-display"><code><?php echo esc_html( wp_json_encode( $item['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></code></pre>
                            <?php elseif ( $item['type'] === 'microdata' && isset( $item['data'] ) ): ?>
                                <div class="hsz-microdata-display">
                                    <div class="hsz-microdata-itemtype">
                                        <strong><?php esc_html_e( 'Item Type:', 'hellaz-sitez-analyzer' ); ?></strong>
                                        <code><?php echo esc_html( $item['itemtype'] ?? 'N/A' ); ?></code>
                                    </div>
                                    <div class="hsz-microdata-properties">
                                        <strong><?php esc_html_e( 'Properties:', 'hellaz-sitez-analyzer' ); ?></strong>
                                        <ul class="hsz-properties-list">
                                            <?php foreach ( array_slice( $item['data'], 0, 10 ) as $prop => $value ): ?>
                                                <li>
                                                    <code class="hsz-property-name"><?php echo esc_html( $prop ); ?>:</code>
                                                    <span class="hsz-property-value"><?php echo esc_html( wp_trim_words( $value, 10 ) ); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php if ( count( $item['data'] ) > 10 ): ?>
                                                <li class="hsz-more-properties">
                                                    <?php printf( esc_html__( '... and %d more properties', 'hellaz-sitez-analyzer' ), count( $item['data'] ) - 10 ); ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ( count( $structured_data ) > 10 ): ?>
                <div class="hsz-more-items-notice">
                    <p><?php printf( esc_html__( 'Showing 10 of %d structured data items. Use Google\'s Rich Results Test for complete analysis.', 'hellaz-sitez-analyzer' ), count( $structured_data ) ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Structured Data Recommendations -->
    <div class="hsz-structured-recommendations">
        <h5><?php esc_html_e( 'Structured Data Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <ul class="hsz-recommendations-list">
            <?php
            $recommendations = [];
            
            // JSON-LD recommendations
            if ( $json_ld_count === 0 && $microdata_count > 0 ) {
                $recommendations[] = __( 'Consider migrating from Microdata to JSON-LD for better maintainability', 'hellaz-sitez-analyzer' );
            }
            
            if ( $json_ld_count === 0 && $microdata_count === 0 ) {
                $recommendations[] = __( 'Add structured data to improve search engine understanding of your content', 'hellaz-sitez-analyzer' );
            }
            
            // Schema type specific recommendations
            if ( ! in_array( 'Organization', $schema_types ) && ! in_array( 'LocalBusiness', $schema_types ) ) {
                $recommendations[] = __( 'Add Organization or LocalBusiness schema for better brand recognition', 'hellaz-sitez-analyzer' );
            }
            
            if ( ! in_array( 'BreadcrumbList', $schema_types ) ) {
                $recommendations[] = __( 'Implement BreadcrumbList schema to enhance navigation in search results', 'hellaz-sitez-analyzer' );
            }
            
            // Content-specific recommendations
            $content_word_count = $metadata['content_analysis']['word_count'] ?? 0;
            if ( $content_word_count > 500 && ! in_array( 'Article', $schema_types ) ) {
                $recommendations[] = __( 'Consider adding Article schema for long-form content', 'hellaz-sitez-analyzer' );
            }
            
            // Validation recommendations
            $recommendations[] = __( 'Test your structured data with Google\'s Rich Results Test tool', 'hellaz-sitez-analyzer' );
            
            // Positive feedback if well implemented
            if ( $json_ld_count > 0 && count( $schema_types ) > 2 ) {
                $recommendations[] = __( 'Excellent structured data implementation! Keep monitoring for new schema opportunities', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <?php foreach ( array_slice( $recommendations, 0, 5 ) as $recommendation ): ?>
                <li class="hsz-recommendation-item">
                    <span class="hsz-recommendation-icon">📊</span>
                    <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Testing Tools -->
    <div class="hsz-structured-tools">
        <h5><?php esc_html_e( 'Testing & Validation Tools', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <div class="hsz-tools-grid">
            <div class="hsz-tool-card">
                <div class="hsz-tool-name"><?php esc_html_e( 'Rich Results Test', 'hellaz-sitez-analyzer' ); ?></div>
                <div class="hsz-tool-description"><?php esc_html_e( 'Google\'s official tool for testing structured data', 'hellaz-sitez-analyzer' ); ?></div>
                <a href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . urlencode( $url ?? '' ) ); ?>" 
                   target="_blank" rel="noopener noreferrer" class="hsz-tool-link">
                    <?php esc_html_e( 'Test Now', 'hellaz-sitez-analyzer' ); ?>
                    <span class="hsz-external-icon">↗</span>
                </a>
            </div>
            
            <div class="hsz-tool-card">
                <div class="hsz-tool-name"><?php esc_html_e( 'Schema Markup Validator', 'hellaz-sitez-analyzer' ); ?></div>
                <div class="hsz-tool-description"><?php esc_html_e( 'Schema.org\'s official validation tool', 'hellaz-sitez-analyzer' ); ?></div>
                <a href="<?php echo esc_url( 'https://validator.schema.org/#url=' . urlencode( $url ?? '' ) ); ?>" 
                   target="_blank" rel="noopener noreferrer" class="hsz-tool-link">
                    <?php esc_html_e( 'Validate', 'hellaz-sitez-analyzer' ); ?>
                    <span class="hsz-external-icon">↗</span>
                </a>
            </div>
        </div>
    </div>

</div>

<script>
// Toggle details functionality
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.hsz-toggle-details');
    toggleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('aria-controls');
            const target = document.getElementById(targetId);
            const icon = this.querySelector('.hsz-toggle-icon');
            
            if (target.style.display === 'none' || target.style.display === '') {
                target.style.display = 'block';
                this.setAttribute('aria-expanded', 'true');
                icon.textContent = '▲';
                this.innerHTML = this.innerHTML.replace('View Details', '<?php esc_html_e( 'Hide Details', 'hellaz-sitez-analyzer' ); ?>');
            } else {
                target.style.display = 'none';
                this.setAttribute('aria-expanded', 'false');
                icon.textContent = '▼';
                this.innerHTML = this.innerHTML.replace('<?php esc_html_e( 'Hide Details', 'hellaz-sitez-analyzer' ); ?>', 'View Details');
            }
        });
    });
});
</script>
