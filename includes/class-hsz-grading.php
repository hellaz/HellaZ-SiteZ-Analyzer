<?php
/**
 * Grading system for HellaZ SiteZ Analyzer.
 *
 * Handles comprehensive website grading and scoring calculations.
 *
 * @package HellaZ_SiteZ_Analyzer
 * @since 1.1.0
 */

namespace HSZ;

defined( 'ABSPATH' ) || exit;

class Grading {

	/**
	 * Grade scale mapping
	 */
	const GRADES = [
		'A+' => 97, 'A' => 93, 'A-' => 90,
		'B+' => 87, 'B' => 83, 'B-' => 80,
		'C+' => 77, 'C' => 73, 'C-' => 70,
		'D+' => 67, 'D' => 63, 'D-' => 60,
		'F' => 0,
	];

	/**
	 * Default category weights
	 */
	private const DEFAULT_WEIGHTS = [
		'performance' => 0.30,
		'security' => 0.30,
		'content' => 0.20,
		'usability' => 0.20
	];

	/**
	 * Grade descriptors
	 */
	private const GRADE_DESCRIPTORS = [
		'A+' => ['Exceptional', '#00C851'],
		'A'  => ['Excellent', '#2BBBAD'],
		'A-' => ['Very Good', '#4285F4'],
		'B+' => ['Good', '#33B679'],
		'B'  => ['Above Average', '#FFA726'],
		'B-' => ['Satisfactory', '#FB8C00'],
		'C+' => ['Fair', '#FF7043'],
		'C'  => ['Below Average', '#EF5350'],
		'C-' => ['Poor', '#E53935'],
		'D+' => ['Very Poor', '#D32F2F'],
		'D'  => ['Critical', '#B71C1C'],
		'D-' => ['Failing', '#9E9E9E'],
		'F'  => ['Unacceptable', '#424242']
	];

	/**
	 * Calculate comprehensive website grade
	 *
	 * @param array $analysis_data Combined analysis data from all components.
	 * @param array $custom_weights Custom category weights.
	 * @return array Complete grading results.
	 */
	public function calculate_comprehensive_grade( array $analysis_data, array $custom_weights = [] ): array {
		// Use custom weights or defaults
		$weights = wp_parse_args( $custom_weights, self::DEFAULT_WEIGHTS );
		
		// Normalize weights to ensure they sum to 1.0
		$total_weight = array_sum( $weights );
		if ( $total_weight > 0 ) {
			foreach ( $weights as $key => $weight ) {
				$weights[ $key ] = $weight / $total_weight;
			}
		}

		$grading_data = [
			'url' => $analysis_data['url'] ?? '',
			'timestamp' => current_time( 'mysql', true ),
			'category_scores' => [],
			'category_grades' => [],
			'category_weights' => $weights,
			'overall_score' => 0,
			'overall_grade' => 'F',
			'grade_breakdown' => [],
			'strengths' => [],
			'weaknesses' => [],
			'priority_improvements' => [],
			'grade_factors' => [],
			'percentile_rank' => 0,
			'improvement_potential' => 0
		];

		// Calculate category scores
		$grading_data['category_scores'] = $this->calculate_category_scores( $analysis_data );
		
		// Convert scores to grades
		$grading_data['category_grades'] = $this->convert_scores_to_grades( $grading_data['category_scores'] );
		
		// Calculate weighted overall score
		$grading_data['overall_score'] = $this->calculate_weighted_score( 
			$grading_data['category_scores'], 
			$weights 
		);
		
		// Convert overall score to grade
		$grading_data['overall_grade'] = $this->get_grade( $grading_data['overall_score'] );
		
		// Generate detailed breakdown
		$grading_data['grade_breakdown'] = $this->generate_grade_breakdown( 
			$grading_data['category_scores'], 
			$grading_data['category_grades'], 
			$weights 
		);
		
		// Identify strengths and weaknesses
		$grading_data['strengths'] = $this->identify_strengths( $grading_data['category_scores'] );
		$grading_data['weaknesses'] = $this->identify_weaknesses( $grading_data['category_scores'] );
		
		// Generate priority improvements
		$grading_data['priority_improvements'] = $this->generate_priority_improvements( 
			$analysis_data, 
			$grading_data['category_scores'],
			$weights
		);
		
		// Calculate grade factors
		$grading_data['grade_factors'] = $this->calculate_grade_factors( $analysis_data );
		
		// Calculate improvement potential
		$grading_data['improvement_potential'] = $this->calculate_improvement_potential( $grading_data['category_scores'] );

		return $grading_data;
	}

