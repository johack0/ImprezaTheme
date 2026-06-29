<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

if ( ! function_exists( 'us_ajax_cform_get_current_id' ) AND wp_doing_ajax() ) {
	/**
	 * The filter extracts an id from the passed data for AJAX requests.
	 *
	 * @param int $current_id The current object id.
	 * @return int Returns the object ID on success, otherwise `0` or `-1`.
	 */
	function us_ajax_cform_get_current_id( $current_id ) {

		// Get queried object id from request post data
		$queried_object_id = (int) us_arr_path( $_POST, 'queried_object_id' );
		if ( $queried_object_id > 0 ) {
			return $queried_object_id;
		}

		// Get post id from request post data
		$post_id = (int) us_arr_path( $_POST, 'post_id' );
		if ( $post_id > 0 ) {
			return $post_id;
		}

		return $current_id;
	}

	/**
	 * The filter determines the type based on the data from the AJAX request.
	 *
	 * @param text $meta_type The meta type.
	 * @return text Returns the current meta type.
	 */
	function us_ajax_cform_get_current_meta_type( $meta_type ) {
		if ( $queried_object_type = (string) us_arr_path( $_POST, 'queried_object_type' ) ) {
			switch ( $queried_object_type ) {
				case 'author':
					return 'user';
					break;
				case 'term':
					return 'term';
					break;
			}
		}
		return $meta_type;
	}

	if ( us_arr_path( $_POST, 'action' ) == 'us_ajax_cform' ) {
		add_filter( 'us_get_current_id', 'us_ajax_cform_get_current_id', 1, 1 );
		add_filter( 'us_get_current_meta_type', 'us_ajax_cform_get_current_meta_type', 1, 1 );
	}
}

if ( ! function_exists( 'us_cform_replace_dynamic_values' ) ) {
	/**
	 * Replace shortcodes within a message template
	 *
	 * @param string $message The message template containing dynamic values
	 * @param array $data An associative array containing the values to replace in the message
	 *
	 * @return string The message with dynamic values replaced by actual values
	 */
	function us_cform_replace_dynamic_values( $message, $data ) {
		if ( empty( $message ) OR ! is_string( $message ) ) {
			return $message;
		}

		$replacements = array(
			'[site_title]' => get_bloginfo( 'name' ),
			'[site_url]' => get_home_url(),
			'[page_title]' => $data['page_title'],
			'[page_url]' => $data['page_url'],
			'[sender_ip]' => us_get_ip(),
			'[sender_name]' => $data['sender_name'],
			'[sender_email]' => $data['sender_email'],
			'[field_list]' => '<ul>' . $data['field_list'] . '</ul>',
		);

		// Replace [field_1], [field_2], etc.
		foreach ( $data['field_values'] as $i => $val ) {
			$replacements[ sprintf( '[field_%d]', $i + 1 ) ] = $val;
		}

		$message = strtr( $message, $replacements );

		return do_shortcode( $message );
	}
}

/**
 * Ajax method for sending contact form via [us_cform] shortcode
 */
