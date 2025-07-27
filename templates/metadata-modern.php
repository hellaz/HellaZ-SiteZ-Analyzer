<?php
/**
 * metadata-modern.php
 * Modern card-style template with richer styling
 *
 * Variables expected:
 * - All from classic, similar variables
 */
?>

<div class="hsz-metadata-modern" style="max-width: 600px; background-color: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; font-family: Arial, sans-serif;">
    <div style="display: flex; align-items: center;">
        <img src="<?php echo esc_url($favicon); ?>" alt="<?php echo esc_attr($title); ?>" style="width: 60px; height: 60px; border-radius: 10px; margin-right: 15px;" loading="lazy" />
        <div>
            <h2 style="margin: 0 0 5px 0; font-size: 1.5em; color: #222;"><?php echo esc_html($title); ?></h2>
            <p style="margin: 0 0 8px 0; color: #555; font-size: 1em;"><?php echo esc_html($description); ?></p>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener noreferrer" style="color: #0073aa; font-size: 0.9em; text-decoration: none;"><?php echo esc_html($url); ?></a>
        </div>
    </div>
    <?php if (!empty($social) && is_array($social)) : ?>
        <div class="hsz-social-links" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($social as $platform => $profile) : ?>
                <?php if (!empty($profile['url'])) : ?>
                    <a href="<?php echo esc_url($profile['url']); ?>" class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>" target="_blank" rel="nofollow noopener noreferrer" style="padding: 8px 14px; background-color: #eee; font-size: 0.9em; border-radius: 20px; color: #444; text-decoration: none;">
                        <?php echo esc_html(ucfirst($platform)); ?><?php if (!empty($profile['username'])) : ?>: <span style="font-weight: normal;"><?php echo esc_html($profile['username']); ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($disclaimer)) : ?>
        <div style="margin-top: 18px; font-size: 0.8em; color: #888;">
            <?php echo esc_html($disclaimer); ?>
        </div>
    <?php endif; ?>
</div>
