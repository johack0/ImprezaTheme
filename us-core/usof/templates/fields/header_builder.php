<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Theme Options Field: header_builder
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
	$value = json_decode( $value, /* to array */TRUE );
}

// Apply fallback for old header data (when header builder was a separate plugin)
if ( function_exists( 'us_header_settings_fallback' ) ) {
	$value = us_header_settings_fallback( $value );
}
if ( function_exists( 'us_fix_header_settings' ) ) {
	$value = us_fix_header_settings( $value );
}

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
foreach( us_config( 'header-settings.elements', array() ) as $elm_type ) {

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

$output = '<div class="us-bld" data-ajaxurl="' . esc_attr( admin_url( 'admin-ajax.php' ) ) . '">';

// States
$output .= '<div class="us-bld-states">';

foreach ( us_get_responsive_states() as $state => $data ) {
	$state_atts = array(
		'class' => 'us-bld-state ui-icon_devices_' . $state,
	);
	if ( $state == 'default' ) {
		$state_atts['class'] .= ' active';
	}
	$output .= '<div' . us_implode_atts( $state_atts ) . '>' . $data['title'] . '</div>';
}

$output .= '</div>'; // .us-bld-states

// Workspace
$output .= '<div class="us-bld-workspace for_default">';

// Editor
if ( ! function_exists( 'ushb_get_elms_placeholders' ) ) {
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
	function ushb_get_elms_placeholders( &$layout, &$data, $place, $config_elms, $admin_labels, $predefined_dynamic_values ) {
		$output = '';
		if ( ! isset( $layout[ $place ] ) OR ! is_array( $layout[ $place ] ) ) {
			return $output;
		}

		foreach ( $layout[ $place ] as $element ) {

			if ( substr( $element, 1, 7 ) == 'wrapper' ) {
				$output .= '<div class="us-bld-editor-wrapper type_' . ( ( $element[0] == 'h' ) ? 'horizontal' : 'vertical' );
				if ( ! isset( $layout[ $element ] ) OR empty( $layout[ $element ] ) ) {
					$output .= ' empty';
				}
				$output .= '" data-id="' . esc_attr( $element ) . '">';
				$output .= '<div class="us-bld-editor-wrapper-content">';
				$output .= ushb_get_elms_placeholders( $layout, $data, $element, $config_elms, $admin_labels, $predefined_dynamic_values );
				$output .= '</div>';
				$output .= '<div class="us-bld-editor-wrapper-controls">';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_add" title="' . esc_attr( __( 'Add element into wrapper', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_edit" title="' . esc_attr( __( 'Edit wrapper', 'us' ) ) . '"></a>';
				$output .= '<a href="javascript:void(0)" class="us-bld-editor-control type_delete" title="' . esc_attr( us_translate( 'Delete' ) ) . '"></a>';
				$output .= '</div>'; // .us-bld-editor-wrapper-controls
				$output .= '</div>'; // .us-bld-editor-wrapper

			} else {

				$elm_type = strtok( $element, ':' );
				$elm_icon = $config_elms[ $elm_type ]['icon'] ?? '';
				$values = $data[ $element ] ?? array();

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

				} else if( $elm_type == 'btn' OR $elm_type == 'text' ) {
					$elm_icon = '';

					// Set default icon for all elements except button
				} else if ( $elm_icon ) {
					$elm_icon = '<i class="' . $elm_icon . '"></i>';
				}

				$admin_label = '';
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

				// Image element
				if ( $elm_type == 'image' ) {

					// Attachment ID
					if ( is_numeric( $values[ $param_name ] ) ) {

						if ( ! $elm_icon = wp_get_attachment_image( $values[ $param_name ], 'thumbnail' ) ) {
							$elm_icon = us_get_img_placeholder( 'thumbnail' );
						}

						$elm_title = $admin_label = '';

					// If value is url put it directly (template import case)
					} elseif ( is_string( $values[ $param_name ] ) AND filter_var( $values[ $param_name ], FILTER_VALIDATE_URL ) ) {

						$elm_icon = sprintf(
							'<img src="%s" alt="">',
							esc_url( $values[ $param_name ] )
						);

						$elm_title = $admin_label = '';
					}

					// Menu/"Simple Menu" element
				} elseif ( ( $elm_type == 'menu' OR $elm_type == 'additional_menu' ) AND ! empty( $values['source'] ) ) {
					$nav_menus = us_get_nav_menus();
					if ( isset( $nav_menus[ $values['source'] ] ) ) {
						$admin_label = $nav_menus[ $values['source'] ];
					}

					// Dropdown element
				} elseif ( $elm_type == 'dropdown' AND isset( $values['source'] ) ) {
					if ( ! empty( $values['link_icon'] ) ) {
						$elm_icon = us_prepare_icon_tag( $values['link_icon'] );
					}
					// Custom Link
					if ( $values['source'] == 'own' ) {
						$admin_label = $values['link_title'] ?? $elm_title;

					} else if ( isset( $param_options[ $values['source'] ] ) ) {
						$admin_label = $param_options[ $values['source'] ];
					}

					// Search element
				} elseif ( $elm_type == 'search' AND ! empty( $values['text'] ) ) {
					$admin_label = strip_tags( $values['text'] );

					// Social Links element
				} elseif ( $elm_type == 'socials' ) {
					$socials_html = '';
					foreach ( $values['items'] as $key => $value ) {
						if ( $value['type'] == 'custom' AND isset( $value['icon'] ) ) {
							$socials_html .= us_prepare_icon_tag( $value['icon'] );
						} else {
							$socials_html .= apply_filters( 'us_icon_tag', '<i class="fab fa-' . $value['type'] . '"></i>' );
						}
					}
					if ( $socials_html ) {
						$elm_icon = '';
					}
					$admin_label = $socials_html ?? $elm_title;
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

				// Output
				$output .= '<div class="us-bld-editor-elm type_' . $elm_type . '" data-id="' . esc_attr( $element ) . '">';
				$output .= '<div class="us-bld-editor-elm-content">';

				if ( $elm_type == 'btn' OR $popup_as_button ) {
					$output .= '<button type="button">';
				}
				if ( $iconpos == 'left' ) {
					$output .= '<span class="us-bld-editor-elm-icon">' . $elm_icon . '</span>';
				}
				$output .= '<span class="us-bld-editor-elm-value">' . $elm_title . '</span>';
				if ( $iconpos == 'right' ) {
					$output .= '<span class="us-bld-editor-elm-icon">' . $elm_icon . '</span>';
				}
				if ( $elm_type == 'btn' OR $popup_as_button ) {
					$output .= '</button>';
				}

				$output .= '</div>'; // .us-bld-editor-elm-content
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
$output .= '<div class="us-bld-editor type_';
$output .= ( us_arr_path( $value, 'default.options.orientation', 'hor' ) == 'ver' ) ? 'ver' : 'hor';
$output .= '">';
foreach ( array( 'top', 'middle', 'bottom' ) as $at_y ) {
	$output .= '<div class="us-bld-editor-row at_' . $at_y;
	if ( ( $at_y == 'top' OR $at_y == 'bottom' ) AND ! us_arr_path( $value, 'default.options.' . $at_y . '_show' ) ) {
		$output .= ' disabled';
	}
	$output .= '">';
	$output .= '<div class="us-bld-editor-row-h">';
	foreach ( array( 'left', 'center', 'right' ) as $at_x ) {

		$output .= '<div class="us-bld-editor-cell at_' . $at_x . '">';

		// Output inner widgets
		$output .= ushb_get_elms_placeholders(
			$value['default']['layout'],
			$value['data'],
			$at_y . '_' . $at_x,
			$config_elms,
			$admin_labels,
			$predefined_dynamic_values
		);
		$output .= '<a href="javascript:void(0)" class="us-bld-editor-add" title="' . esc_attr( __( 'Add element', 'us' ) ) . '"></a>';

		$output .= '</div>'; // .us-bld-editor-cell
	}
	$output .= '</div>'; // .us-bld-editor-row-h
	$output .= '</div>'; // .us-bld-editor-row
}

// Outputting hidden elements
$output .= '<div class="us-bld-editor-row for_hidden">';
$output .= '<div class="us-bld-editor-row-desc">' . __( 'Hidden Elements', 'us' ) . '</div>';
$output .= '<div class="us-bld-editor-row-h">';
$output .= ushb_get_elms_placeholders( $value['default']['layout'], $value['data'], 'hidden', $config_elms, $admin_labels, $predefined_dynamic_values );
$output .= '</div>'; // .us-bld-editor-row-h
$output .= '</div>'; // .us-bld-editor-row
$output .= '</div>'; // .us-bld-editor

// Options
$output .= '<div class="us-bld-options">';
$hb_options_sections = array(
	'global' => __( 'General Header Settings', 'us' ),
	'top' => __( 'Top Area', 'us' ),
	'middle' => __( 'Main Area', 'us' ),
	'bottom' => __( 'Bottom Area', 'us' ),
);

$options_values = us_arr_path( $value, 'default.options', array() );

// Setting starting state to properly handle show_if rules
$options_values['state'] = 'default';
foreach ( $hb_options_sections as $hb_section => $hb_section_title ) {
	$output .= '<div class="us-bld-options-section' . ( ( $hb_section == 'global' ) ? ' active' : '' ) . '" data-id="' . $hb_section . '">';
	$output .= '<div class="us-bld-options-section-title">' . $hb_section_title . '</div>';
	$output .= '<div class="us-bld-options-section-content" style="display: ' . ( ( $hb_section == 'global' ) ? 'block' : 'none' ) . ';">';
	foreach ( us_config( 'header-settings.options.' . $hb_section, array() ) as $field_name => $fld ) {
		if ( ! isset( $fld['type'] ) ) {
			continue;
		}
		$field_html = us_get_template(
			'usof/templates/field', array(
				'name' => $field_name,
				'id' => 'hb_opt_' . $field_name,
				'field' => $fld,
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

// Elements and Options default values
$elms_titles = $elms_icons = $elms_defaults = $options_defaults = array();
foreach ( $config_elms as $elm_type => $elm_config ) {
	$elms_titles[ $elm_type ] = $elm_config['title'] ?? $elm_type;
	$elms_icons[ $elm_type ] = $elm_config['icon'] ?? '';
	$elms_defaults[ $elm_type ] = us_get_elm_defaults( $elm_type, 'header' );
}
foreach ( us_config( 'header-settings.options', array() ) as $group ) {
	foreach ( $group as $param_name => $param ) {
		$options_defaults[ $param_name ] = $param['std'] ?? '';
	}
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
	'menu' => us_translate( 'Menu' ),
	'additional_menu' => __( 'Simple Menu', 'us' ),
	'dropdown' => __( 'Dropdown', 'us' ),
	'social_links' => __( 'Social Links', 'us' ),
	'button' => __( 'Button', 'us' ),
	'cart' => us_translate( 'Cart', 'woocommerce' ),
);

$js_data = array(
	'admin_labels' => $admin_labels,
	'predefined_dynamic_values' => $predefined_dynamic_values,
	'elms_defaults' => $elms_defaults,
	'elms_titles' => $elms_titles,
	'elms_icons' => $elms_icons,
	'options_defaults' => $options_defaults,
	'translations' => $translations,
	'value' => $value,
	'states' => us_get_responsive_states( /* only keys */TRUE ),
	'params' => array(
		'navMenus' => us_get_nav_menus(),
	),
);

$output .= '<div class="us-bld-data hidden"'. us_pass_data_to_js( $js_data ) .'></div>';
$output .= '</div>'; // .us-bld-workspace

// List of elements that can be added
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

// Export & Import
$output .= us_get_template(
	'usof/templates/window_export_import', array(
	'title' => __( 'Header Export / Import', 'us' ),
	'text' => __( 'You can export the saved Header by copying the text inside this field. To import another Header replace the text in this field and click "Import Header" button.', 'us' ),
	'save_text' => __( 'Import Header', 'us' ),
)
);

// Empty header templates window for loading the templates afterwards
$output .= us_get_template(
	'usof/templates/window_header_templates', array(
		'body' => '',
	)
);

echo $output;
