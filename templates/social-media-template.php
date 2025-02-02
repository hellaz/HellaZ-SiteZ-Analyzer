<?php
/**
 * Template for displaying social media links.
 */

// Ensure social media links are sanitized
$social_links = isset($social_media) ? $social_media : [];
?>

<div class="hsz-social-media">
    <?php if (!empty($social_links)) : ?>
        <h4><?php _e('Social Media Profiles', 'hellaz-sitez-analyzer'); ?></h4>
        <ul class="hsz-social-list">
            <?php foreach ($social_links as $platform => $links) : ?>
                <?php foreach ($links as $link) : ?>
                    <li class="hsz-social-item hsz-<?php echo esc_attr($platform); ?>">
                        <a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html(ucfirst($platform)); ?> <!-- Display platform name -->
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php _e('No social media profiles detected.', 'hellaz-sitez-analyzer'); ?></p>
    <?php endif; ?>
</div>
