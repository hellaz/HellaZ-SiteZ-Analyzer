<?php
/**
 * Modern display template for HellaZ SiteZ Analyzer
 */
?>
<div class="hsz-metadata-modern hsz-template">
    <header>
        <?php if ( ! empty( $metadata['favicon'] ) ) : ?>
            <img class="site-favicon" src="<?php echo esc_url( $metadata['favicon'] ); ?>" alt="">
        <?php endif; ?>
        <h3><?php echo esc_html( $metadata['title'] ?? parse_url( $analysis['url'], PHP_URL_HOST ) ); ?></h3>
    </header>

    <div class="site-summary">
        <div class="summary-item">
            <strong><?php esc_html_e( 'Performance', 'hellaz-sitez-analyzer' ); ?></strong>
            <span><?php echo esc_html( $performance['performance_summary']['overall_score'] ); ?></span>
        </div>
        <div class="summary-item">
            <strong><?php esc_html_e( 'Security', 'hellaz-sitez-analyzer' ); ?></strong>
            <span><?php echo esc_html( ucfirst( $security['overall_risk'] ) ); ?></span>
        </div>
        <?php if ( ! empty( $contact['emails'] ) ) : ?>
            <div class="summary-item">
                <strong><?php esc_html_e( 'Email', 'hellaz-sitez-analyzer' ); ?></strong>
                <span><?php echo esc_html( current( wp_list_pluck( $contact['emails'], 'email' ) ) ); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $contact['phones'] ) ) : ?>
        <div class="contact-block">
            <span class="dashicons dashicons-phone"></span>
            <?php echo esc_html( current( wp_list_pluck( $contact['phones'], 'formatted' ) ) ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $contact['addresses'] ) ) : ?>
        <div class="contact-block">
            <span class="dashicons dashicons-location"></span>
            <?php echo esc_html( current( wp_list_pluck( $contact['addresses'], 'formatted' ) ) ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $contact['business_hours'] ) ) : ?>
        <div class="contact-block">
            <span class="dashicons dashicons-clock"></span>
            <?php
            $bh = current( $contact['business_hours'] );
            echo esc_html( "{$bh['day']} {$bh['hours']}" );
            ?>
        </div>
    <?php endif; ?>

    <footer>
        <a href="<?php echo esc_url( $analysis['url'] ); ?>" target="_blank"><?php esc_html_e( 'Visit Site', 'hellaz-sitez-analyzer' ); ?></a>
    </footer>
</div>
