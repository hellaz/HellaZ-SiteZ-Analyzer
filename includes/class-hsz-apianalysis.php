<?php
/**
 * Enhanced API analysis functionality for HellaZ SiteZ Analyzer.
 *
 * This class provides comprehensive third-party API integrations for security analysis,
 * technology detection, performance monitoring, and content analysis with Phase 1 enhancements.
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
 * Class APIAnalysis
 *
 * Handles all third-party API integrations with enhanced capabilities.
 */
class APIAnalysis {

	/**
	 * APIManager instance
	 *
	 * @var APIManager
	 */
	private $apimanager;

	/**
	 * API endpoints and configurations
	 */
	private const API_ENDPOINTS = [
		'ssl_labs' => [
			'url' => 'https://api.ssllabs.com/api/v3/analyze',
			'rate_limit' => 25, // calls per hour
			'timeout' => 30,
			'requires_key' => false
		],
		'virustotal' => [
			'url' => 'https://www.virustotal.com/vtapi/v2/url/report',
			'rate_limit' => 4, // calls per minute
			'timeout' => 20,
			'requires_key' => true
		],
		'builtwith' => [
			'url' => 'https://api.builtwith.com/v19/api.json',
			'rate_limit' => 200, // calls per month
			'timeout' => 15,
			'requires_key' => true
		],
		'urlscan' => [
			'url' => 'https://urlscan.io/api/v1/search/',
			'rate_limit' => 100, // calls per day
			'timeout' => 15,
			'requires_key' => false
		],
		'pagespeed' => [
			'url' => 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed',
			'rate_limit' => 25000, // calls per day
			'timeout' => 30,
			'requires_key' => true
		],
		'webpagetest' => [
			'url' => 'https://www.webpagetest.org/runtest.php',
			'rate_limit' => 200, // calls per day
			'timeout' => 45,
			'requires_key' => true
		]
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->apimanager = new APIManager();
	}

	/**
	 * Comprehensive API analysis
	 *
	 * @param string $url Website URL to analyze.
	 * @param array $options Analysis options.
	 * @return array Complete API analysis results.
	 */
	public function comprehensive_api_analysis( string $url, array $options = [] ): array {
		$options = wp_parse_args( $options, [
			'ssl_analysis' => get_option( 'hsz_ssl_analysis_enabled', true ),
			'security_scan' => get_option( 'hsz_security_analysis_enabled', true ),
			'technology_detection' => get_option( 'hsz_builtwith_enabled', false ),
			'performance_analysis' => get_option( 'hsz_performance_analysis_enabled', true ),
			'reputation_check' => get_option( 'hsz_urlscan_enabled', false ),
			'force_refresh' => false
		]);

		$analysis_results = [
			'url' => $url,
			'timestamp' => current_time( 'mysql', true ),
			'ssl_info' => [],
			'security_analysis' => [],
			'technology_stack' => [],
			'performance_metrics' => [],
			'reputation_data' => [],
			'api_status' => [],
			'analysis_score' => 0,
			'analysis_grade' => 'F',
			'recommendations' => []
		];

		Utils::start_timer( 'comprehensive_api_analysis' );

		// SSL Analysis
		if ( $options['ssl_analysis'] ) {
			$analysis_results['ssl_info'] = $this->get_ssl_info( $url );
			$analysis_results['api_status']['ssl_labs'] = ! empty( $analysis_results['ssl_info'] );
		}

		// Security Analysis
		if ( $options['security_scan'] ) {
			$analysis_results['security_analysis'] = $this->get_comprehensive_security_analysis( $url );
			$analysis_results['api_status']['security_apis'] = ! empty( $analysis_results['security_analysis'] );
		}

		// Technology Detection
		if ( $options['technology_detection'] ) {
			$analysis_results['technology_stack'] = $this->get_technology_analysis( $url );
			$analysis_results['api_status']['builtwith'] = ! empty( $analysis_results['technology_stack'] );
		}

		// Performance Analysis
		if ( $options['performance_analysis'] ) {
			$analysis_results['performance_metrics'] = $this->get_performance_analysis( $url );
			$analysis_results['api_status']['performance_apis'] = ! empty( $analysis_results['performance_metrics'] );
		}

		// Reputation Check
		if ( $options['reputation_check'] ) {
			$analysis_results['reputation_data'] = $this->get_reputation_analysis( $url );
			$analysis_results['api_status']['reputation_apis'] = ! empty( $analysis_results['reputation_data'] );
		}

		// Calculate overall analysis score
		$analysis_results = $this->calculate_api_analysis_score( $analysis_results );

		// Generate recommendations
		$analysis_results['recommendations'] = $this->generate_api_recommendations( $analysis_results );

		$analysis_results['analysis_time'] = Utils::stop_timer( 'comprehensive_api_analysis' );

		// Cache comprehensive results
		$cache_key = 'comprehensive_api_analysis_' . Utils::generate_url_hash( $url );
		Cache::set( $cache_key, $analysis_results, HOUR_IN_SECONDS * 6, 'analysis' );

		return $analysis_results;
	}

	/**
	 * Enhanced SSL information analysis
	 *
	 * @param string $url The URL to analyze.
	 * @return array SSL information with enhanced details.
	 */
	public function get_ssl_info( $url ) {
		// Validate input
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return [ 'error' => __( 'Invalid URL provided', 'hellaz-sitez-analyzer' ) ];
		}

