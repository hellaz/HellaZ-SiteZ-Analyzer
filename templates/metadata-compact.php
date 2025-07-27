<?php
/**
 * metadata-compact.php
 * Minimal clean display template
 *
 * Variables expected:
 * - $url, $title, $description, $disclaimer
 */
?>

<div class="hsz-metadata-compact" style="max-width: 600px; font-family: Arial, sans-serif; font-size: 14px; color: #222;">
    <h3 style="margin: 0 0 4px 0;"><?php echo esc_html($title); ?></h3>
    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener noreferrer" style="font-size: 0.9em; color: #0073aa; text-decoration: none;"><?php echo esc_html($url); ?></a>
    <?php if (!empty($disclaimer)) : ?>
        <p style="margin-top: 6px; font-size: 0.75em; color: #666;"><?php echo esc_html($disclaimer); ?></p>
    <?php endif; ?>
</div>
