<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.1
 */

if ( fusion_is_element_enabled( 'fusion_form_email' ) ) {

	if ( ! class_exists( 'FusionForm_Email' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.1
		 */
		class FusionForm_Email extends Fusion_Form_Component {

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.1
			 */
			public function __construct() {
				parent::__construct( 'fusion_form_email' );
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.1
			 * @return array
			 */
			public static function get_element_defaults() {

				return [
					'label'               => '',
					'name'                => '',
					'required'            => '',
					'invalid_notice'      => '',
					'empty_notice'        => '',
					'placeholder'         => '',
					'input_field_icon'    => '',
					'autocomplete'        => 'off',
					'autocomplete_custom' => '',
					'pattern'             => '',
					'tab_index'           => '',
					'class'               => '',
					'id'                  => '',
					'logics'              => '',
					'tooltip'             => '',
					'value'               => '',
					'must_match'          => '',
				];
			}

			/**
			 * Render form field html.
			 *
			 * @access public
			 * @since 3.1
			 * @param string $content The content.
			 * @return string
			 */
			public function render_input_field( $content ) {
				// If no value, unset so the isset checks which are shared remain the same.
				if ( '' === $this->args['value'] ) {
					unset( $this->args['value'] );
				}
				return $this->generate_input_field( $this->args, 'email' );
			}
		}
	}

	new FusionForm_Email();
}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 3.1
 */
function fusion_form_email() {

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionForm_Email',
			[
				'name'           => esc_attr__( 'Email Field', 'fusion-builder' ),
				'shortcode'      => 'fusion_form_email',
				'icon'           => 'fusiona-af-email',
				'form_component' => true,
				'preview'        => FUSION_BUILDER_PLUGIN_DIR . 'inc/templates/previews/fusion-form-element-preview.php',
				'preview_id'     => 'fusion-builder-block-module-form-element-preview-template',
				'params'         => [
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Field Label', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter the label for the input field. This is how users will identify individual fields.', 'fusion-builder' ),
						'param_name'  => 'label',
						'value'       => '',
						'placeholder' => true,
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Field Name', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter the field name. Please use only lowercase alphanumeric characters, dashes, and underscores.', 'fusion-builder' ),
						'param_name'  => 'name',
						'value'       => '',
						'placeholder' => true,
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Field Value', 'fusion-builder' ),
						'description'  => esc_attr__( 'Enter a starting value for the element.  Usually this should be empty and a placeholder used instead.', 'fusion-builder' ),
						'param_name'   => 'value',
						'value'        => '',
						'dynamic_data' => true,
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Required Field', 'fusion-builder' ),
						'description' => esc_attr__( 'Make a selection to ensure that this field is completed before allowing the user to submit the form.', 'fusion-builder' ),
						'param_name'  => 'required',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Empty Input Notice', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter text validation notice that should display if data input is empty.', 'fusion-builder' ),
						'param_name'  => 'empty_notice',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'required',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Placeholder Text', 'fusion-builder' ),
						'param_name'  => 'placeholder',
						'value'       => '',
						'description' => esc_attr__( 'The placeholder text to display as hint for the input type.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tooltip Text', 'fusion-builder' ),
						'param_name'  => 'tooltip',
						'value'       => '',
						'description' => esc_attr__( 'The text to display as tooltip hint for the input.', 'fusion-builder' ),
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Input Field Icon', 'fusion-builder' ),
						'param_name'  => 'input_field_icon',
						'value'       => '',
						'description' => esc_attr__( 'Select an icon for the input field, click again to deselect.', 'fusion-builder' ),
					],
					'fusion_form_autocomplete_placeholder' => [],
                    [
                        'type'        => 'raw_text',
                        'heading'     => esc_attr__( 'Custom Pattern', 'fusion-builder' ),
                        'param_name'  => 'pattern',
                        'value'       => '',
                        /* translators: Patterns link. */
                        'description' => sprintf( __( 'Enter allowed input pattern. For more info and pattern examples, you can check %s.', 'fusion-builder' ), '<a href="https://www.w3schools.com/Tags/att_input_pattern.asp" target="_blank">' . esc_attr__( 'w3schools', 'fusion-builder' ) . '</a>' ),
                    ],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Invalid Input Notice', 'fusion-builder' ),
						'description' => esc_attr__( 'Enter validation notice that should display if data input is invalid.', 'fusion-builder' ),
						'param_name'  => 'invalid_notice',
						'value'       => '',
						'dependency'  => [
							[
								'element'  => 'pattern',
								'value'    => '',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Input Must Match', 'fusion-builder' ),
						'param_name'  => 'must_match',
						'value'       => '',
						'description' => __( 'Enter a field name from the same form. If set, the form will only be sent if the field values match.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tab Index', 'fusion-builder' ),
						'param_name'  => 'tab_index',
						'value'       => '',
						'description' => esc_attr__( 'Tab index for the form field.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
						'description' => esc_attr__( 'Add a class for the form field.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
						'description' => esc_attr__( 'Add an ID for the form field.', 'fusion-builder' ),
					],
					'fusion_form_logics_placeholder' => [],
				],
			]
		)
	);
}
add_action( 'fusion_builder_before_init', 'fusion_form_email' );
