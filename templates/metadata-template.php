// Template for metadata display
// metadata-template.php
<img src="<?php echo esc_url(HSZ_PLUGIN_URL . 'assets/images/fallback-image.png'); ?>" 
     alt="<?php esc_attr_e('Fallback Image', 'hellaz-sitez-analyzer'); ?>" 
     class="hsz-fallback-image">
<div class="hsz-metadata">
    <h3><?php echo esc_html($metadata['title']); ?></h3>
    <p><?php echo esc_html($metadata['description']); ?></p>
</div>
