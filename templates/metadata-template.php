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
$server_location = isset($metadata['server_location']) ? $metadata['server_location'] : '';
$security_analysis = isset($metadata['security_analysis']) ? $metadata['security_analysis'] : [];
$urlscan_analysis = isset($metadata['urlscan_analysis']) ? $metadata['urlscan_analysis'] : '';

// Disclaimer settings
$enable_disclaimer = get_option('hsz_enable_disclaimer', false);
$disclaimer_message = get_option('hsz_disclaimer_message', __('This is a default disclaimer message.', 'hellaz-sitez-analyzer'));
?>

<div class="hsz-metadata">
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

    <!-- Canonical URL -->
    <?php if ($canonical_url) : ?>
        <p class="hsz-canonical-url">
            <strong><?php _e('Canonical URL:', 'hellaz-sitez-analyzer'); ?></strong>
            <a href="<?php echo $canonical_url; ?>" target="_blank" rel="noopener noreferrer">
                <?php echo $canonical_url; ?>
            </a>
        </p>
    <?php endif; ?>

    <!-- Server Location -->
    <?php if (!empty($server_location)) : ?>
        <p class="hsz-server-location">
            <strong><?php _e('Server Location:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html($server_location); ?>
        </p>
    <?php endif; ?>

    <!-- Technology Stack -->
    <?php if (!empty($technology_stack)) : ?>
        <p class="hsz-technology-stack">
            <strong><?php _e('Technology Stack:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php foreach ($technology_stack as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <!-- Security Analysis -->
    <?php if (!empty($security_analysis)) : ?>
        <p class="hsz-security-analysis">
            <strong><?php _e('Security Analysis:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php foreach ($security_analysis as $key => $value) : ?>
                    <li><?php echo esc_html(ucfirst($key)) . ': ' . esc_html($value); ?></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <!-- URLScan Analysis -->
    <?php if (!empty($urlscan_analysis)) : ?>
        <p class="hsz-urlscan-analysis">
            <strong><?php _e('URLScan Analysis:', 'hellaz-sitez-analyzer'); ?></strong>
            <a href="<?php echo esc_url($urlscan_analysis); ?>" target="_blank"><?php echo esc_html($urlscan_analysis); ?></a>
        </p>
    <?php endif; ?>

    <!-- Disclaimer -->
    <?php if ($enable_disclaimer && !empty($disclaimer_message)) : ?>
        <div class="hsz-disclaimer">
            <p><em><?php echo wp_kses_post($disclaimer_message); ?></em></p>
        </div>
    <?php endif; ?>
</div>
