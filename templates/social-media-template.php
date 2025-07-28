<?php
/**
 * Template: Social Media Icons
 *
 * Renders a list of social media icons based on found profile links.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.0
 *
 * @var array $social An array of social media profile links.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// A secure mapping of domain to icon class and brand name.
// This prevents deriving class names directly from URLs (XSS risk).
$social_icon_map = [
	'facebook.com'   => [ 'icon' => 'fa-facebook', 'name' => 'Facebook' ],
	'twitter.com'    => [ 'icon' => 'fa-twitter', 'name' => 'Twitter' ],
	'x.com'          => [ 'icon' => 'fa-twitter', 'name' => 'X (Twitter)' ],
	'linkedin.com'   => [ 'icon' => 'fa-linkedin', 'name' => 'LinkedIn' ],
	'instagram.com'  => [ 'icon' => 'fa-instagram', 'name' => 'Instagram' ],
	'youtube.com'    => [ 'icon' => 'fa-youtube', 'name' => 'YouTube' ],
	'pinterest.com'  => [ 'icon' => 'fa-pinterest', 'name' => 'Pinterest' ],
	'github.com'     => [ 'icon' => 'fa-github', 'name' => 'GitHub' ],
	'vimeo.com'      => [ 'icon' => 'fa-vimeo', 'name' => 'Vimeo' ],
	't.me'           => [ 'icon' => 'fa-telegram', 'name' => 'Telegram' ],
	'reddit.com'     => [ 'icon' => 'fa-reddit', 'name' => 'Reddit' ],
	'tiktok.com'     => [ 'icon' => 'fa-tiktok', 'name' => 'TikTok' ],
	'threads.net'    => [ 'icon' => 'fa-threads', 'name' => 'Threads' ],
	'discord.com'    => [ 'icon' => 'fa-discord', 'name' => 'Discord' ],
	'twitch.tv'      => [ 'icon' => 'fa-twitch', 'name' => 'Twitch' ],
	'soundcloud.com' => [ 'icon' => 'fa-soundcloud', 'name' => 'SoundCloud' ],
];

?>
<div class="hsz-social-links">
	<?php foreach ( $social as $link ) : ?>
		<?php
		$host        = wp_parse_url( $link, PHP_URL_HOST );
		$icon_class  = 'fa-link'; // Default icon.
		$brand_name  = 'Social Link'; // Default title.

		if ( $host ) {
			// Normalize host by removing 'www.'
			$normalized_host = preg_replace( '/^www\./i', '', $host );

			if ( isset( $social_icon_map[ $normalized_host ] ) ) {
				$icon_class = $social_icon_map[ $normalized_host ]['icon'];
				$brand_name = $social_icon_map[ $normalized_host ]['name'];
			}
		}
		?>
		<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $brand_name ); ?>">
			<i class="fab <?php echo esc_attr( $icon_class ); ?>"></i>
		</a>
	<?php endforeach; ?>
</div>

