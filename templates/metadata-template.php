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
$rss_feeds = isset($metadata['rss_feeds']) ? $metadata['rss_feeds'] : [];
$emails = isset($metadata['emails']) ? $metadata['emails'] : [];
$contact_forms = isset($metadata['contact_forms']) ? $metadata['contact_forms'] : [];
$technology_stack = isset($metadata['technology_stack']) ? $metadata['technology_stack'] : [];
$social_media = isset($metadata['social_media']) ? $metadata['social_media'] : [];
$ssl_info = isset($metadata['ssl_info']) ? $metadata['ssl_info'] : [];
$author = isset($metadata['author']) ? $metadata['author'] : '';
$keywords = isset($metadata['keywords']) ? $metadata['keywords'] : '';
$referrer = isset($metadata['referrer']) ? $metadata['referrer'] : '';
$language = isset($metadata['language']) ? $metadata['language'] : '';
$server_location = isset($metadata['server_location']) ? $metadata['server_location'] : '';
$security_analysis = isset($metadata['security_analysis']) ? $metadata['security_analysis'] : [];
$urlscan_analysis = isset($metadata['urlscan_analysis']) ? $metadata['urlscan_analysis'] : '';

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
                    <a href="<?php echo $canonical_url; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($canonical_url); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Server Location -->
    <div class="hsz-section hsz-server-location">
        <h4><?php _e('Server Location', 'hellaz-sitez-analyzer'); ?></h4>
        <p>
            <?php if (!empty($server_location)) : ?>
                <?php echo esc_html($server_location); ?>
            <?php else : ?>
                <?php _e('Server location unavailable.', 'hellaz-sitez-analyzer'); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Technology Stack -->
    <div class="hsz-section hsz-technology-stack">
        <h4><?php _e('Technology Stack', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($technology_stack)) : ?>
                <?php foreach ($technology_stack as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('Technology stack information unavailable.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Security Analysis -->
    <div class="hsz-section hsz-security-analysis">
        <h4><?php _e('Security Analysis', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($security_analysis)) : ?>
                <?php foreach ($security_analysis as $key => $value) : ?>
                    <li><strong><?php echo esc_html(ucfirst($key)); ?>:</strong> <?php echo esc_html($value); ?></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('Security analysis unavailable.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- URLScan Analysis -->
    <div class="hsz-section hsz-urlscan-analysis">
        <h4><?php _e('URLScan Analysis', 'hellaz-sitez-analyzer'); ?></h4>
        <p>
            <?php if (!empty($urlscan_analysis)) : ?>
                <a href="<?php echo esc_url($urlscan_analysis); ?>" target="_blank" rel="noopener noreferrer">
                    <?php _e('View URLScan Report', 'hellaz-sitez-analyzer'); ?>
                </a>
            <?php else : ?>
                <?php _e('URLScan analysis unavailable.', 'hellaz-sitez-analyzer'); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- RSS Feeds -->
    <div class="hsz-section hsz-rss-feeds">
        <h4><?php _e('RSS Feeds', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($rss_feeds)) : ?>
                <?php foreach ($rss_feeds as $feed) : ?>
                    <li><a href="<?php echo esc_url($feed); ?>" target="_blank"><?php echo esc_html($feed); ?></a></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No RSS feeds detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Emails -->
    <div class="hsz-section hsz-emails">
        <h4><?php _e('Emails', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($emails)) : ?>
                <?php foreach ($emails as $email) : ?>
                    <li><?php echo esc_html($email); ?></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No email addresses detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Contact Forms -->
    <div class="hsz-section hsz-contact-forms">
        <h4><?php _e('Contact Forms', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($contact_forms)) : ?>
                <?php foreach ($contact_forms as $form) : ?>
                    <li><a href="<?php echo esc_url($form); ?>" target="_blank"><?php echo esc_html($form); ?></a></li>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No contact forms detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Social Media Links -->
    <div class="hsz-section hsz-social-media">
        <h4><?php _e('Social Media Profiles', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($social_media)) : ?>
                <?php foreach ($social_media as $platform => $links) : ?>
                    <?php foreach ($links as $link) : ?>
                        <li><a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html(ucfirst($platform)); ?>
                        </a></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <li><?php _e('No social media profiles detected.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- SSL Information -->
    <div class="hsz-section hsz-ssl-info">
        <h4><?php _e('SSL/TLS Information', 'hellaz-sitez-analyzer'); ?></h4>
        <ul>
            <?php if (!empty($ssl_info)) : ?>
                <?php if (!empty($ssl_info['valid_from'])) : ?>
                    <li><strong><?php _e('Valid From:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['valid_from']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['valid_to'])) : ?>
                    <li><strong><?php _e('Valid To:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['valid_to']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['issuer'])) : ?>
                    <li><strong><?php _e('Issuer:', 'hellaz-sitez-analyzer'); ?></strong> <?php echo esc_html($ssl_info['issuer']); ?></li>
                <?php endif; ?>
            <?php else : ?>
                <li><?php _e('SSL/TLS information unavailable.', 'hellaz-sitez-analyzer'); ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Disclaimer -->
    <?php if ($enable_disclaimer && !empty($disclaimer_message)) : ?>
        <div class="hsz-section hsz-disclaimer">
            <h4><?php _e('Disclaimer', 'hellaz-sitez-analyzer'); ?></h4>
            <p><em><?php echo wp_kses_post($disclaimer_message); ?></em></p>
        </div>
    <?php endif; ?>
</div>