		$host = parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return [ 'error' => __( 'Could not extract hostname from URL', 'hellaz-sitez-analyzer' ) ];
		}

		$ssl_info = [
			'hostname' => $host,
			'ssl_enabled' => false,
			'certificate_details' => [],
			'security_assessment' => [],
			'vulnerabilities' => [],
			'recommendations' => [],
			'grade' => 'F',
			'score' => 0
		];

		// Check if site supports HTTPS
		$https_url = 'https://' . $host;
		$https_status = Utils::get_http_status( $https_url );
		
		if ( is_wp_error( $https_status ) || $https_status >= 400 ) {
			$ssl_info['ssl_enabled'] = false;
			$ssl_info['recommendations'][] = __( 'Enable HTTPS by installing an SSL certificate', 'hellaz-sitez-analyzer' );
			return $ssl_info;
		}

		$ssl_info['ssl_enabled'] = true;

		// Try SSL Labs API first for comprehensive analysis
		if ( Utils::check_rate_limit( 'ssl_labs', self::API_ENDPOINTS['ssl_labs']['rate_limit'] ) ) {
			$ssl_labs_data = $this->get_ssl_info_from_ssllabs( $host );
			if ( ! empty( $ssl_labs_data ) && ! isset( $ssl_labs_data['error'] ) ) {
				$ssl_info = array_merge( $ssl_info, $ssl_labs_data );
				Utils::record_api_usage( 'ssl_labs', 'analyze', $ssl_labs_data['response_time'] ?? 0, true );
				return $ssl_info;
			}
		}

		// Fallback to direct certificate analysis
		$cert_data = $this->get_ssl_info_from_certificate( $host );
		$ssl_info = array_merge( $ssl_info, $cert_data );

		// Calculate SSL score and grade
		$ssl_info['score'] = $this->calculate_ssl_score( $ssl_info );
		$ssl_info['grade'] = Utils::sanitize_grade( $ssl_info['score'] );

		return $ssl_info;
	}

	/**
	 * Enhanced SSL Labs API analysis
	 *
	 * @param string $host The hostname to analyze.
	 * @return array SSL information from SSL Labs.
	 */
	private function get_ssl_info_from_ssllabs( $host ) {
		$cache_key = 'hsz_ssl_labs_' . md5( $host );
		
		// Check if analysis is already cached
		$cached_data = Cache::get( $cache_key, 'security' );
		if ( $cached_data ) {
			return $cached_data;
		}

		$start_time = microtime( true );
		
		// Start new analysis or get existing results
		$api_url = self::API_ENDPOINTS['ssl_labs']['url'] . "?host={$host}&fromCache=on&maxAge=24";
		
		$response = $this->apimanager->make_api_request(
			$api_url,
			[
				'timeout' => self::API_ENDPOINTS['ssl_labs']['timeout'],
				'headers' => [
					'Accept' => 'application/json',
					'User-Agent' => 'HellaZ SiteZ Analyzer/' . HSZ_VERSION
				]
			],
			'',  // No automatic caching by APIManager
			0
		);

		$response_time = microtime( true ) - $start_time;

		if ( empty( $response ) || isset( $response['errors'] ) ) {
			Utils::log_error( 'SSL Labs API request failed for host: ' . $host, __FILE__, __LINE__ );
			return [ 'error' => __( 'SSL Labs API request failed', 'hellaz-sitez-analyzer' ) ];
		}

		// Parse SSL Labs response
		$ssl_data = [
			'response_time' => $response_time,
			'ssl_labs_grade' => 'T',
			'protocol_support' => [],
			'cipher_suites' => [],
			'certificate_details' => [],
			'vulnerabilities' => [],
			'security_assessment' => []
		];

		if ( isset( $response['status'] ) && $response['status'] === 'READY' && isset( $response['endpoints'] ) ) {
			$endpoint = $response['endpoints'][0] ?? null;
			
			if ( $endpoint && isset( $endpoint['grade'] ) ) {
				$ssl_data['ssl_labs_grade'] = $endpoint['grade'];
				
				// Extract detailed information
				if ( isset( $endpoint['details'] ) ) {
					$details = $endpoint['details'];
					
					// Certificate information
					if ( isset( $details['cert'] ) ) {
						$cert = $details['cert'];
						$ssl_data['certificate_details'] = [
							'subject' => $cert['subject'] ?? '',
							'common_names' => $cert['commonNames'] ?? [],
							'issuer_subject' => $cert['issuerSubject'] ?? '',
							'sig_alg' => $cert['sigAlg'] ?? '',
							'key' => $cert['key'] ?? '',
							'valid_from' => isset( $cert['notBefore'] ) ? date( 'Y-m-d H:i:s', $cert['notBefore'] / 1000 ) : '',
							'valid_to' => isset( $cert['notAfter'] ) ? date( 'Y-m-d H:i:s', $cert['notAfter'] / 1000 ) : '',
							'days_until_expiry' => isset( $cert['notAfter'] ) ? ceil( ( $cert['notAfter'] / 1000 - time() ) / DAY_IN_SECONDS ) : 0
						];
					}

					// Protocol support
					if ( isset( $details['protocols'] ) ) {
						foreach ( $details['protocols'] as $protocol ) {
							$ssl_data['protocol_support'][] = [
								'name' => $protocol['name'] ?? '',
								'version' => $protocol['version'] ?? ''
							];
						}
					}

					// Cipher suites
					if ( isset( $details['suites'] ) ) {
						$ssl_data['cipher_suites'] = array_slice( $details['suites']['list'] ?? [], 0, 10 ); // Top 10
					}

					// Vulnerabilities
					$vuln_checks = [
						'vulnBeast' => 'BEAST',
						'vulnHeartbleed' => 'Heartbleed',
						'vulnOpenSslCcs' => 'OpenSSL CCS Injection',
						'vulnOpenSSLLuckyMinus20' => 'Lucky Minus 20',
						'vulnPoodle' => 'POODLE',
						'vulnFreak' => 'FREAK',
						'vulnLogjam' => 'Logjam',
						'vulnDrown' => 'DROWN'
					];

					foreach ( $vuln_checks as $check => $name ) {
						if ( isset( $details[ $check ] ) && $details[ $check ] ) {
							$ssl_data['vulnerabilities'][] = $name;
						}
					}

					// Security assessment
					$ssl_data['security_assessment'] = [
						'certificate_transparency' => $details['hasSct'] ?? 0,
						'hsts_policy' => $details['hstsPolicy'] ?? [],
						'hpkp_policy' => $details['hpkpPolicy'] ?? [],
						'ocsp_stapling' => $details['ocspStapling'] ?? false,
						'forward_secrecy' => $details['forwardSecrecy'] ?? 0
					];
				}
			}
		} elseif ( isset( $response['status'] ) && $response['status'] === 'IN_PROGRESS' ) {
			// Analysis still running, return partial data
			$ssl_data['status'] = 'analyzing';
			$ssl_data['message'] = __( 'SSL analysis in progress. Results will be available shortly.', 'hellaz-sitez-analyzer' );
		}

		// Cache results for 24 hours
		Cache::set( $cache_key, $ssl_data, DAY_IN_SECONDS, 'security' );

		return $ssl_data;
	}

	/**
	 * Enhanced direct certificate analysis
	 *
	 * @param string $host The hostname to analyze.
	 * @return array SSL certificate information.
	 */
	private function get_ssl_info_from_certificate( $host ) {
		$ssl_info = [
			'certificate_details' => [],
			'security_assessment' => [],
			'vulnerabilities' => [],
			'method' => 'direct_certificate_parsing'
		];

		$context = stream_context_create([
			'ssl' => [
				'capture_peer_cert' => true,
				'capture_peer_cert_chain' => true,
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);

		$start_time = microtime( true );
		$stream = @stream_socket_client( "ssl://{$host}:443", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context );
		$connection_time = microtime( true ) - $start_time;

		if ( ! $stream ) {
			$ssl_info['error'] = sprintf( __( 'SSL connection failed: %s', 'hellaz-sitez-analyzer' ), $errstr );
			return $ssl_info;
		}

		$ssl_info['connection_time'] = $connection_time;
		$params = stream_context_get_params( $stream );

		if ( isset( $params['options']['ssl']['peer_certificate'] ) ) {
			$cert_resource = $params['options']['ssl']['peer_certificate'];
			$cert = openssl_x509_parse( $cert_resource );

			if ( $cert ) {
				$ssl_info['certificate_details'] = [
					'subject' => $cert['subject']['CN'] ?? $host,
					'issuer' => $cert['issuer']['CN'] ?? $cert['issuer']['O'] ?? 'Unknown',
					'serial_number' => $cert['serialNumber'] ?? '',
					'valid_from' => date( 'Y-m-d H:i:s', $cert['validFrom_time_t'] ),
					'valid_to' => date( 'Y-m-d H:i:s', $cert['validTo_time_t'] ),
					'days_until_expiry' => ceil( ( $cert['validTo_time_t'] - time() ) / DAY_IN_SECONDS ),
					'signature_algorithm' => $cert['signatureTypeSN'] ?? 'Unknown'
				];

				// Get public key information
				$public_key = openssl_pkey_get_public( $cert_resource );
				if ( $public_key ) {
					$key_details = openssl_pkey_get_details( $public_key );
					$ssl_info['certificate_details']['key_size'] = $key_details['bits'] ?? 0;
					$ssl_info['certificate_details']['key_type'] = $this->get_key_type( $key_details['type'] ?? 0 );
				}

				// Check for security issues
				if ( $ssl_info['certificate_details']['days_until_expiry'] < 30 ) {
					$ssl_info['vulnerabilities'][] = sprintf( 
						__( 'Certificate expires in %d days', 'hellaz-sitez-analyzer' ), 
						$ssl_info['certificate_details']['days_until_expiry'] 
					);
				}

				if ( $ssl_info['certificate_details']['key_size'] < 2048 ) {
					$ssl_info['vulnerabilities'][] = sprintf( 
						__( 'Weak key size: %d bits', 'hellaz-sitez-analyzer' ), 
						$ssl_info['certificate_details']['key_size'] 
					);
				}

				// Extract Subject Alternative Names
				if ( isset( $cert['extensions']['subjectAltName'] ) ) {
					$san_string = $cert['extensions']['subjectAltName'];
					preg_match_all( '/DNS:([^,\s]+)/', $san_string, $matches );
					$ssl_info['certificate_details']['san_domains'] = $matches[1] ?? [];
				}
			}
		}

		// Check certificate chain
		if ( isset( $params['options']['ssl']['peer_certificate_chain'] ) ) {
			$chain = $params['options']['ssl']['peer_certificate_chain'];
			$ssl_info['certificate_chain_length'] = count( $chain );
		}

		fclose( $stream );

		return $ssl_info;
	}

	/**
	 * Get key type from OpenSSL constant
	 *
	 * @param int $type OpenSSL key type constant.
	 * @return string Human-readable key type.
	 */
	private function get_key_type( int $type ): string {
		switch ( $type ) {
			case OPENSSL_KEYTYPE_RSA:
				return 'RSA';
			case OPENSSL_KEYTYPE_EC:
				return 'ECC';
			case OPENSSL_KEYTYPE_DH:
				return 'DH';
			default:
				return 'Unknown';
		}
	}

	/**
	 * Calculate SSL score based on various factors
	 *
	 * @param array $ssl_info SSL information.
	 * @return int SSL score (0-100).
	 */
	private function calculate_ssl_score( array $ssl_info ): int {
		$score = 0;

		if ( ! $ssl_info['ssl_enabled'] ) {
			return 0;
		}

		// SSL Labs grade (if available)
		if ( isset( $ssl_info['ssl_labs_grade'] ) ) {
			$grade_scores = [
				'A+' => 100, 'A' => 90, 'A-' => 85,
				'B' => 70, 'C' => 50, 'D' => 30,
				'F' => 10, 'T' => 5
			];
			$score = $grade_scores[ $ssl_info['ssl_labs_grade'] ] ?? 0;
		} else {
			// Calculate score based on available information
			$cert_details = $ssl_info['certificate_details'] ?? [];
			
			// Certificate validity (40 points)
			if ( ! empty( $cert_details ) ) {
				$score += 20; // Basic certificate present
				
				$days_until_expiry = $cert_details['days_until_expiry'] ?? 0;
				if ( $days_until_expiry > 30 ) {
					$score += 20; // Valid certificate
				} elseif ( $days_until_expiry > 0 ) {
					$score += 10; // Expiring soon
				}
			}

			// Key strength (30 points)
			$key_size = $cert_details['key_size'] ?? 0;
			if ( $key_size >= 2048 ) {
				$score += 30;
			} elseif ( $key_size >= 1024 ) {
				$score += 15;
			}

			// Connection quality (30 points)
			$connection_time = $ssl_info['connection_time'] ?? 999;
			if ( $connection_time < 2 ) {
				$score += 30;
			} elseif ( $connection_time < 5 ) {
				$score += 20;
			} elseif ( $connection_time < 10 ) {
				$score += 10;
			}
		}

		// Deduct points for vulnerabilities
		$vulnerability_count = count( $ssl_info['vulnerabilities'] ?? [] );
		$score -= $vulnerability_count * 10;

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Comprehensive security analysis using multiple APIs
	 *
	 * @param string $url Website URL.
	 * @return array Security analysis results.
	 */
	private function get_comprehensive_security_analysis( string $url ): array {
		$security_analysis = [
			'virustotal' => [],
			'urlscan' => [],
			'reputation_summary' => [],
			'overall_risk' => 'unknown',
			'threat_score' => 0
		];

		// VirusTotal Analysis
		$vt_api_key = Utils::decrypt( get_option( 'hsz_virustotal_api_key', '' ) );
		if ( ! empty( $vt_api_key ) && get_option( 'hsz_virustotal_enabled' ) ) {
			$security_analysis['virustotal'] = $this->get_virustotal_analysis( $url, $vt_api_key );
		}

		// URLScan.io Analysis
		if ( get_option( 'hsz_urlscan_enabled' ) ) {
			$security_analysis['urlscan'] = $this->get_urlscan_analysis( $url );
		}

		// Calculate overall risk and threat score
		$security_analysis = $this->calculate_security_risk( $security_analysis );

		return $security_analysis;
	}

	/**
	 * Enhanced VirusTotal analysis
	 *
	 * @param string $url The URL to analyze.
	 * @param string $api_key The VirusTotal API key.
	 * @return array Security analysis data.
	 */
	public function get_virustotal_analysis( $url, $api_key ) {
		// Validate input
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || empty( $api_key ) ) {
			return [ 'error' => __( 'Invalid URL or API key', 'hellaz-sitez-analyzer' ) ];
		}

		$api_key = sanitize_text_field( $api_key );
		$cache_key = 'hsz_virustotal_' . Utils::generate_url_hash( $url );

		// Check rate limiting
		if ( ! Utils::check_rate_limit( 'virustotal', self::API_ENDPOINTS['virustotal']['rate_limit'] ) ) {
			return [ 'error' => __( 'VirusTotal API rate limit exceeded', 'hellaz-sitez-analyzer' ) ];
		}

		$start_time = microtime( true );
		
		$api_url = self::API_ENDPOINTS['virustotal']['url'] . '?' . http_build_query([
			'apikey' => $api_key,
			'resource' => $url
		]);

		$response = $this->apimanager->make_api_request(
			$api_url,
			[
				'timeout' => self::API_ENDPOINTS['virustotal']['timeout']
			],
			$cache_key,
			HOUR_IN_SECONDS * 2
		);

		$response_time = microtime( true ) - $start_time;
		Utils::record_api_usage( 'virustotal', 'url_report', $response_time, ! empty( $response ) );

		if ( empty( $response ) ) {
			return [ 'error' => __( 'VirusTotal API request failed', 'hellaz-sitez-analyzer' ) ];
		}

		// Parse the VirusTotal response
		if ( isset( $response['response_code'] ) && $response['response_code'] == 1 ) {
			$analysis_data = [
				'scanned' => true,
				'positives' => $response['positives'] ?? 0,
				'total' => $response['total'] ?? 0,
				'scan_date' => $response['scan_date'] ?? '',
				'permalink' => $response['permalink'] ?? '',
				'scans' => $response['scans'] ?? [],
				'threat_categories' => [],
				'risk_level' => 'clean'
			];

			// Analyze scan results for threat categories
			if ( isset( $response['scans'] ) && is_array( $response['scans'] ) ) {
				$threat_categories = [];
				foreach ( $response['scans'] as $engine => $result ) {
					if ( isset( $result['detected'] ) && $result['detected'] && isset( $result['result'] ) ) {
						$category = $this->categorize_threat( $result['result'] );
						if ( ! in_array( $category, $threat_categories, true ) ) {
							$threat_categories[] = $category;
						}
					}
				}
				$analysis_data['threat_categories'] = $threat_categories;
			}

			// Determine risk level
			$positives = $analysis_data['positives'];
			if ( $positives == 0 ) {
				$analysis_data['risk_level'] = 'clean';
			} elseif ( $positives <= 2 ) {
				$analysis_data['risk_level'] = 'low';
			} elseif ( $positives <= 5 ) {
				$analysis_data['risk_level'] = 'medium';
			} else {
				$analysis_data['risk_level'] = 'high';
			}

			return $analysis_data;
		} elseif ( isset( $response['response_code'] ) && $response['response_code'] == 0 ) {
			return [
				'scanned' => false,
				'message' => __( 'URL not found in VirusTotal database', 'hellaz-sitez-analyzer' ),
				'suggestion' => __( 'Submit URL for scanning', 'hellaz-sitez-analyzer' )
			];
		} elseif ( isset( $response['response_code'] ) && $response['response_code'] == -2 ) {
			return [
				'scanned' => false,
				'message' => __( 'URL queued for analysis', 'hellaz-sitez-analyzer' ),
				'suggestion' => __( 'Check back later for results', 'hellaz-sitez-analyzer' )
			];
		}

		return [ 'error' => __( 'Unexpected VirusTotal response', 'hellaz-sitez-analyzer' ) ];
	}

	/**
	 * Categorize threat based on detection result
	 *
	 * @param string $threat_result Threat detection result.
	 * @return string Threat category.
	 */
	private function categorize_threat( string $threat_result ): string {
		$threat_result = strtolower( $threat_result );
		
		$categories = [
			'malware' => ['malware', 'trojan', 'virus', 'backdoor', 'rootkit'],
			'phishing' => ['phishing', 'phish', 'scam', 'fraud'],
			'suspicious' => ['suspicious', 'unwanted', 'adware', 'pup'],
			'spam' => ['spam', 'spammer'],
			'exploit' => ['exploit', 'vulnerability', 'injection']
		];

		foreach ( $categories as $category => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $threat_result, $keyword ) !== false ) {
					return $category;
				}
			}
		}

		return 'other';
	}

	/**
	 * Enhanced URLScan.io analysis
	 *
	 * @param string $url The URL to analyze.
	 * @return array URLScan.io analysis results.
	 */
	public function get_urlscan_analysis( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return [ 'error' => __( 'Invalid URL provided', 'hellaz-sitez-analyzer' ) ];
		}

		$host = parse_url( $url, PHP_URL_HOST );
		$cache_key = 'hsz_urlscan_' . md5( $host );

		$start_time = microtime( true );
		
		$api_url = self::API_ENDPOINTS['urlscan']['url'] . '?' . http_build_query([
			'q' => 'domain:' . $host,
			'size' => 10
		]);

		$response = $this->apimanager->make_api_request(
			$api_url,
			[
				'timeout' => self::API_ENDPOINTS['urlscan']['timeout']
			],
			$cache_key,
			HOUR_IN_SECONDS * 6
		);

		$response_time = microtime( true ) - $start_time;
		Utils::record_api_usage( 'urlscan', 'search', $response_time, ! empty( $response ) );

		if ( empty( $response ) || ! isset( $response['results'] ) ) {
			return [ 'error' => __( 'URLScan.io API request failed', 'hellaz-sitez-analyzer' ) ];
		}

		$analysis_data = [
			'total_scans' => $response['total'] ?? 0,
			'recent_scans' => [],
			'malicious_count' => 0,
			'suspicious_count' => 0,
			'overall_verdict' => 'clean',
			'technologies' => [],
			'certificates' => []
		];

		foreach ( $response['results'] as $result ) {
			$scan_data = [
				'url' => $result['page']['url'] ?? '',
				'scan_time' => $result['task']['time'] ?? '',
				'screenshot' => $result['screenshot'] ?? '',
				'verdict' => [
					'overall' => $result['verdicts']['overall'] ?? [],
					'engines' => $result['verdicts']['engines'] ?? []
				]
			];

			// Count verdicts
			if ( isset( $result['verdicts']['overall']['malicious'] ) && $result['verdicts']['overall']['malicious'] ) {
				$analysis_data['malicious_count']++;
			}
			if ( isset( $result['verdicts']['overall']['suspicious'] ) && $result['verdicts']['overall']['suspicious'] ) {
				$analysis_data['suspicious_count']++;
			}

			$analysis_data['recent_scans'][] = $scan_data;
		}

		// Determine overall verdict
		if ( $analysis_data['malicious_count'] > 0 ) {
			$analysis_data['overall_verdict'] = 'malicious';
		} elseif ( $analysis_data['suspicious_count'] > 0 ) {
			$analysis_data['overall_verdict'] = 'suspicious';
		}

		return $analysis_data;
	}

	/**
	 * Calculate overall security risk
	 *
	 * @param array $security_analysis Security analysis data.
	 * @return array Updated security analysis with risk assessment.
	 */
	private function calculate_security_risk( array $security_analysis ): array {
		$threat_score = 0;
		$risk_factors = [];

		// VirusTotal risk assessment
		if ( isset( $security_analysis['virustotal']['positives'] ) ) {
			$positives = $security_analysis['virustotal']['positives'];
			$total = $security_analysis['virustotal']['total'] ?? 1;
			$vt_risk_score = ( $positives / $total ) * 100;
			$threat_score += $vt_risk_score * 0.6; // 60% weight

			if ( $positives > 0 ) {
				$risk_factors[] = sprintf( 
					__( '%d out of %d engines detected threats', 'hellaz-sitez-analyzer' ), 
					$positives, 
					$total 
				);
			}
		}

		// URLScan.io risk assessment
		if ( isset( $security_analysis['urlscan']['malicious_count'] ) ) {
			$malicious = $security_analysis['urlscan']['malicious_count'];
			$suspicious = $security_analysis['urlscan']['suspicious_count'];
			$total_scans = $security_analysis['urlscan']['total_scans'] ?? 1;
			
			$urlscan_risk_score = ( ( $malicious * 2 + $suspicious ) / $total_scans ) * 100;
			$threat_score += $urlscan_risk_score * 0.4; // 40% weight

			if ( $malicious > 0 || $suspicious > 0 ) {
				$risk_factors[] = sprintf( 
					__( 'URLScan.io found %d malicious and %d suspicious scans', 'hellaz-sitez-analyzer' ), 
					$malicious, 
					$suspicious 
				);
			}
		}

		$security_analysis['threat_score'] = round( min( $threat_score, 100 ) );

		// Determine overall risk level
		if ( $threat_score >= 75 ) {
			$security_analysis['overall_risk'] = 'high';
		} elseif ( $threat_score >= 50 ) {
			$security_analysis['overall_risk'] = 'medium';
		} elseif ( $threat_score >= 25 ) {
			$security_analysis['overall_risk'] = 'low';
		} else {
			$security_analysis['overall_risk'] = 'clean';
		}

		$security_analysis['reputation_summary'] = [
			'risk_factors' => $risk_factors,
			'threat_score' => $security_analysis['threat_score'],
			'overall_risk' => $security_analysis['overall_risk']
		];

		return $security_analysis;
	}

	/**
	 * Technology stack analysis
	 *
	 * @param string $url Website URL.
	 * @return array Technology analysis results.
	 */
	private function get_technology_analysis( string $url ): array {
		$builtwith_key = Utils::decrypt( get_option( 'hsz_builtwith_api_key', '' ) );
		
		if ( empty( $builtwith_key ) ) {
			return [ 'error' => __( 'BuiltWith API key not configured', 'hellaz-sitez-analyzer' ) ];
		}

		return $this->get_builtwith_analysis( $url, $builtwith_key );
	}

	/**
	 * Enhanced BuiltWith analysis
	 *
	 * @param string $url The URL to analyze.
	 * @param string $api_key The BuiltWith API key.
	 * @return array Technology stack data.
	 */
	public function get_builtwith_analysis( $url, $api_key ) {
		// Validate input
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || empty( $api_key ) ) {
			return [ 'error' => __( 'Invalid URL or API key', 'hellaz-sitez-analyzer' ) ];
		}

		$api_key = sanitize_text_field( $api_key );
		$cache_key = 'hsz_builtwith_' . Utils::generate_url_hash( $url );

		// Check rate limiting (BuiltWith has monthly limits)
		if ( ! Utils::check_rate_limit( 'builtwith', self::API_ENDPOINTS['builtwith']['rate_limit'] ) ) {
			return [ 'error' => __( 'BuiltWith API rate limit exceeded', 'hellaz-sitez-analyzer' ) ];
		}

		$start_time = microtime( true );
		
		$api_url = self::API_ENDPOINTS['builtwith']['url'] . '?' . http_build_query([
			'KEY' => $api_key,
			'LOOKUP' => $url
		]);

		$response = $this->apimanager->make_api_request(
			$api_url,
			[
				'timeout' => self::API_ENDPOINTS['builtwith']['timeout']
			],
			$cache_key,
			DAY_IN_SECONDS * 7 // Cache for a week
		);

		$response_time = microtime( true ) - $start_time;
		Utils::record_api_usage( 'builtwith', 'lookup', $response_time, ! empty( $response ) );

		if ( empty( $response ) || ! isset( $response['Results'] ) ) {
			return [ 'error' => __( 'BuiltWith API request failed', 'hellaz-sitez-analyzer' ) ];
		}

		// Parse the BuiltWith response
		$technology_data = [
			'technologies' => [],
			'categories' => [],
			'total_technologies' => 0,
			'technology_breakdown' => []
		];

		if ( isset( $response['Results'][0]['Result']['Paths'] ) ) {
			$all_technologies = [];
			$categories = [];

			foreach ( $response['Results'][0]['Result']['Paths'] as $path ) {
				if ( isset( $path['Technologies'] ) ) {
					foreach ( $path['Technologies'] as $tech ) {
						$tech_name = $tech['Name'] ?? 'Unknown';
						$tech_category = $tech['Categories'][0]['Name'] ?? 'Other';
						
						if ( ! in_array( $tech_name, $all_technologies, true ) ) {
							$all_technologies[] = $tech_name;
							
							if ( ! isset( $categories[ $tech_category ] ) ) {
								$categories[ $tech_category ] = [];
							}
							$categories[ $tech_category ][] = $tech_name;
						}
					}
				}
			}

			$technology_data['technologies'] = $all_technologies;
			$technology_data['categories'] = array_keys( $categories );
			$technology_data['total_technologies'] = count( $all_technologies );
			$technology_data['technology_breakdown'] = $categories;
		}

		return $technology_data;
	}

	/**
	 * Performance analysis using multiple APIs
	 *
	 * @param string $url Website URL.
	 * @return array Performance analysis results.
	 */
	private function get_performance_analysis( string $url ): array {
		$performance_data = [
			'pagespeed' => [],
			'webpagetest' => [],
			'performance_summary' => []
		];

		// Google PageSpeed Insights
		$pagespeed_key = Utils::decrypt( get_option( 'hsz_pagespeed_api_key', '' ) );
		if ( ! empty( $pagespeed_key ) && get_option( 'hsz_pagespeed_enabled' ) ) {
			$performance_data['pagespeed'] = $this->get_pagespeed_analysis( $url, $pagespeed_key );
		}

		// WebPageTest (if configured)
		$webpagetest_key = Utils::decrypt( get_option( 'hsz_webpagetest_api_key', '' ) );
		if ( ! empty( $webpagetest_key ) && get_option( 'hsz_webpagetest_enabled' ) ) {
			$performance_data['webpagetest'] = $this->get_webpagetest_analysis( $url, $webpagetest_key );
		}

		// Calculate performance summary
		$performance_data['performance_summary'] = $this->calculate_performance_summary( $performance_data );

		return $performance_data;
	}

	/**
	 * Google PageSpeed Insights analysis
	 *
	 * @param string $url Website URL.
	 * @param string $api_key PageSpeed API key.
	 * @return array PageSpeed analysis results.
	 */
	private function get_pagespeed_analysis( string $url, string $api_key ): array {
		$cache_key = 'hsz_pagespeed_' . Utils::generate_url_hash( $url );

		$strategies = [ 'mobile', 'desktop' ];
		$pagespeed_data = [];

		foreach ( $strategies as $strategy ) {
			$api_url = self::API_ENDPOINTS['pagespeed']['url'] . '?' . http_build_query([
				'url' => $url,
				'key' => $api_key,
				'strategy' => $strategy,
				'category' => 'performance'
			]);

			$start_time = microtime( true );
			$response = $this->apimanager->make_api_request(
				$api_url,
				[
					'timeout' => self::API_ENDPOINTS['pagespeed']['timeout']
				],
				$cache_key . '_' . $strategy,
				HOUR_IN_SECONDS * 6
			);
			$response_time = microtime( true ) - $start_time;

			Utils::record_api_usage( 'pagespeed', 'runPagespeed', $response_time, ! empty( $response ) );

			if ( ! empty( $response ) && isset( $response['lighthouseResult'] ) ) {
				$lighthouse = $response['lighthouseResult'];
				
				$pagespeed_data[ $strategy ] = [
					'score' => round( ( $lighthouse['categories']['performance']['score'] ?? 0 ) * 100 ),
					'metrics' => $this->extract_pagespeed_metrics( $lighthouse ),
					'opportunities' => $this->extract_pagespeed_opportunities( $lighthouse ),
					'diagnostics' => $this->extract_pagespeed_diagnostics( $lighthouse )
				];
			}
		}

		return $pagespeed_data;
	}

	/**
	 * Extract PageSpeed metrics
	 *
	 * @param array $lighthouse Lighthouse data.
	 * @return array Extracted metrics.
	 */
	private function extract_pagespeed_metrics( array $lighthouse ): array {
		$audits = $lighthouse['audits'] ?? [];
		$metrics = [];

		$metric_keys = [
			'first-contentful-paint' => 'FCP',
			'largest-contentful-paint' => 'LCP',
			'first-input-delay' => 'FID',
			'cumulative-layout-shift' => 'CLS',
			'speed-index' => 'Speed Index',
			'time-to-interactive' => 'TTI'
		];

		foreach ( $metric_keys as $key => $label ) {
			if ( isset( $audits[ $key ] ) ) {
				$audit = $audits[ $key ];
				$metrics[ $key ] = [
					'label' => $label,
					'value' => $audit['numericValue'] ?? 0,
					'displayValue' => $audit['displayValue'] ?? 'N/A',
					'score' => $audit['score'] ?? 0
				];
			}
		}

		return $metrics;
	}

	/**
	 * Extract PageSpeed opportunities
	 *
	 * @param array $lighthouse Lighthouse data.
	 * @return array Optimization opportunities.
	 */
	private function extract_pagespeed_opportunities( array $lighthouse ): array {
		$audits = $lighthouse['audits'] ?? [];
		$opportunities = [];

		$opportunity_keys = [
			'unused-css-rules',
			'unused-javascript',
			'modern-image-formats',
			'offscreen-images',
			'render-blocking-resources'
		];

		foreach ( $opportunity_keys as $key ) {
			if ( isset( $audits[ $key ] ) && isset( $audits[ $key ]['details'] ) ) {
				$audit = $audits[ $key ];
				$opportunities[ $key ] = [
					'title' => $audit['title'] ?? '',
					'description' => $audit['description'] ?? '',
					'score' => $audit['score'] ?? 1,
					'savings' => $audit['numericValue'] ?? 0
				];
			}
		}

		return $opportunities;
	}

	/**
	 * Extract PageSpeed diagnostics
	 *
	 * @param array $lighthouse Lighthouse data.
	 * @return array Diagnostic information.
	 */
	private function extract_pagespeed_diagnostics( array $lighthouse ): array {
		$audits = $lighthouse['audits'] ?? [];
		$diagnostics = [];

		$diagnostic_keys = [
			'total-byte-weight',
			'dom-size',
			'critical-request-chains',
			'server-response-time'
		];

		foreach ( $diagnostic_keys as $key ) {
			if ( isset( $audits[ $key ] ) ) {
				$audit = $audits[ $key ];
				$diagnostics[ $key ] = [
					'title' => $audit['title'] ?? '',
					'description' => $audit['description'] ?? '',
					'displayValue' => $audit['displayValue'] ?? 'N/A',
					'score' => $audit['score'] ?? 1
				];
			}
		}

		return $diagnostics;
	}

	/**
	 * WebPageTest analysis (placeholder for future implementation)
	 *
	 * @param string $url Website URL.
	 * @param string $api_key WebPageTest API key.
	 * @return array WebPageTest results.
	 */
	private function get_webpagetest_analysis( string $url, string $api_key ): array {
		// This would integrate with WebPageTest API
		// For now, return placeholder structure
		return [
			'status' => 'not_implemented',
			'message' => __( 'WebPageTest integration coming soon', 'hellaz-sitez-analyzer' )
		];
	}

	/**
	 * Calculate performance summary
	 *
	 * @param array $performance_data Performance analysis data.
	 * @return array Performance summary.
	 */
	private function calculate_performance_summary( array $performance_data ): array {
		$summary = [
			'overall_score' => 0,
			'mobile_score' => 0,
			'desktop_score' => 0,
			'core_web_vitals' => [],
			'performance_grade' => 'F',
			'key_issues' => []
		];

		// Extract scores from PageSpeed data
		if ( isset( $performance_data['pagespeed']['mobile']['score'] ) ) {
			$summary['mobile_score'] = $performance_data['pagespeed']['mobile']['score'];
		}

		if ( isset( $performance_data['pagespeed']['desktop']['score'] ) ) {
			$summary['desktop_score'] = $performance_data['pagespeed']['desktop']['score'];
		}

		// Calculate overall score (weighted average: 60% mobile, 40% desktop)
		if ( $summary['mobile_score'] > 0 && $summary['desktop_score'] > 0 ) {
			$summary['overall_score'] = round( ( $summary['mobile_score'] * 0.6 ) + ( $summary['desktop_score'] * 0.4 ) );
		} elseif ( $summary['mobile_score'] > 0 ) {
			$summary['overall_score'] = $summary['mobile_score'];
		} elseif ( $summary['desktop_score'] > 0 ) {
			$summary['overall_score'] = $summary['desktop_score'];
		}

		$summary['performance_grade'] = Utils::sanitize_grade( $summary['overall_score'] );

		return $summary;
	}

	/**
	 * Reputation analysis using multiple sources
	 *
	 * @param string $url Website URL.
	 * @return array Reputation analysis results.
	 */
	private function get_reputation_analysis( string $url ): array {
		$reputation_data = [
			'urlscan' => [],
			'domain_age' => [],
			'reputation_score' => 0,
			'reputation_grade' => 'F'
		];

		// URLScan.io analysis (already implemented)
		$reputation_data['urlscan'] = $this->get_urlscan_analysis( $url );

		// Domain age analysis (basic implementation)
		$reputation_data['domain_age'] = $this->get_domain_age_info( $url );

		// Calculate reputation score
		$reputation_data = $this->calculate_reputation_score( $reputation_data );

		return $reputation_data;
	}

	/**
	 * Get domain age information
	 *
	 * @param string $url Website URL.
	 * @return array Domain age information.
	 */
	private function get_domain_age_info( string $url ): array {
		$host = parse_url( $url, PHP_URL_HOST );
		
		// This is a basic implementation
		// In a full implementation, you might use WHOIS APIs
		return [
			'domain' => $host,
			'age_days' => 'unknown',
			'registration_date' => 'unknown',
			'expiration_date' => 'unknown'
		];
	}

	/**
	 * Calculate reputation score
	 *
	 * @param array $reputation_data Reputation data.
	 * @return array Updated reputation data with score.
	 */
	private function calculate_reputation_score( array $reputation_data ): array {
		$score = 50; // Start with neutral score

		// URLScan.io reputation impact
		if ( isset( $reputation_data['urlscan']['overall_verdict'] ) ) {
			switch ( $reputation_data['urlscan']['overall_verdict'] ) {
				case 'clean':
					$score += 30;
					break;
				case 'suspicious':
					$score -= 20;
					break;
				case 'malicious':
					$score -= 40;
					break;
			}
		}

		$reputation_data['reputation_score'] = max( 0, min( 100, $score ) );
		$reputation_data['reputation_grade'] = Utils::sanitize_grade( $reputation_data['reputation_score'] );

		return $reputation_data;
	}

	/**
	 * Calculate overall API analysis score
	 *
	 * @param array $analysis_results Analysis results.
	 * @return array Updated results with overall score.
	 */
	private function calculate_api_analysis_score( array $analysis_results ): array {
		$scores = [];
		$weights = [
			'ssl' => 0.25,
			'security' => 0.30,
			'performance' => 0.25,
			'reputation' => 0.20
		];

		// SSL score
		if ( isset( $analysis_results['ssl_info']['score'] ) ) {
			$scores['ssl'] = $analysis_results['ssl_info']['score'];
		}

		// Security score (inverse of threat score)
		if ( isset( $analysis_results['security_analysis']['threat_score'] ) ) {
			$scores['security'] = max( 0, 100 - $analysis_results['security_analysis']['threat_score'] );
		}

		// Performance score
		if ( isset( $analysis_results['performance_metrics']['performance_summary']['overall_score'] ) ) {
			$scores['performance'] = $analysis_results['performance_metrics']['performance_summary']['overall_score'];
		}

		// Reputation score
		if ( isset( $analysis_results['reputation_data']['reputation_score'] ) ) {
			$scores['reputation'] = $analysis_results['reputation_data']['reputation_score'];
		}

		// Calculate weighted average
		$weighted_sum = 0;
		$total_weight = 0;

		foreach ( $weights as $category => $weight ) {
			if ( isset( $scores[ $category ] ) ) {
				$weighted_sum += $scores[ $category ] * $weight;
				$total_weight += $weight;
			}
		}

		$analysis_results['analysis_score'] = $total_weight > 0 ? round( $weighted_sum / $total_weight ) : 0;
		$analysis_results['analysis_grade'] = Utils::sanitize_grade( $analysis_results['analysis_score'] );

		return $analysis_results;
	}

	/**
	 * Generate API-based recommendations
	 *
	 * @param array $analysis_results Analysis results.
	 * @return array Recommendations.
	 */
	private function generate_api_recommendations( array $analysis_results ): array {
		$recommendations = [];

		// SSL recommendations
		if ( isset( $analysis_results['ssl_info']['score'] ) && $analysis_results['ssl_info']['score'] < 70 ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'ssl',
				'title' => __( 'Improve SSL Configuration', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Upgrade SSL certificate, enable HSTS, and address security vulnerabilities.', 'hellaz-sitez-analyzer' )
			];
		}

		// Security recommendations
		if ( isset( $analysis_results['security_analysis']['threat_score'] ) && $analysis_results['security_analysis']['threat_score'] > 25 ) {
			$recommendations[] = [
				'priority' => 'critical',
				'category' => 'security',
				'title' => __( 'Address Security Threats', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Security scanners detected potential threats. Immediate investigation required.', 'hellaz-sitez-analyzer' )
			];
		}

		// Performance recommendations
		if ( isset( $analysis_results['performance_metrics']['performance_summary']['overall_score'] ) && 
			 $analysis_results['performance_metrics']['performance_summary']['overall_score'] < 70 ) {
			$recommendations[] = [
				'priority' => 'high',
				'category' => 'performance',
				'title' => __( 'Optimize Website Performance', 'hellaz-sitez-analyzer' ),
				'description' => __( 'Improve Core Web Vitals by optimizing images, reducing JavaScript, and enabling caching.', 'hellaz-sitez-analyzer' )
			];
		}

		return $recommendations;
	}

	/**
	 * Get API analysis summary
	 *
	 * @param array $analysis_data API analysis data.
	 * @return array API analysis summary.
	 */
	public static function get_api_summary( array $analysis_data ): array {
		return [
			'overall_grade' => $analysis_data['analysis_grade'] ?? 'F',
			'overall_score' => $analysis_data['analysis_score'] ?? 0,
			'ssl_enabled' => $analysis_data['ssl_info']['ssl_enabled'] ?? false,
			'ssl_grade' => $analysis_data['ssl_info']['grade'] ?? 'F',
			'threat_level' => $analysis_data['security_analysis']['overall_risk'] ?? 'unknown',
			'performance_score' => $analysis_data['performance_metrics']['performance_summary']['overall_score'] ?? 0,
			'technologies_detected' => $analysis_data['technology_stack']['total_technologies'] ?? 0,
			'api_services_used' => count( array_filter( $analysis_data['api_status'] ?? [] ) )
		];
	}
}