	/**
	 * Calculate scores for each category
	 *
	 * @param array $analysis_data Analysis data from all components.
	 * @return array Category scores.
	 */
	private function calculate_category_scores( array $analysis_data ): array {
		$category_scores = [
			'performance' => 0,
			'security' => 0,
			'content' => 0,
			'usability' => 0
		];

		// Performance Score
		if ( isset( $analysis_data['performance_data'] ) ) {
			$category_scores['performance'] = $analysis_data['performance_data']['performance_score'] ?? 0;
		}

		// Security Score
		if ( isset( $analysis_data['security_data'] ) ) {
			$category_scores['security'] = $analysis_data['security_data']['security_score'] ?? 0;
		}

		// Content Score (based on metadata quality)
		if ( isset( $analysis_data['metadata_data'] ) ) {
			$category_scores['content'] = $this->calculate_content_score( $analysis_data['metadata_data'] );
		}

		// Usability Score (based on multiple factors)
		$category_scores['usability'] = $this->calculate_usability_score( $analysis_data );

		return $category_scores;
	}

	/**
	 * Calculate content score based on metadata quality
	 *
	 * @param array $metadata_data Metadata analysis data.
	 * @return int Content score (0-100).
	 */
	private function calculate_content_score( array $metadata_data ): int {
		$score = 0;
		$factors = [];

		// Title presence and quality (25 points)
		$title = $metadata_data['title'] ?? '';
		if ( ! empty( $title ) ) {
			$title_length = strlen( $title );
			if ( $title_length >= 30 && $title_length <= 60 ) {
				$factors['title'] = 25;
			} elseif ( $title_length >= 20 && $title_length <= 70 ) {
				$factors['title'] = 20;
			} elseif ( ! empty( $title ) ) {
				$factors['title'] = 15;
			}
		}

		// Description presence and quality (25 points)
		$description = $metadata_data['description'] ?? '';
		if ( ! empty( $description ) ) {
			$desc_length = strlen( $description );
			if ( $desc_length >= 120 && $desc_length <= 160 ) {
				$factors['description'] = 25;
			} elseif ( $desc_length >= 80 && $desc_length <= 200 ) {
				$factors['description'] = 20;
			} elseif ( ! empty( $description ) ) {
				$factors['description'] = 15;
			}
		}

		// Open Graph tags (20 points)
		$og_tags = $metadata_data['og'] ?? [];
		$og_score = 0;
		if ( isset( $og_tags['title'] ) ) $og_score += 5;
		if ( isset( $og_tags['description'] ) ) $og_score += 5;
		if ( isset( $og_tags['image'] ) ) $og_score += 5;
		if ( isset( $og_tags['url'] ) ) $og_score += 3;
		if ( isset( $og_tags['type'] ) ) $og_score += 2;
		$factors['open_graph'] = min( 20, $og_score );

		// Twitter Card tags (15 points)
		$twitter_tags = $metadata_data['twitter'] ?? [];
		$twitter_score = 0;
		if ( isset( $twitter_tags['card'] ) ) $twitter_score += 5;
		if ( isset( $twitter_tags['title'] ) ) $twitter_score += 4;
		if ( isset( $twitter_tags['description'] ) ) $twitter_score += 3;
		if ( isset( $twitter_tags['image'] ) ) $twitter_score += 3;
		$factors['twitter_card'] = min( 15, $twitter_score );

		// Structured data (10 points)
		$structured_data = $metadata_data['structured_data'] ?? [];
		if ( ! empty( $structured_data ) ) {
			$factors['structured_data'] = min( 10, count( $structured_data ) * 2 );
		}

		// Language and charset (5 points)
		if ( isset( $metadata_data['languages'] ) && ! empty( $metadata_data['languages'] ) ) {
			$factors['language'] = 5;
		}

		return array_sum( $factors );
	}

