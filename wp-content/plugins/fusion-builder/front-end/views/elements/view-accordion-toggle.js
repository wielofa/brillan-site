/* global FusionPageBuilderElements, fusionAllElements */
var FusionPageBuilder = FusionPageBuilder || {};

( function() {

	jQuery( document ).ready( function() {

		// Toggle child View.
		FusionPageBuilder.fusion_toggle = FusionPageBuilder.ChildElementView.extend( {

			/**
			 * Runs during render() call.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			onRender: function() {
				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					this.model.attributes.selectors[ 'class' ] += ' ' + this.className;
					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}
			},

			/**
			 * Runs after view DOM is patched.
			 *
			 * @since 2.0
			 * @return {void}
			 */
			afterPatch: function() {

				if ( 'undefined' !== typeof this.model.attributes.selectors ) {
					this.model.attributes.selectors[ 'class' ] += ' ' + this.className;
					this.setElementAttributes( this.$el, this.model.attributes.selectors );
				}

				// Using non debounced version for smoothness.
				this.refreshJs();
			},

			/**
			 * Modify template attributes.
			 *
			 * @since 2.0
			 * @param {Object} atts - The attributes.
			 * @return {Object}
			 */
			filterTemplateAtts: function( atts ) {
				var attributes = {},
					parent          = this.model.get( 'parent' ),
					parentModel     = FusionPageBuilderElements.find( function( model ) {
						return model.get( 'cid' ) == parent;
					} ),
					parentValues    = jQuery.extend( true, {}, fusionAllElements.fusion_accordion.defaults, _.fusionCleanParameters( parentModel.get( 'params' ) ) );

				this.values = atts.values;

				// Validate values.
				this.validateValues( atts.values );

				// Create attribute objects.
				attributes.toggleShortcodeCollapse   = this.buildCollapseAttr( atts.values );
				attributes.toggleShortcodeDataToggle = this.buildDataToggleAttr( atts.values, parentValues, parentModel );
				attributes.headingAttr               = this.buildHeadingAttr( atts.values );
				attributes.panelTitlegAttr           = this.buildPanelTitleAttr( atts.values, parentValues );
				attributes.contentAttr               = this.buildContentAttr( atts.values );
				attributes.title                     = atts.values.title;
				attributes.elementContent            = atts.values.element_content;
				attributes.activeIcon                = '' !== parentValues.active_icon ? _.fusionFontAwesome( parentValues.active_icon ) : 'awb-icon-minus';
				attributes.inActiveIcon              = '' !== parentValues.inactive_icon ? _.fusionFontAwesome( parentValues.inactive_icon ) : 'awb-icon-plus';
				attributes.titleTag                  = '' !== parentValues.title_tag ? parentValues.title_tag : 'h4';

				// Set selectors.
				this.buildPanelAttr( atts.values, parentValues );

				// Any extras that need passed on.
				attributes.cid    = this.model.get( 'cid' );
				attributes.parent = this.model.get( 'parent' );

				attributes.usingDynamicParent = this.isParentHasDynamicContent( parentValues );
				return attributes;
			},

			/**
			 * Modifies the values.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {void}
			 */
			validateValues: function( values ) {
				values.toggle_class = ( 'yes' === values.open ) ? 'in' : '';
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @return {Object}
			 */
			buildCollapseAttr: function( values ) {
				var collapseID              = '#accordion-' + this.model.get( 'cid' ),
					toggleShortcodeCollapse = {
						id: collapseID.replace( '#', '' ),
						class: 'panel-collapse collapse ' + values.toggle_class
					};

				return toggleShortcodeCollapse;
			},

			/**
			 * Builds the panel title attributes.
			 *
			 * @since 3.12.1
			 * @param {Object} values - The values object.
			 * @param {Object} parentValues - The parent object values.
			 * @return {Object}
			 */
			buildPanelTitleAttr: function( values, parentValues ) {
				atts = {
					class: 'panel-title toggle',
					id: 'toggle_'  + this.model.get( 'cid' ),
				};

				if ( -1 !== parentValues.title_font_size.indexOf( 'clamp(' ) ) {
					atts[ 'class' ] += ' awb-responsive-type__disable';
				}

				return atts;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @param {Object} parentValues - The parent object values.
			 * @return {Object}
			 */
			buildPanelAttr: function( values, parentValues ) {
				var toggleShortcodePanel = {
					class: 'fusion-panel panel-default',
					style: ''
				};

				if ( ' ' !== values[ 'class' ] ) {
					toggleShortcodePanel[ 'class' ] += ' ' + values[ 'class' ];
				}

				if ( '' !== values.id ) {
					toggleShortcodePanel.id = values.id;
				}

				toggleShortcodePanel[ 'class' ] += ' panel-' + this.model.get( 'cid' );

				if ( '1' == parentValues.boxed_mode || 'yes' === parentValues.boxed_mode ) {
					toggleShortcodePanel[ 'class' ] += ' fusion-toggle-no-divider fusion-toggle-boxed-mode';
				} else {
					// eslint-disable-next-line no-lonely-if
					if ( '0' === parentValues.divider_line || 'no' === parentValues.divider_line ) {
						toggleShortcodePanel[ 'class' ] += ' fusion-toggle-no-divider';
					} else {
						toggleShortcodePanel[ 'class' ] += ' fusion-toggle-has-divider';
					}
				}

				toggleShortcodePanel.style += this.getStyleVariables( values );

				this.model.set( 'selectors', toggleShortcodePanel );
				return toggleShortcodePanel;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @param {Object} values - The values object.
			 * @param {Object} parentValues - The parent values object.
			 * @param {Object} parentModel - The parent element model.
			 * @return {Object}
			 */
			buildDataToggleAttr: function( values, parentValues, parentModel ) {
				var toggleShortcodeDataToggle = {},
					collapseID                = '#accordion-' + this.model.get( 'cid' );

				if ( 'yes' === values.open ) {
					toggleShortcodeDataToggle[ 'class' ] = 'active';
				}

				// Accessibility enhancements.
				toggleShortcodeDataToggle[ 'aria-expanded' ] = ( 'yes' === values.open ) ? 'true' : 'false';
				toggleShortcodeDataToggle[ 'aria-controls' ] = collapseID;
				toggleShortcodeDataToggle.role               = 'button';

				toggleShortcodeDataToggle[ 'data-toggle' ] = 'collapse';
				if ( 'toggles' !== parentValues.type ) {
					toggleShortcodeDataToggle[ 'data-parent' ] = '#accordion-cid' + parentModel.attributes.cid;
				}
				toggleShortcodeDataToggle[ 'data-target' ] =  collapseID;
				toggleShortcodeDataToggle.href           =  collapseID;

				return toggleShortcodeDataToggle;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildHeadingAttr: function() {
				var that = this,
					headingAttr = {
						class: 'fusion-toggle-heading'
					};

				headingAttr = _.fusionInlineEditor( {
					cid: that.model.get( 'cid' ),
					param: 'title',
					'disable-return': true,
					'disable-extra-spaces': true,
					toolbar: false
				}, headingAttr );

				return headingAttr;
			},

			/**
			 * Builds attributes.
			 *
			 * @since 2.0
			 * @return {Object}
			 */
			buildContentAttr: function() {
				var that = this,
					contentAttr = {
						class: 'panel-body toggle-content fusion-clearfix'
					};

				contentAttr = _.fusionInlineEditor( {
					cid: that.model.get( 'cid' )
				}, contentAttr );

				return contentAttr;
			},

			/**
			 * Gets style variables.
			 *
			 * @since 3.9
			 * @param {Object} values - The values.
			 * @return {String}
			 */
			getStyleVariables: function( values ) {
				const cssVarsOptions = [
					'content_text_transform',
					'content_line_height'
				];

				cssVarsOptions.content_font_size = { 'callback': _.fusionGetValueWithUnit };
				cssVarsOptions.content_letter_spacing = { 'callback': _.fusionGetValueWithUnit };

				const customVars = [];
				const title_typography = _.fusionGetFontStyle( 'title_font', values, 'object' );


				if ( title_typography[ 'font-family' ] ) {
					customVars.title_font_family = title_typography[ 'font-family' ];
				}

				if ( title_typography[ 'font-weight' ] ) {
					customVars.title_font_weight = title_typography[ 'font-weight' ];
				}

				if ( title_typography[ 'font-style' ] ) {
					customVars.title_font_style = title_typography[ 'font-style' ];
				}

				if ( values.title_font_size ) {
					customVars.title_font_size = _.fusionGetValueWithUnit( values.title_font_size );
				}

				if ( values.title_letter_spacing ) {
					customVars.title_letter_spacing = _.fusionGetValueWithUnit( values.title_letter_spacing );
				}

				if ( values.title_line_height ) {
					customVars.title_line_height = values.title_line_height;
				}

				if ( values.title_text_transform ) {
					customVars.title_text_transform = values.title_text_transform;
				}

				if ( values.title_color ) {
					customVars.title_color = values.title_color;
				}

				return this.getCssVarsForOptions( cssVarsOptions ) + this.getCustomCssVars( customVars ) + this.getFontStylingVars( 'content_font', values );
			}

		} );
	} );
}( jQuery ) );
