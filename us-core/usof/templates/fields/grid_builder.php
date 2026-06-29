<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options Field: grid_builder
 *
 * Advanced header builder.
 *
 * @var $name string Field name
 * @var $id string Field ID
 * @var $field array Field options
 *
 * @var $value array Current value
 */

if ( ! empty( $value ) AND is_string( $value ) AND $value[0] === '{' ) {
	$value = json_decode( $value, TRUE );
}
$value = us_fix_grid_layout_settings( $value );

// Apply fallback to element values
if ( is_array( $value ) AND ! empty( $value[ 'data' ] ) ) {
	foreach( $value['data'] as $elm_id => $elm_options ) {
		$value['data'][ $elm_id ] = apply_filters( 'us_edit_atts_fallback_us_' . /* elm_name */strtok( $elm_id, ':' ), $elm_options );
	}
}

// Predefined dynamic values
$predefined_dynamic_values = usof_get_predefined_dynamic_values();

$config_elms = $admin_labels = array();

// Get element configs and admin_labels
foreach( us_config( 'grid-settings.elements', array() ) as $elm_type ) {

	$config_elms[ $elm_type ] = us_config( 'elements/' . $elm_type );

	foreach ( (array) $config_elms[ $elm_type ]['params'] as $param_name => $param ) {
		if ( isset( $param['us_admin_label'] ) ) {
			$admin_labels[ $elm_type ] = array(
				'param_name' => $param_name,
				'param_options' => $param['options'] ?? array(),
				'dynamic_values' => isset( $param['dynamic_values'] ),
			);
			if ( isset( $param['dynamic_values'] ) AND is_array( $param['dynamic_values'] ) ) {
				$predefined_dynamic_values = array_merge( $predefined_dynamic_values, array_values( $param['dynamic_values'] ) );
			}
			break;
		}
	}
}

if ( ! function_exists( 'usgb_get_custom_field_labels' ) ) {
	/**
	 * Get translatable custom field labels divided by groupes
	 *
	 * @return array
	 */
	function usgb_get_custom_field_labels() {
		$post_custom_fields = us_config( 'elements/post_custom_field.params.key.options', array() );
		$post_custom_fields_translation = array();

		foreach ( $post_custom_fields as $group_id => $post_custom_field ) {
			if ( $group_id === 'custom' ) {
				$post_custom_fields_translation[ $group_id ] = $post_custom_field;
				continue;
			}

			$group_title = $group_id;
			if ( is_numeric( $group_id ) AND $post_custom_field['__group_label__'] ) {
				$group_title = $post_custom_field['__group_label__'];
			}
			foreach ( $post_custom_field as $field_key => $field_name ) {
				if ( $field_key === '__group_label__' ) {
					continue;
				}
				$post_custom_fields_translation[ $field_key ] = $group_title . ': ' . $field_name;
			}
		}

		if ( ! empty( $post_custom_fields_translation ) ) {
			return $post_custom_fields_translation;
		}

		return $post_custom_fields;
	}
}

$output = '<div class="us-bld" data-ajaxurl="' . esc_attr( admin_url( 'admin-ajax.php' ) ) . '">';

// States
$output .= '<div class="us-bld-states" style="display: none;">';
$output .= '<div class="us-bld-state ui-icon_devices_default active">' . us_translate( 'Default' ) . '</div>';
$output .= '</div>';

// Workspace
$output .= '<div class="us-bld-workspace for_default">';

