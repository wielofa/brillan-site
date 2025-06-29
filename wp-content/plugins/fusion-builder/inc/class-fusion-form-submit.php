<?php
/**
 * Handle Form Submit.
 *
 * @since 3.1
 * @package fusion-builder
 */

/**
 * Handle Form Submit.
 *
 * @since 3.1
 */
class Fusion_Form_Submit {

	/**
	 * The reCAPTCHA class instance
	 *
	 * @access public
	 * @var bool|object
	 */
	public $re_captcha = false;

	/**
	 * Whats the error?
	 *
	 * @access public
	 * @var string
	 */
	public $captcha_error = '';

	/**
	 * ReCapatcha error flag.
	 *
	 * @access public
	 * @var bool
	 */
	public $has_error = false;

	/**
	 * Array of uploaded files, used to add email attachments.
	 *
	 * @access protected
	 * @var array
	 */
	protected $uploads = [];

	/**
	 * Email errors.
	 *
	 * @access public
	 * @var string
	 */
	public $mail_error = false;

	/**
	 * Database Submission ID.
	 *
	 * @access public
	 * @var string
	 */
	protected $db_submission_id = null;

	/**
	 * Form admin capability, used to decide who can see the debug info.
	 *
	 * @access public
	 * @var string
	 */
	protected $admin_cap = 'edit_others_posts';

	/**
	 * Initializes hooks, filters and administrative functions.
	 *
	 * @since 3.1
	 * @access public
	 */
	public function __construct() {

		add_action( 'wp_ajax_fusion_form_submit_ajax', [ $this, 'ajax_form_submit' ] );
		add_action( 'wp_ajax_nopriv_fusion_form_submit_ajax', [ $this, 'ajax_form_submit' ] );
	}

	/**
	 * Form submission will be stored in the database.
	 *
	 * @access protected
	 * @since 3.1.1
	 * @param array $data Form data array.
	 * @return void
	 */
	protected function submit_form_to_database( $data ) {

		if ( ! $data ) {
			$data = $this->get_submit_data();
		}

		// Email errors.
		if ( $this->mail_error ) {
			$data['submission']['data']['email_errors'] = wp_json_encode( $this->mail_error );
		}

		$data['submission']['data'] = isset( $data['submission']['data'] ) && is_array( $data['submission']['data'] ) ? wp_json_encode( $data['submission']['data'] ) : null;

		$fusion_forms  = new Fusion_Form_DB_Forms();
		$submission    = new Fusion_Form_DB_Submissions();
		$submission_id = $submission->insert( $data['submission'] );

		foreach ( $data['data'] as $field => $value ) {
			$field_data  = ( is_array( $value ) ) ? implode( ' | ', $value ) : $value;
			$field_label = isset( $data['field_labels'][ $field ] ) ? $data['field_labels'][ $field ] : '';
			$db_field_id = $fusion_forms->insert_form_field( $data['submission']['form_id'], $field, $field_label );
			$entries     = new Fusion_Form_DB_Entries();

			$entries->insert(
				[
					'form_id'       => absint( $data['submission']['form_id'] ),
					'submission_id' => absint( $submission_id ),
					'field_id'      => sanitize_key( $db_field_id ),
					'value'         => $field_data,
					'privacy'       => in_array( $field, $data['fields_holding_privacy_data'], true ),
				]
			);
			$this->db_submission_id = absint( $submission_id );
		}
	}

