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

    <!-- Author -->
    <?php if (!empty($author)) : ?>
        <p class="hsz-author">
            <strong><?php _e('Author:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html($author); ?>
        </p>
    <?php endif; ?>

    <!-- Keywords -->
    <?php if (!empty($keywords)) : ?>
        <p class="hsz-keywords">
            <strong><?php _e('Keywords:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html($keywords); ?>
        </p>
    <?php endif; ?>

    <!-- Referrer -->
    <?php if (!empty($referrer)) : ?>
        <p class="hsz-referrer">
            <strong><?php _e('Referrer:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html($referrer); ?>
        </p>
    <?php endif; ?>

    <!-- Language -->
    <?php if (!empty($language)) : ?>
        <p class="hsz-language">
            <strong><?php _e('Language:', 'hellaz-sitez-analyzer'); ?></strong>
            <?php echo esc_html($language); ?>
        </p>
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php if ($canonical_url) : ?>
        <p class="hsz-canonical-url">
            <strong><?php _e('Canonical URL:', 'hellaz-sitez-analyzer'); ?></strong>
            <a href="<?php echo $canonical_url; ?>" target="_blank" rel="noopener noreferrer">
                <?php echo $canonical_url; ?>
            </a>
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

    <!-- Social Media Links -->
    <?php if (!empty($social_media)) : ?>
        <div class="hsz-social-media">
            <h4><?php _e('Social Media Profiles', 'hellaz-sitez-analyzer'); ?></h4>
            <ul class="hsz-social-list">
                <?php foreach ($social_media as $platform => $links) : ?>
                    <?php foreach ($links as $link) : ?>
                        <li class="hsz-social-item hsz-<?php echo esc_attr($platform); ?>">
                            <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(ucfirst($platform)); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- SSL Info -->
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

    <!-- Disclaimer -->
    <?php if ($enable_disclaimer && !empty($disclaimer_message)) : ?>
        <div class="hsz-disclaimer">
            <p><em><?php echo wp_kses_post($disclaimer_message); ?></em></p>
        </div>
    <?php endif; ?>
</div>
