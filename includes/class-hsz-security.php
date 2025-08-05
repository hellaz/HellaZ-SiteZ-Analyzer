<?php
/**
 * Security analysis functionality for HellaZ SiteZ Analyzer.
 *
 * This class provides comprehensive security analysis including SSL/TLS evaluation,
 * security headers assessment, vulnerability scanning, malware detection,
 * and security grading with Phase 1 enhancements.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.0.2
 */

namespace HSZ;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Security
 *
 * Handles all security-related analysis and checks with enhanced capabilities.
 */
class Security {

	/**
	 * Security API endpoints
	 */
	private const VIRUSTOTAL_API_URL = 'https://www.virustotal.com/vtapi/v2/url/report';
	private const URLSCAN_API_URL = 'https://urlscan.io/api/v1/search/';
	private const SSL_LABS_API_URL = 'https://api.ssllabs.com/api/v3/analyze';
	
	/**
	 * Security check weights for scoring
	 */
	private const SECURITY_WEIGHTS = [
		'ssl_analysis' => 0.30,
		'security_headers' => 0.25,
		'malware_detection' => 0.20,
		'vulnerability_scan' => 0.15,
		'blacklist_status' => 0.10
	];

	/**
	 * Required security headers with their importance weights
	 */
	private const SECURITY_HEADERS = [
		'strict-transport-security' => ['weight' => 0.25, 'critical' => true],
		'content-security-policy' => ['weight' => 0.20, 'critical' => true],
		'x-frame-options' => ['weight' => 0.15, 'critical' => true],
		'x-content-type-options' => ['weight' => 0.15, 'critical' => true],
		'x-xss-protection' => ['weight' => 0.10, 'critical' => false],
		'referrer-policy' => ['weight' => 0.10, 'critical' => false],
		'permissions-policy' => ['weight' => 0.05, 'critical' => false]
	];

	/**
	 * Perform comprehensive security analysis
	 *
	 * @param string $url Website URL to analyze.
	 * @param array $options Analysis options.
	 * @return array Security analysis results.
	 */
	public function analyze_security( string $url, array $options = [] ): array {
		// Validate URL
		$url_validation = Utils::validate_url( $url );
		if ( is_wp_error( $url_validation ) ) {
			return ['error' => $url_validation->get_error_message()];
		}

		// Set default options
		$options = wp_parse_args( $options, [
			'ssl_analysis' => get_option( 'hsz_ssl_analysis_enabled', true ),
			'security_headers' => get_option( 'hsz_security_headers_check', true ),
			'malware_scan' => get_option( 'hsz_security_analysis_enabled', true ),
			'vulnerability_scan' => get_option( 'hsz_vulnerability_scan_enabled', true ),
			'blacklist_check' => get_option( 'hsz_security_analysis_enabled', true ),
			'force_refresh' => false,
			'detailed_analysis' => true
		]);

		// Check cache first
		$cache_key = 'security_' . Utils::generate_url_hash( $url ) . '_' . md5( serialize( $options ) );
		$cached_data = Utils::get_cache( $cache_key );

		if ( $cached_data && ! $options['force_refresh'] ) {
			return $cached_data;
		}

		Utils::start_timer( 'security_analysis' );
		
		$security_data = [
			'url' => $url,
			'timestamp' => current_time( 'mysql', true ),
			'ssl_analysis' => [],
			'security_headers' => [],
			'malware_scan' => [],
			'vulnerability_scan' => [],
			'blacklist_check' => [],
			'certificate_info' => [],
			'security_score' => 0,
			'security_grade' => 'F',
			'overall_status' => 'unknown',
			'risk_level' => 'unknown',
			'issues' => [],
			'recommendations' => [],
			'passed_checks' => 0,
			'total_checks' => 0
		];

		// Perform SSL/TLS Analysis
		if ( $options['ssl_analysis'] ) {
			$security_data['ssl_analysis'] = $this->analyze_ssl_tls( $url, $options );
		}

		// Analyze Security Headers
		if ( $options['security_headers'] ) {
			$security_data['security_headers'] = $this->analyze_security_headers( $url );
		}

		// Scan for Malware
		if ( $options['malware_scan'] ) {
			$security_data['malware_scan'] = $this->scan_malware( $url );
		}

		// Vulnerability Scanning
		if ( $options['vulnerability_scan'] ) {
			$security_data['vulnerability_scan'] = $this->scan_vulnerabilities( $url );
		}

		// Blacklist Checking
		if ( $options['blacklist_check'] ) {
			$security_data['blacklist_check'] = $this->check_blacklists( $url );
		}

		// Enhanced Certificate Analysis
		if ( $options['ssl_analysis'] && Utils::is_https( $url ) ) {
			$security_data['certificate_info'] = $this->get_detailed_certificate_info( $url );
		}

		// Calculate overall security metrics
		$security_data = $this->calculate_security_metrics( $security_data );

		// Generate recommendations
		$security_data = $this->generate_security_recommendations( $security_data );

		$security_data['analysis_time'] = Utils::stop_timer( 'security_analysis' );

		// Cache the results
		$cache_duration = get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 );
		Utils::set_cache( $cache_key, $security_data, $cache_duration );

		// Store in enhanced database cache
		$this->store_security_results( $url, $security_data );

