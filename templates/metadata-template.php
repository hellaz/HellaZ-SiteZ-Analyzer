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
</div>
