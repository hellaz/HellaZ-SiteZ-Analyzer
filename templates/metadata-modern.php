<?php
/**
 * Template: Modern Metadata Display
 *
 * A modern, card-based view with a background image.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 *
 * @var string $url                 The URL being analyzed.
 * @var string $display_title       The title of the page.
 * @var string $display_description The description of the page.
 * @var string $favicon             The URL of the favicon.
 * @var array  $og                  Array of Open Graph tags.
 * @var array  $twitter             Array of Twitter Card tags.
 * @var array  $social              An array of social media profile links.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine the best image to use for the background, prioritizing social images.
$bg_image = $og['image'] ?? ( $twitter['image'] ?? '' );

$style = ! empty( $bg_image ) ? 'style="background-image: url(' . esc_url( $bg_image ) . ');"' : '';
?>
<div class="hsz-card hsz-modern" <?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-modern-link">
		<div class="hsz-modern-overlay">
			<div class="hsz-content">
				<?php if ( ! empty( $favicon ) ) : ?>
					<img src="<?php echo esc_url( $favicon ); ?>" class="hsz-favicon" alt="<?php echo esc_attr( $display_title ); ?> Favicon" />
				<?php endif; ?>

				<?php if ( ! empty( $display_title ) ) : ?>
					<h3 class="hsz-title"><?php echo esc_html( $display_title ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( $display_description ) ) : ?>
					<p class="hsz-description"><?php echo esc_html( $display_description ); ?></p>
				<?php endif; ?>
			</div>

			<?php
			if ( ! empty( $social ) && is_array( $social ) ) {
				include 'social-media-template.php';
			}
			?>
		</div>
	</a>
</div>
