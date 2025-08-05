<?php
/**
 * Template Partial: Enhanced Social Media Section
 *
 * Enhanced version of the social media template with better organization,
 * platform categorization, and detailed social media presence analysis.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $social Array of social media profile links
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Skip if no social media data
if ( empty( $social ) || ! is_array( $social ) ) {
    return;
}

// Enhanced social media mapping with categories
$social_platform_map = [
    // Major Social Networks
    'facebook.com' => [ 'icon' => 'fa-facebook', 'name' => 'Facebook', 'category' => 'major', 'color' => '#1877F2' ],
    'twitter.com' => [ 'icon' => 'fa-twitter', 'name' => 'Twitter', 'category' => 'major', 'color' => '#1DA1F2' ],
    'x.com' => [ 'icon' => 'fa-twitter', 'name' => 'X (Twitter)', 'category' => 'major', 'color' => '#000000' ],
    'linkedin.com' => [ 'icon' => 'fa-linkedin', 'name' => 'LinkedIn', 'category' => 'professional', 'color' => '#0A66C2' ],
    'instagram.com' => [ 'icon' => 'fa-instagram', 'name' => 'Instagram', 'category' => 'major', 'color' => '#E4405F' ],
    
    // Video Platforms
    'youtube.com' => [ 'icon' => 'fa-youtube', 'name' => 'YouTube', 'category' => 'media', 'color' => '#FF0000' ],
    'vimeo.com' => [ 'icon' => 'fa-vimeo', 'name' => 'Vimeo', 'category' => 'media', 'color' => '#1AB7EA' ],
    'tiktok.com' => [ 'icon' => 'fa-tiktok', 'name' => 'TikTok', 'category' => 'media', 'color' => '#000000' ],
    'twitch.tv' => [ 'icon' => 'fa-twitch', 'name' => 'Twitch', 'category' => 'media', 'color' => '#9146FF' ],
    
    // Professional/Business  
    'github.com' => [ 'icon' => 'fa-github', 'name' => 'GitHub', 'category' => 'professional', 'color' => '#181717' ],
    'behance.net' => [ 'icon' => 'fa-behance', 'name' => 'Behance', 'category' => 'professional', 'color' => '#1769FF' ],
    'dribbble.com' => [ 'icon' => 'fa-dribbble', 'name' => 'Dribbble', 'category' => 'professional', 'color' => '#EA4C89' ],
    
    // Creative/Visual
    'pinterest.com' => [ 'icon' => 'fa-pinterest', 'name' => 'Pinterest', 'category' => 'visual', 'color' => '#BD081C' ],
    'flickr.com' => [ 'icon' => 'fa-flickr', 'name' => 'Flickr', 'category' => 'visual', 'color' => '#0063DC' ],
    'deviantart.com' => [ 'icon' => 'fa-deviantart', 'name' => 'DeviantArt', 'category' => 'visual', 'color' => '#05CC47' ],
    
    // Audio/Music
    'soundcloud.com' => [ 'icon' => 'fa-soundcloud', 'name' => 'SoundCloud', 'category' => 'audio', 'color' => '#FF5500' ],
    'spotify.com' => [ 'icon' => 'fa-spotify', 'name' => 'Spotify', 'category' => 'audio', 'color' => '#1DB954' ],
    'music.apple.com' => [ 'icon' => 'fa-apple', 'name' => 'Apple Music', 'category' => 'audio', 'color' => '#000000' ],
    
    // Messaging/Communication
    't.me' => [ 'icon' => 'fa-telegram', 'name' => 'Telegram', 'category' => 'messaging', 'color' => '#0088CC' ],
    'telegram.me' => [ 'icon' => 'fa-telegram', 'name' => 'Telegram', 'category' => 'messaging', 'color' => '#0088CC' ],
    'discord.com' => [ 'icon' => 'fa-discord', 'name' => 'Discord', 'category' => 'messaging', 'color' => '#5865F2' ],
    'whatsapp.com' => [ 'icon' => 'fa-whatsapp', 'name' => 'WhatsApp', 'category' => 'messaging', 'color' => '#25D366' ],
    'wa.me' => [ 'icon' => 'fa-whatsapp', 'name' => 'WhatsApp', 'category' => 'messaging', 'color' => '#25D366' ],
    
    // Community/Discussion
    'reddit.com' => [ 'icon' => 'fa-reddit', 'name' => 'Reddit', 'category' => 'community', 'color' => '#FF4500' ],
    'mastodon.social' => [ 'icon' => 'fa-mastodon', 'name' => 'Mastodon', 'category' => 'community', 'color' => '#563ACC' ],
    'threads.net' => [ 'icon' => 'fa-threads', 'name' => 'Threads', 'category' => 'major', 'color' => '#000000' ],
    
    // Emerging/New Platforms
    'bluesky.social' => [ 'icon' => 'fa-cloud', 'name' => 'Bluesky', 'category' => 'emerging', 'color' => '#00A8E8' ],
    'clubhouse.com' => [ 'icon' => 'fa-microphone', 'name' => 'Clubhouse', 'category' => 'audio', 'color' => '#F1C40F' ],
    
    // Regional/Specialized
    'weibo.com' => [ 'icon' => 'fa-weibo', 'name' => 'Weibo', 'category' => 'regional', 'color' => '#E6162D' ],
    'vk.com' => [ 'icon' => 'fa-vk', 'name' => 'VKontakte', 'category' => 'regional', 'color' => '#4C75A3' ],
    'ok.ru' => [ 'icon' => 'fa-odnoklassniki', 'name' => 'Odnoklassniki', 'category' => 'regional', 'color' => '#ED812B' ],
];

// Process and categorize social media profiles
$categorized_profiles = [
    'major' => [],
    'professional' => [],
    'media' => [],
    'visual' => [],
    'audio' => [],
    'messaging' => [],
    'community' => [],
    'emerging' => [],
    'regional' => [],
    'other' => []
];

foreach ( $social as $profile_url ) {
    $host = wp_parse_url( $profile_url, PHP_URL_HOST );
    if ( ! $host ) continue;
    
    // Normalize host by removing 'www.'
    $normalized_host = preg_replace( '/^www\./i', '', $host );
    
    $platform_info = [
        'url' => $profile_url,
        'host' => $normalized_host,
        'icon' => 'fa-link',
        'name' => ucfirst( str_replace( '.com', '', $normalized_host ) ),
        'category' => 'other',
        'color' => '#666666'
    ];
    
    // Check if we have specific info for this platform
    if ( isset( $social_platform_map[ $normalized_host ] ) ) {
        $platform_info = array_merge( $platform_info, $social_platform_map[ $normalized_host ] );
    }
    
    $categorized_profiles[ $platform_info['category'] ][] = $platform_info;
}

// Remove empty categories
$categorized_profiles = array_filter( $categorized_profiles );
?>

<div class="hsz-social-media-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-social"></span>
        <?php esc_html_e( 'Social Media Presence', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- Social Media Overview -->
    <div class="hsz-social-overview">
        <div class="hsz-social-stats">
            <div class="hsz-stat-card">
                <div class="hsz-stat-number"><?php echo esc_html( count( $social ) ); ?></div>
                <div class="hsz-stat-label">
                    <?php echo count( $social ) === 1 ? 
                        esc_html__( 'Social Profile', 'hellaz-sitez-analyzer' ) : 
                        esc_html__( 'Social Profiles', 'hellaz-sitez-analyzer' ); ?>
                </div>
            </div>
            
            <div class="hsz-stat-card">
                <div class="hsz-stat-number"><?php echo esc_html( count( $categorized_profiles ) ); ?></div>
                <div class="hsz-stat-label">
                    <?php echo count( $categorized_profiles ) === 1 ? 
                        esc_html__( 'Platform Category', 'hellaz-sitez-analyzer' ) : 
                        esc_html__( 'Platform Categories', 'hellaz-sitez-analyzer' ); ?>
                </div>
            </div>
        </div>
        
        <!-- Social Presence Assessment -->
        <div class="hsz-social-assessment">
            <?php
            $presence_score = min( 100, count( $social ) * 10 );
            
            if ( $presence_score >= 80 ) {
                $presence_status = 'excellent';
                $presence_message = __( 'Excellent social media presence', 'hellaz-sitez-analyzer' );
            } elseif ( $presence_score >= 60 ) {
                $presence_status = 'good';
                $presence_message = __( 'Good social media presence', 'hellaz-sitez-analyzer' );
            } elseif ( $presence_score >= 30 ) {
                $presence_status = 'average';
                $presence_message = __( 'Average social media presence', 'hellaz-sitez-analyzer' );
            } else {
                $presence_status = 'limited';
                $presence_message = __( 'Limited social media presence', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <div class="hsz-presence-indicator hsz-presence-<?php echo esc_attr( $presence_status ); ?>">
                <span class="hsz-presence-score"><?php echo esc_html( $presence_score ); ?>%</span>
                <span class="hsz-presence-label"><?php echo esc_html( $presence_message ); ?></span>
            </div>
        </div>
    </div>

    <!-- Major Social Media Platforms -->
    <?php if ( ! empty( $categorized_profiles['major'] ) ): ?>
        <div class="hsz-social-category hsz-major-platforms">
            <h5 class="hsz-category-title">
                <span class="hsz-category-icon">🌟</span>
                <?php esc_html_e( 'Major Social Networks', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-social-platforms hsz-platforms-major">
                <?php foreach ( $categorized_profiles['major'] as $platform ): ?>
                    <a href="<?php echo esc_url( $platform['url'] ); ?>" 
                       target="_blank" rel="noopener noreferrer" 
                       class="hsz-social-platform hsz-platform-major"
                       title="<?php echo esc_attr( sprintf( __( 'Visit %s profile', 'hellaz-sitez-analyzer' ), $platform['name'] ) ); ?>"
                       style="--platform-color: <?php echo esc_attr( $platform['color'] ); ?>">
                        <span class="hsz-platform-icon">
                            <i class="fab <?php echo esc_attr( $platform['icon'] ); ?>"></i>
                        </span>
                        <span class="hsz-platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
                        <span class="hsz-external-icon">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Professional Platforms -->
    <?php if ( ! empty( $categorized_profiles['professional'] ) ): ?>
        <div class="hsz-social-category hsz-professional-platforms">
            <h5 class="hsz-category-title">
                <span class="hsz-category-icon">💼</span>
                <?php esc_html_e( 'Professional Networks', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-social-platforms hsz-platforms-professional">
                <?php foreach ( $categorized_profiles['professional'] as $platform ): ?>
                    <a href="<?php echo esc_url( $platform['url'] ); ?>" 
                       target="_blank" rel="noopener noreferrer" 
                       class="hsz-social-platform hsz-platform-professional"
                       title="<?php echo esc_attr( sprintf( __( 'Visit %s profile', 'hellaz-sitez-analyzer' ), $platform['name'] ) ); ?>"
                       style="--platform-color: <?php echo esc_attr( $platform['color'] ); ?>">
                        <span class="hsz-platform-icon">
                            <i class="fab <?php echo esc_attr( $platform['icon'] ); ?>"></i>
                        </span>
                        <span class="hsz-platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
                        <span class="hsz-external-icon">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Media Platforms -->
    <?php if ( ! empty( $categorized_profiles['media'] ) ): ?>
        <div class="hsz-social-category hsz-media-platforms">
            <h5 class="hsz-category-title">
                <span class="hsz-category-icon">🎬</span>
                <?php esc_html_e( 'Media & Video Platforms', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-social-platforms hsz-platforms-media">
                <?php foreach ( $categorized_profiles['media'] as $platform ): ?>
                    <a href="<?php echo esc_url( $platform['url'] ); ?>" 
                       target="_blank" rel="noopener noreferrer" 
                       class="hsz-social-platform hsz-platform-media"
                       title="<?php echo esc_attr( sprintf( __( 'Visit %s profile', 'hellaz-sitez-analyzer' ), $platform['name'] ) ); ?>"
                       style="--platform-color: <?php echo esc_attr( $platform['color'] ); ?>">
                        <span class="hsz-platform-icon">
                            <i class="fab <?php echo esc_attr( $platform['icon'] ); ?>"></i>
                        </span>
                        <span class="hsz-platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
                        <span class="hsz-external-icon">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Other Platform Categories -->
    <?php 
    $other_categories = [
        'visual' => [ 'icon' => '🎨', 'title' => __( 'Visual & Creative Platforms', 'hellaz-sitez-analyzer' ) ],
        'audio' => [ 'icon' => '🎵', 'title' => __( 'Audio & Music Platforms', 'hellaz-sitez-analyzer' ) ],
        'messaging' => [ 'icon' => '💬', 'title' => __( 'Messaging Platforms', 'hellaz-sitez-analyzer' ) ],
        'community' => [ 'icon' => '👥', 'title' => __( 'Community Platforms', 'hellaz-sitez-analyzer' ) ],
        'emerging' => [ 'icon' => '🚀', 'title' => __( 'Emerging Platforms', 'hellaz-sitez-analyzer' ) ],
        'regional' => [ 'icon' => '🌏', 'title' => __( 'Regional Platforms', 'hellaz-sitez-analyzer' ) ]
    ];
    ?>

    <?php foreach ( $other_categories as $category_key => $category_info ): ?>
        <?php if ( ! empty( $categorized_profiles[ $category_key ] ) ): ?>
            <div class="hsz-social-category hsz-<?php echo esc_attr( $category_key ); ?>-platforms">
                <h5 class="hsz-category-title">
                    <span class="hsz-category-icon"><?php echo esc_html( $category_info['icon'] ); ?></span>
                    <?php echo esc_html( $category_info['title'] ); ?>
                </h5>
                
                <div class="hsz-social-platforms hsz-platforms-<?php echo esc_attr( $category_key ); ?>">
                    <?php foreach ( $categorized_profiles[ $category_key ] as $platform ): ?>
                        <a href="<?php echo esc_url( $platform['url'] ); ?>" 
                           target="_blank" rel="noopener noreferrer" 
                           class="hsz-social-platform hsz-platform-<?php echo esc_attr( $category_key ); ?>"
                           title="<?php echo esc_attr( sprintf( __( 'Visit %s profile', 'hellaz-sitez-analyzer' ), $platform['name'] ) ); ?>"
                           style="--platform-color: <?php echo esc_attr( $platform['color'] ); ?>">
                            <span class="hsz-platform-icon">
                                <i class="fab <?php echo esc_attr( $platform['icon'] ); ?>"></i>
                            </span>
                            <span class="hsz-platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
                            <span class="hsz-external-icon">↗</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Other/Unrecognized Platforms -->
    <?php if ( ! empty( $categorized_profiles['other'] ) ): ?>
        <div class="hsz-social-category hsz-other-platforms">
            <h5 class="hsz-category-title">
                <span class="hsz-category-icon">🔗</span>
                <?php esc_html_e( 'Other Social Links', 'hellaz-sitez-analyzer' ); ?>
            </h5>
            
            <div class="hsz-social-platforms hsz-platforms-other">
                <?php foreach ( $categorized_profiles['other'] as $platform ): ?>
                    <a href="<?php echo esc_url( $platform['url'] ); ?>" 
                       target="_blank" rel="noopener noreferrer" 
                       class="hsz-social-platform hsz-platform-other"
                       title="<?php echo esc_attr( sprintf( __( 'Visit %s', 'hellaz-sitez-analyzer' ), $platform['host'] ) ); ?>">
                        <span class="hsz-platform-icon">
                            <i class="fas fa-link"></i>
                        </span>
                        <span class="hsz-platform-name"><?php echo esc_html( $platform['name'] ); ?></span>
                        <span class="hsz-external-icon">↗</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Social Media Recommendations -->
    <div class="hsz-social-recommendations">
        <h5><?php esc_html_e( 'Social Media Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
        
        <ul class="hsz-recommendations-list">
            <?php
            $recommendations = [];
            
            // Platform-specific recommendations
            if ( empty( $categorized_profiles['major'] ) ) {
                $recommendations[] = __( 'Consider establishing presence on major social networks like Facebook, Twitter, or Instagram', 'hellaz-sitez-analyzer' );
            }
            
            if ( empty( $categorized_profiles['professional'] ) ) {
                $recommendations[] = __( 'Add professional networking profiles like LinkedIn for business connections', 'hellaz-sitez-analyzer' );
            }
            
            if ( count( $social ) < 3 ) {
                $recommendations[] = __( 'Expand your social media presence to reach different audience segments', 'hellaz-sitez-analyzer' );
            }
            
            // Content-specific recommendations based on site type
            $has_media = ! empty( $categorized_profiles['media'] );
            $has_visual = ! empty( $categorized_profiles['visual'] );
            
            if ( ! $has_media ) {
                $recommendations[] = __( 'Consider YouTube or other video platforms if you create multimedia content', 'hellaz-sitez-analyzer' );
            }
            
            if ( ! $has_visual ) {
                $recommendations[] = __( 'Pinterest or Instagram could help if you have visual content to share', 'hellaz-sitez-analyzer' );
            }
            
            // Positive feedback
            if ( count( $social ) >= 5 ) {
                $recommendations[] = __( 'Excellent social media coverage! Ensure profiles are active and regularly updated', 'hellaz-sitez-analyzer' );
            }
            
            // Default recommendation
            if ( empty( $recommendations ) ) {
                $recommendations[] = __( 'Your social media presence looks good! Consider adding more platforms relevant to your audience', 'hellaz-sitez-analyzer' );
            }
            ?>
            
            <?php foreach ( array_slice( $recommendations, 0, 4 ) as $recommendation ): ?>
                <li class="hsz-recommendation-item">
                    <span class="hsz-recommendation-icon">📱</span>
                    <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

</div>