		return $security_data;
	}

	/**
	 * Analyze SSL/TLS configuration
	 *
	 * @param string $url Website URL.
	 * @param array $options Analysis options.
	 * @return array SSL analysis results.
	 */
	private function analyze_ssl_tls( string $url, array $options = [] ): array {
		$parsed_url = parse_url( $url );
		$host = $parsed_url['host'] ?? '';
		$port = $parsed_url['port'] ?? 443;
		
		if ( empty( $host ) ) {
			return ['error' => __( 'Invalid hostname for SSL analysis.', 'hellaz-sitez-analyzer' )];
		}

		$ssl_analysis = [
			'enabled' => false,
			'protocol_version' => '',
			'cipher_suite' => '',
			'key_size' => 0,
			'certificate_valid' => false,
			'certificate_trusted' => false,
			'certificate_expires' => null,
			'days_until_expiry' => 0,
			'certificate_issuer' => '',
			'certificate_subject' => '',
			'supports_tls_1_3' => false,
			'supports_tls_1_2' => false,
			'supports_sni' => false,
			'hsts_enabled' => false,
			'hsts_max_age' => 0,
			'vulnerabilities' => [],
			'grade' => 'F',
			'score' => 0,
			'issues' => [],
			'recommendations' => []
		];

		// Check if HTTPS is available
		if ( ! Utils::is_https( $url ) ) {
			// Test if HTTPS version exists
			$https_url = str_replace( 'http://', 'https://', $url );
			$https_status = Utils::get_http_status( $https_url );
			
			if ( ! is_wp_error( $https_status ) && $https_status >= 200 && $https_status < 400 ) {
				$ssl_analysis['issues'][] = __( 'Website supports HTTPS but is accessed via HTTP', 'hellaz-sitez-analyzer' );
				$ssl_analysis['recommendations'][] = __( 'Redirect all traffic to HTTPS for better security', 'hellaz-sitez-analyzer' );
			} else {
				$ssl_analysis['issues'][] = __( 'Website does not support HTTPS encryption', 'hellaz-sitez-analyzer' );
				$ssl_analysis['recommendations'][] = __( 'Install an SSL certificate to enable HTTPS', 'hellaz-sitez-analyzer' );
				return $ssl_analysis;
			}
		}

		// Perform detailed SSL analysis
		$ssl_context = stream_context_create([
			'ssl' => [
				'capture_peer_cert' => true,
				'capture_peer_cert_chain' => true,
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);

		$stream = @stream_socket_client(
			"ssl://{$host}:{$port}",
			$errno,
			$errstr,
			10,
			STREAM_CLIENT_CONNECT,
			$ssl_context
		);

		if ( $stream ) {
			$ssl_analysis['enabled'] = true;
			$params = stream_context_get_params( $stream );
			$ssl_info = stream_get_meta_data( $stream );
			
			// Extract SSL/TLS information
			if ( isset( $params['options']['ssl']['peer_certificate'] ) ) {
				$cert_info = openssl_x509_parse( $params['options']['ssl']['peer_certificate'] );
				
				if ( $cert_info ) {
					$ssl_analysis['certificate_valid'] = true;
					$ssl_analysis['certificate_expires'] = date( 'Y-m-d H:i:s', $cert_info['validTo_time_t'] );
					$ssl_analysis['certificate_issuer'] = $cert_info['issuer']['CN'] ?? $cert_info['issuer']['O'] ?? 'Unknown';
					$ssl_analysis['certificate_subject'] = $cert_info['subject']['CN'] ?? 'Unknown';
					
					// Calculate days until expiry
					$ssl_analysis['days_until_expiry'] = ceil( ( $cert_info['validTo_time_t'] - time() ) / DAY_IN_SECONDS );
					
					// Check certificate expiration
					if ( $ssl_analysis['days_until_expiry'] < 30 ) {
						$ssl_analysis['issues'][] = sprintf(
							__( 'Certificate expires in %d days', 'hellaz-sitez-analyzer' ),
							$ssl_analysis['days_until_expiry']
						);
						$ssl_analysis['recommendations'][] = __( 'Renew SSL certificate before expiration', 'hellaz-sitez-analyzer' );
					}

					// Extract key information
					$public_key = openssl_pkey_get_public( $params['options']['ssl']['peer_certificate'] );
					if ( $public_key ) {
						$key_details = openssl_pkey_get_details( $public_key );
						$ssl_analysis['key_size'] = $key_details['bits'] ?? 0;
						
						if ( $ssl_analysis['key_size'] < 2048 ) {
							$ssl_analysis['issues'][] = sprintf(
								__( 'Weak key size: %d bits', 'hellaz-sitez-analyzer' ),
								$ssl_analysis['key_size']
							);
							$ssl_analysis['recommendations'][] = __( 'Use at least 2048-bit RSA keys or 256-bit ECC keys', 'hellaz-sitez-analyzer' );
						}
					}
				}
			}
			
			fclose( $stream );
		} else {
			$ssl_analysis['issues'][] = sprintf( __( 'SSL connection failed: %s', 'hellaz-sitez-analyzer' ), $errstr );
		}

		// Check for HSTS header
		$response = wp_remote_head( $url, [
			'timeout' => 15,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);

		if ( ! is_wp_error( $response ) ) {
			$headers = wp_remote_retrieve_headers( $response );
			$hsts_header = $headers['strict-transport-security'] ?? '';
			
			if ( $hsts_header ) {
				$ssl_analysis['hsts_enabled'] = true;
				
				// Extract max-age value
				if ( preg_match( '/max-age=(\d+)/', $hsts_header, $matches ) ) {
					$ssl_analysis['hsts_max_age'] = intval( $matches[1] );
					
					if ( $ssl_analysis['hsts_max_age'] < 31536000 ) { // Less than 1 year
						$ssl_analysis['recommendations'][] = __( 'Consider increasing HSTS max-age to at least 1 year', 'hellaz-sitez-analyzer' );
					}
				}
			} else {
				$ssl_analysis['issues'][] = __( 'HSTS header not implemented', 'hellaz-sitez-analyzer' );
				$ssl_analysis['recommendations'][] = __( 'Implement HTTP Strict Transport Security (HSTS)', 'hellaz-sitez-analyzer' );
			}
		}

		// Use SSL Labs API for detailed analysis if enabled and available
		if ( get_option( 'hsz_ssl_labs_enabled' ) && $ssl_analysis['enabled'] ) {
			$ssl_labs_data = $this->get_ssl_labs_analysis( $host );
			if ( ! isset( $ssl_labs_data['error'] ) ) {
				$ssl_analysis = array_merge( $ssl_analysis, $ssl_labs_data );
			}
		}

		// Calculate SSL score and grade
		$ssl_analysis['score'] = $this->calculate_ssl_score( $ssl_analysis );
		$ssl_analysis['grade'] = Utils::sanitize_grade( $ssl_analysis['score'] );

		return $ssl_analysis;
	}

	/**
	 * Get SSL Labs analysis
	 *
	 * @param string $host Hostname to analyze.
	 * @return array SSL Labs results.
	 */
	private function get_ssl_labs_analysis( string $host ): array {
		$api_url = self::SSL_LABS_API_URL . '?host=' . urlencode( $host ) . '&fromCache=on&maxAge=24';
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 30,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return ['error' => __( 'Invalid JSON response from SSL Labs API.', 'hellaz-sitez-analyzer' )];
		}

		if ( isset( $data['endpoints'][0] ) ) {
			$endpoint = $data['endpoints'][0];
			
			return [
				'ssl_labs_grade' => $endpoint['grade'] ?? 'T',
				'protocol_version' => $endpoint['details']['protocols'][0]['name'] ?? '',
				'cipher_suite' => $endpoint['details']['suites']['list'][0]['name'] ?? '',
				'supports_tls_1_3' => $this->supports_protocol( $endpoint, 'TLS 1.3' ),
				'supports_tls_1_2' => $this->supports_protocol( $endpoint, 'TLS 1.2' ),
				'vulnerabilities' => $this->extract_vulnerabilities( $endpoint ),
				'certificate_trusted' => ( $endpoint['details']['cert']['issues'] ?? 0 ) === 0
			];
		}

		return ['error' => __( 'No SSL Labs data available.', 'hellaz-sitez-analyzer' )];
	}

	/**
	 * Check if endpoint supports specific protocol
	 *
	 * @param array $endpoint SSL Labs endpoint data.
	 * @param string $protocol_name Protocol name to check.
	 * @return bool True if supported.
	 */
	private function supports_protocol( array $endpoint, string $protocol_name ): bool {
		if ( ! isset( $endpoint['details']['protocols'] ) ) {
			return false;
		}

		foreach ( $endpoint['details']['protocols'] as $protocol ) {
			if ( isset( $protocol['name'] ) && $protocol['name'] === $protocol_name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract vulnerabilities from SSL Labs endpoint data
	 *
	 * @param array $endpoint SSL Labs endpoint data.
	 * @return array Vulnerabilities found.
	 */
	private function extract_vulnerabilities( array $endpoint ): array {
		$vulnerabilities = [];
		$vuln_checks = [
			'vulnBeast' => 'BEAST',
			'vulnHeartbleed' => 'Heartbleed',
			'vulnOpenSslCcs' => 'OpenSSL CCS Injection',
			'vulnOpenSSLLuckyMinus20' => 'Lucky Minus 20',
			'vulnPoodle' => 'POODLE',
			'vulnFreak' => 'FREAK'
		];

		foreach ( $vuln_checks as $key => $name ) {
			if ( isset( $endpoint['details'][ $key ] ) && $endpoint['details'][ $key ] ) {
				$vulnerabilities[] = $name;
			}
		}

		return $vulnerabilities;
	}

	/**
	 * Analyze security headers
	 *
	 * @param string $url Website URL.
	 * @return array Security headers analysis.
	 */
	private function analyze_security_headers( string $url ): array {
		$response = wp_remote_head( $url, [
			'timeout' => 15,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION,
			'redirection' => 5
		]);

		if ( is_wp_error( $response ) ) {
			return ['error' => $response->get_error_message()];
		}

		$headers = wp_remote_retrieve_headers( $response );
		$header_analysis = [
			'score' => 0,
			'grade' => 'F',
			'present_headers' => [],
			'missing_headers' => [],
			'header_details' => [],
			'issues' => [],
			'recommendations' => []
		];

		$weighted_score = 0;
		$total_weight = 0;

		foreach ( self::SECURITY_HEADERS as $header => $config ) {
			$header_value = $headers[ $header ] ?? null;
			$total_weight += $config['weight'];
			
			if ( $header_value ) {
				$header_analysis['present_headers'][] = $header;
				$security_level = $this->evaluate_header_security( $header, $header_value );
				
				$header_analysis['header_details'][ $header ] = [
					'value' => $header_value,
					'security_level' => $security_level,
					'score' => $this->get_header_score( $security_level ),
					'description' => $this->get_header_description( $header )
				];

				$weighted_score += $this->get_header_score( $security_level ) * $config['weight'];
			} else {
				$header_analysis['missing_headers'][] = $header;
				
				if ( $config['critical'] ) {
					$header_analysis['issues'][] = sprintf(
						__( 'Critical security header missing: %s', 'hellaz-sitez-analyzer' ),
						$header
					);
				}
				
				$header_analysis['recommendations'][] = sprintf(
					__( 'Implement %s header for better security', 'hellaz-sitez-analyzer' ),
					$header
				);
			}
		}

		$header_analysis['score'] = $total_weight > 0 ? round( ( $weighted_score / $total_weight ) * 100 ) : 0;
		$header_analysis['grade'] = Utils::sanitize_grade( $header_analysis['score'] );

		return $header_analysis;
	}

	/**
	 * Evaluate security header configuration
	 *
	 * @param string $header Header name.
	 * @param string $value Header value.
	 * @return string Security level (excellent, good, fair, poor).
	 */
	private function evaluate_header_security( string $header, string $value ): string {
		$value = strtolower( trim( $value ) );
		
		switch ( strtolower( $header ) ) {
			case 'strict-transport-security':
				if ( strpos( $value, 'max-age=' ) !== false ) {
					preg_match( '/max-age=(\d+)/', $value, $matches );
					$max_age = isset( $matches[1] ) ? intval( $matches[1] ) : 0;
					
					if ( $max_age >= 31536000 && strpos( $value, 'includesubdomains' ) !== false && strpos( $value, 'preload' ) !== false ) {
						return 'excellent';
					} elseif ( $max_age >= 31536000 && strpos( $value, 'includesubdomains' ) !== false ) {
						return 'good';
					} elseif ( $max_age >= 86400 ) {
						return 'fair';
					}
				}
				return 'poor';
				
			case 'content-security-policy':
				if ( strpos( $value, "default-src 'self'" ) !== false || strpos( $value, "script-src 'self'" ) !== false ) {
					if ( strpos( $value, "'unsafe-inline'" ) === false && strpos( $value, "'unsafe-eval'" ) === false ) {
						return 'excellent';
					}
					return 'good';
				} elseif ( strpos( $value, "'unsafe-inline'" ) === false && strpos( $value, "'unsafe-eval'" ) === false ) {
					return 'fair';
				}
				return 'poor';
				
			case 'x-frame-options':
				if ( in_array( $value, ['deny', 'sameorigin'], true ) ) {
					return $value === 'deny' ? 'excellent' : 'good';
				}
				return 'poor';
				
			case 'x-content-type-options':
				return $value === 'nosniff' ? 'excellent' : 'poor';
				
			case 'x-xss-protection':
				if ( strpos( $value, '1; mode=block' ) !== false ) {
					return 'excellent';
				} elseif ( $value === '1' ) {
					return 'good';
				} elseif ( $value === '0' ) {
					return 'fair';
				}
				return 'poor';
				
			case 'referrer-policy':
				$secure_policies = ['no-referrer', 'no-referrer-when-downgrade', 'strict-origin', 'strict-origin-when-cross-origin'];
				if ( in_array( $value, $secure_policies, true ) ) {
					return $value === 'no-referrer' ? 'excellent' : 'good';
				}
				return 'fair';
				
			default:
				return 'fair';
		}
	}

	/**
	 * Get numeric score from security level
	 *
	 * @param string $level Security level.
	 * @return int Score (0-100).
	 */
	private function get_header_score( string $level ): int {
		switch ( $level ) {
			case 'excellent': return 100;
			case 'good': return 80;
			case 'fair': return 60;
			case 'poor': return 20;
			default: return 0;
		}
	}

	/**
	 * Get header description
	 *
	 * @param string $header Header name.
	 * @return string Header description.
	 */
	private function get_header_description( string $header ): string {
		$descriptions = [
			'strict-transport-security' => __( 'Forces HTTPS connections and prevents protocol downgrade attacks', 'hellaz-sitez-analyzer' ),
			'content-security-policy' => __( 'Prevents XSS attacks by controlling resource loading', 'hellaz-sitez-analyzer' ),
			'x-frame-options' => __( 'Prevents clickjacking attacks by controlling iframe embedding', 'hellaz-sitez-analyzer' ),
			'x-content-type-options' => __( 'Prevents MIME type sniffing attacks', 'hellaz-sitez-analyzer' ),
			'x-xss-protection' => __( 'Enables browser XSS protection (legacy)', 'hellaz-sitez-analyzer' ),
			'referrer-policy' => __( 'Controls referrer information sent with requests', 'hellaz-sitez-analyzer' ),
			'permissions-policy' => __( 'Controls browser feature permissions', 'hellaz-sitez-analyzer' )
		];

		return $descriptions[ strtolower( $header ) ] ?? '';
	}

	/**
	 * Scan for malware using multiple sources
	 *
	 * @param string $url Website URL.
	 * @return array Malware scan results.
	 */
	private function scan_malware( string $url ): array {
		$scan_results = [
			'clean' => true,
			'threats_detected' => 0,
			'risk_level' => 'low',
			'scan_engines' => [],
			'last_scan' => current_time( 'mysql', true ),
			'details' => [],
			'recommendations' => []
		];

		// VirusTotal scanning
		if ( get_option( 'hsz_virustotal_enabled' ) ) {
			$scan_results = $this->scan_virustotal( $url, $scan_results );
		}

		// URLScan.io scanning
		if ( get_option( 'hsz_urlscan_enabled' ) ) {
			$scan_results = $this->scan_urlscan_io( $url, $scan_results );
		}

		// Google Safe Browsing (if enabled)
		if ( get_option( 'hsz_google_safebrowsing_enabled' ) ) {
			$scan_results = $this->scan_google_safebrowsing( $url, $scan_results );
		}

		// Determine risk level
		if ( $scan_results['threats_detected'] > 0 ) {
			$scan_results['clean'] = false;
			if ( $scan_results['threats_detected'] >= 5 ) {
				$scan_results['risk_level'] = 'high';
			} elseif ( $scan_results['threats_detected'] >= 2 ) {
				$scan_results['risk_level'] = 'medium';
			} else {
				$scan_results['risk_level'] = 'low';
			}
		}

		return $scan_results;
	}

	/**
	 * Scan URL with VirusTotal
	 *
	 * @param string $url Website URL.
	 * @param array $scan_results Current scan results.
	 * @return array Updated scan results.
	 */
	private function scan_virustotal( string $url, array $scan_results ): array {
		$api_key = Utils::decrypt( get_option( 'hsz_virustotal_api_key', '' ) );
		if ( ! $api_key ) {
			return $scan_results;
		}

		$params = [
			'apikey' => $api_key,
			'resource' => $url
		];

		$start_time = microtime( true );
		$response = wp_remote_get( self::VIRUSTOTAL_API_URL . '?' . http_build_query( $params ), [
			'timeout' => 20,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);
		$response_time = microtime( true ) - $start_time;

		$success = ! is_wp_error( $response );
		Utils::record_api_usage( 'virustotal', 'url_report', $response_time, $success );

		if ( is_wp_error( $response ) ) {
			$scan_results['scan_engines']['virustotal'] = ['error' => $response->get_error_message()];
			return $scan_results;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $data['response_code'] ) ) {
			$scan_results['scan_engines']['virustotal'] = ['error' => 'Invalid API response'];
			return $scan_results;
		}

		if ( $data['response_code'] == 1 ) {
			$positives = $data['positives'] ?? 0;
			$total = $data['total'] ?? 0;
			
			$scan_results['scan_engines']['virustotal'] = [
				'positives' => $positives,
				'total' => $total,
				'scan_date' => $data['scan_date'] ?? '',
				'permalink' => $data['permalink'] ?? '',
				'clean' => $positives === 0
			];

			if ( $positives > 0 ) {
				$scan_results['threats_detected'] += $positives;
				$scan_results['details'][] = sprintf(
					__( 'VirusTotal: %d out of %d security vendors flagged this URL as malicious', 'hellaz-sitez-analyzer' ),
					$positives,
					$total
				);
			}
		} else {
			$scan_results['scan_engines']['virustotal'] = ['error' => 'URL not found in VirusTotal database'];
		}

		return $scan_results;
	}

	/**
	 * Scan URL with URLScan.io
	 *
	 * @param string $url Website URL.
	 * @param array $scan_results Current scan results.
	 * @return array Updated scan results.
	 */
	private function scan_urlscan_io( string $url, array $scan_results ): array {
		$host = Utils::get_domain( $url );
		$api_url = self::URLSCAN_API_URL . '?q=domain:' . urlencode( $host );
		
		$start_time = microtime( true );
		$response = wp_remote_get( $api_url, [
			'timeout' => 15,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);
		$response_time = microtime( true ) - $start_time;

		$success = ! is_wp_error( $response );
		Utils::record_api_usage( 'urlscan', 'search', $response_time, $success );

		if ( is_wp_error( $response ) ) {
			$scan_results['scan_engines']['urlscan'] = ['error' => $response->get_error_message()];
			return $scan_results;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() === JSON_ERROR_NONE && isset( $data['results'] ) ) {
			$malicious_count = 0;
			$suspicious_count = 0;
			
			foreach ( $data['results'] as $result ) {
				if ( isset( $result['verdicts']['overall']['malicious'] ) && $result['verdicts']['overall']['malicious'] ) {
					$malicious_count++;
				}
				if ( isset( $result['verdicts']['overall']['suspicious'] ) && $result['verdicts']['overall']['suspicious'] ) {
					$suspicious_count++;
				}
			}

			$scan_results['scan_engines']['urlscan'] = [
				'total_scans' => count( $data['results'] ),
				'malicious_verdicts' => $malicious_count,
				'suspicious_verdicts' => $suspicious_count,
				'clean' => $malicious_count === 0
			];

			if ( $malicious_count > 0 ) {
				$scan_results['threats_detected'] += $malicious_count;
				$scan_results['details'][] = sprintf(
					__( 'URLScan.io detected %d malicious verdicts', 'hellaz-sitez-analyzer' ),
					$malicious_count
				);
			}

			if ( $suspicious_count > 0 ) {
				$scan_results['details'][] = sprintf(
					__( 'URLScan.io flagged %d scans as suspicious', 'hellaz-sitez-analyzer' ),
					$suspicious_count
				);
			}
		}

		return $scan_results;
	}

	/**
	 * Scan with Google Safe Browsing (placeholder for future implementation)
	 *
	 * @param string $url Website URL.
	 * @param array $scan_results Current scan results.
	 * @return array Updated scan results.
	 */
	private function scan_google_safebrowsing( string $url, array $scan_results ): array {
		// This would require Google Safe Browsing API implementation
		// For now, return the scan results unchanged
		$scan_results['scan_engines']['google_safebrowsing'] = [
			'status' => 'not_implemented',
			'message' => 'Google Safe Browsing integration coming soon'
		];
		
		return $scan_results;
	}

	/**
	 * Scan for vulnerabilities
	 *
	 * @param string $url Website URL.
	 * @return array Vulnerability scan results.
	 */
	private function scan_vulnerabilities( string $url ): array {
		$vuln_data = [
			'vulnerabilities_found' => 0,
			'severity_breakdown' => ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0],
			'scan_details' => [],
			'recommendations' => [],
			'overall_risk' => 'low'
		];

		// Check for exposed sensitive files
		$vuln_data = $this->check_exposed_files( $url, $vuln_data );
		
		// Check for common security misconfigurations
		$vuln_data = $this->check_security_misconfigurations( $url, $vuln_data );
		
		// Check for information disclosure
		$vuln_data = $this->check_information_disclosure( $url, $vuln_data );

		// Determine overall risk level
		if ( $vuln_data['severity_breakdown']['critical'] > 0 ) {
			$vuln_data['overall_risk'] = 'critical';
		} elseif ( $vuln_data['severity_breakdown']['high'] > 0 ) {
			$vuln_data['overall_risk'] = 'high';
		} elseif ( $vuln_data['severity_breakdown']['medium'] > 0 ) {
			$vuln_data['overall_risk'] = 'medium';
		}

		return $vuln_data;
	}

	/**
	 * Check for exposed sensitive files
	 *
	 * @param string $url Website URL.
	 * @param array $vuln_data Current vulnerability data.
	 * @return array Updated vulnerability data.
	 */
	private function check_exposed_files( string $url, array $vuln_data ): array {
		$sensitive_files = [
			// Critical files
			'/.env' => 'critical',
			'/wp-config.php.bak' => 'critical',
			'/config.php.bak' => 'critical',
			'/database.sql' => 'critical',
			'/backup.sql' => 'critical',
			'/.git/config' => 'high',
			'/composer.json' => 'medium',
			'/package.json' => 'medium',
			
			// Information disclosure
			'/phpinfo.php' => 'high',
			'/info.php' => 'high',
			'/test.php' => 'medium',
			'/adminer.php' => 'high',
			'/phpmyadmin/' => 'high',
			
			// Configuration files
			'/.htaccess' => 'medium',
			'/web.config' => 'medium',
			'/crossdomain.xml' => 'low',
			'/clientaccesspolicy.xml' => 'low',
			
			// Common info files
			'/robots.txt' => 'info',
			'/sitemap.xml' => 'info',
			'/humans.txt' => 'info'
		];

		$base_url = rtrim( $url, '/' );
		
		foreach ( $sensitive_files as $file => $severity ) {
			$test_url = $base_url . $file;
			$status_code = Utils::get_http_status( $test_url );
			
			if ( ! is_wp_error( $status_code ) && $status_code === 200 ) {
				if ( $severity !== 'info' ) {
					$vuln_data['vulnerabilities_found']++;
					$vuln_data['severity_breakdown'][ $severity ]++;
					
					$vuln_data['scan_details'][] = [
						'type' => 'exposed_file',
						'severity' => $severity,
						'description' => sprintf( __( 'Sensitive file exposed: %s', 'hellaz-sitez-analyzer' ), $file ),
						'url' => $test_url,
						'recommendation' => $this->get_file_exposure_recommendation( $file )
					];
				}
			}
		}

		return $vuln_data;
	}

	/**
	 * Get recommendation for exposed file
	 *
	 * @param string $file File path.
	 * @return string Recommendation.
	 */
	private function get_file_exposure_recommendation( string $file ): string {
		$recommendations = [
			'/.env' => __( 'Remove or protect .env file containing environment variables', 'hellaz-sitez-analyzer' ),
			'/wp-config.php.bak' => __( 'Remove backup WordPress configuration files', 'hellaz-sitez-analyzer' ),
			'/.git/config' => __( 'Remove .git directory from web root', 'hellaz-sitez-analyzer' ),
			'/phpinfo.php' => __( 'Remove PHP info scripts from production', 'hellaz-sitez-analyzer' ),
			'/.htaccess' => __( 'Protect .htaccess file from direct access', 'hellaz-sitez-analyzer' )
		];

		return $recommendations[ $file ] ?? __( 'Secure or remove this exposed file', 'hellaz-sitez-analyzer' );
	}

	/**
	 * Check for security misconfigurations
	 *
	 * @param string $url Website URL.
	 * @param array $vuln_data Current vulnerability data.
	 * @return array Updated vulnerability data.
	 */
	private function check_security_misconfigurations( string $url, array $vuln_data ): array {
		// Check for directory listing
		$parsed_url = parse_url( $url );
		$base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
		
		$common_dirs = ['/wp-content/uploads/', '/images/', '/assets/', '/files/'];
		
		foreach ( $common_dirs as $dir ) {
			$dir_url = $base_url . $dir;
			$response = wp_remote_get( $dir_url, [
				'timeout' => 10,
				'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
			]);
			
			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				$status_code = wp_remote_retrieve_response_code( $response );
				
				if ( $status_code === 200 && ( strpos( $body, 'Index of' ) !== false || strpos( $body, 'Directory Listing' ) !== false ) ) {
					$vuln_data['vulnerabilities_found']++;
					$vuln_data['severity_breakdown']['medium']++;
					
					$vuln_data['scan_details'][] = [
						'type' => 'directory_listing',
						'severity' => 'medium',
						'description' => sprintf( __( 'Directory listing enabled: %s', 'hellaz-sitez-analyzer' ), $dir ),
						'url' => $dir_url,
						'recommendation' => __( 'Disable directory listing in web server configuration', 'hellaz-sitez-analyzer' )
					];
				}
			}
		}

		return $vuln_data;
	}

	/**
	 * Check for information disclosure
	 *
	 * @param string $url Website URL.
	 * @param array $vuln_data Current vulnerability data.
	 * @return array Updated vulnerability data.
	 */
	private function check_information_disclosure( string $url, array $vuln_data ): array {
		// Check HTTP response headers for information disclosure
		$response = wp_remote_head( $url, [
			'timeout' => 15,
			'user-agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
		]);

		if ( ! is_wp_error( $response ) ) {
			$headers = wp_remote_retrieve_headers( $response );
			
			// Check for server version disclosure
			$server_header = $headers['server'] ?? '';
			if ( $server_header && preg_match( '/\d+\.\d+/', $server_header ) ) {
				$vuln_data['vulnerabilities_found']++;
				$vuln_data['severity_breakdown']['low']++;
				
				$vuln_data['scan_details'][] = [
					'type' => 'information_disclosure',
					'severity' => 'low',
					'description' => sprintf( __( 'Server version disclosed: %s', 'hellaz-sitez-analyzer' ), $server_header ),
					'recommendation' => __( 'Hide server version information in HTTP headers', 'hellaz-sitez-analyzer' )
				];
			}

			// Check for X-Powered-By header
			$powered_by = $headers['x-powered-by'] ?? '';
			if ( $powered_by ) {
				$vuln_data['vulnerabilities_found']++;
				$vuln_data['severity_breakdown']['low']++;
				
				$vuln_data['scan_details'][] = [
					'type' => 'information_disclosure',
					'severity' => 'low',
					'description' => sprintf( __( 'Technology stack disclosed: %s', 'hellaz-sitez-analyzer' ), $powered_by ),
					'recommendation' => __( 'Remove X-Powered-By header to reduce attack surface', 'hellaz-sitez-analyzer' )
				];
			}
		}

		return $vuln_data;
	}

	/**
	 * Check blacklist status
	 *
	 * @param string $url Website URL.
	 * @return array Blacklist check results.
	 */
	private function check_blacklists( string $url ): array {
		$blacklist_data = [
			'blacklisted' => false,
			'blacklist_count' => 0,
			'blacklists' => [],
			'details' => [],
			'risk_level' => 'low'
		];

		$host = Utils::get_domain( $url );
		
		// This is a placeholder for blacklist checking
		// In a real implementation, you would integrate with various blacklist APIs
		
		$blacklist_data['blacklists']['checked'] = [
			'google_safebrowsing' => false,
			'malware_domain_list' => false,
			'phishtank' => false,
			'surbl' => false
		];

		return $blacklist_data;
	}

	/**
	 * Get detailed certificate information
	 *
	 * @param string $url Website URL.
	 * @return array Certificate information.
	 */
	private function get_detailed_certificate_info( string $url ): array {
		$parsed_url = parse_url( $url );
		$host = $parsed_url['host'] ?? '';
		$port = $parsed_url['port'] ?? 443;
		
		if ( empty( $host ) ) {
			return ['error' => __( 'Invalid hostname for certificate analysis.', 'hellaz-sitez-analyzer' )];
		}

		$cert_info = [
			'valid' => false,
			'subject' => '',
			'issuer' => '',
			'serial_number' => '',
			'valid_from' => '',
			'valid_to' => '',
			'fingerprint_sha1' => '',
			'fingerprint_sha256' => '',
			'public_key_algorithm' => '',
			'signature_algorithm' => '',
			'key_size' => 0,
			'san_domains' => [],
			'certificate_chain' => [],
			'ocsp_status' => 'unknown',
			'issues' => []
		];

		$context = stream_context_create([
			'ssl' => [
				'capture_peer_cert' => true,
				'capture_peer_cert_chain' => true,
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		]);

		$stream = @stream_socket_client(
			"ssl://{$host}:{$port}",
			$errno,
			$errstr,
			10,
			STREAM_CLIENT_CONNECT,
			$context
		);

		if ( $stream ) {
			$params = stream_context_get_params( $stream );
			
			if ( isset( $params['options']['ssl']['peer_certificate'] ) ) {
				$cert_resource = $params['options']['ssl']['peer_certificate'];
				$cert_data = openssl_x509_parse( $cert_resource );
				
				if ( $cert_data ) {
					$cert_info['valid'] = true;
					$cert_info['subject'] = $cert_data['subject']['CN'] ?? '';
					$cert_info['issuer'] = $cert_data['issuer']['CN'] ?? '';
					$cert_info['serial_number'] = $cert_data['serialNumber'] ?? '';
					$cert_info['valid_from'] = date( 'Y-m-d H:i:s', $cert_data['validFrom_time_t'] );
					$cert_info['valid_to'] = date( 'Y-m-d H:i:s', $cert_data['validTo_time_t'] );
					$cert_info['signature_algorithm'] = $cert_data['signatureTypeSN'] ?? '';
					
					// Get certificate fingerprints
					openssl_x509_export( $cert_resource, $cert_string );
					$cert_der = base64_decode( str_replace([
						'-----BEGIN CERTIFICATE-----',
						'-----END CERTIFICATE-----',
						"\r", "\n", " "
					], '', $cert_string ) );
					
					$cert_info['fingerprint_sha1'] = strtoupper( sha1( $cert_der ) );
					$cert_info['fingerprint_sha256'] = strtoupper( hash( 'sha256', $cert_der ) );
					
					// Extract SAN domains
					if ( isset( $cert_data['extensions']['subjectAltName'] ) ) {
						$san_string = $cert_data['extensions']['subjectAltName'];
						preg_match_all( '/DNS:([^,\s]+)/', $san_string, $matches );
						$cert_info['san_domains'] = $matches[1] ?? [];
					}

					// Get public key information
					$public_key = openssl_pkey_get_public( $cert_resource );
					if ( $public_key ) {
						$key_details = openssl_pkey_get_details( $public_key );
						$cert_info['key_size'] = $key_details['bits'] ?? 0;
						$cert_info['public_key_algorithm'] = $key_details['type'] === OPENSSL_KEYTYPE_RSA ? 'RSA' : 
															 ( $key_details['type'] === OPENSSL_KEYTYPE_EC ? 'ECC' : 'Unknown' );
					}
				}
			}

			// Analyze certificate chain
			if ( isset( $params['options']['ssl']['peer_certificate_chain'] ) ) {
				$chain = $params['options']['ssl']['peer_certificate_chain'];
				foreach ( $chain as $cert ) {
					$chain_cert = openssl_x509_parse( $cert );
					if ( $chain_cert ) {
						$cert_info['certificate_chain'][] = [
							'subject' => $chain_cert['subject']['CN'] ?? '',
							'issuer' => $chain_cert['issuer']['CN'] ?? '',
							'valid_to' => date( 'Y-m-d H:i:s', $chain_cert['validTo_time_t'] )
						];
					}
				}
			}
			
			fclose( $stream );
		}

		return $cert_info;
	}

	/**
	 * Calculate SSL score
	 *
	 * @param array $ssl_analysis SSL analysis data.
	 * @return int SSL score (0-100).
	 */
	private function calculate_ssl_score( array $ssl_analysis ): int {
		if ( ! $ssl_analysis['enabled'] ) {
			return 0;
		}

		$score = 0;

		// Certificate validity (25 points)
		if ( $ssl_analysis['certificate_valid'] ) {
			$score += 25;
			
			// Bonus for days until expiry
			$days = $ssl_analysis['days_until_expiry'] ?? 0;
			if ( $days > 90 ) {
				$score += 5;
			} elseif ( $days < 30 ) {
				$score -= 10;
			}
		}

		// Key size (20 points)
		$key_size = $ssl_analysis['key_size'] ?? 0;
		if ( $key_size >= 2048 ) {
			$score += 20;
		} elseif ( $key_size >= 1024 ) {
			$score += 10;
		}

		// HSTS implementation (20 points)
		if ( $ssl_analysis['hsts_enabled'] ) {
			$score += 15;
			$hsts_max_age = $ssl_analysis['hsts_max_age'] ?? 0;
			if ( $hsts_max_age >= 31536000 ) { // 1 year
				$score += 5;
			}
		}

		// Protocol support (15 points)
		if ( $ssl_analysis['supports_tls_1_3'] ?? false ) {
			$score += 15;
		} elseif ( $ssl_analysis['supports_tls_1_2'] ?? false ) {
			$score += 10;
		}

		// Vulnerabilities penalty
		$vulnerabilities = $ssl_analysis['vulnerabilities'] ?? [];
		$score -= count( $vulnerabilities ) * 5;

		// SSL Labs grade bonus
		if ( isset( $ssl_analysis['ssl_labs_grade'] ) ) {
			$grade_bonus = [
				'A+' => 20, 'A' => 15, 'A-' => 10,
				'B' => 5, 'C' => 0, 'D' => -5,
				'F' => -10, 'T' => -15
			];
			$score += $grade_bonus[ $ssl_analysis['ssl_labs_grade'] ] ?? 0;
		}

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Calculate overall security metrics
	 *
	 * @param array $security_data Security analysis data.
	 * @return array Updated security data with calculated metrics.
	 */
	private function calculate_security_metrics( array $security_data ): array {
		$scores = [];
		$total_checks = 0;
		$passed_checks = 0;

		// SSL Analysis Score
		if ( ! empty( $security_data['ssl_analysis'] ) && ! isset( $security_data['ssl_analysis']['error'] ) ) {
			$ssl_score = $security_data['ssl_analysis']['score'] ?? $this->calculate_ssl_score( $security_data['ssl_analysis'] );
			$scores['ssl_analysis'] = $ssl_score;
			$total_checks++;
			if ( $ssl_score >= 70 ) $passed_checks++;
		}

		// Security Headers Score
		if ( ! empty( $security_data['security_headers'] ) && ! isset( $security_data['security_headers']['error'] ) ) {
			$headers_score = $security_data['security_headers']['score'] ?? 0;
			$scores['security_headers'] = $headers_score;
			$total_checks++;
			if ( $headers_score >= 70 ) $passed_checks++;
		}

		// Malware Scan Score
		if ( ! empty( $security_data['malware_scan'] ) ) {
			$malware_clean = $security_data['malware_scan']['clean'] ?? true;
			$threat_count = $security_data['malware_scan']['threats_detected'] ?? 0;
			$malware_score = $malware_clean ? 100 : max( 0, 100 - ( $threat_count * 20 ) );
			$scores['malware_detection'] = $malware_score;
			$total_checks++;
			if ( $malware_score >= 70 ) $passed_checks++;
		}

		// Vulnerability Scan Score
		if ( ! empty( $security_data['vulnerability_scan'] ) ) {
			$vuln_count = $security_data['vulnerability_scan']['vulnerabilities_found'] ?? 0;
			$critical_count = $security_data['vulnerability_scan']['severity_breakdown']['critical'] ?? 0;
			$high_count = $security_data['vulnerability_scan']['severity_breakdown']['high'] ?? 0;
			
			$vuln_score = 100 - ( $critical_count * 25 ) - ( $high_count * 15 ) - ( max( 0, $vuln_count - $critical_count - $high_count ) * 5 );
			$vuln_score = max( 0, $vuln_score );
			$scores['vulnerability_scan'] = $vuln_score;
			$total_checks++;
			if ( $vuln_score >= 70 ) $passed_checks++;
		}

		// Blacklist Status Score
		if ( ! empty( $security_data['blacklist_check'] ) ) {
			$blacklisted = $security_data['blacklist_check']['blacklisted'] ?? false;
			$blacklist_score = $blacklisted ? 0 : 100;
			$scores['blacklist_status'] = $blacklist_score;
			$total_checks++;
			if ( $blacklist_score >= 70 ) $passed_checks++;
		}

		// Calculate weighted overall score
		$weighted_sum = 0;
		$total_weight = 0;

		foreach ( self::SECURITY_WEIGHTS as $category => $weight ) {
			if ( isset( $scores[ $category ] ) ) {
				$weighted_sum += $scores[ $category ] * $weight;
				$total_weight += $weight;
			}
		}

		$security_data['security_score'] = $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
		$security_data['security_grade'] = Utils::sanitize_grade( $security_data['security_score'] );
		$security_data['passed_checks'] = $passed_checks;
		$security_data['total_checks'] = $total_checks;

		// Determine overall status
		$score = $security_data['security_score'];
		if ( $score >= 90 ) {
			$security_data['overall_status'] = 'excellent';
			$security_data['risk_level'] = 'very_low';
		} elseif ( $score >= 80 ) {
			$security_data['overall_status'] = 'good';
			$security_data['risk_level'] = 'low';
		} elseif ( $score >= 70 ) {
			$security_data['overall_status'] = 'fair';
			$security_data['risk_level'] = 'medium';
		} elseif ( $score >= 60 ) {
			$security_data['overall_status'] = 'poor';
			$security_data['risk_level'] = 'high';
		} else {
			$security_data['overall_status'] = 'critical';
			$security_data['risk_level'] = 'very_high';
		}

		return $security_data;
	}

	/**
	 * Generate security recommendations
	 *
	 * @param array $security_data Security analysis data.
	 * @return array Updated security data with recommendations.
	 */
	private function generate_security_recommendations( array $security_data ): array {
		$issues = [];
		$recommendations = [];

		// SSL/TLS recommendations
		if ( isset( $security_data['ssl_analysis'] ) ) {
			$ssl = $security_data['ssl_analysis'];
			
			if ( ! ( $ssl['enabled'] ?? false ) ) {
				$issues[] = __( 'Website does not use HTTPS encryption', 'hellaz-sitez-analyzer' );
				$recommendations[] = [
					'priority' => 'critical',
					'category' => 'ssl',
					'title' => __( 'Enable HTTPS', 'hellaz-sitez-analyzer' ),
					'description' => __( 'Install an SSL certificate and redirect all HTTP traffic to HTTPS to protect user data and improve SEO rankings.', 'hellaz-sitez-analyzer' )
				];
			} else {
				if ( ( $ssl['days_until_expiry'] ?? 0 ) < 30 ) {
					$issues[] = sprintf( __( 'SSL certificate expires in %d days', 'hellaz-sitez-analyzer' ), $ssl['days_until_expiry'] );
					$recommendations[] = [
						'priority' => 'high',
						'category' => 'ssl',
						'title' => __( 'Renew SSL Certificate', 'hellaz-sitez-analyzer' ),
						'description' => __( 'SSL certificate is nearing expiration. Renew it to prevent service interruption.', 'hellaz-sitez-analyzer' )
					];
				}

				if ( ! ( $ssl['hsts_enabled'] ?? false ) ) {
					$issues[] = __( 'HSTS (HTTP Strict Transport Security) not implemented', 'hellaz-sitez-analyzer' );
					$recommendations[] = [
						'priority' => 'high',
						'category' => 'ssl',
						'title' => __( 'Implement HSTS', 'hellaz-sitez-analyzer' ),
						'description' => __( 'Add Strict-Transport-Security header to prevent protocol downgrade attacks.', 'hellaz-sitez-analyzer' )
					];
				}
			}
		}

		// Security headers recommendations
		if ( isset( $security_data['security_headers']['missing_headers'] ) ) {
			$missing = $security_data['security_headers']['missing_headers'];
			if ( ! empty( $missing ) ) {
				$issues[] = sprintf( __( '%d critical security headers are missing', 'hellaz-sitez-analyzer' ), count( $missing ) );
				$recommendations[] = [
					'priority' => 'high',
					'category' => 'headers',
					'title' => __( 'Implement Security Headers', 'hellaz-sitez-analyzer' ),
					'description' => sprintf( __( 'Add missing security headers: %s', 'hellaz-sitez-analyzer' ), implode( ', ', $missing ) )
				];
			}
		}

		// Malware recommendations
		if ( isset( $security_data['malware_scan'] ) && ! ( $security_data['malware_scan']['clean'] ?? true ) ) {
			$threat_count = $security_data['malware_scan']['threats_detected'] ?? 0;
			$issues[] = sprintf( __( '%d security threats detected', 'hellaz-sitez-analyzer' ), $threat_count );
			$recommendations[] = [
				'priority' => 'critical',
				'category' => 'malware',
				'title' => __( 'Remove Malware', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Immediately clean infected files and scan for malware. Consider using a security plugin and regular backups.', 'hellaz-sitez-analyzer' )
			];
		}

		// Vulnerability recommendations
		if ( isset( $security_data['vulnerability_scan'] ) ) {
			$vuln_count = $security_data['vulnerability_scan']['vulnerabilities_found'] ?? 0;
			if ( $vuln_count > 0 ) {
				$critical_count = $security_data['vulnerability_scan']['severity_breakdown']['critical'] ?? 0;
				$high_count = $security_data['vulnerability_scan']['severity_breakdown']['high'] ?? 0;
				
				$issues[] = sprintf( __( '%d vulnerabilities found (%d critical, %d high)', 'hellaz-sitez-analyzer' ), $vuln_count, $critical_count, $high_count );
				$recommendations[] = [
					'priority' => $critical_count > 0 ? 'critical' : ( $high_count > 0 ? 'high' : 'medium' ),
					'category' => 'vulnerabilities',
					'title' => __( 'Fix Security Vulnerabilities', 'hellaz-sitez-analyzer' ),
					'description' => __( 'Address identified vulnerabilities by removing exposed files and securing sensitive information.', 'hellaz-sitez-analyzer' )
				];
			}
		}

		$security_data['issues'] = $issues;
		$security_data['recommendations'] = $recommendations;

		return $security_data;
	}

	/**
	 * Store security results in enhanced database
	 *
	 * @param string $url Website URL.
	 * @param array $security_data Security analysis results.
	 */
	private function store_security_results( string $url, array $security_data ): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hsz_security_results';
		
		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return;
		}

		$url_hash = Utils::generate_url_hash( $url );
		$expires_at = date( 'Y-m-d H:i:s', time() + get_option( 'hsz_security_cache_duration', HOUR_IN_SECONDS * 12 ) );

		$wpdb->replace(
			$table_name,
			[
				'url_hash' => $url_hash,
				'url' => $url,
				'security_score' => $security_data['security_score'],
				'ssl_grade' => $security_data['ssl_analysis']['grade'] ?? 'F',
				'ssl_enabled' => ( $security_data['ssl_analysis']['enabled'] ?? false ) ? 1 : 0,
				'security_headers' => wp_json_encode( $security_data['security_headers'] ),
				'malware_scan_results' => wp_json_encode( $security_data['malware_scan'] ),
				'vulnerability_scan' => wp_json_encode( $security_data['vulnerability_scan'] ),
				'blacklist_status' => wp_json_encode( $security_data['blacklist_check'] ),
				'certificate_info' => wp_json_encode( $security_data['certificate_info'] ),
				'security_issues' => wp_json_encode( $security_data['issues'] ),
				'recommendations' => wp_json_encode( $security_data['recommendations'] ),
				'analysis_time' => $security_data['analysis_time'] ?? 0,
				'created_at' => current_time( 'mysql', true ),
				'expires_at' => $expires_at
			],
			[
				'%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s'
			]
		);
	}

	/**
	 * Get security analysis summary
	 *
	 * @param array $security_data Security analysis data.
	 * @return array Security summary.
	 */
	public static function get_security_summary( array $security_data ): array {
		return [
			'overall_grade' => $security_data['security_grade'] ?? 'F',
			'overall_score' => $security_data['security_score'] ?? 0,
			'risk_level' => $security_data['risk_level'] ?? 'unknown',
			'https_enabled' => ( $security_data['ssl_analysis']['enabled'] ?? false ),
			'certificate_valid' => ( $security_data['ssl_analysis']['certificate_valid'] ?? false ),
			'malware_clean' => ( $security_data['malware_scan']['clean'] ?? true ),
			'vulnerabilities_count' => $security_data['vulnerability_scan']['vulnerabilities_found'] ?? 0,
			'critical_issues' => count( array_filter( $security_data['recommendations'] ?? [], function( $rec ) {
				return $rec['priority'] === 'critical';
			}))
		];
	}
}