if ( ! function_exists( 'us_ajax_cform' ) ) {

	add_action( 'wp_ajax_nopriv_us_ajax_cform', 'us_ajax_cform' );
	add_action( 'wp_ajax_us_ajax_cform', 'us_ajax_cform' );

	/**
	 * The handler of the received data from the Contact Form
	 */
	function us_ajax_cform() {

		$post_id = (int) us_arr_path( $_POST, 'post_id' );
		if ( $post_id <= 0 OR ! $post = get_post( $post_id ) ) {
			wp_send_json_error();
		}

		// Get the serial number of a form on a page
		$form_index = (int) us_arr_path( $_POST, 'form_index', /* default */1 );

		// Retrieve the relevant shortcode from the page to get options
		preg_match_all( '~(\[us_cform(.*?)\])((.*?)\[/us_cform\])?~', $post->post_content, $matches );

		if ( ! isset( $matches[0][ $form_index - 1 ] ) ) {
			wp_send_json_error();
		}

		// Get the relevant shortcode options
		$shortcode = $matches[1][ $form_index - 1 ];
		$shortcode = substr_replace( $shortcode, ' ]', - 1 );
		$shortcode_atts = shortcode_parse_atts( $shortcode );

		$field_types = array_keys( us_config( 'elements/cform.params.items.params.type.options' ) );

		if ( ! empty( $shortcode_atts['items'] ) ) {
			$used_fields = json_decode( urldecode( $shortcode_atts['items'] ), /* as array */TRUE );
		} else {
			$used_fields = us_config( 'elements/cform.params.items.std' );
		}

		$used_fields = $used_fields ?: array();

		$formatted_field_list = $sender_email = $sender_name = '';
		$errors = $headers = $existing_fields = $inbound_field_labels = array();

		// Needed for [field_i] values
		$field_values = array();

		// Validate fields and compose a message
		foreach( $used_fields as $field ) {

			$field_type = $field['type'] ?? NULL;

			// Check if the field type is correct
			if ( ! in_array( $field_type, $field_types ) ) {
				continue;
			}

			// Skip info field
			if ( $field_type == 'info' ) {
				continue;
			}

			// Allow saving user IP to a post if Agreement Box is present
			if ( $field_type == 'agreement' ) {
				$save_user_ip = TRUE;
			}

			// Set Agreement Box and Captcha to be always required
			if ( $field_type == 'agreement' OR $field_type == 'captcha' ) {
				$field['required'] = 1;
			}

			// Get a unique field name
			$field_uniqid = $form_index . '_' . $field_type;
			if ( ! isset( $existing_fields[ $field_uniqid ] ) ) {
				$existing_fields[ $field_uniqid ] = 0;
			}
			$existing_fields[ $field_uniqid ] += 1;

			$field_name = 'us_form_' . $field_uniqid . '_' . $existing_fields[ $field_uniqid ];

			// Use email field value inside "FROM: email"
			if (
				$field_type === 'email'
				AND ! empty( $field['is_used_as_from_email'] )
				AND ! empty( $_POST[ $field_name ] )
				AND is_email( $_POST[ $field_name ] )
			) {
				$sender_email = sanitize_email( $_POST[ $field_name ] );
			}

			// Use text field value inside "FROM: name"
			if (
				$field_type === 'text'
				AND ! empty( $field['is_used_as_from_name'] )
				AND ! empty( $_POST[ $field_name ] )
			) {
				$sender_name .= ( $sender_name ? ' ' : '' ) . sanitize_text_field( $_POST[ $field_name ] );
			}

			// Check if fields are required
			if ( $field_name AND ! empty( $field['required'] ) ) {
				if ( $field_type === 'captcha' ) {
					$captcha_value = isset( $_POST[ $field_name ] ) ? esc_attr( $_POST[ $field_name ] ) : NULL;
					if ( ! us_cform_is_valid_captcha( $captcha_value ) ) {
						$errors[ $field_name ]['error_message'] = __( 'Enter the equation result to proceed', 'us' );
					}

					// For file fields
				} elseif ( $field_type === 'file' ) {
					if ( empty( $_FILES[ $field_name ] ) ) {
						$errors[ $field_name ]['error_message'] = __( 'Fill out this field', 'us' );
					}

				} elseif ( ! isset( $_POST[ $field_name ] ) OR $_POST[ $field_name ] === '' ) {
					$errors[ $field_name ]['error_message'] = __( 'Fill out this field', 'us' );
				}
			}

			// reCaptcha validation
			if ( $field_type === 'reCAPTCHA' AND us_get_option( 'reCAPTCHA_site_key', '' ) ) {
				$reCAPTCHA_validation_result = us_cform_is_valid_recaptcha();
				if ( ! $reCAPTCHA_validation_result ) {
					$errors[ $field_type ]['error_message'] = __( 'reCAPTCHA keys are incorrect', 'us' );
				} elseif ( $reCAPTCHA_validation_result === 'scoring_failed' ) {
					$errors[ $field_type ]['error_message'] = __( 'reCAPTCHA validation failed', 'us' );
				}
			}

			// Validation of file field
			if (
				$field_type === 'file'
				AND isset( $_FILES[ $field_name ] )
				AND us_arr_path( $_FILES, $field_name . '.error' ) === 0
			) {
				// File extension validation
				if ( ! us_cform_is_allowed_extensions( $_FILES[ $field_name ], $field['accept'] ) ) {
					$file_extension = us_strtolower( pathinfo( $_FILES[ $field_name ]['name'], PATHINFO_EXTENSION ) );
					$errors[ $field_name ]['error_message'] = sprintf( __( '%s file type is not allowed', 'us' ), $file_extension );
				}

				// If the size is not set, set the default
				if ( ! $file_max_size = (int) us_arr_path( $field, 'file_max_size' ) ) {
					$file_max_size = 10;
				}

				/**
				 * Get the size of the uploaded file in megabytes
				 * @var int
				 */
				$current_file_size = ceil( (int) $_FILES[ $field_name ]['size'] / 1048576 /* kb = 1mb */ );
				if ( $current_file_size > $file_max_size ) {
					$errors[ $field_name ]['error_message'] = sprintf( __( 'File size cannot exceed %s MB', 'us' ), $file_max_size );
				}
			}

			// Skip fields, which shouldn't have a text content
			if ( in_array( $field_type, array( 'captcha', 'file' ) ) ) {
				continue;
			}

			// Generate a message content
			if ( $field_type == 'agreement' AND ! empty( $field['value'] ) ) {
				$agreement_content = '<p>' . __( 'The sender has given his consent.', 'us' ) . '<br>';
				$agreement_content .= __( 'Agreement text', 'us' ) . ': <strong>' . strip_tags( $field['value'], '<a>' ) . '</strong><br>';
				$agreement_content .= __( 'Agreement date and time', 'us' ) . ': <strong>' . gmdate( 'Y-m-d H:i:s' ) . ' GMT</strong><br>';
				$agreement_content .= __( 'IP address', 'us' ) . ': <strong>' . us_get_ip() . '</strong></p>';

			} else {
				$field_value = '';

				// Collect field labels separately for inbound messages
				if ( us_get_option( 'cform_inbound_messages' ) ) {
					if ( $label = us_arr_path( $field, 'label' ) ) {
						$inbound_field_labels[] = sanitize_text_field( $label );

					} elseif ( $placeholder = us_arr_path( $field, 'placeholder' ) ) {
						$inbound_field_labels[] = sanitize_text_field( $placeholder );

					} else {
						$inbound_field_labels[] = __( 'Field', 'us' ) . ' ' . $field_type;
					}
				}

				$field_sender_data = $_POST[ $field_name ] ?? '';

				if ( is_array( $field_sender_data ) ) {
					$counter = 0;
					$field_values[] = wp_strip_all_tags( implode( ', ', array_map( 'stripslashes', $field_sender_data ) ) );
					foreach ( $field_sender_data as $value ) {
						$field_value .= '<strong>' . wp_strip_all_tags( stripslashes( $value ) ) . '</strong>';
						$counter ++;
						if ( $counter < count( $field_sender_data ) ) {
							$field_value .= ', ';
						}
					}

				} elseif ( ! empty( $field_sender_data ) ) {
					$field_sender_data = wp_strip_all_tags( stripslashes( $field_sender_data ) );
					$field_values[] = $field_sender_data;

					// Replace line breaks with <br> for correct appearance in HTML
					$field_value .= '<strong>' . nl2br( $field_sender_data, FALSE ) . '</strong>';

				} else {

					// Skip empty value
					if ( $field_type == 'radio' AND empty( $field['pre_select_first_value'] ) ) {
						continue;
					}

					$field_value .= '-';
					$field_values[] = '';
				}

				if ( $label = us_arr_path( $field, 'label' ) ) {
					$text_before_value = sanitize_text_field( $label ) . ': ';

				} elseif ( $placeholder = us_arr_path( $field, 'placeholder' ) ) {
					$text_before_value = sanitize_text_field( $placeholder ) . ': ';

				} else {
					$text_before_value = '';
				}

				$formatted_field_list .= '<li>' . $text_before_value . $field_value . '</li>';
			}
		}

		if ( ! empty( $errors ) ) {
			if ( us_amp() ) {
				$message = sprintf( us_translate( 'Required fields are marked %s' ), '*' );
				wp_send_json( compact( 'message' ), 400 );
			} else {
				wp_send_json_error( $errors );
			}
		}

		// Get the title and link from the page where the form is located
		$page_title = $post->post_title;
		$page_url = get_the_permalink( $post_id );

		if (
			$queried_object_id = (int) us_arr_path( $_POST, 'queried_object_id' )
			AND $queried_object_type = us_arr_path( $_POST, 'queried_object_type' )
		) {
			if ( $queried_object_type == 'term' AND $term = get_term( $queried_object_id ) ) {
				$page_title = $term->name;
				$page_url = get_term_link( $term->term_id );
			} elseif ( $queried_object_type == 'author' AND $user = get_userdata( $queried_object_id ) ) {
				$page_title = $user->display_name;
				$page_url = get_author_posts_url( $user->ID );
			} elseif ( $queried_object_type == 'post_type' AND $post_type = get_post_type_object( $queried_object_id ) ) {
				$page_title = $post_type->label;
				$page_url = get_post_type_archive_link( $post_type->name );
			} elseif ( $post = get_post( $queried_object_id ) ) {
				$page_title = $post->post_title;
				$page_url = get_the_permalink( $post->ID );
			}
		}

		// Generate the mail content
		$email_message = ! empty( $shortcode_atts['email_message'] )
			? $shortcode_atts['email_message']
			: us_config( 'elements/cform.params.email_message.std' );

		if ( $email_message = base64_decode( $email_message, TRUE ) ) {
			$email_message = rawurldecode( $email_message );
		}

		$email_message = us_replace_dynamic_value( $email_message );

		$mail_body = us_cform_replace_dynamic_values( $email_message, array(
			'page_title' => $page_title,
			'page_url' => $page_url,
			'sender_name' => $sender_name,
			'sender_email' => $sender_email,
			'field_list' => $formatted_field_list,
			'field_values' => $field_values,
		) );

		$mail_body .= $agreement_content ?? '';

		if ( is_rtl() ) {
			$mail_body = '<div style="direction: rtl; unicode-bidi: embed;">' . $mail_body . '</div>';
		}

		// Get subject from the Contact Form settings
		$mail_subject = ! empty( $shortcode_atts['email_subject'] )
			? $shortcode_atts['email_subject']
			: us_config( 'elements/cform.params.email_subject.std' );

		// Shortcode attributes can't contain '[' and ']', so replace them back
		$mail_subject = str_replace( '`{`', '[', $mail_subject );
		$mail_subject = str_replace( '`}`', ']', $mail_subject );

		$mail_subject = us_replace_dynamic_value( $mail_subject );

		$mail_subject = us_cform_replace_dynamic_values( $mail_subject, array(
			'page_title' => $page_title,
			'page_url' => $page_url,
			'sender_name' => $sender_name,
			'sender_email' => $sender_email,
			'field_list' => $formatted_field_list,
			'field_values' => $field_values,
		) );

		// Decode special characters
		$mail_subject = htmlspecialchars_decode( $mail_subject, ENT_HTML5 | ENT_QUOTES );

		// Get email recipient
		$mail_to = get_option( 'admin_email' );
		if ( ! empty( $shortcode_atts['receiver_email'] ) ) {
			$mail_to = array_map( 'sanitize_email', explode( ',', us_replace_dynamic_value( $shortcode_atts['receiver_email'] ) ) );
		}

		// Change the name of "From" value
		add_filter( 'wp_mail_from_name', 'us_cfrom_mail_from_name' );

		// BCC email
		if (
			! empty( $shortcode_atts['bcc_email'] )
			AND $bcc_emails = array_map( 'sanitize_email', explode( ',', $shortcode_atts['bcc_email'] ) )
		) {
			$headers[] = 'bcc: ' . implode( ',', $bcc_emails );
		}

		// Reply-To email
		if ( ! empty( $shortcode_atts['reply_to'] ) ) {
			$headers[] = 'Reply-To: ' . sanitize_email( $shortcode_atts['reply_to'] );

		} elseif ( $sender_email ) {
			$headers[] = 'Reply-To: ' . $sender_email;
		}

		// Change content type of email to support HTML tags
		$headers[] = 'content-type: text/html';

		// List of attached files
		$mail_attachments = array();
		if ( ! empty( $_FILES ) ) {
			foreach( $_FILES as &$attachment ) {
				/*
				 * @see https://developer.wordpress.org/reference/functions/wp_handle_upload/#top
				 */
				$uploaded_attachment = wp_handle_upload( $attachment, array( 'test_form' => FALSE ) );
				if (
					isset( $uploaded_attachment['file'] )
					AND file_exists( $uploaded_attachment['file'] )
				) {
					$mail_attachments[] = (string) $uploaded_attachment['file'];
				}
			}
		}
		unset( $attachment );

		// For the use of phpmailer data in inbound messages
		if ( us_get_option( 'cform_inbound_messages' ) ) {
			add_action( 'phpmailer_init', function( $phpmailer ) {

				global $us_cform_inbound_from_name, $us_cform_inbound_from_email;

				$us_cform_inbound_from_name = $phpmailer->FromName ?? '';
				$us_cform_inbound_from_email = $phpmailer->From ?? '';
			} );
		}

		// Send attempt
		$success = wp_mail( $mail_to, $mail_subject, $mail_body, $headers, $mail_attachments );

		// Delete attachments from the server
		foreach( $mail_attachments as $attachment ) {
			if ( file_exists( $attachment ) ) {
				wp_delete_file( $attachment );
			}
		}

		if ( $success ) {

			// Save message into post type
			if ( us_get_option( 'cform_inbound_messages' ) ) {

				global $us_cform_inbound_from_name, $us_cform_inbound_from_email;

				$sender_user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
					? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
					: '';

				$meta_input = array(
					'_cform_inbound_from_name' => $us_cform_inbound_from_name,
					'_cform_inbound_from_email' => $us_cform_inbound_from_email,
					'_cform_inbound_page_title' => $page_title,
					'_cform_inbound_page_url' => $page_url,
					'_cform_inbound_sender_user_agent' => $sender_user_agent,
					'_cform_inbound_sender_ip' => ! empty( $save_user_ip ) ? us_get_ip() : '-',
					'_cform_inbound_field_values' => $field_values,
					'_cform_inbound_field_labels' => $inbound_field_labels,
				);
				$post_data = array(
					'post_type' => 'us_cform_inbound',
					'post_title' => $mail_subject,
					'post_content' => $mail_body,
					'post_status' => 'publish',
					'post_author' => 1,
					'meta_input' => wp_slash( $meta_input ),
				);
				wp_insert_post( $post_data );
			}

			// Auto respond to a sender email
			if ( ! empty( $shortcode_atts['auto_respond'] ) AND $sender_email ) {

				$auto_respond_message = ! empty( $shortcode_atts['auto_respond_message'] )
					? $shortcode_atts['auto_respond_message']
					: us_config( 'elements/cform.params.auto_respond_message.std' );

				if ( $auto_respond_message = base64_decode( $auto_respond_message, TRUE ) ) {
					$auto_respond_message = rawurldecode( $auto_respond_message );
				}

				$auto_respond_message = us_replace_dynamic_value( $auto_respond_message );

				$auto_respond_message = us_cform_replace_dynamic_values( $auto_respond_message, array(
					'page_title' => $page_title,
					'page_url' => $page_url,
					'sender_name' => $sender_name,
					'sender_email' => $sender_email,
					'field_list' => $formatted_field_list,
					'field_values' => $field_values,
				) );

				$auto_respond_subject = ! empty( $shortcode_atts['auto_respond_subject'] )
					? $shortcode_atts['auto_respond_subject']
					: us_config( 'elements/cform.params.auto_respond_subject.std' );

				// Shortcode attributes can't contain '[' and ']', so replace them back
				$auto_respond_subject = str_replace( '`{`', '[', $auto_respond_subject );
				$auto_respond_subject = str_replace( '`}`', ']', $auto_respond_subject );

				$auto_respond_subject = us_replace_dynamic_value( $auto_respond_subject );

				$auto_respond_subject = us_cform_replace_dynamic_values( $auto_respond_subject, array(
					'page_title' => $page_title,
					'page_url' => $page_url,
					'sender_name' => $sender_name,
					'sender_email' => $sender_email,
					'field_list' => $formatted_field_list,
					'field_values' => $field_values,
				) );

				$auto_respond_subject = htmlspecialchars_decode( $auto_respond_subject, ENT_HTML5 | ENT_QUOTES );

				add_filter( 'wp_mail_content_type', 'us_cform_content_type' );
				function us_cform_content_type() {
					return 'text/html';
				}

				wp_mail( $sender_email, $auto_respond_subject, $auto_respond_message );

				remove_filter( 'wp_mail_content_type', 'us_cform_content_type' );
			}

			remove_filter( 'wp_mail_from_name', 'us_cfrom_mail_from_name' );

			if ( ! isset( $shortcode_atts['action_after_sending'] ) ) {

				$success_message = ! empty( $shortcode_atts['success_message'] )
					? $shortcode_atts['success_message']
					: us_config( 'elements/cform.params.success_message.std' );

				// If the message has base64 format, decode it
				if ( $success_message = base64_decode( $success_message, TRUE ) ) {
					$success_message = rawurldecode( $success_message );
				}

				$success_message = us_replace_dynamic_value( $success_message );

				$success_message = us_cform_replace_dynamic_values( $success_message, array(
					'page_title' => $page_title,
					'page_url' => $page_url,
					'sender_name' => $sender_name,
					'sender_email' => $sender_email,
					'field_list' => $formatted_field_list,
					'field_values' => $field_values,
				) );

				if ( us_amp() ) {
					wp_send_json( array( 'message' => $success_message ), 200 );
				} else {
					wp_send_json_success( array( 'message' => $success_message ) );
				}

			} elseif ( $shortcode_atts['action_after_sending'] == 'redirect' ) {
				if ( ! empty( $shortcode_atts['redirect_url'] ) ) {
					$redirect_url = esc_url( us_replace_dynamic_value( $shortcode_atts['redirect_url'] ) );
					wp_send_json_success( array( 'redirect_url' => $redirect_url ) );
				}

			} elseif ( $shortcode_atts['action_after_sending'] == 'open_popup' ) {
				if ( ! empty( $shortcode_atts['popup_selector'] ) ) {
					$popup_selector = us_replace_dynamic_value( $shortcode_atts['popup_selector'] );
				} else {
					$popup_selector = '';
				}
				wp_send_json_success( array( 'popup_selector' => $popup_selector ) );

			} else {
				wp_send_json_success( array() );
			}

		} else {
			$message = __( 'Cannot send the message. Please contact the website administrator.', 'us' );
			if ( us_amp() ) {
				wp_send_json( compact( 'message' ), 400 );
			} else {
				wp_send_json_error( $message );
			}
		}
	}
}

