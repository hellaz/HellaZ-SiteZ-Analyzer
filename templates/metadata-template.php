<?php
/**
 * Template for displaying metadata.
 */

// Extract metadata (ensure it's sanitized)
$title = isset($metadata['title']) ? esc_html($metadata['title']) : __('Untitled', 'hellaz-sitez-analyzer');
$description = isset($metadata['description']) ? esc_html($metadata['description']) : apply_filters('hsz_fallback_description', '');
$fallback_image = get_option('hsz_fallback_image', apply_filters('hsz_fallback_image', ''));
$favicon = isset($metadata['favicon']) ? esc_url($metadata['favicon']) : esc_url($fallback_image);
$canonical_url = isset($metadata['canonical_url']) ? esc_url($metadata['canonical_url']) : '';
$og_title = isset($metadata['og:title']) ? esc_html($metadata['og:title']) : '';
$twitter_title = isset($metadata['twitter:title']) ? esc_html($metadata['twitter:title']) : '';
$rss_feeds = isset($metadata['rss_feeds']) && is_array($metadata['rss_feeds']) ? $metadata['rss_feeds'] : [];
$emails = isset($metadata['emails']) && is_array($metadata['emails']) ? $metadata['emails'] : [];
$contact_forms = isset($metadata['contact_forms']) && is_array($metadata['contact_forms']) ? $metadata['contact_forms'] : [];
$technology_stack = isset($metadata['technology_stack']) && is_array($metadata['technology_stack']) ? $metadata['technology_stack'] : [];
$social_media = isset($metadata['social_media']) && is_array($metadata['social_media']) ? $metadata['social_media'] : [];
$ssl_info = isset($metadata['ssl_info']) && is_array($metadata['ssl_info']) ? $metadata['ssl_info'] : [];
$author = isset($metadata['author']) ? $metadata['author'] : '';
$keywords = isset($metadata['keywords']) ? $metadata['keywords'] : '';
$referrer = isset($metadata['referrer']) ? $metadata['referrer'] : '';
$language = isset($metadata['language']) ? $metadata['language'] : '';
$server_location = isset($metadata['server_location']) ? $metadata['server_location'] : '';
$security_analysis = isset($metadata['security_analysis']) && is_array($metadata['security_analysis']) ? $metadata['security_analysis'] : [];
$urlscan_api_key = get_option('hsz_urlscan_api_key', '');
$urlscan_analysis = isset($metadata['urlscan_analysis']) && !empty($urlscan_api_key) ? $metadata['urlscan_analysis'] : '';

// Link target option from plugin settings
$link_target = get_option('hsz_link_target', '_blank');

// Disclaimer settings
$enable_disclaimer = get_option('hsz_enable_disclaimer', false);
$disclaimer_message = get_option('hsz_disclaimer_message', __('This is a default disclaimer message.', 'hellaz-sitez-analyzer'));
?>

