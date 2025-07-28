<?php
/**
 * Template: Compact Metadata Display
 *
 * A minimalist view of the analyzed metadata, suitable for small areas.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 *
 * @var string $url           The URL being analyzed.
 * @var string $display_title The title of the page.
 * @var string $favicon       The URL of the favicon.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hsz-card hsz-compact">
	<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-compact-link">
		<?php if ( ! empty( $favicon ) ) : ?>
			<img src="<?php echo esc_url( $favicon ); ?>" class="hsz-favicon" alt="<?php echo esc_attr( $display_title ); ?> Favicon" />
		<?php endif; ?>

		<?php if ( ! empty( $display_title ) ) : ?>
			<span class="hsz-title"><?php echo esc_html( $display_title ); ?></span>
		<?php endif; ?>
	</a>
</div>