if ( ! function_exists( 'us_cform_is_allowed_extensions' ) ) {
	/**
	 * Check file for allowed extension.
	 * Note: Extension check function is based on standard HTML5 file accept
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/accept
	 *
	 * @param array $file The file information
	 * @param string $accepts The allowed file types
	 * @return bool Returns True on success, False otherwise.
	 */
	function us_cform_is_allowed_extensions( $file, $accepts = '' ) {
		if ( empty( $accepts ) ) {
			return TRUE;
		}
		if ( empty( $file ) OR us_arr_path( $file, 'error' ) !== 0 ) {
			return FALSE;
		}

		// Get allowed extensions or mime types
		$accepts = array_map( 'trim', explode( ',', us_strtolower( $accepts ) ) );

		// Get file extension from name.
		$file['extension'] = '.' . us_strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		// Determination if file extension is available in the accepts
		if ( in_array( $file['extension'], $accepts ) ) {
			return TRUE;
		}

		foreach ( $accepts as $accept ) {
			if ( empty( $accept ) ) {
				continue;
			}
			// @link https://mimesniff.spec.whatwg.org
			if ( strpos( $accept, '/' ) !== FALSE ) {
				$accept_matches = explode( '/', $accept, /* min limit */2 );
				if (
					$accept === $file['type']
					OR (
						$accept_matches[1] === '*'
						AND strpos( $file['type'], $accept_matches[0] ) === 0
					)
				) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}
}

if ( ! function_exists( 'us_cform_is_valid_captcha' ) ) {
	/**
	 * Captcha validation
	 *
	 * @param string $value The captcha value
	 * @return bool True if successful, false otherwise
	 */
	function us_cform_is_valid_captcha( $value = NULL ) {
		$fields = array();
		foreach ( $_POST as $key => $field ) {
			if ( preg_match( '~^us_form_\d_([^_]+_)\d_(\w+)$~', $key, $matches ) ) {
				$fields[ $matches[1] . $matches[2] ] = $field;
			} elseif ( preg_match( '~^us_form_\d_([^_]+)_\d$~', $key, $matches ) ) {
				$fields[ $matches[1] ] = $field;
			}
		}
		if ( $hash = us_arr_path( $fields, 'captcha_hash', /* Default */NULL ) ) {
			$hash = stripslashes( $hash );
		}
		return $hash === md5( $value . NONCE_SALT );
	}
}

if ( ! function_exists( 'us_cform_is_valid_recaptcha' ) ) {
	/**
	 * reCAPTCHA validation
	 *
	 * @return bool|string TRUE if successful, FALSE otherwise, or error message
	 */
	function us_cform_is_valid_recaptcha() {
		$recaptcha_token = us_arr_path( $_POST, 'g-recaptcha-response' ) ? sanitize_text_field( us_arr_path( $_POST, 'g-recaptcha-response' ) ) : '';

		if ( empty( $recaptcha_token ) ) {
			return FALSE;
		}

		$recaptcha_secret = us_get_option( 'reCAPTCHA_secret_key', '' );

		if ( empty( $recaptcha_secret ) ) {
			return FALSE;
		}

		// Verify request - https://developers.google.com/recaptcha/docs/verify
		$verify_request = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'body' => array(
				'secret' => $recaptcha_secret,
				'response' => $recaptcha_token,
			),
		) );

		if ( is_wp_error( $verify_request ) ) {
			return FALSE;
		}

		$recaptcha_result = json_decode( wp_remote_retrieve_body( $verify_request ), TRUE );

		if ( ! $recaptcha_result['success'] ) {
			return FALSE;
		}

		// 0.5 is the default threshold
		if ( $recaptcha_result['score'] < 0.5 ) {
			return 'scoring_failed';
		}

		return TRUE;
	}
}

/**
 * Use the website name instead of the "WordPress" word in the "From:"
 */
if ( ! function_exists( 'us_cfrom_mail_from_name' ) ) {
	function us_cfrom_mail_from_name() {
		return wp_specialchars_decode( get_bloginfo(), ENT_QUOTES );
	}
}