<div class="hsz-metadata-container">
    <!-- Favicon -->
    <div class="hsz-favicon">
        <img src="<?php echo $favicon; ?>" alt="<?php esc_attr_e('Website Favicon', 'hellaz-sitez-analyzer'); ?>" class="hsz-fallback-image">
    </div>

    <!-- Title -->
    <h3 class="hsz-title">
        <?php echo $title; ?>
    </h3>

    <!-- Description -->
    <p class="hsz-description">
        <?php echo $description; ?>
    </p>

    <!-- Basic Metadata Section -->
    <div class="hsz-section hsz-basic-metadata">
        <h4><?php _e('Basic Metadata', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($author)) : ?>
                <li><strong><?php _e('Author:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($author); ?></li>
            <?php endif; ?>

            <?php if (!empty($keywords)) : ?>
                <li><strong><?php _e('Keywords:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($keywords); ?></li>
            <?php endif; ?>

            <?php if (!empty($referrer)) : ?>
                <li><strong><?php _e('Referrer:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($referrer); ?></li>
            <?php endif; ?>

            <?php if (!empty($language)) : ?>
                <li><strong><?php _e('Language:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($language); ?></li>
            <?php endif; ?>

            <?php if ($canonical_url) : ?>
                <li><strong><?php _e('Canonical URL:', 'hellaz-sitez-analyzer'); ?></strong> 
                    <a href="<?php echo $canonical_url; ?>" target="<?php echo esc_attr($link_target); ?>" rel="noopener noreferrer"><?php echo esc_html($canonical_url); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Server Information (Combined Server Location + SSL/TLS Info) -->
    <div class="hsz-section hsz-server-info">
        <h4><?php _e('Server Information', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($server_location)) : ?>
                <li><strong><?php _e('Server Location:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($server_location); ?></li>
            <?php endif; ?>

            <?php if (!empty($ssl_info)) : ?>
                <?php if (!empty($ssl_info['valid_from'])) : ?>
                    <li><strong><?php _e('SSL Valid From:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['valid_from']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['valid_to'])) : ?>
                    <li><strong><?php _e('SSL Valid To:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['valid_to']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['issuer'])) : ?>
                    <li><strong><?php _e('SSL Issuer:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['issuer']); ?></li>
                <?php endif; ?>
            <?php else : ?>
                <li><?php _e('SSL/TLS information unavailable.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>

            <?php if (!empty($security_analysis)) : ?>
                <?php foreach ($security_analysis as $key => $value) : ?>
                    <li><strong><?php echo esc_html(ucfirst($key)); ?>:</strong> <?php echo esc_html($value); ?></li>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($urlscan_analysis)) : ?>
                <li><strong><?php _e('URLScan Report:', 'hellaz-sitez-analyzer'); ?></strong> 
                    <a href="<?php echo esc_url($urlscan_analysis); ?>" target="<?php echo esc_attr($link_target); ?>"><?php _e('View Report', 'hellaz-sitez-analyzer'); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Social Media Profiles -->
    <div class="hsz-section hsz-social-media">
        <h4><?php _e('Social Media Profiles', 'hellaz-sitez-analyzer'); ?></h4>
        <ul class="hsz-social-list">
            <?php if (!empty($social_media)) : ?>
                <?php foreach ($social_media as $platform => $profiles) : ?>
                    <?php foreach ($profiles as $profile) : ?>
                        <li>
                            <a href="<?php echo esc_url($profile['url']); ?>" target="<?php echo esc_attr($link_target); ?>" rel="noopener noreferrer">
                                <i class="fab fa-<?php echo esc_attr($platform); ?>"></i> <?php echo esc_html($profile['username']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No social media profiles detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Contact Information (Emails + Contact Forms in 2 Columns) -->
    <div class="hsz-section hsz-contact-info">
        <h4><?php _e('Contact Information', 'hellaz-sitez-analyzer'); ?></h4>
        <div class="hsz-columns">
            <div class="hsz-column">
                <h5><?php _e('Emails', 'hellaz-sitez-analyzer'); ?></h5>
                <ul>
                    <?php if (!empty($emails)) : ?>
                        <?php foreach ($emails as $email) : ?>
                            <li><a href="mailto:<?php echo esc_attr($email); ?>" target="_top"><?php echo esc_html($email); ?></a></li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e('No email addresses detected.', 'hellaz-sitez-analyzer'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="hsz-column">
                <h5><?php _e('Contact Forms', 'hellaz-sitez-analyzer'); ?></h5>
                <ul>
                    <?php if (!empty($contact_forms)) : ?>
                        <?php foreach ($contact_forms as $form) : ?>
                            <li><a href="<?php echo esc_url($form); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($form); ?></a></li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e('No contact forms detected.', 'hellaz-sitez-analyzer'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- RSS Feeds -->
    <div class="hsz-section hsz-rss-feeds">
        <h4><?php _e('RSS Feeds', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($rss_feeds)) : ?>
                <?php foreach ($rss_feeds as $feed) : ?>
                    <li><a href="<?php echo esc_url($feed); ?>" target="<?php echo esc_attr($link_target); ?>"><?php echo esc_html($feed); ?></a></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No RSS feeds detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Disclaimer -->
    <?php if ($enable_disclaimer && !empty($disclaimer_message)) : ?>
        <p class="hsz-disclaimer"><em><?php echo wp_kses_post($disclaimer_message); ?></em></p>
    <?php endif; ?>
</div>
