<?php
/**
 * Template: Classic Metadata Display
 *
 * This template provides a standard, detailed view of the analyzed metadata.
 * It is designed to be included by a widget or shortcode.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 *
 * @var string $url                 The URL being analyzed.
 * @var string $display_title       The title of the page.
 * @var string $display_description The description of the page.
 * @var string $favicon             The URL of the favicon.
 * @var array  $social              An array of social media profile links.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="hsz-card hsz-classic">
	<?php if ( ! empty( $favicon ) ) : ?>
		<div class="hsz-favicon">
			<img src="<?php echo esc_url( $favicon ); ?>" alt="<?php echo esc_attr( $display_title ); ?> Favicon" />
		</div>
	<?php endif; ?>

	<div class="hsz-content">
		<?php if ( ! empty( $display_title ) ) : ?>
			<h3 class="hsz-title">
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $display_title ); ?>
				</a>
			</h3>
		<?php endif; ?>

		<?php if ( ! empty( $display_description ) ) : ?>
			<p class="hsz-description"><?php echo esc_html( $display_description ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	// Include the social media template part if social links were found.
	if ( ! empty( $social ) && is_array( $social ) ) {
		// The 'social' variable is passed to the included template.
		include 'social-media-template.php';
	}
	?>
</div>
