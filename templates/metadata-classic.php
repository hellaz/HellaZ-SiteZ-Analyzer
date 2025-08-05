<?php
/**
 * Classic display template for HellaZ SiteZ Analyzer
 *
 * Variables available:
 *   $metadata     Array of metadata results
 *   $social       Array of social profiles
 *   $performance  Array of performance data
 *   $security     Array of security data
 *   $feeds        Array of feed data
 *   $contact      Array of contact information
 *   $analysis     Full analysis results
 */
?>
<div class="hsz-metadata-classic hsz-template">
    <h2><?php echo esc_html( $metadata['title'] ?? parse_url( $analysis['url'], PHP_URL_HOST ) ); ?></h2>
    <p class="hsz-description"><?php echo esc_html( $metadata['description'] ?? '' ); ?></p>

    <?php if ( ! empty( $metadata['favicon'] ) ) : ?>
        <img class="hsz-favicon" src="<?php echo esc_url( $metadata['favicon'] ); ?>" alt="">
    <?php endif; ?>

    <?php if ( ! empty( $metadata['images'] ) ) : ?>
        <div class="hsz-images">
            <?php foreach ( array_slice( $metadata['images'], 0, 5 ) as $img ) : ?>
                <img src="<?php echo esc_url( $img['src'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $social ) ) : ?>
        <div class="hsz-social-profiles">
            <h3><?php esc_html_e( 'Social Profiles', 'hellaz-sitez-analyzer' ); ?></h3>
            <ul>
                <?php foreach ( $social as $profile ) : ?>
                    <li><a href="<?php echo esc_url( $profile['url'] ); ?>" target="_blank"><?php echo esc_html( ucfirst( $profile['platform'] ) ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $performance ) ) : ?>
        <div class="hsz-performance">
            <h3><?php esc_html_e( 'Performance', 'hellaz-sitez-analyzer' ); ?></h3>
            <p><?php printf( esc_html__( 'Overall Score: %d', 'hellaz-sitez-analyzer' ), $performance['performance_summary']['overall_score'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $security ) ) : ?>
        <div class="hsz-security">
            <h3><?php esc_html_e( 'Security', 'hellaz-sitez-analyzer' ); ?></h3>
            <p><?php printf( esc_html__( 'Threat Level: %s', 'hellaz-sitez-analyzer' ), ucfirst( $security['overall_risk'] ) ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $feeds ) ) : ?>
        <div class="hsz-feeds">
            <h3><?php esc_html_e( 'Feeds', 'hellaz-sitez-analyzer' ); ?></h3>
            <ul>
                <?php foreach ( $feeds as $feed ) : ?>
                    <li><a href="<?php echo esc_url( $feed['url'] ); ?>" target="_blank"><?php echo esc_html( $feed['title'] ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $contact ) ) : ?>
        <div class="hsz-contact-info">
            <h3><?php esc_html_e( 'Contact Information', 'hellaz-sitez-analyzer' ); ?></h3>

            <?php if ( ! empty( $contact['emails'] ) ) : ?>
                <p><strong><?php esc_html_e( 'Email:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $contact['emails'], 'email' ) ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $contact['phones'] ) ) : ?>
                <p><strong><?php esc_html_e( 'Phone:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $contact['phones'], 'formatted' ) ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $contact['addresses'] ) ) : ?>
                <p><strong><?php esc_html_e( 'Address:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php
                    $addresses = wp_list_pluck( $contact['addresses'], 'formatted' );
                    echo esc_html( implode( '; ', $addresses ) );
                    ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $contact['contact_forms'] ) ) : ?>
                <p><strong><?php esc_html_e( 'Contact Form:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php esc_html_e( 'Detected', 'hellaz-sitez-analyzer' ); ?>
                </p>
            <?php endif; ?>

            <?php if ( ! empty( $contact['business_hours'] ) ) : ?>
                <p><strong><?php esc_html_e( 'Business Hours:', 'hellaz-sitez-analyzer' ); ?></strong>
                    <?php
                    $hours = [];
                    foreach ( $contact['business_hours'] as $h ) {
                        $hours[] = esc_html( "{$h['day']} {$h['hours']}" );
                    }
                    echo esc_html( implode( '; ', $hours ) );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Recommendations -->
    <?php if ( ! empty( $analysis['recommendations'] ) ) : ?>
        <div class="hsz-recommendations">
            <h3><?php esc_html_e( 'Recommendations', 'hellaz-sitez-analyzer' ); ?></h3>
            <ul>
                <?php foreach ( $analysis['recommendations'] as $rec ) : ?>
                    <li><?php echo esc_html( $rec['recommendation'] ?? $rec['title'] ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
