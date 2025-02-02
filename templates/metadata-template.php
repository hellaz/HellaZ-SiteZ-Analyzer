<?php
/**
 * Template for displaying metadata.
 */

// Extract metadata (ensure it's sanitized)
$title = isset($metadata['title']) ? esc_html($metadata['title']) : __('Untitled', 'hellaz-sitez-analyzer');
$description = isset($metadata['description']) ? esc_html($metadata['description']) : apply_filters('hsz_fallback_description', '');
$favicon = isset($metadata['favicon']) ? esc_url($metadata['favicon']) : apply_filters('hsz_fallback_image', '');
$canonical_url = isset($metadata['canonical_url']) ? esc_url($metadata['canonical_url']) : '';
$og_title = isset($metadata['og:title']) ? esc_html($metadata['og:title']) : '';
$twitter_title = isset($metadata['twitter:title']) ? esc_html($metadata['twitter:title']) : '';
$rss_feeds = isset($metadata['rss_feeds']) ? $metadata['rss_feeds'] : [];
$emails = isset($metadata['emails']) ? $metadata['emails'] : [];
$contact_forms = isset($metadata['contact_forms']) ? $metadata['contact_forms'] : [];
$technology_stack = isset($metadata['technology_stack']) ? $metadata['technology_stack'] : [];
$social_media = isset($metadata['social_media']) ? $metadata['social_media'] : [];
$ssl_info = isset($metadata['ssl_info']) ? $metadata['ssl_info'] : [];
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

    <!-- Open Graph Title -->
    <?php if ($og_title) : ?>
        <p class="hsz-og-title">
            <strong><?php _e('Open Graph Title:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo $og_title; ?>
        </p>
    <?php endif; ?>

    <!-- Twitter Title -->
    <?php if ($twitter_title) : ?>
        <p class="hsz-twitter-title">
            <strong><?php _e('Twitter Title:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo $twitter_title; ?>
        </p>
    <?php endif; ?>

    <!-- RSS Feeds -->
    <?php if (!empty($rss_feeds)) : ?>
        <p class="hsz-rss-feeds">
            <strong><?php _e('RSS Feeds:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php foreach ($rss_feeds as $feed) : ?>
                    <li><a href="<?php echo esc_url($feed); ?>" target="_blank"><?php echo esc_html($feed); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <!-- Emails -->
    <?php if (!empty($emails)) : ?>
        <p class="hsz-emails">
            <strong><?php _e('Emails:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo implode(', ', array_map('esc_html', $emails)); ?>
        </p>
    <?php endif; ?>

    <!-- Contact Forms -->
    <?php if (!empty($contact_forms)) : ?>
        <p class="hsz-contact-forms">
            <strong><?php _e('Contact Forms:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php foreach ($contact_forms as $form) : ?>
                    <li><a href="<?php echo esc_url($form); ?>" target="_blank"><?php echo esc_html($form); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <!-- Technology Stack -->
    <?php if (!empty($technology_stack)) : ?>
        <p class="hsz-technology-stack">
            <strong><?php _e('Technology Stack:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php foreach ($technology_stack as $key => $value) : ?>
                    <li><?php echo esc_html(ucfirst($key)) . ': ' . esc_html($value); ?></li>
                <?php endforeach; ?>
            </ul>
        </p>
    <?php endif; ?>

    <!-- Social Media Links -->
    <?php if (!empty($social_media)) : ?>
        <div class="hsz-social-media">
            <h4><?php _e('Social Media Profiles', 'hellaz-sitez-analyzer'); ?></h4>
            <ul class="hsz-social-list">
                <?php foreach ($social_media as $platform => $links) : ?>
                    <?php foreach ($links as $link) : ?>
                        <li class="hsz-social-item hsz-<?php echo esc_attr($platform); ?>">
                            <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(ucfirst($platform)); ?> <!-- Display platform name -->
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- SSL/TLS Information -->
    <?php if (!empty($ssl_info)) : ?>
        <p class="hsz-ssl-info">
            <strong><?php _e('SSL/TLS Information:', 'hellaz-sitez-analyzer'); ?></strong>
            <ul>
                <?php if (!empty($ssl_info['valid_from'])) : ?>
                    <li><?php _e('Valid From:', 'hellaz-sitez-analyzer'); ?> <?php echo esc_html($ssl_info['valid_from']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['valid_to'])) : ?>
                    <li><?php _e('Valid To:', 'hellaz-sitez-analyzer'); ?> <?php echo esc_html($ssl_info['valid_to']); ?></li>
                <?php endif; ?>
                <?php if (!empty($ssl_info['issuer'])) : ?>
                    <li><?php _e('Issuer:', 'hellaz-sitez-analyzer'); ?> <?php echo esc_html($ssl_info['issuer']); ?></li>
                <?php endif; ?>
            </ul>
        </p>
    <?php endif; ?>
</div>
