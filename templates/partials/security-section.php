<?php
/**
 * Template Partial: Security Analysis Section
 *
 * Displays comprehensive security analysis including SSL/TLS status,
 * security headers, VirusTotal results, and URLScan.io data.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 *
 * @var array $security Security analysis data
 * @var array $metadata Full metadata array
 * @var array $api_data API data from VirusTotal, URLScan.io, etc.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get security data from multiple possible sources
$security_data = $security ?? $metadata['security'] ?? [];
$virustotal_data = $api_data['virustotal'] ?? $security_data['virustotal'] ?? [];
$urlscan_data = $api_data['urlscan'] ?? $security_data['urlscan'] ?? [];

// Skip section if no security data
if ( empty( $security_data ) && empty( $virustotal_data ) && empty( $urlscan_data ) ) {
    return;
}
?>

<div class="hsz-security-section">
    <h4 class="hsz-section-title">
        <span class="hsz-icon hsz-icon-security"></span>
        <?php esc_html_e( 'Security Analysis', 'hellaz-sitez-analyzer' ); ?>
    </h4>

    <!-- Overall Security Grade -->
    <?php if ( isset( $security_data['overall_grade'] ) ): ?>
        <div class="hsz-security-grade">
            <div class="hsz-grade-display">
                <span class="hsz-grade hsz-grade-<?php echo esc_attr( strtolower( $security_data['overall_grade'] ) ); ?>">
                    <?php echo esc_html( $security_data['overall_grade'] ); ?>
                </span>
                <span class="hsz-grade-label">
                    <?php esc_html_e( 'Security Grade', 'hellaz-sitez-analyzer' ); ?>
                </span>
            </div>
            <?php if ( isset( $security_data['overall_score'] ) ): ?>
                <div class="hsz-score-bar">
                    <div class="hsz-score-fill" style="width: <?php echo esc_attr( $security_data['overall_score'] ); ?>%"></div>
                    <span class="hsz-score-text"><?php echo esc_html( $security_data['overall_score'] ); ?>/100</span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- SSL/TLS Certificate Analysis -->
    <?php if ( ! empty( $security_data['ssl'] ) ): ?>
        <div class="hsz-ssl-analysis">
            <h5><?php esc_html_e( 'SSL/TLS Certificate', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-ssl-status hsz-ssl-<?php echo esc_attr( $security_data['ssl']['status'] ?? 'unknown' ); ?>">
                <div class="hsz-ssl-indicator">
                    <?php if ( ( $security_data['ssl']['valid'] ?? false ) === true ): ?>
                        <span class="hsz-ssl-icon hsz-ssl-valid">🔒</span>
                        <span class="hsz-ssl-text"><?php esc_html_e( 'Valid SSL Certificate', 'hellaz-sitez-analyzer' ); ?></span>
                    <?php else: ?>
                        <span class="hsz-ssl-icon hsz-ssl-invalid">⚠️</span>
                        <span class="hsz-ssl-text"><?php esc_html_e( 'SSL Issues Detected', 'hellaz-sitez-analyzer' ); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $security_data['ssl']['certificate'] ) ): ?>
                    <div class="hsz-ssl-details">
                        <?php $cert = $security_data['ssl']['certificate']; ?>
                        
                        <?php if ( ! empty( $cert['issuer'] ) ): ?>
                            <div class="hsz-ssl-detail">
                                <span class="hsz-ssl-label"><?php esc_html_e( 'Issued by:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-ssl-value"><?php echo esc_html( $cert['issuer'] ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $cert['valid_from'] ) ): ?>
                            <div class="hsz-ssl-detail">
                                <span class="hsz-ssl-label"><?php esc_html_e( 'Valid from:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-ssl-value"><?php echo esc_html( date( 'M j, Y', strtotime( $cert['valid_from'] ) ) ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $cert['valid_to'] ) ): ?>
                            <div class="hsz-ssl-detail">
                                <span class="hsz-ssl-label"><?php esc_html_e( 'Valid until:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-ssl-value"><?php echo esc_html( date( 'M j, Y', strtotime( $cert['valid_to'] ) ) ); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $cert['days_until_expiry'] ) ): ?>
                            <div class="hsz-ssl-detail">
                                <span class="hsz-ssl-label"><?php esc_html_e( 'Days until expiry:', 'hellaz-sitez-analyzer' ); ?></span>
                                <span class="hsz-ssl-value hsz-expiry-<?php echo esc_attr( $cert['days_until_expiry'] < 30 ? 'warning' : 'good' ); ?>">
                                    <?php echo esc_html( $cert['days_until_expiry'] ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Security Headers Analysis -->
    <?php if ( ! empty( $security_data['headers'] ) ): ?>
        <div class="hsz-security-headers">
            <h5><?php esc_html_e( 'Security Headers', 'hellaz-sitez-analyzer' ); ?></h5>
            
            <div class="hsz-headers-grid">
                <?php
                $security_headers = [
                    'strict-transport-security' => [
                        'name' => 'HSTS',
                        'description' => __( 'HTTP Strict Transport Security', 'hellaz-sitez-analyzer' )
                    ],
                    'content-security-policy' => [
                        'name' => 'CSP',
                        'description' => __( 'Content Security Policy', 'hellaz-sitez-analyzer' )
                    ],
                    'x-frame-options' => [
                        'name' => 'X-Frame-Options',
                        'description' => __( 'Clickjacking Protection', 'hellaz-sitez-analyzer' )
                    ],
                    'x-content-type-options' => [
                        'name' => 'X-Content-Type-Options',
                        'description' => __( 'MIME Type Sniffing Protection', 'hellaz-sitez-analyzer' )
                    ],
                    'x-xss-protection' => [
                        'name' => 'X-XSS-Protection',
                        'description' => __( 'XSS Protection', 'hellaz-sitez-analyzer' )
                    ],
                    'referrer-policy' => [
                        'name' => 'Referrer-Policy',
                        'description' => __( 'Referrer Policy', 'hellaz-sitez-analyzer' )
                    ]
                ];
                ?>

                <?php foreach ( $security_headers as $header_key => $header_info ): ?>
                    <?php 
                    $header_present = isset( $security_data['headers'][ $header_key ] );
                    $header_value = $security_data['headers'][ $header_key ] ?? null;
                    ?>
                    <div class="hsz-header-item hsz-header-<?php echo esc_attr( $header_present ? 'present' : 'missing' ); ?>">
                        <div class="hsz-header-status">
                            <span class="hsz-header-icon"><?php echo $header_present ? '✓' : '✗'; ?></span>
                            <span class="hsz-header-name"><?php echo esc_html( $header_info['name'] ); ?></span>
                        </div>
                        <div class="hsz-header-description"><?php echo esc_html( $header_info['description'] ); ?></div>
                        <?php if ( $header_present && $header_value ): ?>
                            <div class="hsz-header-value">
                                <code><?php echo esc_html( wp_trim_words( $header_value, 8 ) ); ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- VirusTotal Analysis -->
    <?php if ( ! empty( $virustotal_data ) ): ?>
        <div class="hsz-virustotal-analysis">
            <h5>
                <?php esc_html_e( 'VirusTotal Scan', 'hellaz-sitez-analyzer' ); ?>
                <span class="hsz-api-badge">API</span>
            </h5>

            <div class="hsz-virustotal-result">
                <?php if ( isset( $virustotal_data['threat_detected'] ) ): ?>
                    <div class="hsz-threat-status hsz-threat-<?php echo esc_attr( $virustotal_data['threat_detected'] ? 'detected' : 'clean' ); ?>">
                        <div class="hsz-threat-indicator">
                            <?php if ( $virustotal_data['threat_detected'] ): ?>
                                <span class="hsz-threat-icon">⚠️</span>
                                <span class="hsz-threat-text"><?php esc_html_e( 'Threats Detected', 'hellaz-sitez-analyzer' ); ?></span>
                            <?php else: ?>
                                <span class="hsz-threat-icon">✅</span>
                                <span class="hsz-threat-text"><?php esc_html_e( 'No Threats Detected', 'hellaz-sitez-analyzer' ); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ( isset( $virustotal_data['positives'] ) && isset( $virustotal_data['total'] ) ): ?>
                            <div class="hsz-scan-results">
                                <span class="hsz-scan-stats">
                                    <?php printf( 
                                        esc_html__( '%d of %d security vendors flagged this URL', 'hellaz-sitez-analyzer' ),
                                        intval( $virustotal_data['positives'] ),
                                        intval( $virustotal_data['total'] )
                                    ); ?>
                                </span>
                                
                                <?php if ( $virustotal_data['positives'] > 0 ): ?>
                                    <div class="hsz-threat-bar">
                                        <div class="hsz-threat-fill" style="width: <?php echo esc_attr( ( $virustotal_data['positives'] / $virustotal_data['total'] ) * 100 ); ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $virustotal_data['scan_date'] ) ): ?>
                    <div class="hsz-scan-info">
                        <span class="hsz-scan-label"><?php esc_html_e( 'Last scanned:', 'hellaz-sitez-analyzer' ); ?></span>
                        <span class="hsz-scan-date"><?php echo esc_html( date( 'M j, Y H:i', strtotime( $virustotal_data['scan_date'] ) ) ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $virustotal_data['permalink'] ) ): ?>
                    <div class="hsz-scan-link">
                        <a href="<?php echo esc_url( $virustotal_data['permalink'] ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-external-link">
                            <?php esc_html_e( 'View Full VirusTotal Report', 'hellaz-sitez-analyzer' ); ?>
                            <span class="hsz-external-icon">↗</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Threat Types -->
                <?php if ( ! empty( $virustotal_data['threat_types'] ) && is_array( $virustotal_data['threat_types'] ) ): ?>
                    <div class="hsz-threat-types">
                        <h6><?php esc_html_e( 'Detected Threat Types:', 'hellaz-sitez-analyzer' ); ?></h6>
                        <div class="hsz-threat-tags">
                            <?php foreach ( $virustotal_data['threat_types'] as $threat_type ): ?>
                                <span class="hsz-threat-tag"><?php echo esc_html( $threat_type ); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- URLScan.io Analysis -->
    <?php if ( ! empty( $urlscan_data ) ): ?>
        <div class="hsz-urlscan-analysis">
            <h5>
                <?php esc_html_e( 'URLScan.io Analysis', 'hellaz-sitez-analyzer' ); ?>
                <span class="hsz-api-badge">API</span>
            </h5>

            <div class="hsz-urlscan-result">
                <!-- Screenshot -->
                <?php if ( ! empty( $urlscan_data['screenshot'] ) ): ?>
                    <div class="hsz-urlscan-screenshot">
                        <h6><?php esc_html_e( 'Website Screenshot', 'hellaz-sitez-analyzer' ); ?></h6>
                        <div class="hsz-screenshot-container">
                            <img src="<?php echo esc_url( $urlscan_data['screenshot'] ); ?>" 
                                 alt="<?php esc_attr_e( 'Website screenshot from URLScan.io', 'hellaz-sitez-analyzer' ); ?>"
                                 class="hsz-screenshot-image"
                                 loading="lazy">
                            <div class="hsz-screenshot-overlay">
                                <a href="<?php echo esc_url( $urlscan_data['screenshot'] ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-screenshot-link">
                                    <?php esc_html_e( 'View Full Size', 'hellaz-sitez-analyzer' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Scan Information -->
                <?php if ( ! empty( $urlscan_data['page_info'] ) ): ?>
                    <div class="hsz-urlscan-info">
                        <h6><?php esc_html_e( 'Page Information', 'hellaz-sitez-analyzer' ); ?></h6>
                        <div class="hsz-page-info-grid">
                            
                            <?php if ( isset( $urlscan_data['page_info']['server'] ) ): ?>
                                <div class="hsz-info-item">
                                    <span class="hsz-info-label"><?php esc_html_e( 'Server:', 'hellaz-sitez-analyzer' ); ?></span>
                                    <span class="hsz-info-value"><?php echo esc_html( $urlscan_data['page_info']['server'] ); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ( isset( $urlscan_data['page_info']['ip'] ) ): ?>
                                <div class="hsz-info-item">
                                    <span class="hsz-info-label"><?php esc_html_e( 'IP Address:', 'hellaz-sitez-analyzer' ); ?></span>
                                    <span class="hsz-info-value"><?php echo esc_html( $urlscan_data['page_info']['ip'] ); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ( isset( $urlscan_data['page_info']['country'] ) ): ?>
                                <div class="hsz-info-item">
                                    <span class="hsz-info-label"><?php esc_html_e( 'Country:', 'hellaz-sitez-analyzer' ); ?></span>
                                    <span class="hsz-info-value"><?php echo esc_html( $urlscan_data['page_info']['country'] ); ?></span>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endif; ?>

                <!-- Security Information from URLScan -->
                <?php if ( ! empty( $urlscan_data['security_info'] ) ): ?>
                    <div class="hsz-urlscan-security">
                        <h6><?php esc_html_e( 'Security Analysis', 'hellaz-sitez-analyzer' ); ?></h6>
                        <?php $sec_info = $urlscan_data['security_info']; ?>
                        
                        <?php if ( isset( $sec_info['malicious'] ) ): ?>
                            <div class="hsz-security-result hsz-security-<?php echo esc_attr( $sec_info['malicious'] ? 'malicious' : 'clean' ); ?>">
                                <span class="hsz-security-icon"><?php echo $sec_info['malicious'] ? '⚠️' : '✅'; ?></span>
                                <span class="hsz-security-text">
                                    <?php echo $sec_info['malicious'] ? 
                                        esc_html__( 'Potentially malicious content detected', 'hellaz-sitez-analyzer' ) : 
                                        esc_html__( 'No malicious content detected', 'hellaz-sitez-analyzer' ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Link to full URLScan report -->
                <?php if ( ! empty( $urlscan_data['scan_url'] ) ): ?>
                    <div class="hsz-urlscan-link">
                        <a href="<?php echo esc_url( $urlscan_data['scan_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="hsz-external-link">
                            <?php esc_html_e( 'View Full URLScan Report', 'hellaz-sitez-analyzer' ); ?>
                            <span class="hsz-external-icon">↗</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Security Recommendations -->
    <?php if ( ! empty( $security_data['recommendations'] ) ): ?>
        <div class="hsz-security-recommendations">
            <h5><?php esc_html_e( 'Security Recommendations', 'hellaz-sitez-analyzer' ); ?></h5>
            <ul class="hsz-recommendations-list">
                <?php foreach ( array_slice( $security_data['recommendations'], 0, 5 ) as $recommendation ): ?>
                    <li class="hsz-recommendation-item">
                        <span class="hsz-recommendation-icon">🔒</span>
                        <span class="hsz-recommendation-text"><?php echo esc_html( $recommendation ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Security Issues -->
    <?php if ( ! empty( $security_data['issues'] ) ): ?>
        <div class="hsz-security-issues">
            <h5><?php esc_html_e( 'Security Issues', 'hellaz-sitez-analyzer' ); ?></h5>
            <ul class="hsz-issues-list">
                <?php foreach ( array_slice( $security_data['issues'], 0, 5 ) as $issue ): ?>
                    <li class="hsz-issue-item">
                        <span class="hsz-issue-icon">⚠️</span>
                        <span class="hsz-issue-text"><?php echo esc_html( $issue ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>