	/**
	 * Calculate usability score
	 *
	 * @param array $analysis_data Complete analysis data.
	 * @return int Usability score (0-100).
	 */
	private function calculate_usability_score( array $analysis_data ): int {
		$score = 0;
		$factors = [];

		// Mobile responsiveness (30 points)
		if ( isset( $analysis_data['performance_data']['lighthouse_metrics']['accessibility_score'] ) ) {
			$accessibility_score = $analysis_data['performance_data']['lighthouse_metrics']['accessibility_score'];
			$factors['accessibility'] = round( $accessibility_score * 0.3 );
		}

		// HTTPS usage (20 points)
		if ( isset( $analysis_data['security_data']['ssl_analysis']['enabled'] ) && 
			 $analysis_data['security_data']['ssl_analysis']['enabled'] ) {
			$factors['https'] = 20;
		}

		// Page load speed (25 points) - based on Core Web Vitals
		if ( isset( $analysis_data['performance_data']['core_web_vitals'] ) ) {
			$cwv = $analysis_data['performance_data']['core_web_vitals'];
			$good_vitals = 0;
			$total_vitals = 0;
			
			foreach ( ['lcp', 'fid', 'cls'] as $vital ) {
				if ( isset( $cwv[ $vital ] ) ) {
					$total_vitals++;
					if ( $cwv[ $vital ]['rating'] === 'good' ) {
						$good_vitals++;
					}
				}
			}
			
			if ( $total_vitals > 0 ) {
				$factors['core_web_vitals'] = round( ( $good_vitals / $total_vitals ) * 25 );
			}
		}

		// Navigation and structure (15 points)
		if ( isset( $analysis_data['metadata_data']['links'] ) ) {
			$links = $analysis_data['metadata_data']['links'];
			$internal_links = count( array_filter( $links, function( $link ) {
				return $link['type'] === 'internal';
			}));
			
			if ( $internal_links > 0 ) {
				$factors['navigation'] = min( 15, $internal_links );
			}
		}

		// Error pages and broken links (10 points deduction for issues)
		$base_navigation_score = 10;
		if ( isset( $analysis_data['security_data']['vulnerability_scan']['vulnerabilities_found'] ) ) {
			$vulnerabilities = $analysis_data['security_data']['vulnerability_scan']['vulnerabilities_found'];
			$base_navigation_score = max( 0, $base_navigation_score - $vulnerabilities );
		}
		$factors['error_handling'] = $base_navigation_score;

		return array_sum( $factors );
	}

	/**
	 * Convert numeric scores to letter grades
	 *
	 * @param array $scores Category scores.
	 * @return array Category grades.
	 */
	private function convert_scores_to_grades( array $scores ): array {
		$grades = [];
		foreach ( $scores as $category => $score ) {
			$grades[ $category ] = $this->get_grade( $score );
		}
		return $grades;
	}

	/**
	 * Calculate weighted overall score
	 *
	 * @param array $scores Category scores.
	 * @param array $weights Category weights.
	 * @return float Weighted score.
	 */
	private function calculate_weighted_score( array $scores, array $weights ): float {
		$weighted_sum = 0;
		$total_weight = 0;

		foreach ( $scores as $category => $score ) {
			$weight = $weights[ $category ] ?? 0;
			$weighted_sum += $score * $weight;
			$total_weight += $weight;
		}

		return $total_weight > 0 ? round( $weighted_sum / $total_weight, 2 ) : 0;
	}

