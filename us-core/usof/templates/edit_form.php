<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output a single element's editing form
 *
 * @var $type    string Element type
 * @var $params  array  List of config-based params
 * @var $values  array  List of param_name => value
 * @var $context string Context param states which builder is it
 */

// Validating and sanitizing input
$values = ( isset( $values ) AND is_array( $values ) ) ? $values : array();
$context = $context ?? 'header';

// Validating, sanitizing and grouping params
$groups = $groups_indexes = array();
foreach ( $params as $param_name => &$param ) {

	if ( isset( $param['context'] ) AND ! in_array( $context, $param['context'] ) ) {
		continue;
	}

	$param['classes'] = $param['classes'] ?? '';
	$param['type'] = $param['type'] ?? 'text';

	// Check if context specific standard value is set
	$param['std'] = $param[ $context . '_std' ] ?? $param['std'] ?? '';

	// Filling missing values with standard ones
	if ( ! isset( $values[ $param_name ] ) ) {
		$values[ $param_name ] = $param['std'];
	}

	$group = $param['group'] ?? us_translate( 'General' );
	if ( ! isset( $groups[ $group ] ) ) {
		$groups_indexes[] = $group;
		$groups[ $group ] = array();
	}
	$groups[ $group ][ $param_name ] = &$param;
}
unset( $param );

// Sorts all array values by weight.
foreach ( $groups as &$params ) {
	us_sort_by_weight( $params );
}
unset( $params );

$output = '<div class="usof-form for_' . $type . '">';

// Message for deprecated elements
if ( usb_is_builder_page() AND ! empty( $deprecated ) ) {
	$output .= '<div class="usof-message type_deprecated">';
	$output .= __( 'This element is outdated. It won\'t be supported in the future.', 'us' );
	if ( ! empty( $alternative_elms ) ) {
		$output .= ' ' . sprintf(
			__( 'Use the following elements instead: %s', 'us' ),
			'<strong>' . $alternative_elms . '</strong>'
		);
	}
	$output .= '</div>';
}

if ( count( $groups_indexes ) > 1 ) {
	$output .= '<div class="usof-tabs">';
	$output .= '<div class="usof-tabs-list">';
	foreach ( $groups_indexes as $index => $group ) {
		$output .= '<div class="usof-tabs-item' . ( $index === 0 ? ' active' : '' ) . '">' . $group . '</div>';
	}
	$output .= '</div>';
	$output .= '<div class="usof-tabs-sections">';
}

foreach ( $groups_indexes as $index => $group ) {
	if ( count( $groups_indexes ) > 1 ) {
		$output .= '<div class="usof-tabs-section" style="display: ' . ( $index ? 'none' : 'flex' ) . '">';
	}
	$attributes_with_prefixes = array(
		'title',
		'description',
		'std',
		'cols',
		'classes',
		'show_if',
		'states',
		'with_position',
	);

	$show_fields = array();
	$group_params = &$groups[ $group ];
	foreach ( $group_params as $param_name => &$field ) {
		foreach ( $attributes_with_prefixes as $attribute ) {
			if ( ! empty( $field[ $context . '_' . $attribute ] ) ) {
				$field[ $attribute ] = $field[ $context . '_' . $attribute ];
			}
		}

		// If the parent parameter is hidden, then hide all children
		if ( ! isset( $show_fields[ $param_name ] ) ) {
			$show_if = us_arr_path( $field, 'show_if' );
			$show_fields[ $param_name ] = ( ! $show_if OR usof_execute_show_if( $show_if, $values ) );

			if ( is_array( $show_if ) ) {
				// If we have one condition, then turn it into an array to simplify checking
				if (
					isset( $show_if[0] )
					AND is_string( $show_if[0] )
					AND count( $show_if ) === 3
				) {
					$show_if = array( $show_if );
				}

				$condition_names = array();
				foreach ( $show_if as $index => $condition ) {
					$condition_field_name = is_array( $condition )
						? us_arr_path( $condition, '0' )
						: $condition;

					if (
						$condition_field_name
						AND ! in_array( strtolower( $condition_field_name ), array( 'or', 'and' ) )
						AND us_arr_path( $show_fields, $condition_field_name ) === FALSE
					) {
						$show_fields[ $param_name ] = FALSE;

						// Show the field by default
					} else {
						$show_fields[ $param_name ] = TRUE;
					}
				}
			}
		}

		$output .= us_get_template(
			'usof/templates/field', array(
				'context' => $context,
				'field' => $field,
				'id' => $context . '_' . $type . '_' . $param_name,
				'name' => $param_name,
				'show_field' => us_arr_path( $show_fields, $param_name ),
				'values' => $values,
			)
		);
	}
	unset( $group_params, $field, $show_fields );

	if ( count( $groups_indexes ) > 1 ) {
		$output .= '</div>'; // .usof-tabs-section
	}
}

if ( count( $groups ) > 1 ) {
	$output .= '</div>'; // .usof-tabs-sections
	$output .= '</div>'; // .usof-tabs
}
$output .= '</div>';

echo $output;
