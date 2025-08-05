<?php
/**
 * Compact display template for HellaZ SiteZ Analyzer
 */
?>
<div class="hsz-metadata-compact hsz-template">
    <h4><?php echo esc_html( $metadata['title'] ?? parse_url( $analysis['url'], PHP_URL_HOST ) ); ?></h4>

    <?php if ( ! empty( $contact['emails'] ) ) : ?>
        <p><span class="dashicons dashicons-email"></span>
        <?php echo esc_html( current( wp_list_pluck( $contact['emails'], 'email' ) ) ); ?></p>
    <?php endif; ?>

    <?php if ( ! empty( $contact['phones'] ) ) : ?>
        <p><span class="dashicons dashicons-phone"></span>
        <?php echo esc_html( current( wp_list_pluck( $contact['phones'], 'formatted' ) ) ); ?></p>
    <?php endif; ?>

    <?php if ( ! empty( $contact['addresses'] ) ) : ?>
        <p><span class="dashicons dashicons-location"></span>
        <?php echo esc_html( current( wp_list_pluck( $contact['addresses'], 'formatted' ) ) ); ?></p>
    <?php endif; ?>

    <p>
        <span class="dashicons dashicons-chart-bar"></span>
        <?php printf( esc_html__( 'Perf: %d', 'hellaz-sitez-analyzer' ), $performance['performance_summary']['overall_score'] ); ?>
        &nbsp;|&nbsp;
        <span class="dashicons dashicons-shield"></span>
        <?php printf( esc_html__( 'Sec: %s', 'hellaz-sitez-analyzer' ), ucfirst( $security['overall_risk'] ) ); ?>
    </p>
</div>