	/**
	 * Generate detailed grade breakdown
	 *
	 * @param array $scores Category scores.
	 * @param array $grades Category grades.
	 * @param array $weights Category weights.
	 * @return array Grade breakdown.
	 */
	private function generate_grade_breakdown( array $scores, array $grades, array $weights ): array {
		$breakdown = [];

		foreach ( $scores as $category => $score ) {
			$grade = $grades[ $category ];
			$weight = $weights[ $category ] * 100; // Convert to percentage
			
			$breakdown[ $category ] = [
				'score' => $score,
				'grade' => $grade,
				'weight' => $weight,
				'weighted_contribution' => round( $score * $weights[ $category ], 2 ),
				'descriptor' => self::GRADE_DESCRIPTORS[ $grade ][0] ?? 'Unknown',
				'color' => self::GRADE_DESCRIPTORS[ $grade ][1] ?? '#666666',
				'status' => $this->get_category_status( $score )
			];
		}

		return $breakdown;
	}

	/**
	 * Get category status based on score
	 *
	 * @param int $score Category score.
	 * @return string Status description.
	 */
	private function get_category_status( int $score ): string {
		if ( $score >= 90 ) {
			return __( 'Excellent - No immediate action needed', 'hellaz-sitez-analyzer' );
		} elseif ( $score >= 80 ) {
			return __( 'Good - Minor improvements possible', 'hellaz-sitez-analyzer' );
		} elseif ( $score >= 70 ) {
			return __( 'Fair - Some improvements recommended', 'hellaz-sitez-analyzer' );
		} elseif ( $score >= 60 ) {
			return __( 'Poor - Significant improvements needed', 'hellaz-sitez-analyzer' );
		} else {
			return __( 'Critical - Immediate attention required', 'hellaz-sitez-analyzer' );
		}
	}

	/**
	 * Identify strengths based on category scores
	 *
	 * @param array $scores Category scores.
	 * @return array Identified strengths.
	 */
	private function identify_strengths( array $scores ): array {
		$strengths = [];
		
		foreach ( $scores as $category => $score ) {
			if ( $score >= 85 ) {
				$strengths[] = [
					'category' => $category,
					'score' => $score,
					'description' => $this->get_strength_description( $category, $score )
				];
			}
		}

		// Sort by score descending
		usort( $strengths, function( $a, $b ) {
			return $b['score'] - $a['score'];
		});

		return $strengths;
	}

	/**
	 * Identify weaknesses based on category scores
	 *
	 * @param array $scores Category scores.
	 * @return array Identified weaknesses.
	 */
	private function identify_weaknesses( array $scores ): array {
		$weaknesses = [];
		
		foreach ( $scores as $category => $score ) {
			if ( $score < 70 ) {
				$weaknesses[] = [
					'category' => $category,
					'score' => $score,
					'description' => $this->get_weakness_description( $category, $score ),
					'severity' => $this->get_weakness_severity( $score )
				];
			}
		}

		// Sort by score ascending (worst first)
		usort( $weaknesses, function( $a, $b ) {
			return $a['score'] - $b['score'];
		});

		return $weaknesses;
	}

	/**
	 * Get strength description
	 *
	 * @param string $category Category name.
	 * @param int $score Category score.
	 * @return string Strength description.
	 */
	private function get_strength_description( string $category, int $score ): string {
		$descriptions = [
			'performance' => __( 'Website loads quickly with excellent Core Web Vitals', 'hellaz-sitez-analyzer' ),
			'security' => __( 'Strong security measures protect against common threats', 'hellaz-sitez-analyzer' ),
			'content' => __( 'Well-optimized content with complete metadata', 'hellaz-sitez-analyzer' ),
			'usability' => __( 'Excellent user experience with good accessibility', 'hellaz-sitez-analyzer' )
		];

		return $descriptions[ $category ] ?? __( 'Performing well in this category', 'hellaz-sitez-analyzer' );
	}

