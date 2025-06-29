<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.1
 */

if ( fusion_is_element_enabled( 'fusion_form_select' ) ) {

	if ( ! class_exists( 'FusionForm_Select' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.1
		 */
		class FusionForm_Select extends Fusion_Form_Component {

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.1
			 */
			public function __construct() {
				parent::__construct( 'fusion_form_select' );
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
					'empty_notice'        => '',
					'placeholder'         => '',
					'input_field_icon'    => '',
					'autocomplete'        => 'off',
					'autocomplete_custom' => '',
					'multiselect'         => '',
					'options'             => '',
					'tab_index'           => '',
					'class'               => '',
					'id'                  => '',
					'logics'              => '',
					'tooltip'             => '',
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
				$options = '';
				$html    = '';
				$name    = 'yes' === $this->args['multiselect'] ? $this->args['name'] . '[]' : $this->args['name'];

				if ( empty( $this->args['options'] ) ) {
					return $html;
				} else {
					$this->args['options'] = json_decode( fusion_decode_if_needed( $this->args['options'] ), true );
				}

				$element_data = $this->create_element_data( $this->args );

				if ( '' !== $this->args['tooltip'] ) {
					$element_data['label'] .= $this->get_field_tooltip( $this->args );
				}

				if ( 'yes' === $this->args['multiselect'] ) {
					$height                = count( $this->args['options'] );
					$height                = 5 < $height ? '6.5' : $height + 1.5;
					$element_data['style'] = ' style="height:' . $height . 'em;"';
				}

				if ( isset( $this->args['placeholder'] ) && '' !== $this->args['placeholder'] && 'yes' !== $this->args['multiselect'] ) {
					$options .= '<option value="" disabled selected>' . $this->args['placeholder'] . '</option>';
				}

				foreach ( $this->args['options'] as $option ) {
					$selected = $option[0] ? ' selected ' : '';
					$label    = trim( $option[1] );
					$value    = '' !== $option[2] ? trim( $option[2] ) : $label;

					$options .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
				}

				$element_html  = '<div class="fusion-select-wrapper">';
				$element_html .= '<select ';

				$autocomplete  = 'custom' === $this->args['autocomplete'] ? $this->args['autocomplete_custom'] : $this->args['autocomplete'];
				$element_html .= 'autocomplete="' . esc_attr( $autocomplete ) . '" ';

				$element_html .= 'yes' === $this->args['multiselect'] ? 'multiple ' : '';
				$element_html .= '' !== $element_data['empty_notice'] ? 'data-empty-notice="' . $element_data['empty_notice'] . '" ' : '';
				$element_html .= 'tabindex="' . $this->args['tab_index'] . '" id="' . $this->args['name'] . '" name="' . $name . '"' . $element_data['class'] . $element_data['required'] . $element_data['style'] . $element_data['holds_private_data'] . '>';
				$element_html .= $options;
				$element_html .= '</select>';

				$element_html .= 'yes' === $this->args['multiselect'] ? '' : '<div class="select-arrow"><svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M1.5 1.75L6 6.25L10.5 1.75" stroke="#6D6D6D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/> </svg></div>';
				$element_html .= '</div>';

				if ( isset( $this->args['input_field_icon'] ) && '' !== $this->args['input_field_icon'] ) {
					$icon_html     = '<div class="fusion-form-input-with-icon">';
					$icon_html    .= '<i class=" ' . fusion_font_awesome_name_handler( $this->args['input_field_icon'] ) . '"></i>';
					$element_html  = $icon_html . $element_html;
					$element_html .= '</div>';
				}

				if ( 'above' === $this->params['form_meta']['label_position'] ) {
					$html .= $element_data['label'] . $element_html;
				} else {
					$html .= $element_html . $element_data['label'];
				}

				return $html;
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.1
			 * @return void
			 */
			public function add_css_files() {
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/form/select.min.css' );
			}
		}
	}

	new FusionForm_Select();
}

/**
 * Map shortcode to Fusion Builder
 *
 * @since 3.1
 */
function fusion_form_select() {

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionForm_Select',
			[
				'name'           => esc_attr__( 'Select Field', 'fusion-builder' ),
				'shortcode'      => 'fusion_form_select',
				'icon'           => 'fusiona-af-dropdown',
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
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Multiselect', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose whether you want a multi select or not.', 'fusion-builder' ),
						'param_name'  => 'multiselect',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
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
						'description' => esc_attr__( 'The placeholder text to display as the initial selection.', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'multiselect',
								'value'    => 'no',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Tooltip Text', 'fusion-builder' ),
						'param_name'  => 'tooltip',
						'value'       => '',
						'description' => esc_attr__( 'The text to display as tooltip hint for the input.', 'fusion-builder' ),
					],
					[
						'type'           => 'form_options',
						'heading'        => esc_html__( 'Options', 'fusion-builder' ),
						'param_name'     => 'options',
						'description'    => esc_html__( 'Add options for the input field. Use the checkbox to preselect a value.', 'fusion-builder' ),
						'value'          => 'W1tmYWxzZSwiT3B0aW9uIiwiIl1d',
						'allow_multiple' => 'no',
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
add_action( 'fusion_builder_before_init', 'fusion_form_select' );