	/**
	 * Form submission will be sent to email.
	 *
	 * @access protected
	 * @since 3.1.1
	 * @param array $data Form data array.
	 * @return bool
	 */
	protected function submit_form_to_email( $data ) {

		if ( ! $data ) {
			$data = $this->get_submit_data();
		}

		$form_post_id   = isset( $_POST['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_meta      = Fusion_Builder_Form_Helper::fusion_form_get_form_meta( $form_post_id );
		$to             = ! empty( $form_meta['email'] ) ? $form_meta['email'] : get_option( 'admin_email' );
		$reply_to_email = ! empty( $form_meta['email_reply_to'] ) ? $form_meta['email_reply_to'] : '';
		$from_name      = ! empty( $form_meta['email_from'] ) ? $form_meta['email_from'] : 'WordPress';
		$default_email  = 'wordpress@' . preg_replace( '#^www\.#', '', wp_parse_url( network_home_url(), PHP_URL_HOST ) );
		$from_id        = ! empty( $form_meta['email_from_id'] ) ? $form_meta['email_from_id'] : $default_email;
		$subject        = ! empty( $form_meta['email_subject'] ) ? $form_meta['email_subject'] : sprintf(
			/* Translators: The form-ID. */
			esc_html__( '%s - Form Submission Notification', 'fusion-builder' ),
			get_the_title( $form_post_id )
		);

		$subject_encode     = ! empty( $form_meta['email_subject_encode'] ) ? $form_meta['email_subject_encode'] : 0;
		$attachments        = [];
		$use_attachments    = 'yes' === fusion_data()->post_meta( $form_post_id )->get( 'email_attachments' ) ? true : false;
		$hidden_field_names = isset( $data['hidden_field_names'] ) && is_array( $data['hidden_field_names'] ) ? $data['hidden_field_names'] : [];

		// We don't want internal email fields to sent in email.
		$data = $this->remove_internal_email_fields( $data );

		$email_data = '';
		foreach ( $data['data'] as $field => $value ) {

			// Don't add fields which are hidden by form logic.
			if ( in_array( $field, $hidden_field_names, true ) ) {
				continue;
			}

			// Don't add attachments to email body.
			if ( true === $use_attachments && isset( $this->uploads[ $field ] ) ) {
				continue;
			}

			$value       = is_array( $value ) ? implode( ' | ', $value ) : $value;
			$field_label = isset( $data['field_labels'][ $field ] ) && '' !== $data['field_labels'][ $field ] ? $data['field_labels'][ $field ] : Fusion_Builder_Form_Helper::fusion_name_to_label( $field );

			$email_data .= '<tr>';
			$email_data .= '<th align="left">' . htmlentities( $field_label, ENT_COMPAT, 'UTF-8' ) . '</th>';

			if ( isset( $data['field_types'][ $field ] ) && 'textarea' === $data['field_types'][ $field ] ) {
				$email_data .= '<td>' . nl2br( htmlentities( $value, ENT_COMPAT, 'UTF-8' ) ) . '</td>';
			} else {
				$email_data .= '<td>' . htmlentities( $value, ENT_COMPAT, 'UTF-8' ) . '</td>';
			}
			$email_data .= '</tr>';

			// Replace placholders.
			if ( '' !== $to && false !== strpos( $to, '[' . $field . ']' ) ) {
				$to = str_replace( '[' . $field . ']', $value, $to );
			}

			if ( '' !== $reply_to_email && false !== strpos( $reply_to_email, '[' . $field . ']' ) ) {
				$reply_to_email = str_replace( '[' . $field . ']', $value, $reply_to_email );
			}

			if ( false !== strpos( $from_name, '[' . $field . ']' ) ) {
				$from_name = str_replace( '[' . $field . ']', $value, $from_name );
			}

			if ( false !== strpos( $from_id, '[' . $field . ']' ) ) {
				$from_id = str_replace( '[' . $field . ']', $value, $from_id );
			}

			if ( false !== strpos( $subject, '[' . $field . ']' ) ) {
				$subject = str_replace( '[' . $field . ']', $value, $subject );
			}
		}

		$title   = htmlentities( $subject );
		$message = "<html><head><title>$title</title></head><body><table cellspacing='4' cellpadding='4' align='left'>$email_data</table></body></html>";

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$headers .= 'From: ' . $from_name . ' <' . $from_id . '>' . "\r\n";

		if ( '' !== $reply_to_email ) {
			$headers .= 'Reply-to: ' . $reply_to_email . "\r\n";
		}

		if ( $subject_encode ) {
			$subject = '=?utf-8?B?' . base64_encode( $subject ) . '?=';
		}

		// Add attachments if there are uploaded files.
		if ( true === $use_attachments && ! empty( $this->uploads ) ) {
			foreach ( $this->uploads as $field_name ) {
				foreach ( $field_name as $upload ) {
					$attachments[] = $upload['file'];
				}
			}
		}

		$sendmail_args = apply_filters(
			'fusion_form_send_mail_args',
			[
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => $attachments,
			],
			$data['submission']['form_id'],
			$data
		);

		add_action( 'wp_mail_failed', [ $this, 'mail_failed_error' ] );

		$sendmail = wp_mail(
			$sendmail_args['to'],
			$sendmail_args['subject'],
			$sendmail_args['message'],
			$sendmail_args['headers'],
			$sendmail_args['attachments']
		);

		remove_action( 'wp_mail_failed', [ $this, 'mail_failed_error' ] );

		return $sendmail;
	}

	/**
	 * URL Action.
	 *
	 * @access public
	 * @since 3.7
	 * @param array $data Form data array.
	 * @param array $args Ajax data.
	 * @return array
	 */
	public function form_to_url_action( $data, $args ) {

		// Get the form-ID.
		$form_id = 0;
		if ( isset( $args['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$form_id = absint( str_replace( 'fusion-form-', '', sanitize_text_field( wp_unslash( $args['form_id'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( $form_id && 'fusion_form' === get_post_type( $form_id ) ) {

			// GET URL and method from post meta.
			$request_url    = fusion_data()->post_meta( $form_id )->get( 'action' );
			$request_method = fusion_data()->post_meta( $form_id )->get( 'url_method' );

			// Error if no URL was found.
			if ( ! $request_url ) {
				return $this->get_results_from_message( 'error', 'no_url' );
			}

			$request_args = [
				'method' => 'POST',
			];

			// Get the form method.
			if ( '' !== $request_method ) {
				$request_args['method'] = strtoupper( $request_method );

				// Fallback in case we don't have a valid value.
				if ( ! in_array( $request_args['method'], [ 'POST', 'GET', 'HEAD', 'PUT', 'DELETE' ], true ) ) {
					$request_args['method'] = 'POST';
				}
			}

			// Add the submission arguments to our request.
			$request_args['body']            = wp_parse_args( $data['data'], $data['submission'] );
			$request_args['body']['form_id'] = $form_id;

			// Add custom headers if defined.
			$custom_headers = fusion_data()->post_meta( $form_id )->get( 'custom_headers' );
			if ( ! empty( $custom_headers ) && is_string( $custom_headers ) ) {
				$custom_headers = json_decode( $custom_headers, true );
			}

			if ( ! empty( $custom_headers ) ) {
				$request_args['headers'] = [];
				foreach ( $custom_headers as $header ) {
					$request_args['headers'][ $header['header_key'] ] = $header['header_value'];
				}
			}

			// Make the request.
			$response = wp_safe_remote_request( $request_url, $request_args );

			if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {
				$data['response_body'] = ( is_string( $response['body'] ) ) ? $response['body'] : wp_json_encode( $response['body'] );
				$type                  = $this->get_response_type_string( $response );

				return $this->get_results_from_message( $type, $data['response_body'] );
			}
		}
		return $this->get_results_from_message( 'error', __( 'URL Failed', 'fusion-builder' ) );
	}

	/**
	 * Ajax callback for form submission.
	 *
	 * @access public
	 * @since 3.7
	 * @return void
	 */
	public function ajax_form_submit() {

		$form_post_id = isset( $_POST['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$responses    = [];
		$success      = [];
		$errors       = [];

		// Checks nonce, recaptcha and similar. Dies if checks fail.
		$this->pre_process_form_submit();

		$data = $this->get_submit_data();

		$actions       = $this->handle_form_actions( $data, $form_post_id, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
		$notifications = $this->handle_form_notifications( $data, $form_post_id );

		$responses = array_merge( $responses, $notifications, $actions );

		if ( ! empty( $responses ) ) {
			foreach ( $responses as $key => $res ) {

				if ( isset( $res['status'] ) && 'error' === $res['status'] ) {
					$errors[ $key ] = isset( $res['info'] ) ? $res['info'] : 'form_failed';
				}

				if ( isset( $res['status'] ) && 'success' === $res['status'] ) {
					$success[ $key ] = isset( $res['info'] ) ? $res['info'] : 'form_submitted';
				}
			}
		}

		// Handle errors.
		if ( ! empty( $errors ) ) {

			// If current user can edit forms display the detailed errors.
			if ( is_user_logged_in() && current_user_can( $this->admin_cap ) ) {
				$message = $this->get_results_from_message( 'error', $errors );
			} else {
				$message = $this->get_results_from_message( 'error', 'form_failed' );
			}
			die( wp_json_encode( apply_filters( 'awb_form_return_message', $message, $form_post_id, $errors ) ) );
		}

		// If form submitted successfully increase the form entries.
		$fusion_forms = new Fusion_Form_DB_Forms();
		$fusion_forms->increment_submissions_count( $data['submission']['form_id'] );

		// If current user can edit forms he can see the details in inspector network tab.
		if ( is_user_logged_in() && current_user_can( $this->admin_cap ) ) {
			$message = $this->get_results_from_message( 'success', $success );
		} else {
			$message = $this->get_results_from_message( 'success', 'form_submitted' );
		}
		die( wp_json_encode( apply_filters( 'awb_form_return_message', $message, $form_post_id, $success ) ) );
	}

	/**
	 * Form notifications.
	 *
	 * @access protected
	 * @since 3.7
	 * @param array $data Form data array.
	 * @param array $id Form Post ID.
	 * @return bool
	 */
	protected function handle_form_notifications( $data, $id ) {

		if ( ! $data ) {
			$data = $this->get_submit_data();
		}

		$output        = [];
		$notifications = fusion_data()->post_meta( $id )->get( 'notifications' );
		if ( $notifications && is_string( $notifications ) && 5 < strlen( $notifications ) ) {
			$notifications = json_decode( $notifications, true );
		}

		if ( is_array( $notifications ) ) {
			$i = 1;
			foreach ( $notifications as $notification ) {
				$to             = ! empty( $notification['email'] ) ? $notification['email'] : get_option( 'admin_email' );
				$reply_to_email = ! empty( $notification['email_reply_to'] ) ? $notification['email_reply_to'] : '';
				$from_name      = ! empty( $notification['email_from'] ) ? $notification['email_from'] : 'WordPress';
				$default_email  = 'wordpress@' . preg_replace( '#^www\.#', '', wp_parse_url( network_home_url(), PHP_URL_HOST ) );
				$from_id        = ! empty( $notification['email_from_id'] ) ? $notification['email_from_id'] : $default_email;
				$subject        = ! empty( $notification['email_subject'] ) ? $notification['email_subject'] : sprintf(
					/* Translators: The form-ID. */
					esc_html__( '%s - Form Submission Notification', 'fusion-builder' ),
					get_the_title( $id )
				);

				$subject_encode  = ! empty( $notification['email_subject_encode'] ) ? $notification['email_subject_encode'] : 0;
				$attachments     = [];
				$use_attachments = ! empty( $notification['email_attachments'] ) && 'yes' === $notification['email_attachments'] ? true : false;
				$email_message   = ! empty( $notification['email_message'] ) ? $this->custom_email_message( $notification['email_message'], $subject, $data ) : $this->default_email_message( $subject, $data, $use_attachments );

				$hidden_field_names = isset( $data['hidden_field_names'] ) && is_array( $data['hidden_field_names'] ) ? $data['hidden_field_names'] : [];

				// We don't want internal email fields to sent in email.
				$data = $this->remove_internal_email_fields( $data );

				foreach ( $data['data'] as $field => $value ) {

					// Don't add fields which are hidden by form logic.
					if ( in_array( $field, $hidden_field_names, true ) ) {
						continue;
					}

					// Don't add attachments to email body.
					if ( true === $use_attachments && isset( $this->uploads[ $field ] ) ) {
						continue;
					}

					$value       = is_array( $value ) ? implode( ' | ', $value ) : $value;
					$field_label = isset( $data['field_labels'][ $field ] ) && '' !== $data['field_labels'][ $field ] ? $data['field_labels'][ $field ] : Fusion_Builder_Form_Helper::fusion_name_to_label( $field );

					// Replace placholders.
					if ( '' !== $to && false !== strpos( $to, '[' . $field . ']' ) ) {
						$to = str_replace( '[' . $field . ']', $value, $to );
					}

					if ( '' !== $reply_to_email && false !== strpos( $reply_to_email, '[' . $field . ']' ) ) {
						$reply_to_email = str_replace( '[' . $field . ']', $value, $reply_to_email );
					}

					if ( false !== strpos( $from_name, '[' . $field . ']' ) ) {
						$from_name = str_replace( '[' . $field . ']', $value, $from_name );
					}

					if ( false !== strpos( $from_id, '[' . $field . ']' ) ) {
						$from_id = str_replace( '[' . $field . ']', $value, $from_id );
					}

					if ( false !== strpos( $subject, '[' . $field . ']' ) ) {
						$subject = str_replace( '[' . $field . ']', $value, $subject );
					}
				}

				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=UTF8' . "\r\n";
				$headers .= 'From: ' . $from_name . ' <' . $from_id . '>' . "\r\n";

				if ( isset( $notification['email_cc'] ) ) {
					$additional_emails = explode( "\n", $notification['email_cc'] );
					foreach ( $additional_emails as $email_cc ) {
						preg_match( '/\[.+\]/', $email_cc, $matches );
						if ( isset( $matches[0] ) && isset( $data['data'][ str_replace( [ '[', ']' ], '', $matches[0] ) ] ) ) {
							$email_cc = $data['data'][ str_replace( [ '[', ']' ], '', $matches[0] ) ];
						}

						$headers .= 'Cc: ' . $email_cc . "\r\n";
					}
				}

				if ( isset( $notification['email_bcc'] ) ) {
					$additional_emails = explode( "\n", $notification['email_bcc'] );
					foreach ( $additional_emails as $email_bcc ) {
						preg_match( '/\[.+\]/', $email_bcc, $matches );
						if ( isset( $matches[0] ) && isset( $data['data'][ str_replace( [ '[', ']' ], '', $matches[0] ) ] ) ) {
							$email_bcc = $data['data'][ str_replace( [ '[', ']' ], '', $matches[0] ) ];
						}

						$headers .= 'Bcc: ' . $email_bcc . "\r\n";
					}
				}

				if ( '' !== $reply_to_email ) {
					$headers .= 'Reply-to: ' . $reply_to_email . "\r\n";
				}

				if ( $subject_encode ) {
					$subject = '=?utf-8?B?' . base64_encode( $subject ) . '?=';
				}

				// Add attachments if there are uploaded files.
				if ( true === $use_attachments && ! empty( $this->uploads ) ) {
					foreach ( $this->uploads as $field_name ) {
						foreach ( $field_name as $upload ) {
							$attachments[] = $upload['file'];
						}
					}
				}

				$sendmail_args = apply_filters(
					'fusion_form_send_mail_args',
					[
						'to'          => $to,
						'subject'     => $subject,
						'message'     => $email_message,
						'headers'     => $headers,
						'attachments' => $attachments,
					],
					$data['submission']['form_id'],
					$data
				);

				add_action( 'wp_mail_failed', [ $this, 'mail_failed_error' ] );

				$sendmail = wp_mail(
					$sendmail_args['to'],
					$sendmail_args['subject'],
					$sendmail_args['message'],
					$sendmail_args['headers'],
					$sendmail_args['attachments']
				);
				if ( $sendmail ) {
					if ( is_user_logged_in() && current_user_can( $this->admin_cap ) ) {
						$output[ 'email_' . $i ] = $this->get_results_from_message(
							'success',
							sprintf(
							/* translators: %s: Email to */
								__( 'Email to (%s) has been sent.', 'fusion-builder' ),
								$to
							)
						);
					} else {
						$output[ 'email_' . $i ] = $this->get_results_from_message( 'success', __( 'Email has been sent.', 'fusion-builder' ) );
					}
				} else {
					if ( is_user_logged_in() && current_user_can( $this->admin_cap ) && $this->mail_error ) {
						$output[ 'email_' . $i ] = $this->get_results_from_message( 'error', $this->mail_error );
					} else {
						$output[ 'email_' . $i ] = $this->get_results_from_message( 'error', __( 'Email Failed', 'fusion-builder' ) );
					}
				}
				remove_action( 'wp_mail_failed', [ $this, 'mail_failed_error' ] );

				$i++;
			}
		}

		return $output;
	}

	/**
	 * Form actions.
	 *
	 * @access protected
	 * @since 3.7
	 * @param array $data Form data array.
	 * @param array $id Form Post ID.
	 * @param array $args Ajax data.
	 * @return array
	 */
	protected function handle_form_actions( $data, $id, $args ) {

		if ( ! $data ) {
			$data = $this->get_submit_data();
		}

		$form_meta = Fusion_Builder_Form_Helper::fusion_form_get_form_meta( $id );

		$actions = $form_meta['form_actions'];

		// Backward Compatibility.
		$type = fusion_data()->post_meta( $id )->get( 'form_type' );

		if ( empty( $actions ) || ! is_array( $actions ) ) {
			if ( 'database' === $type ) {
				$actions = [ 'database' ];
			} elseif ( 'email' === $type ) {
				$actions = [ 'email' ];
			} elseif ( 'database_email' === $type ) {
				$actions = [ 'database', 'email' ];
			} elseif ( 'url' === $type ) {
				$actions = [ 'url' ];
			} else {
				return [];
			}
		}
		$output = [];

		$custom_actions = (array) FusionBuilder()->get_custom_form_actions();

		foreach ( $actions as $action ) {

			// Save to database.
			if ( 'database' === $action ) {

				$this->submit_form_to_database( $data );

				if ( $data['submission']['form_id'] ) {
					$output['database'] = $this->get_results_from_message( 'success', __( 'Form data is saved to database.', 'fusion-builder' ) );
				} else {
					$output['database'] = $this->get_results_from_message( 'error', __( 'Saving form data to database failed.', 'fusion-builder' ) );
				}
			}

			// Send to url.
			if ( 'url' === $action ) {
				$output['url'] = $this->form_to_url_action( $data, $args );
			}

			// Email action for BC only.
			if ( 'email' === $action ) {
				$sendmail = $this->submit_form_to_email( $data );

				if ( $sendmail ) {
					$output['email'] = $this->get_results_from_message( 'success', __( 'Email has been sent.', 'fusion-builder' ) );
				} else {
					if ( is_user_logged_in() && current_user_can( $this->admin_cap ) && $this->mail_error ) {
						$output['email'] = $this->get_results_from_message( 'error', $this->mail_error );
					} else {
						$output['email'] = $this->get_results_from_message( 'error', __( 'Email Failed', 'fusion-builder' ) );
					}
				}
			}

			if ( isset( $custom_actions[ $action ]['callback'] ) ) {
				$output[ $action ] = call_user_func_array( $custom_actions[ $action ]['callback'], [ $data, $id, $form_meta, $args ] );
			}
		}

		return $output;
	}

	/**
	 * Form default email message.
	 *
	 * @access protected
	 * @since 3.7
	 * @param string $subject the subject.
	 * @param array  $data Form data array.
	 * @param bool   $attachments Attachments.
	 * @return bool
	 */
	protected function default_email_message( $subject, $data, $attachments = false ) {

		$email_data = $this->form_fields_table( $data, $attachments );
		$title      = htmlentities( $subject );
		$message    = "<html><head><title>$title</title></head><body><table cellspacing='4' cellpadding='4' align='left'>$email_data</table></body></html>";

		return $message;
	}

	/**
	 * Form custom email message.
	 *
	 * @access protected
	 * @since 3.7
	 * @param string $message the custom message.
	 * @param string $subject the subject.
	 * @param array  $data Form data array.
	 * @return bool
	 */
	protected function custom_email_message( $message, $subject, $data ) {

		$title = htmlentities( $subject );

		// replace placeholders.
		$message = $this->replace_placeholders( $message, $data );

		$custom_message = "<html><head><title>$title</title></head><body><table cellspacing='4' cellpadding='4' align='left'>" . wpautop( $message ) . '</table></body></html>';

		return $custom_message;
	}

	/**
	 * Form fields as table.
	 *
	 * @access protected
	 * @since 3.7
	 * @param array $data Form data array.
	 * @param bool  $attachments Attachments.
	 * @return bool
	 */
	protected function form_fields_table( $data, $attachments = false ) {
		$hidden_field_names = isset( $data['hidden_field_names'] ) && is_array( $data['hidden_field_names'] ) ? $data['hidden_field_names'] : [];

		$table = '<table>';
		foreach ( $data['data'] as $field => $value ) {

			// Don't add fields which are hidden by form logic.
			if ( in_array( $field, $hidden_field_names, true ) ) {
				continue;
			}

			// Don't add attachments to email body if attachment enabled.
			if ( isset( $this->uploads[ $field ] ) && $attachments ) {
				continue;
			}

			// Add uploaded files to the message body if attachment disabled.
			if ( isset( $this->uploads[ $field ] ) && ! $attachments ) {
				if ( strpos( $value, '|' ) !== false ) {
					$value = explode( '|', $value );
				}

				if ( is_array( $value ) ) {
					$i     = 1;
					$links = [];
					foreach ( $value as $link ) {
						$links[] = '<a href="' . esc_url( trim( $link ) ) . '" target="_blank">' . esc_html__( 'View File', 'fusion-builder' ) . ' ' . $i . '</a>';
						$i++;
					}
					$value = join( ', ', $links );
				} else {
					$value = '<a href="' . esc_url( trim( $value ) ) . '" target="_blank">' . esc_html__( 'View File', 'fusion-builder' ) . '</a>';
				}
			} else {
				$value = is_array( $value ) ? implode( "\r\n", $value ) : $value;

				$value = htmlentities( $value, ENT_COMPAT, 'UTF-8' );
			}
			$field_label = isset( $data['field_labels'][ $field ] ) && '' !== $data['field_labels'][ $field ] ? $data['field_labels'][ $field ] : Fusion_Builder_Form_Helper::fusion_name_to_label( $field );

			$table .= '<tr>';
			$table .= '<th align="left" style="vertical-align:top;white-space:nowrap;">' . htmlentities( $field_label, ENT_COMPAT, 'UTF-8' ) . '</th>';

			if ( isset( $data['field_types'][ $field ] ) && ( 'textarea' === $data['field_types'][ $field ] || 'select' === $data['field_types'][ $field ] ) ) {
				$table .= '<td>' . nl2br( $value ) . '</td>';
			} else {
				$table .= '<td>' . $value . '</td>';
			}
			$table .= '</tr>';

		}
		$table .= '</table>';

		return $table;
	}

	/**
	 * Replace form placeholders.
	 *
	 * @access public
	 * @since 3.7
	 * @param string $text the subject.
	 * @param array  $data Form data array.
	 * @return bool
	 */
	public function replace_placeholders( $text, $data ) {
		preg_match_all( '/\[.*?\]/', $text, $matches );

		if ( ! empty( $matches[0] ) && is_array( $matches[0] ) ) {

			$output = $text;

			foreach ( $matches[0] as $placeholder ) {
				if ( '[all_fields]' === $placeholder ) {

					$output = str_replace( $placeholder, $this->form_fields_table( $data ), $output );

				} elseif ( '[source_url]' === $placeholder ) {

					$source_url = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
					$output     = str_replace( $placeholder, $source_url, $output );

				} elseif ( '[submission_id]' === $placeholder ) {
					$submission_id = '';
					if ( $this->db_submission_id ) {
						$submission_id = $this->db_submission_id;
					}

					$output = str_replace( $placeholder, $submission_id, $output );

					$this->db_submission_id = null;
				} else {

					preg_match( '/\[(.*?)\]/', $placeholder, $tag );
					$field_name  = isset( $tag[1] ) ? $tag[1] : '';
					$field_value = $field_name && isset( $data['data'][ $field_name ] ) ? $data['data'][ $field_name ] : '';
					$field_value = is_array( $field_value ) ? implode( '|', $field_value ) : $field_value;
					$field_value = htmlentities( $field_value, ENT_COMPAT, 'UTF-8' );

					$output = str_replace( $placeholder, $field_value, $output );
				}
			}
		} else {
			$output = $text;
		}

		return $output;
	}

	/**
	 * Process nonce, recaptcha and similar checks.
	 * Dies if checks fail.
	 *
	 * @access protected
	 * @since 3.1.1
	 * @return void
	 */
	protected function pre_process_form_submit() {

		$form_post_id = isset( $_POST['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_meta    = Fusion_Builder_Form_Helper::fusion_form_get_form_meta( $form_post_id );

		// Check for form type.
		if ( isset( $form_meta['form_type'] ) && 'ajax' !== $form_meta['form_type'] ) {
			die( wp_json_encode( $this->get_results_from_message( 'error', 'not allowed' ) ) );
		}

		if ( ! isset( $form_meta['nonce_method'] ) || 'none' !== $form_meta['nonce_method'] ) {
			// Verify the form submission nonce.
			check_ajax_referer( 'fusion_form_nonce', 'fusion_form_nonce' );
		}

		// If we are in demo mode, just pretend it has sent.
		if ( apply_filters( 'fusion_form_demo_mode', false ) ) {
			die( wp_json_encode( $this->get_results_from_message( 'success', 'demo' ) ) );
		}

		// Check reCAPTCHA response and die if error.
		$this->check_recaptcha_response();
	}

	/**
	 * Proces nonce, recaptcha and similar checks.
	 * Dies if checks fail.
	 *
	 * @access protected
	 * @since 3.1.1
	 * @param array $data Form data array.
	 * @return array
	 */
	protected function remove_internal_email_fields( $data ) {

		// Remove data used for internal purpose, only for email submission type.
		if ( isset( $data['data']['fusion_form_email'] ) ) {
			unset( $data['data']['fusion_form_email'] );
		}

		if ( isset( $data['data']['fusion_form_email_from'] ) ) {
			unset( $data['data']['fusion_form_email_from'] );
		}

		if ( isset( $data['data']['fusion_form_email_from_id'] ) ) {
			unset( $data['data']['fusion_form_email_from_id'] );
		}

		if ( isset( $data['data']['fusion_form_email_subject'] ) ) {
			unset( $data['data']['fusion_form_email_subject'] );
		}

		if ( isset( $data['data']['fusion_form_email_subject_encode'] ) ) {
			unset( $data['data']['fusion_form_email_subject_encode'] );
		}

		return $data;
	}

	/**
	 * Get the submission data.
	 *
	 * @access public
	 * @since 3.1.0
	 * @return array
	 */
	public function get_submit_data() {

		$form_data          = wp_unslash( $_POST['formData'] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$files              = isset( $_FILES ) && ! empty( $_FILES ) ? $_FILES : []; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$field_labels       = (array) json_decode( stripcslashes( $_POST['field_labels'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$field_types        = (array) json_decode( stripcslashes( $_POST['field_types'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		$hidden_field_names = (array) json_decode( stripcslashes( $_POST['hidden_field_names'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

		parse_str( $form_data, $form_data_array ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput

		// Sanitize user input.
		$form_data_array = map_deep( $form_data_array, 'Fusion_Builder_Form_Helper::fusion_form_sanitize' );

		$files   = apply_filters( 'awb_form_upload_files', $files, $form_data );
		$uploads = ! empty( $files ) ? $this->handle_upload( $files ) : [];
		if ( ! empty( $uploads ) && is_array( $uploads ) ) {
			foreach ( $uploads as $upload_name => $upload_url ) {
				$upload_name                        = explode( '@|@', $upload_name );
				$form_data_array[ $upload_name[0] ] = ! empty( $form_data_array[ $upload_name[0] ] ) ? $form_data_array[ $upload_name[0] ] . ' | ' . $upload_url : $upload_url;
			}
		}

		$user_agent = '';
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
		}
		$source_url = '';
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$source_url = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
		}
		$ip = '';
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}
		$post_id      = isset( $_POST['post_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_post_id = isset( $_POST['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		$fusion_forms = new Fusion_Form_DB_Forms();
		$form_id      = $fusion_forms->insert(
			[
				'form_id' => $form_post_id, // phpcs:ignore WordPress.Security.NonceVerification
				'views'   => 0,
			]
		);

		$data = [
			'submission'         => [
				'form_id'            => absint( $form_id ),
				'time'               => gmdate( 'Y-m-d H:i:s' ),
				'source_url'         => sanitize_text_field( $source_url ),
				'post_id'            => absint( $post_id ),
				'user_id'            => absint( get_current_user_id() ),
				'user_agent'         => sanitize_text_field( $user_agent ),
				'ip'                 => sanitize_text_field( $ip ),
				'is_read'            => false,
				'privacy_scrub_date' => gmdate( 'Y-m-d' ),
				'on_privacy_scrub'   => 'anonymize',
			],
			'data'               => $form_data_array,
			'field_labels'       => $field_labels,
			'field_types'        => $field_types,
			'hidden_field_names' => $hidden_field_names,
		];

		// Allow filtering the submission data.
		$data = apply_filters( 'fusion_builder_form_submission_data', $data );

		$fields_holding_privacy_data = [];
		if ( isset( $data['data']['fusion-fields-hold-private-data'] ) ) {
			$fields_holding_privacy_data = explode( ',', $data['data']['fusion-fields-hold-private-data'] );
			unset( $data['data']['fusion-fields-hold-private-data'] );
		}

		$data['fields_holding_privacy_data'] = $fields_holding_privacy_data;

		unset( $data['data']['fusion_privacy_store_ip_ua'] );
		unset( $data['data']['fusion_privacy_expiration_interval'] );
		unset( $data['data']['privacy_expiration_action'] );
		unset( $data['data'][ 'fusion-form-nonce-' . $form_post_id ] );

		if ( isset( $data['data']['g-recaptcha-response'] ) ) {
			unset( $data['data']['g-recaptcha-response'] );
		}

		if ( isset( $data['data']['fusion-form-recaptcha-response'] ) ) {
			unset( $data['data']['fusion-form-recaptcha-response'] );
		}

		// HubSpot data options.  Add do_action here for further extensions.
		if ( class_exists( 'Fusion_Hubspot' ) && 'contact' === fusion_data()->post_meta( $form_post_id )->get( 'hubspot_action' ) ) {
			if ( '' !== get_post_meta( $form_post_id, 'form_hubspot_map', true ) ) {
				$hubspot_response = Fusion_Hubspot()->submit_form( $data, $field_labels, $form_post_id );
			} else {
				$hubspot_response = Fusion_Hubspot()->create_contact( $data, fusion_data()->post_meta( $form_post_id )->get( 'hubspot_map' ), $field_labels );
			}

			$data['submission']['data']['hubspot_response'] = ! empty( $hubspot_response ) ? $hubspot_response : wp_json_encode( [ 'notice' => __( 'Not connected to HubSpot API.', 'fusion-builder' ) ] );
		}

		// Mailchimp data options.  Add do_action here for further extensions.
		if ( class_exists( 'Fusion_Mailchimp' ) && 'contact' === fusion_data()->post_meta( $form_post_id )->get( 'mailchimp_action' ) && $this->check_mailchimp_consent( $form_post_id, $data ) ) { // here.
			$data['submission']['data']['mailchimp_response'] = Fusion_Mailchimp()->create_contact( $data, $form_post_id, $field_labels );
		}

		do_action( 'fusion_form_submission_data', $data, $form_post_id );

		return $data;
	}

	/**
	 * Checks MailChimp consent.
	 *
	 * @access protected
	 * @since 3.9
	 * @param int   $form_post_id Form post ID.
	 * @param array $data Form   data array.
	 * @return bool
	 */
	protected function check_mailchimp_consent( $form_post_id, $data ) {
		$option  = fusion_data()->post_meta( $form_post_id )->get( 'mailchimp_consent' );
		$consent = true;

		if ( ! empty( $option ) && ( ! isset( $data['data'][ $option ] ) || ! $data['data'][ $option ] ) ) {
			$consent = false;
		}

		return $consent;
	}

	/**
	 * Check the reCAPTCHA response and die if error.
	 *
	 * @access protected
	 * @since 3.1
	 * @return void
	 */
	protected function check_recaptcha_response() {

		$form_post_id = isset( $_POST['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_data    = Fusion_Builder_Form_Helper::fusion_get_form_post_content( $form_post_id );

		if ( ! isset( $_POST['g-recaptcha-response'] ) && isset( $form_data['content'] ) && false !== strpos( $form_data['content'], '[fusion_form_recaptcha' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->has_error     = true;
			$this->captcha_error = __( 'Sorry, ReCaptcha could not verify that you are a human. Please try again.', 'fusion-builder' );
			$results             = [
				'status'  => 'error',
				'captcha' => 'failed',
				'info'    => 'captcha',
				'message' => $this->captcha_error,
			];
			$this->has_error     = false;
			die( wp_json_encode( $results ) );
		}
		if ( isset( $_POST['g-recaptcha-response'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->process_recaptcha( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( $this->has_error ) {
				$results         = [
					'status'  => 'error',
					'captcha' => 'failed',
					'info'    => 'captcha',
					'message' => $this->captcha_error,
				];
				$this->has_error = false;
				die( wp_json_encode( $results ) );
			}
		}
	}

	/**
	 * Check reCAPTCHA.
	 *
	 * @since 3.1
	 * @access private
	 * @return void
	 */
	private function process_recaptcha() {
		$response = class_exists( 'AWB_Google_Recaptcha' ) ? AWB_Google_Recaptcha::get_instance()->verify() : [];
		if ( is_array( $response ) && $response['has_error'] && $response['message'] ) {
			$this->has_error     = $response['has_error'];
			$this->captcha_error = $response['message'];
		}
	}

	/**
	 * Handles the file upload using wp native function.
	 *
	 * @since 3.1
	 * @param array $files The uploaded files array.
	 * @return array $moved_files Array containing uploaded files data or the error.
	 */
	public function handle_upload( $files ) {
		$uploaded_files = [];
		$moved_files    = [];

		foreach ( $files as $file ) {
			foreach ( $file as $key => $data ) {
				foreach ( $data as $key2 => $file_data ) {
					$uploaded_files[ $key2 ][ $key ] = $file_data;
				}
			}
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		add_filter( 'sanitize_file_name', [ $this, 'randomize_name' ] );
		add_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );

		// Create form directory if not already there.
		$upload = wp_upload_dir( null, false );

		if ( ! file_exists( $upload['path'] ) ) {
			wp_mkdir_p( $upload['path'] );

			$index_file = @fopen( $upload['path'] . '/index.html', 'wb' );
			if ( $index_file ) {
				fclose( $index_file );
			}
		}

		foreach ( $uploaded_files as $field_name => $uploaded_file ) {
			$upload_overrides = [
				'test_form' => false,
			];

			$move_file = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $move_file && isset( $move_file['error'] ) ) {
				die( wp_json_encode( $this->get_results_from_message( 'error', 'upload_failed' ) ) );
			}
			$moved_files[ $field_name ] = $move_file['url'];

			// Build uploads, used for email attachments.
			$upload_name = explode( '@|@', $field_name );

			if ( ! isset( $this->uploads[ $upload_name[0] ] ) ) {
				$this->uploads[ $upload_name[0] ] = [];
			}
			$this->uploads[ $upload_name[0] ][] = $move_file;
		}

		remove_filter( 'sanitize_file_name', [ $this, 'randomize_name' ] );
		remove_filter( 'upload_dir', [ $this, 'custom_upload_dir' ] );

		return $moved_files;
	}

	/**
	 * Change the upload location to a separate folder.
	 *
	 * @since 3.1
	 * @param array $dir Upload directory info.
	 * @return array
	 */
	public function custom_upload_dir( $dir = [] ) {
		$dir['path']   = $dir['basedir'] . '/fusion-forms';
		$dir['url']    = $dir['baseurl'] . '/fusion-forms';
		$dir['subdir'] = '/fusion-forms';
		return $dir;
	}

	/**
	 * Change upload file name to a random string.
	 *
	 * @since 3.1
	 * @param string $filename File name.
	 * @return string File name.
	 */
	public function randomize_name( $filename = '' ) {
		$ext = empty( pathinfo( $filename, PATHINFO_EXTENSION ) ) ? '' : '.' . pathinfo( $filename, PATHINFO_EXTENSION );

		return apply_filters( 'awb_forms_upload_file_name', uniqid() . $ext, $filename );
	}

	/**
	 * Get status string from status code.
	 *
	 * @access protected
	 * @since 3.2.1
	 * @param array $response The HTTP response array.
	 * @return string The status string.
	 */
	protected function get_response_type_string( $response ) {
		$code  = (string) wp_remote_retrieve_response_code( $response );
		$types = [
			'1' => 'info',
			'2' => 'success',
			'3' => 'redirect',
			'4' => 'client_error',
			'5' => 'server_error',
		];

		return isset( $types[ $code[0] ] ) ? $types[ $code[0] ] : 'error';
	}

	/**
	 * Get results message.
	 *
	 * @access protected
	 * @param string $type Can be success|error.
	 * @param string $info Type of success/error.
	 * @return string
	 */
	protected function get_results_from_message( $type, $info ) {
		return [
			'status' => $type,
			'info'   => $info,
		];
	}

	/**
	 * Adds mail failure error.
	 *
	 * @access protected
	 * @param object $wp_error WordPress error object.
	 * @return void
	 */
	public function mail_failed_error( $wp_error ) {
		if ( is_wp_error( $wp_error ) && isset( $wp_error->errors ) ) {
			$this->mail_error = isset( $wp_error->errors['wp_mail_failed'] ) ? $wp_error->errors['wp_mail_failed'] : false;
		}
	}
}