	/**
	 * Get weakness description
	 *
	 * @param string $category Category name.
	 * @param int $score Category score.
	 * @return string Weakness description.
	 */
	private function get_weakness_description( string $category, int $score ): string {
		$descriptions = [
			'performance' => __( 'Slow loading times negatively impact user experience', 'hellaz-sitez-analyzer' ),
			'security' => __( 'Security vulnerabilities expose the website to risks', 'hellaz-sitez-analyzer' ),
			'content' => __( 'Missing or poorly optimized content metadata', 'hellaz-sitez-analyzer' ),
			'usability' => __( 'User experience issues may frustrate visitors', 'hellaz-sitez-analyzer' )
		];

		return $descriptions[ $category ] ?? __( 'Needs improvement in this category', 'hellaz-sitez-analyzer' );
	}

	/**
	 * Get weakness severity
	 *
	 * @param int $score Category score.
	 * @return string Severity level.
	 */
	private function get_weakness_severity( int $score ): string {
		if ( $score < 40 ) {
			return 'critical';
		} elseif ( $score < 60 ) {
			return 'high';
		} else {
			return 'medium';
		}
	}

	/**
	 * Generate priority improvements
	 *
	 * @param array $analysis_data Complete analysis data.
	 * @param array $scores Category scores.
	 * @param array $weights Category weights.
	 * @return array Priority improvements.
	 */
	private function generate_priority_improvements( array $analysis_data, array $scores, array $weights ): array {
		$improvements = [];

		foreach ( $scores as $category => $score ) {
			if ( $score < 80 ) {
				$weight = $weights[ $category ];
				$potential_impact = ( 100 - $score ) * $weight;
				
				$improvements[] = [
					'category' => $category,
					'current_score' => $score,
					'potential_impact' => round( $potential_impact, 2 ),
					'priority' => $this->calculate_improvement_priority( $score, $weight ),
					'recommendations' => $this->get_category_recommendations( $category, $analysis_data ),
					'estimated_effort' => $this->estimate_improvement_effort( $category, $score )
				];
			}
		}

		// Sort by potential impact descending
		usort( $improvements, function( $a, $b ) {
			return $b['potential_impact'] <=> $a['potential_impact'];
		});

		return array_slice( $improvements, 0, 5 ); // Top 5 improvements
	}

	/**
	 * Calculate improvement priority
	 *
	 * @param int $score Current score.
	 * @param float $weight Category weight.
	 * @return string Priority level.
	 */
	private function calculate_improvement_priority( int $score, float $weight ): string {
		$impact_score = ( 100 - $score ) * $weight;
		
		if ( $impact_score > 20 ) {
			return 'high';
		} elseif ( $impact_score > 10 ) {
			return 'medium';
		} else {
			return 'low';
		}
	}

	/**
	 * Get category-specific recommendations
	 *
	 * @param string $category Category name.
	 * @param array $analysis_data Analysis data.
	 * @return array Recommendations.
	 */
	private function get_category_recommendations( string $category, array $analysis_data ): array {
		$recommendations = [];

		switch ( $category ) {
			case 'performance':
				if ( isset( $analysis_data['performance_data']['recommendations'] ) ) {
					$recommendations = array_slice( $analysis_data['performance_data']['recommendations'], 0, 3 );
				}
				break;
				
			case 'security':
				if ( isset( $analysis_data['security_data']['recommendations'] ) ) {
					$recommendations = array_slice( $analysis_data['security_data']['recommendations'], 0, 3 );
				}
				break;
				
			case 'content':
				$recommendations = [
					__( 'Optimize page titles (30-60 characters)', 'hellaz-sitez-analyzer' ),
					__( 'Add meta descriptions (120-160 characters)', 'hellaz-sitez-analyzer' ),
					__( 'Implement Open Graph tags', 'hellaz-sitez-analyzer' )
				];
				break;
				
			case 'usability':
				$recommendations = [
					__( 'Improve mobile responsiveness', 'hellaz-sitez-analyzer' ),
					__( 'Enhance page loading speed', 'hellaz-sitez-analyzer' ),
					__( 'Fix accessibility issues', 'hellaz-sitez-analyzer' )
				];
				break;
		}

		return $recommendations;
	}

