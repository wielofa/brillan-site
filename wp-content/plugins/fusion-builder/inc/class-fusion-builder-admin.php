<?php
/**
 * The Fusion_Builder_Admin class.true
 *
 * @package fusion-builder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Fusion_Builder_Admin class.
 *
 * @since 1.0
 */
class Fusion_Builder_Admin {

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'avada_add_admin_menu_pages', [ $this, 'admin_menu' ], 20 );

		add_action( 'avada_dashboard_main_menu_options_sub_menu_items', [ $this, 'add_avada_dashboard_main_menu_options_sub_menu_items' ], 10 );
		add_action( 'avada_dashboard_sticky_menu_items', [ $this, 'add_avada_dashboard_sticky_menu_items' ], 10 );
		add_action( 'avada_dashboard_sticky_menu_items', [ $this, 'add_avada_dashboard_sticky_menu_items_library' ], 30 );

		add_action( 'admin_post_save_fb_settings', [ $this, 'settings_save' ] );
		add_action( 'admin_post_awb_save_layout_order', [ $this, 'save_layout_order_form' ] );
		add_action( 'wp_ajax_awb_save_layout_order', [ $this, 'save_layout_order_ajax' ] );

		add_action( 'admin_footer', [ $this, 'add_builder_update_buttons' ], 1 );
		add_action( 'edit_form_top', [ $this, 'edit_form_top' ] );
		add_action( 'wp_ajax_fusion_admin_layout_delete', [ $this, 'delete_layout' ] );
		add_action( 'wp_ajax_fusion_admin_layout_update', [ $this, 'update_layout' ] );
		add_action( 'wp_ajax_fusion_admin_layout_options', [ $this, 'get_layout_options' ] );

		add_action( 'wp_ajax_fusion_check_elements', [ $this, 'check_elements' ] );

		add_action( 'wp_ajax_update_page_template_post_meta', [ $this, 'update_page_template_post_meta' ] );

		add_action( 'current_screen', [ $this, 'display_critical_css_disabled_admin_notice' ] );

		add_filter( 'rank_math/researches/toc_plugins', [ $this, 'add_rankmath_toc_plugin' ] );
	}

	/**
	 * Update title actions
	 *
	 * @access public
	 * @return void
	 */
	public function edit_form_top() {
		global $post;

		$post_type = isset( $post->post_type ) ? $post->post_type : false;
		$slug      = 'fusion_tb_section' === $post_type ? 'avada-layouts' : 'avada-builder-library';
		/* translators: Theme Builder|library. */
		$message = sprintf( __( 'Back to %s', 'fusion-builder' ), 'fusion_tb_section' === $post_type ? __( 'Layout Builder', 'fusion-builder' ) : __( 'library', 'fusion-builder' ) );
		$url     = menu_page_url( $slug, false );

		if ( ! in_array( $post_type, [ 'fusion_tb_section', 'fusion_template' ], true ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery( '.page-title-action[href*="post-new.php"]' ).text( '<?php echo esc_html( $message ); ?>' ).attr( 'href', '<?php echo esc_url( $url ); ?>' );
		</script>
		<?php
	}

	/**
	 * Bottom update buttons on edit screen.
	 *
	 * @access public
	 */
	public function add_builder_update_buttons() {
		global $post, $pagenow;

		$post_type = isset( $post->post_type ) ? $post->post_type : false;

		if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) && post_type_supports( $post_type, 'editor' ) ) {
			// Escaping is done on output.
			$publish_button_text     = ( isset( $post->post_status ) && ( 'publish' === $post->post_status || 'private' === $post->post_status ) ) ? __( 'Update', 'fusion-builder' ) : __( 'Publish', 'fusion-builder' );
			$fusion_builder_settings = get_option( 'fusion_builder_settings', [] );

			$enable_builder_sticky_publish_buttons = true;

			if ( isset( $fusion_builder_settings['enable_builder_sticky_publish_buttons'] ) ) {
				$enable_builder_sticky_publish_buttons = $fusion_builder_settings['enable_builder_sticky_publish_buttons'];
			}

			if ( ! isset( $post->ID ) || ! $enable_builder_sticky_publish_buttons ) {
				return;
			}
			?>
			<div class="fusion-builder-update-buttons <?php echo ( 'publish' !== $post->post_status && 'future' !== $post->post_status && 'pending' !== $post->post_status && 'private' !== $post->post_status ) ? 'fusion-draft-button' : ''; ?>">
				<a href="#" class="button button-secondary fusion-preview" target="wp-preview-<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Preview', 'fusion-builder' ); ?></a>
				<?php if ( 'publish' !== $post->post_status && 'future' !== $post->post_status && 'pending' !== $post->post_status ) { ?>
				<a href="#"<?php echo ( 'private' === $post->post_status ) ? ' style="display:none"' : ''; ?> class="button button-secondary fusion-save-draft"><?php esc_html_e( 'Save Draft', 'fusion-builder' ); ?></a>
			<?php } ?>
				<a href="#" class="button button-primary fusion-update"><?php echo esc_html( $publish_button_text ); ?></a>
			</div>
			<?php
		}
	}

	/**
	 * Admin Menu.
	 *
	 * @access public
	 */
	public function admin_menu() {

		$layouts = add_submenu_page( 'avada', esc_html__( 'Avada Layouts', 'fusion-builder' ), esc_html__( 'Layouts', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_layout' ), 'avada-layouts', [ $this, 'layouts' ], 5 );
		add_action( 'admin_print_scripts-' . $layouts, [ $this, 'scripts_advanced' ] );
		add_action( 'admin_print_scripts-' . $layouts, [ $this, 'layout_builder' ] );

		$layout_sections = add_submenu_page( 'avada', esc_html__( 'Avada Layout Sections', 'fusion-builder' ), esc_html__( 'Layout Sections', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_section' ), 'avada-layout-sections', [ $this, 'layout_sections' ], 20 );
		add_action( 'admin_print_scripts-' . $layout_sections, [ $this, 'scripts_advanced' ] );

		if ( false !== AWB_Off_Canvas::is_enabled() ) {
			$off_canvas = add_submenu_page( 'avada', esc_html__( 'Off Canvas', 'fusion-builder' ), esc_html__( 'Off Canvas', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'awb_off_canvas' ), 'avada-off-canvas', [ $this, 'off_canvas' ], 6 );
			add_action( 'admin_print_scripts-' . $off_canvas, [ $this, 'scripts_advanced' ] );
		}

		$icons = add_submenu_page( 'avada', esc_html__( 'Avada Icons', 'fusion-builder' ), esc_html__( 'Icons', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'fusion_icons' ), 'avada-icons', [ $this, 'icons' ], 7 );
		add_action( 'admin_print_scripts-' . $icons, [ $this, 'scripts_advanced' ] );

		if ( Fusion_Form_Builder::is_enabled() ) {
			$forms = add_submenu_page( 'avada', esc_html__( 'Avada Forms', 'fusion-builder' ), esc_html__( 'Forms', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'fusion_form' ), 'avada-forms', [ $this, 'forms' ], 8 );

			$order         = ! current_user_can( apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'fusion_form' ) ) && current_user_can( apply_filters( 'awb_role_manager_access_capability', 'moderate_comments', 'fusion_form', 'submissions_access' ) ) ? 8 : 21;
			$forms_entries = add_submenu_page( 'avada', esc_html__( 'Avada Form Entries', 'fusion-builder' ), esc_html__( 'Form Entries', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'moderate_comments', 'fusion_form', 'submissions_access' ), 'avada-form-entries', [ $this, 'forms_entries' ], $order );

			add_action( 'admin_print_scripts-' . $forms_entries, [ $this, 'form_builder' ] );
			add_action( 'admin_print_scripts-' . $forms_entries, [ $this, 'scripts_advanced' ] );

			add_action( 'admin_print_scripts-' . $forms, [ $this, 'form_builder' ] );
			add_action( 'admin_print_scripts-' . $forms, [ $this, 'scripts_advanced' ] );
		}

		$library = add_submenu_page( 'avada', esc_html__( 'Avada Library', 'fusion-builder' ), esc_html__( 'Library', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'avada_library' ), 'avada-library', [ $this, 'library' ], 10 );
		add_action( 'admin_print_scripts-' . $library, [ $this, 'scripts_advanced' ] );

		$options = add_submenu_page( 'avada', esc_html__( 'Avada Builder Options', 'fusion-builder' ), esc_html__( 'Builder Options', 'fusion-builder' ), apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'awb_global_options' ), 'avada-builder-options', [ $this, 'options' ], 19 );
		add_action( 'admin_print_scripts-' . $options, [ $this, 'scripts_advanced' ] );

		add_action( 'admin_footer', 'fusion_the_admin_font_async' );
	}

	/**
	 * Add items to the Avada dashboard main menu options sub-menu.
	 *
	 * @access public
	 * @since 3.0
	 * @param string $screen The current screen.
	 * @return void
	 */
	public function add_avada_dashboard_main_menu_options_sub_menu_items( $screen ) {
		?>
		<li class="avada-db-menu-sub-item">
			<a class="avada-db-menu-sub-item-link<?php echo ( 'builder-options' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'builder-options' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-builder-options' ) ); ?>">
				<i class="fusiona-equalizer"></i>
				<div class="avada-db-menu-sub-item-text">
					<div class="avada-db-menu-sub-item-label"><?php esc_html_e( 'Builder Options', 'fusion-builder' ); ?></div>
					<div class="avada-db-menu-sub-item-desc"><?php esc_html_e( 'Edit the Avada Builder options.', 'fusion-builder' ); ?></div>
				</div>
			</a>
		</li>
		<?php
	}

	/**
	 * Add items to the Avada dashboard sticky menu.
	 *
	 * @access public
	 * @since 3.0
	 * @param string $screen The current screen.
	 * @return void
	 */
	public function add_avada_dashboard_sticky_menu_items( $screen ) {
		if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_layout' ) ) || current_user_can( apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_section' ) ) ) :
			?>
			<li class="avada-db-menu-item avada-db-menu-item-layouts"><a class="avada-db-menu-item-link<?php echo ( 'layouts' === $screen || 'layout-sections' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'layouts' === $screen || ! current_user_can( apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_layout' ) ) ) ? '#' : admin_url( 'admin.php?page=avada-layouts' ) ); ?>" ><i class="fusiona-layouts"></i><span class="avada-db-menu-item-text"><?php esc_html_e( 'Layouts', 'fusion-builder' ); ?></span></a>
				<ul class="avada-db-menu-sub avada-db-menu-sub-layouts">
			<?php if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_layout' ) ) ) : ?>
					<li class="avada-db-menu-sub-item avada-db-menu-sub-item-layouts">
						<a class="avada-db-menu-sub-item-link<?php echo ( 'layouts' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'layouts' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-layouts' ) ); ?>">
							<i class="fusiona-layouts"></i>
							<div class="avada-db-menu-sub-item-text">
								<div class="avada-db-menu-sub-item-label"><?php esc_html_e( 'Layout Builder', 'fusion-builder' ); ?></div>
								<div class="avada-db-menu-sub-item-desc"><?php esc_html_e( 'Edit your site layouts.', 'fusion-builder' ); ?></div>
							</div>
						</a>
					</li>
				<?php endif; ?>
			<?php if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'manage_options', 'fusion_tb_section' ) ) ) : ?>
					<li class="avada-db-menu-sub-item avada-db-menu-sub-item-layout-sections">
						<a class="avada-db-menu-sub-item-link<?php echo ( 'layout-sections' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'layout-sections' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-layout-sections' ) ); ?>">
							<i class="fusiona-content"></i>
							<div class="avada-db-menu-sub-item-text">
								<div class="avada-db-menu-sub-item-label"><?php esc_html_e( 'Layout Section Builder', 'fusion-builder' ); ?></div>
								<div class="avada-db-menu-sub-item-desc"><?php esc_html_e( 'Edit specific sections of a layout.', 'fusion-builder' ); ?></div>
							</div>
						</a>
					</li>
				<?php endif; ?>
				</ul>
			</li>
			<?php endif; ?>
		<?php
		if ( false !== AWB_Off_Canvas::is_enabled() && current_user_can( apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'awb_off_canvas' ) ) ) :
			?>
			<li class="avada-db-menu-item avada-db-menu-item-icons"><a class="avada-db-menu-item-link<?php echo ( 'off-canvas' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'off-canvas' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-off-canvas' ) ); ?>" ><i class="fusiona-off-canvas"></i><span class="avada-db-menu-item-text"><?php esc_html_e( 'Off Canvas', 'fusion-builder' ); ?></span></a></li>
		<?php endif; ?>
		<?php if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'fusion_icons' ) ) ) : ?>
			<li class="avada-db-menu-item avada-db-menu-item-icons"><a class="avada-db-menu-item-link<?php echo ( 'icons' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'icons' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-icons' ) ); ?>" ><i class="fusiona-icons"></i><span class="avada-db-menu-item-text"><?php esc_html_e( 'Icons', 'fusion-builder' ); ?></span></a></li>
			<?php
		endif;
	}

	/**
	 * Add library item to the Avada dashboard sticky menu.
	 *
	 * @access public
	 * @since 3.1
	 * @param string $screen The current screen.
	 * @return void
	 */
	public function add_avada_dashboard_sticky_menu_items_library( $screen ) {
		if ( current_user_can( apply_filters( 'awb_role_manager_access_capability', 'edit_posts', 'avada_library' ) ) ) :
			?>
			<li class="avada-db-menu-item avada-db-menu-item-library"><a class="avada-db-menu-item-link<?php echo ( 'library' === $screen ) ? ' avada-db-active' : ''; ?>" href="<?php echo esc_url( ( 'library' === $screen ) ? '#' : admin_url( 'admin.php?page=avada-library' ) ); ?>" ><i class="fusiona-drive"></i><span class="avada-db-menu-item-text"><?php esc_html_e( 'Library', 'fusion-builder' ); ?></span></a></li>
			<?php
		endif;
	}

	/**
	 * Admin scripts.
	 *
	 * @access public
	 * @return void
	 */
	public function scripts_general() {
		wp_enqueue_style( 'fusion_builder_admin_css', FUSION_BUILDER_PLUGIN_URL . 'assets/admin/css/fusion-builder-admin.css', [], FUSION_BUILDER_VERSION );
		wp_enqueue_style( 'fusion-font-icomoon', FUSION_LIBRARY_URL . '/assets/fonts/icomoon-admin/icomoon.css', [], FUSION_BUILDER_VERSION, 'all' );
	}

	/**
	 * Admin scripts including js.
	 *
	 * @access public
	 * @return void
	 */
	public function scripts_advanced() {
		$this->scripts_general();

		wp_enqueue_script( 'fusion_builder_admin_js', FUSION_BUILDER_PLUGIN_URL . 'js/admin/fusion-builder-admin.js', [], FUSION_BUILDER_VERSION, false );

		// Localize Scripts.
		wp_localize_script(
			'fusion_builder_admin_js',
			'fusionBuilderAdmin',
			[
				'remove_all_studio_content' => esc_html__( 'Are you sure you want to remove all Avada Studio content?', 'fusion-builder' ),
			]
		);

		if ( class_exists( 'Avada' ) ) {
			wp_enqueue_style( 'avada_admin_css', trailingslashit( Avada::$template_dir_url ) . 'assets/admin/css/avada-admin.css', [], AVADA_VERSION );
		}
	}

	/**
	 * Admin scripts including js.
	 *
	 * @access public
	 * @since 2.2
	 */
	public function layout_builder() {
		include FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layout-builder/layouts.php';
		include FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layout-builder/layout.php';
		include FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layout-builder/layout-options.php';
		include FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layout-builder/layout-child-option.php';

		wp_enqueue_script( 'fusion_builder_app_util_js', FUSION_LIBRARY_URL . '/inc/fusion-app/util.js', [ 'jquery', 'jquery-ui-core', 'underscore', 'backbone' ], FUSION_BUILDER_VERSION, true );
		wp_enqueue_script( 'fusion_layouts', FUSION_BUILDER_PLUGIN_URL . 'inc/admin-screens/layout-builder/layouts.js', [ 'fusion_builder_app_util_js', 'jquery-ui-sortable' ], FUSION_BUILDER_VERSION, false );
		wp_enqueue_script( 'fusion_layout', FUSION_BUILDER_PLUGIN_URL . 'inc/admin-screens/layout-builder/layout.js', [ 'fusion_builder_app_util_js', 'jquery-ui-sortable' ], FUSION_BUILDER_VERSION, false );
		wp_enqueue_script( 'fusion_layout_options', FUSION_BUILDER_PLUGIN_URL . 'inc/admin-screens/layout-builder/layout-options.js', [ 'fusion_builder_app_util_js' ], FUSION_BUILDER_VERSION, false );
	}

	/**
	 * Admin scripts including js.
	 *
	 * @access public
	 * @since 2.2
	 */
	public function form_builder() {
		wp_enqueue_style( 'fusion_form_admin_css', FUSION_BUILDER_PLUGIN_URL . 'assets/admin/css/fusion-form-admin.css', [], FUSION_BUILDER_VERSION );

		wp_enqueue_script( 'fusion_form_admin_js', FUSION_BUILDER_PLUGIN_URL . 'assets/admin/js/fusion-form-admin.js', [ 'jquery' ], FUSION_BUILDER_VERSION, false );

		// Localize Scripts.
		wp_localize_script(
			'fusion_form_admin_js',
			'fusionBuilderConfig',
			[
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
				'fusion_import_nonce'  => wp_create_nonce( 'fusion_import_nonce' ),
				'fusion_entry_nonce'   => wp_create_nonce( 'fusion_entry_nonce' ),
				'remove_entry_message' => esc_html__( 'Are you sure you want to delete this submission?', 'fusion-builder' ),
			]
		);
	}

	/**
	 * Loads the template file.
	 *
	 * @since  2.2
	 * @access public
	 */
	public function layout_sections() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layout-sections.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  2.2
	 * @access public
	 */
	public function layouts() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/layouts.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  3.1
	 * @access public
	 */
	public function forms() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/forms.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  3.1
	 * @access public
	 */
	public function forms_entries() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/forms-entries.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  2.2
	 * @access public
	 */
	public function icons() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/icons.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  2.2
	 * @access public
	 */
	public function off_canvas() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/off-canvas.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function library() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/library.php';
	}

	/**
	 * Loads the template file.
	 *
	 * @access public
	 */
	public function options() {
		require_once FUSION_BUILDER_PLUGIN_DIR . 'inc/admin-screens/options.php';
	}

	/**
	 * Add the title.
	 *
	 * @static
	 * @access protected
	 * @since 1.0
	 * @param string $title The title.
	 * @param string $page  The page slug.
	 */
	protected static function admin_tab( $title, $page ) {

		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$active_page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( $active_page === $page ) {
			$link       = 'javascript:void(0);';
			$active_tab = ' nav-tab-active';
		} else {
			$link       = 'admin.php?page=' . $page;
			$active_tab = '';
		}

		// Exception for layout section page, Layouts tab is active.
		if ( 'avada-layout-sections' === $active_page && 'fusion-layouts' === $page ) {
			$link       = 'javascript:void(0);';
			$active_tab = ' nav-tab-active';
		}

		// Exception for Form Creator page, sub tabs are active.
		if ( 'fusion-forms-entries' === $active_page && 'fusion-forms' === $page ) {
			$link       = 'javascript:void(0);';
			$active_tab = ' nav-tab-active';
		}

		echo '<a href="' . esc_url_raw( $link ) . '" class="nav-tab' . esc_attr( $active_tab ) . '">' . $title . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Adds the header.
	 *
	 * @static
	 * @access public
	 * @param string $screen The current screen.
	 * @return void
	 */
	public static function header( $screen = 'dashboard' ) {
		if ( class_exists( 'Avada' ) ) {
			Avada_Admin::get_admin_screens_header( $screen );
		}
	}

	/**
	 * Adds the footer.
	 *
	 * @static
	 * @access public
	 */
	public static function footer() {
		if ( class_exists( 'Avada' ) ) {
			Avada_Admin::get_admin_screens_footer();
		}
	}

	/**
	 * Get social media links
	 *
	 * @static
	 * @access public
	 * @since 7.0
	 * @return string The social media link markup
	 */
	public static function get_social_media_links() {
		$social_media_markup = '<a href="https://www.facebook.com/ThemeFusion-101565403356430/" target="_blank" class="fusion-social-media nav-tab dashicons dashicons-facebook-alt"></a>
		<a href="https://x.com/theme_fusion" target="_blank" class="fusion-social-media nav-tab dashicons dashicons-twitter"></a>
		<a href="https://www.instagram.com/themefusion/" target="_blank" class="fusion-social-media nav-tab dashicons dashicons-instagram"></a>
		<a href="https://www.youtube.com/channel/UC_C7uAOAH9RMzZs-CKCZ62w" target="_blank" class="fusion-social-media nav-tab fusiona-youtube"></a>';

		return apply_filters( 'fusion_admin_social_media_links', $social_media_markup );
	}

	/**
	 * Handles the saving of settings in admin area.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function settings_save() {
		check_admin_referer( 'fusion_builder_save_fb_settings', 'fusion_builder_save_fb_settings' );

		update_option( 'fusion_builder_settings', $_POST );

		// Reset cache because CSS and JS depends on which elements are loaded.
		fusion_reset_all_caches();

		wp_safe_redirect( admin_url( 'admin.php?page=avada-builder-options' ) );
		exit;
	}

	/**
	 * Handles the saving of the layout order on the admin screen.
	 *
	 * @static
	 * @access public
	 * @since 3.9
	 * @param string $layout_id ID of a new layout.
	 * @param string $operation The operation to be performed: add|delete.
	 * @return bool
	 */
	public static function save_layout_order( $layout_id = '', $operation = 'add' ) {
		if ( $layout_id || isset( $_POST['awb_layout_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$layout_id        = (string) $layout_id;
			$builder_settings = get_option( 'fusion_builder_settings', [ 'awb_layout_order' => '' ] );

			if ( $layout_id ) {
				if ( 'add' === $operation ) {
					if ( false === strpos( $builder_settings['awb_layout_order'], $layout_id ) ) {
						$builder_settings['awb_layout_order'] .= ',' . $layout_id;
					}
				} else {
					$builder_settings['awb_layout_order'] = str_replace( ',' . $layout_id, '', $builder_settings['awb_layout_order'] );
				}
			} else {
				$builder_settings['awb_layout_order'] = sanitize_text_field( wp_unslash( $_POST['awb_layout_order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			$saved = update_option( 'fusion_builder_settings', $builder_settings );

			return $saved;
		}

		return false;
	}

	/**
	 * Handles the saving of the layout order form on the admin screen.
	 *
	 * @access public
	 * @since 3.9
	 * @return void
	 */
	public function save_layout_order_form() {
		check_admin_referer( 'awb_save_layout_order', 'awb_save_layout_order' );

		$saved = self::save_layout_order();

		if ( '1' === fusion_library()->get_option( 'clear_object_cache' ) ) {
			wp_cache_flush();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=avada-layouts' ) );
		exit;
	}

	/**
	 * Handles the saving of the layout order on the admin screen using ajax.
	 *
	 * @access public
	 * @since 3.9
	 * @return void
	 */
	public function save_layout_order_ajax() {
		check_ajax_referer( 'fusion_tb_new_layout', 'security' );

		$saved = self::save_layout_order();

		if ( false !== $saved ) {
			if ( '1' === fusion_library()->get_option( 'clear_object_cache' ) ) {
				wp_cache_flush();
			}

			echo wp_json_encode( [ 'success' => true ] );
			wp_die();
		}

		wp_send_json_error();
		wp_die();
	}

	/**
	 * Handles the removal of a layout.
	 *
	 * @access private
	 * @since 2.2
	 */
	public function delete_layout() {
		check_ajax_referer( 'fusion_tb_new_layout', 'security' );

		if ( isset( $_POST['post_id'] ) ) {
			$post_id = sanitize_text_field( wp_unslash( $_POST['post_id'] ) );
			$delete  = wp_delete_post( absint( $post_id ) );

			if ( false !== $delete ) {
				self::save_layout_order( $post_id, 'delete' );

				echo wp_json_encode( [ 'success' => true ] );
				wp_die();
			}
			wp_send_json_error();
			wp_die();
		}

		wp_send_json_error();
		wp_die();
	}

	/**
	 * Handles the update of a layout.
	 *
	 * @access private
	 * @since 2.2
	 */
	public function update_layout() {

		check_ajax_referer( 'fusion_tb_new_layout', 'security' );
		// Initial checks.
		if ( ! isset( $_POST['action_type'] ) ) {
			wp_send_json_error( esc_html( 'Missing action_type' ) );
		}

		$id          = isset( $_POST['layout_id'] ) ? sanitize_text_field( wp_unslash( $_POST['layout_id'] ) ) : false;
		$action_type = sanitize_text_field( wp_unslash( $_POST['action_type'] ) );
		$term_name   = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		switch ( $action_type ) {
			case 'update_title':
				if ( isset( $_POST['title'] ) && $id ) {
					Fusion_Template_Builder::update_layout_title( $id, $_POST['title'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					wp_send_json_success();
				}
				break;

			case 'create_template':
				if ( isset( $_POST['name'] ) && $id && $term_name ) {
					$template    = [
						'post_title'  => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
						'post_status' => 'publish',
						'post_type'   => 'fusion_tb_section',
					];
					$template_id = wp_insert_post( $template, true );

					if ( is_wp_error( $template_id ) ) {
						$error_string = $template_id->get_error_message();
						wp_send_json_error( esc_html( $error_string ) );
					}

					$template_type = wp_set_object_terms( $template_id, $term_name, 'fusion_tb_category' );
					if ( is_wp_error( $template_type ) ) {
						$error_string = $template_type->get_error_message();
						wp_send_json_error( esc_html( $error_string ) );
					}

					$content                                 = ( isset( $_POST['content'] ) ) ? $_POST['content'] : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$content['template_terms'][ $term_name ] = $template_id;
					wp_send_json_success(
						[
							'content'   => Fusion_Template_Builder::update_layout_content( $id, $content ),
							'templates' => Fusion_Template_Builder()->get_templates_by_term(),
						]
					);
				}
				break;

			case 'update_layout':
				if ( isset( $_POST['layout_id'] ) && isset( $_POST['content'] ) && $id ) {
					wp_send_json_success(
						[
							'content' => Fusion_Template_Builder::update_layout_content( $id, $_POST['content'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						]
					);
				}
				break;

			case 'update_layouts':
				if ( isset( $_POST['layouts'] ) ) {
					$layouts = wp_unslash( $_POST['layouts'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$data    = [];
					foreach ( $layouts as $layout_id => $layout ) {
						$data[ $layout_id ] = Fusion_Template_Builder::update_layout_content( $layout_id, $layout );
					}
					wp_send_json_success( $data );
				}
				break;

			default:
				break;
		}
		wp_send_json_error( esc_html( 'Invalid action_type or missing layout_id' ) );
	}

	/**
	 * Ajax callback to get the layout options.
	 *
	 * @access public
	 * @since 2.2
	 * @return void
	 */
	public function get_layout_options() {
		check_ajax_referer( 'fusion_tb_new_layout', 'security' );

		$parent = isset( $_POST['parent'] ) ? sanitize_text_field( wp_unslash( $_POST['parent'] ) ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$page   = isset( $_POST['page'] ) ? (int) $_POST['page'] : 1;

		$conditions = Fusion_Template_Builder()->get_layout_child_conditions( $parent, $page, $search );

		wp_send_json_success( array_values( $conditions ) );
	}

	/**
	 * Check all elements on content.
	 *
	 * @access public
	 * @since 3.0
	 * @return void
	 */
	public function check_elements() {
		check_ajax_referer( 'fusion_import_nonce', 'fusion_import_nonce' );

		if ( class_exists( 'Avada' ) && file_exists( Avada::$template_dir_path . '/includes/class-awb-performance-wizard.php' ) ) {
			include_once Avada::$template_dir_path . '/includes/class-awb-performance-wizard.php';
			AWB_Performance_Wizard()->element_scan();
			wp_die();
		}

		wp_send_json_error( new WP_Error( 404, __( 'Avada performance wizard is missing.', 'Fusion-Builder' ) ) );
	}

	/**
	 * Updates the page template post meta when a page gets created on back-end through WP autosavee when live editor button is clicked.
	 *
	 * @since 3.8.1
	 * @access public
	 * @return void
	 */
	public function update_page_template_post_meta() {

		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

		if ( isset( $_POST['post_id'] ) && '' !== $_POST['post_id'] && current_user_can( 'edit_post', $_POST['post_id'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$post = get_post( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );

			if ( 'page' === $post->post_type && class_exists( 'Avada' ) && '100_width' === Avada()->settings->get( 'page_template' ) ) {
				update_post_meta( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ), '_wp_page_template', '100-width.php' );
			}
		}

		wp_die();
	}

	/**
	 * Display a dismissible critical CSS message.
	 *
	 * @since 7.10
	 */
	public function display_critical_css_disabled_admin_notice() {
		global $current_user;

		if ( false === $current_user || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$fusion_settings                = awb_get_fusion_settings();
		$critical_css_used_and_disabled = ( (int) $fusion_settings->get( 'critical_css' ) && (int) get_option( 'awb_disable_critical_css' ) );
		if ( ! $critical_css_used_and_disabled ) {
			return;
		}

		$current_screen         = get_current_screen();
		$is_avada_critical_page = ( is_object( $current_screen ) && 'avada_page_avada-critical' === $current_screen->id );
		if ( $is_avada_critical_page ) {
			return;
		}

		$id      = 'awb_critical_css_disable_notice__' . str_replace( '.', '', FUSION_BUILDER_VERSION );
		$message = esc_html( wptexturize( __( 'Avada Critical CSS has been disabled. This happens automatically, because after the theme has been updated, the old CSS can cause artifacts on your pages. You need to go to "Critical CSS" page to enable it.', 'fusion-builder' ) ) );
		new Fusion_Admin_Notice( $id, $message, true, 'warning', true, 'user_meta', $id );
	}

	/**
	 * Add Avada to the RankMaths ToC plugins.
	 *
	 * @access public
	 * @since 7.12
	 * @param array $toc_plugins The registered ToC plugins.
	 * @return array The updated ToC plugin array.
	 */
	public function add_rankmath_toc_plugin( $toc_plugins ) {
		global $post;

		$content  = isset( $post->post_content ) ? $post->post_content : '';
		$override = Fusion_Template_Builder()->get_override( 'content' );
		$content .= isset( $override->post_content ) ? $override->post_content : $content;

		if ( $content ) {
			preg_match( '/\[fusion_table_of_contents/', $content, $toc_match );

			if ( ! empty( $toc_match ) ) {
				$toc_plugins['fusion-builder/fusion-builder.php'] = 'Avada Website Builder';
			}
		}

		return $toc_plugins;
	}
}
new Fusion_Builder_Admin();