// Editor
if ( ! function_exists( 'usgb_get_elms_placeholders' ) ) {
	/**
	 * Prepare HTML for elements list for a certain elements area
	 *
	 * @param array $layout
	 * @param array $data Elements data
	 * @param string $place
	 * @param array $config_elms
	 * @param array $admin_labels
	 * @param array $predefined_dynamic_values
	 *
	 * @return string
	 */
	function usgb_get_elms_placeholders( &$layout, &$data, $place, $config_elms, $admin_labels, $predefined_dynamic_values ) {
		$output = '';

		if ( ! isset( $layout[ $place ] ) OR ! is_array( $layout[ $place ] ) ) {
			return $output;
		}

		foreach ( $layout[ $place ] as $elm_id ) {

			// Check if the element has absolute position (= at least one design options position is not empty)
			$is_absolute = FALSE;
			foreach ( (array) us_get_responsive_states( /* only keys */TRUE ) as $_state ) {
				$_position_value = us_arr_path( $data, $elm_id . '.css.' . $_state . '.position', '' );
				if ( $_position_value == 'absolute' ) {
					$is_absolute = TRUE;
					break;
				}
			}

			$_atts = array(
				'data-id' => $elm_id,
			);

			// Wrapper element
			if ( substr( $elm_id, 1, 7 ) == 'wrapper' ) {

				$_atts['class'] = 'us-bld-editor-wrapper type_' . ( $elm_id[0] == 'h' ? 'horizontal' : 'vertical' )
				;
				if ( $is_absolute ) {
					$_atts['class'] .= ' is_absolute_pos';
				}
				if ( ! isset( $layout[ $elm_id ] ) OR empty( $layout[ $elm_id ] ) ) {
					$_atts['class'] .= ' empty';
				}

				$output .= '<div' . us_implode_atts( $_atts ) . '>';
				$output .= '<div class="us-bld-editor-wrapper-content">';
				$output .= usgb_get_elms_placeholders( $layout, $data, $elm_id, $config_elms, $admin_labels, $predefined_dynamic_values );
				$output .= '</div>';
				$output .= '<div class="us-bld-editor-wrapper-controls">';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_add" title="' . esc_attr( __( 'Add element into wrapper', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_edit" title="' . esc_attr( __( 'Edit wrapper', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_clone" title="' . esc_attr( __( 'Duplicate', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_delete" title="' . esc_attr( us_translate( 'Delete' ) ) . '"></a>';
				$output .= '</div>'; // .us-bld-editor-wrapper-controls
				$output .= '</div>'; // .us-bld-editor-wrapper

				// Standard element
			} else {

				$elm_type = strtok( $elm_id, ':' );
				$elm_icon = $config_elms[ $elm_type ]['icon'] ?? '';
				$values = $data[ $elm_id ] ?? array();

				$popup_as_button = (
					$elm_type == 'popup' AND $values['show_on'] == 'btn'
				);

				$popup_as_icon = (
					$elm_type == 'popup' AND $values['show_on'] == 'icon'
				);

				$popup_trigger_not_button = (
					$elm_type == 'popup' AND $values['show_on'] != 'btn'
				);

				// The button may contain only an icon and no text.
				if ( $elm_type == 'btn' OR $elm_type == 'text' OR $popup_as_button ) {
					$elm_title = $values['label'] ?? '';

				} else {
					$elm_title = $config_elms[ $elm_type ]['title'] ?? '';
				}

				// Icon
				if ( ! empty( $values['icon'] ) ) {
					$elm_icon = us_prepare_icon_tag( $values['icon'] );

					// Popup icon for button
				} else if ( $popup_as_button ) {
					$elm_icon = us_prepare_icon_tag( $values['btn_icon'] ?? '' );

				} else if( $elm_type == 'btn' ) {
					$elm_icon = '';

					// Set default icon for all elements except button
				} else if ( $elm_icon ) {
					$elm_icon = '<i class="' . $elm_icon . '"></i>';
				}

				$admin_label = '';
				$after_admin_label = '';
				$param_name = $admin_labels[ $elm_type ]['param_name'] ?? '';
				$param_options = $admin_labels[ $elm_type ]['param_options'] ?? array();

				if ( $param_name AND isset( $values[ $param_name ] ) ) {
					$admin_label = $values[ $param_name ];
				}

				// Show Popup via "Icon"
				if ( $popup_as_icon ) {
					if ( ! empty( $values['btn_icon'] ) ) {
						$elm_icon = us_prepare_icon_tag( $values['btn_icon'] );
					}
					$elm_title = $admin_label = '';
				}

				// Post Custom Field element
				if ( $elm_type == 'post_custom_field' AND $admin_label == 'custom' ) {
					if ( isset( $values[ 'custom_key' ] ) ) {
						$admin_label = $values[ 'custom_key' ];
					}

					// Product Data element
				} elseif ( $elm_type == 'product_field' AND $admin_label == 'sale_badge' ) {
					if ( ! empty( $values['sale_text'] ) ) {
						$after_admin_label = sprintf( ': "%s"', $values['sale_text'] );
					}

					// User Data element
				} elseif ( $elm_type == 'user_data' AND $admin_label == 'custom' ) {
					if ( isset( $values[ 'custom_field' ] ) ) {
						$admin_label = $values[ 'custom_field' ];
					}

					// Image element
				} elseif ( $elm_type == 'image' AND is_numeric( $values[ $param_name ] ) ) {
					if ( ! $elm_icon = wp_get_attachment_image( $values[ $param_name ], 'thumbnail' ) ) {
						$elm_icon = us_get_img_placeholder( 'thumbnail' );
					}
					$elm_title = $admin_label = '';
				}

				// Dynamic values
				if ( isset( $admin_labels[ $elm_type ]['dynamic_values'] ) ) {

					$admin_label = $predefined_dynamic_values[ $admin_label ] ?? $admin_label;

					if ( us_is_dynamic_variable( $admin_label ) ) {
						$var_name = preg_replace( '/^{{([\dA-z\/\|\-_]+)}}$/', '$1', $admin_label );
						$admin_label = $predefined_dynamic_values[ $var_name ] ?? $admin_label;
					}
				}

				if ( ! empty( $admin_label ) AND ! $popup_trigger_not_button ) {
					$elm_title = $admin_label;
				}

				// Final check if $elm_title is still slug
				if ( ! empty( $param_options[ $elm_title ] ) ) {
					$elm_title = $param_options[ $elm_title ];
				}

				$iconpos = $values['iconpos'] ?? 'left';

				if ( $popup_as_button AND ! empty( $values['btn_iconpos'] ) ) {
					$iconpos = $values['btn_iconpos'];
				}

				$_atts['class'] = 'us-bld-editor-elm type_' . $elm_type;

				// Output
				$output .= '<div' . us_implode_atts( $_atts ) . '>';
				$output .= '<div class="us-bld-editor-elm-content">';

				if ( $elm_type == 'btn' OR $popup_as_button ) {
					$output .= '<button type="button">';
				}
				if ( $iconpos == 'left' ) {
					$output .= '<span class="us-bld-editor-elm-icon">' . $elm_icon . '</span>';
				}
				$output .= '<span class="us-bld-editor-elm-value">' . $elm_title . $after_admin_label . '</span>';
				if ( $iconpos == 'right' ) {
					$output .= '<span class="us-bld-editor-elm-icon">' . $elm_icon . '</span>';
				}
				if ( $elm_type == 'btn' OR $popup_as_button ) {
					$output .= '</button>';
				}
				$output .= '</div>'; // .us-bld-editor-wrapper-content
				$output .= '<div class="us-bld-editor-elm-controls">';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_edit" title="' . esc_attr( __( 'Edit element', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_clone" title="' . esc_attr( __( 'Duplicate', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_delete" title="' . esc_attr( us_translate( 'Delete' ) ) . '"></a>';
				$output .= '</div>'; // .us-bld-editor-elm-controls
				$output .= '</div>'; // .us-bld-editor-elm
			}
		}

		return $output;
	}
}

$output .= '<div class="us-bld-editor">';
$output .= '<div class="us-bld-editor-row at_middle">';
$output .= '<div class="us-bld-editor-row-h">';
$output .= '<div class="us-bld-editor-cell at_center">';

// Output inner widgets keeping middle_center for compatibility
$output .= usgb_get_elms_placeholders( $value['default']['layout'], $value['data'], 'middle_center', $config_elms, $admin_labels, $predefined_dynamic_values );

$output .= '<a href="javascript:void(0)" class="us-bld-editor-add" title="' . esc_attr( __( 'Add element', 'us' ) ) . '"></a>';

$output .= '</div>'; // .us-bld-editor-cell
$output .= '</div>'; // .us-bld-editor-row-h
$output .= '</div>'; // .us-bld-editor-row
$output .= '</div>'; // .us-bld-editor

// Options
$output .= '<div class="us-bld-options">';
$hb_options_sections = array(
	'global' => __( 'Grid Layout Settings', 'us' ),
	// 'hover' => __( 'On hover', 'us' ),
);

// Fallback for options
$value = us_grid_layout_settings_fallback( $value );

$options_values = us_arr_path( $value, 'default.options', array() );

// Setting starting state to properly handle show_if rules
$options_values['state'] = 'default';
foreach ( $hb_options_sections as $hb_section => $hb_section_title ) {
	$output .= '<div class="us-bld-options-section' . ( ( $hb_section == 'global' ) ? ' active' : '' ) . '" data-id="' . $hb_section . '">';
	$output .= '<div class="us-bld-options-section-title">' . $hb_section_title . '</div>';
	$output .= '<div class="us-bld-options-section-content" style="display: ' . ( ( $hb_section == 'global' ) ? 'block' : 'none' ) . ';">';
	foreach ( us_config( 'grid-settings.options.' . $hb_section, array() ) as $field_name => $field ) {
		if ( ! isset( $field['type'] ) ) {
			continue;
		}
		$field_html = us_get_template(
			'usof/templates/field', array(
				'name' => $field_name,
				'id' => 'hb_opt_' . $field_name,
				'field' => $field,
				'values' => $options_values,
			)
		);
		// Changing rows' classes to prevent auto-init of these rows as main fields
		$field_html = preg_replace( '~usof\-form\-(row|wrapper) ~', 'usof-subform-$1 ', $field_html );
		$output .= $field_html;
	}
	$output .= '</div>'; // .us-bld-options-section-content
	$output .= '</div>'; // .us-bld-options-section
}
$output .= ' </div>'; // .us-bld-options


// Elements' default values
$elms_titles = $element_icons = $default_values = array();
foreach ( $config_elms as $elm_type => $elm_config ) {
	$elms_titles[ $elm_type ] = $elm_config['title'] ?? $elm_type;
	$element_icons[ $elm_type ] = $elm_config['icon'] ?? '';
	$default_values[ $elm_type ] = us_get_elm_defaults( $elm_type, 'grid' );
}

$translations = array(
	'template_replace_confirm' => __( 'Selected template will overwrite all your current elements and settings! Are you sure want to apply it?', 'us' ),
	'orientation_change_confirm' => __( 'Are you sure want to change the header orientation? Some of your elements\' positions may be changed', 'us' ),
	'element_delete_confirm' => __( 'Are you sure want to delete the element?', 'us' ),
	'add_element' => __( 'Add element into wrapper', 'us' ),
	'edit_element' => __( 'Edit element', 'us' ),
	'clone_element' => __( 'Duplicate', 'us' ),
	'delete_element' => us_translate( 'Delete' ),
	'edit_wrapper' => __( 'Edit wrapper', 'us' ),
	'delete_wrapper' => us_translate( 'Delete' ),
);

$js_data = array(
	'admin_labels' => $admin_labels,
	'predefined_dynamic_values' => $predefined_dynamic_values,
	'default_values' => $default_values,
	'element_icons' => $element_icons,
	'elms_titles' => $elms_titles,
	'translations' => $translations,
	'value' => $value,
	'params' => array(
		'navMenus' => us_get_nav_menus(),
	),
);
$output .= '<div class="us-bld-data hidden"'. us_pass_data_to_js( $js_data ) .'></div>';
$output .= '</div>'; // .us-bld-workspace

$output .= us_get_template(
	'usof/templates/window_add', array(
		'elements' => array_keys( $config_elms ),
	)
);

// Empty editor window for loading the elements afterwards
$output .= us_get_template(
	'usof/templates/window_edit', array(
		'titles' => $elms_titles,
		'body' => '',
	)
);

$output .= us_get_template(
	'usof/templates/window_export_import', array(
		'title' => __( 'Export / Import', 'us' ),
		'text' => __( 'To import another Grid Layout replace the text in this field and click "Import" button.', 'us' ),
		'save_text' => __( 'Import Grid Layout', 'us' ),
	)
);

// Empty grid layout templates window for loading the templates afterwards
$output .= us_get_template(
	'usof/templates/window_templates', array(
		'body' => '',
	)
);
echo $output;