	/**
	 * Estimate improvement effort
	 *
	 * @param string $category Category name.
	 * @param int $score Current score.
	 * @return string Effort estimate.
	 */
	private function estimate_improvement_effort( string $category, int $score ): string {
		$effort_matrix = [
			'performance' => $score < 50 ? 'high' : ( $score < 70 ? 'medium' : 'low' ),
			'security' => $score < 60 ? 'medium' : 'low',
			'content' => 'low',
			'usability' => $score < 50 ? 'high' : 'medium'
		];

		return $effort_matrix[ $category ] ?? 'medium';
	}

	/**
	 * Calculate grade factors that influenced the final grade
	 *
	 * @param array $analysis_data Complete analysis data.
	 * @return array Grade factors.
	 */
	private function calculate_grade_factors( array $analysis_data ): array {
		$factors = [];

		// Performance factors
		if ( isset( $analysis_data['performance_data'] ) ) {
			$perf = $analysis_data['performance_data'];
			$factors['performance'] = [
				'core_web_vitals' => $perf['core_web_vitals'] ?? [],
				'lighthouse_score' => $perf['lighthouse_metrics']['performance_score'] ?? 0,
				'server_response_time' => $perf['server_metrics']['response_time'] ?? 0
			];
		}

		// Security factors
		if ( isset( $analysis_data['security_data'] ) ) {
			$sec = $analysis_data['security_data'];
			$factors['security'] = [
				'https_enabled' => $sec['ssl_analysis']['enabled'] ?? false,
				'security_headers_count' => count( $sec['security_headers']['present_headers'] ?? [] ),
				'vulnerabilities_count' => $sec['vulnerability_scan']['vulnerabilities_found'] ?? 0
			];
		}

		return $factors;
	}

	/**
	 * Calculate improvement potential
	 *
	 * @param array $scores Category scores.
	 * @return int Improvement potential (0-100).
	 */
	private function calculate_improvement_potential( array $scores ): int {
		$total_potential = 0;
		$categories = count( $scores );

		foreach ( $scores as $score ) {
			$potential = max( 0, 100 - $score );
			$total_potential += $potential;
		}

		return $categories > 0 ? round( $total_potential / $categories ) : 0;
	}

	/**
	 * Convert numeric score to letter grade
	 *
	 * @param float|int $score Numeric score.
	 * @return string Letter grade.
	 */
	public static function get_grade( float $score ): string {
		foreach ( self::GRADES as $grade => $min_score ) {
			if ( $score >= $min_score ) {
				return $grade;
			}
		}
		return 'F';
	}

	/**
	 * Get grade descriptor and color
	 *
	 * @param string $grade Letter grade.
	 * @return array Grade descriptor and color.
	 */
	public static function get_grade_info( string $grade ): array {
		return [
			'descriptor' => self::GRADE_DESCRIPTORS[ $grade ][0] ?? 'Unknown',
			'color' => self::GRADE_DESCRIPTORS[ $grade ][1] ?? '#666666'
		];
	}

	/**
	 * Calculate weighted overall score from component scores
	 *
	 * @param array $scores Component scores.
	 * @param array $weights Component weights.
	 * @return float Weighted overall score.
	 */
	public static function calculate_overall_score( array $scores, array $weights ): float {
		$total_weight = 0;
		$weighted_sum = 0;

		foreach ( $scores as $component => $score ) {
			$weight = $weights[ $component ] ?? 0;
			$total_weight += $weight;
			$weighted_sum += $score * $weight;
		}

		return $total_weight > 0 ? round( $weighted_sum / $total_weight, 2 ) : 0;
	}

	/**
	 * Get breakdown of grades for multiple components
	 *
	 * @param array $scores Component scores.
	 * @return array Grade breakdown.
	 */
	public static function breakdown_grades( array $scores ): array {
		$breakdown = [];
		foreach ( $scores as $component => $score ) {
			$breakdown[ $component ] = self::get_grade( $score );
		}
		return $breakdown;
	}
}
