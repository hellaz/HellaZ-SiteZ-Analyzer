<?php
/**
 * metadata-classic.php
 * Classic table layout template for metadata display
 *
 * Variables expected in scope:
 * - $url (string)
 * - $title (string)
 * - $description (string)
 * - $favicon (string)
 * - $social (array of social profiles)
 * - $disclaimer (string)
 */
?>

<table class="hsz-metadata-classic" style="border-collapse: collapse; width: 100%; max-width: 600px; font-family: Arial, sans-serif;">
    <tr style="border-bottom: 1px solid #ddd;">
        <td style="width: 60px; padding: 10px; vertical-align: middle;">
            <img src="<?php echo esc_url($favicon); ?>" alt="<?php echo esc_attr($title); ?>" style="max-width: 48px; border-radius: 6px;" loading="lazy">
        </td>
        <td style="padding: 10px; vertical-align: middle;">
            <h3 style="margin:0; font-size: 1.2em;"><?php echo esc_html($title); ?></h3>
            <p style="margin: 5px 0;"><?php echo esc_html($description); ?></p>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener noreferrer" style="font-size: 0.85em; color: #0073aa;"><?php echo esc_html($url); ?></a>
        </td>
    </tr>
    <?php if (!empty($social) && is_array($social)) : ?>
    <tr>
        <td colspan="2" style="padding: 10px;">
            <div class="hsz-social-links" style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($social as $platform => $profile) : ?>
                    <?php if (!empty($profile['url'])) : ?>
                        <a href="<?php echo esc_url($profile['url']); ?>" class="hsz-social-link hsz-<?php echo esc_attr($platform); ?>" target="_blank" rel="nofollow noopener noreferrer" style="padding: 6px 10px; background-color: #f0f0f0; border-radius: 4px; font-size: 0.85em; color: #444; text-decoration: none;">
                            <?php echo esc_html(ucfirst($platform)); ?>
                            <?php if (!empty($profile['username'])) : ?>
                                <span class="hsz-social-username" style="font-weight: normal;">: <?php echo esc_html($profile['username']); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($disclaimer)) : ?>
    <tr>
        <td colspan="2" style="padding: 10px; font-size: 0.75em; color: #666;">
            <?php echo esc_html($disclaimer); ?>
        </td>
    </tr>
    <?php endif; ?>
</table>
