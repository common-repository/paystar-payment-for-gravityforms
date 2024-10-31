<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
Plugin Name: paystar-payment-for-gravityforms
Plugin URI: https://paystar.ir
Description: paystar-payment-for-gravityforms
Version: 1.0
Author: پی استار
Text Domain: paystar-payment-for-gravityforms
Domain Path: /languages
 */


load_plugin_textdomain('paystar-payment-for-gravityforms', false, basename(dirname(__FILE__)) . '/languages');
register_activation_hook( __FILE__, array( 'GFPersian_Gateway_PayStar', "add_permissions" ) );
add_action( 'init', array( 'GFPersian_Gateway_PayStar', 'init' ) );
__('paystar-payment-for-gravityforms', 'paystar-payment-for-gravityforms');

require_once( 'database.php' );
require_once( 'chart.php' );

class GFPersian_Gateway_PayStar {

	//Dont Change this Parameter if you are legitimate !!!
	public static $author = "HANNANStd";

	private static $version = "2.3.0";
	private static $min_gravityforms_version = "1.9.10";
	private static $config = null;

	public static function init() {
		if ( ! class_exists( "GFPersian_Payments" ) || ! defined( 'GF_PERSIAN_VERSION' ) || version_compare( GF_PERSIAN_VERSION, '2.3.1', '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice_persian_gf' ) );
			return false;
		}
		if ( ! self::is_gravityforms_supported() ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice_gf_support' ) );
			return false;
		}
		add_filter( 'members_get_capabilities', array( __CLASS__, "members_get_capabilities" ) );
		if ( is_admin() && self::has_access() ) {
			add_filter( 'gform_tooltips', array( __CLASS__, 'tooltips' ) );
			add_filter( 'gform_addon_navigation', array( __CLASS__, 'menu' ) );
			add_action( 'gform_entry_info', array( __CLASS__, 'payment_entry_detail' ), 4, 2 );
			add_action( 'gform_after_update_entry', array( __CLASS__, 'update_payment_entry' ), 4, 2 );
			if ( get_option( "gf_paystar_configured" ) ) {
				add_filter( 'gform_form_settings_menu', array( __CLASS__, 'toolbar' ), 10, 2 );
				add_action( 'gform_form_settings_page_paystar', array( __CLASS__, 'feed_page' ) );
			}
			if ( rgget( "page" ) == "gf_settings" ) {
				RGForms::add_settings_page( array(
						'name'      => 'gf_paystar',
						'tab_label' => __( 'PayStar', 'paystar-payment-for-gravityforms' ),
						'title'     => __( 'PayStar Configuration settings', 'paystar-payment-for-gravityforms' ),
						'handler'   => array( __CLASS__, 'settings_page' ),
					)
				);
			}
			if ( self::is_paystar_page() ) {
				wp_enqueue_script( array( "sack" ) );
				self::setup();
			}
			add_action( 'wp_ajax_gf_paystar_update_feed_active', array( __CLASS__, 'update_feed_active' ) );
		}
		if ( get_option( "gf_paystar_configured" ) ) {
			add_filter( "gform_disable_post_creation", array( __CLASS__, "delay_posts" ), 10, 3 );
			add_filter( "gform_is_delayed_pre_process_feed", array( __CLASS__, "delay_addons" ), 10, 4 );
			add_filter( "gform_confirmation", array( __CLASS__, "Request" ), 1000, 4 );
			add_action( 'wp', array( __CLASS__, 'Verify' ), 5 );
		}
		add_filter( "gform_logging_supported", array( __CLASS__, "set_logging_supported" ) );
		add_filter( 'gf_payment_gateways', array( __CLASS__, 'paystar-payment-for-gravityforms' ), 2 );
		do_action( 'gravityforms_gateways' );
		do_action( 'gravityforms_paystar' );
	}

	public static function admin_notice_persian_gf() {
		$class   = 'notice notice-error';
		$message = sprintf( __( "to use this plugin, you have to install persian gravity forms version 2.3.1 or newer. to install %s click here %s.", 'paystar-payment-for-gravityforms' ), '<a href="' . admin_url( "plugin-install.php?tab=plugin-information&plugin=persian-gravity-forms&TB_iframe=true&width=772&height=884" ) . '">', '</a>' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}

	public static function admin_notice_gf_support() {
		$class   = 'notice notice-error';
		$message = sprintf( __( "to use this plugin, you have to install gravity forms version %s or newer. to update gravity forms %s click here %s.", 'paystar-payment-for-gravityforms' ), self::$min_gravityforms_version, "<a href='http://gravityforms.ir/11378' target='_blank'>", "</a>" );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}

	public static function gravityformspaystar( $form, $entry ) {
		$paystar = array(
			'class' => ( __CLASS__ . '|' . self::$author ),
			'title' => __( 'PayStar', 'paystar-payment-for-gravityforms' ),
			'param' => array(
				'name'   => __( 'Name', 'paystar-payment-for-gravityforms' ),
				'email'  => __( 'Email', 'paystar-payment-for-gravityforms' ),
				'mobile' => __( 'Mobile', 'paystar-payment-for-gravityforms' ),
				'desc'   => __( 'Description', 'paystar-payment-for-gravityforms' )
			)
		);
		return apply_filters( self::$author . '_gf_paystar_detail', apply_filters( self::$author . '_gf_gateway_detail', $paystar, $form, $entry ), $form, $entry );
	}

	public static function add_permissions() {
		global $wp_roles;
		$editable_roles = get_editable_roles();
		foreach ( (array) $editable_roles as $role => $details ) {
			if ( $role == 'administrator' || in_array( 'gravityforms_edit_forms', $details['capabilities'] ) ) {
				$wp_roles->add_cap( $role, 'gravityforms_paystar' );
				$wp_roles->add_cap( $role, 'gravityforms_paystar_uninstall' );
			}
		}
	}

	public static function members_get_capabilities( $caps ) {
		return array_merge( $caps, array( "gravityforms_paystar", "gravityforms_paystar_uninstall" ) );
	}

	private static function setup() {
		if ( get_option( "gf_paystar_version" ) != self::$version ) {
			GFPersian_DB_PayStar::update_table();
			update_option( "gf_paystar_version", self::$version );
		}
	}

	public static function tooltips( $tooltips ) {
		$tooltips["gateway_name"] = __( "BE CAREFULL!!! this part of settings is to display to customers. to pervent any problems, set it up and dont change it again.", 'paystar-payment-for-gravityforms' );
		return $tooltips;
	}

	public static function menu( $menus ) {
		$permission = "gravityforms_paystar";
		if ( ! empty( $permission ) ) {
			$menus[] = array(
				"name"       => "gf_paystar",
				"label"      => __( "PayStar", 'paystar-payment-for-gravityforms' ),
				"callback"   => array( __CLASS__, "paystar_page" ),
				"permission" => $permission
			);
		}
		return $menus;
	}

	public static function toolbar( $menu_items ) {
		$menu_items[] = array(
			'name'  => 'paystar',
			'label' => __( 'PayStar', 'paystar-payment-for-gravityforms' )
		);
		return $menu_items;
	}

	private static function is_gravityforms_supported() {
		if ( class_exists( "GFCommon" ) ) {
			$is_correct_version = version_compare( GFCommon::$version, self::$min_gravityforms_version, ">=" );
			return $is_correct_version;
		} else {
			return false;
		}
	}

	protected static function has_access( $required_permission = 'gravityforms_paystar' ) {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include( ABSPATH . "wp-includes/pluggable.php" );
		}
		return GFCommon::current_user_can_any( $required_permission );
	}

	protected static function get_base_url() {
		return plugins_url( null, __FILE__ );
	}

	protected static function get_base_path() {
		$folder = basename( dirname( __FILE__ ) );
		return WP_PLUGIN_DIR . "/" . $folder;
	}

	public static function set_logging_supported( $plugins ) {
		$plugins[ basename( dirname( __FILE__ ) ) ] = "PayStar";
		return $plugins;
	}

	public static function uninstall() {
		if ( ! self::has_access( "gravityforms_paystar_uninstall" ) ) {
			die( __( "You do not have sufficient permissions to do so. Your access level is lower than allowed", 'paystar-payment-for-gravityforms' ) );
		}
		GFPersian_DB_PayStar::drop_tables();
		delete_option( "gf_paystar_settings" );
		delete_option( "gf_paystar_configured" );
		delete_option( "gf_paystar_version" );
		$plugin = basename( dirname( __FILE__ ) ) . "/index.php";
		deactivate_plugins( $plugin );
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	private static function is_paystar_page() {
		$current_page    = in_array( trim( strtolower( rgget( "page" ) ) ), array( 'gf_paystar', 'paystar' ) );
		$current_view    = in_array( trim( strtolower( rgget( "view" ) ) ), array( 'gf_paystar', 'paystar' ) );
		$current_subview = in_array( trim( strtolower( rgget( "subview" ) ) ), array( 'gf_paystar', 'paystar' ) );
		return $current_page || $current_view || $current_subview;
	}

	public static function feed_page() {
		GFFormSettings::page_header(); ?>
        <h3>
			<span><i class="fa fa-credit-card"></i> <?php esc_html_e( 'PayStar', 'paystar-payment-for-gravityforms' ) ?>
                <a id="add-new-confirmation" class="add-new-h2"
                   href="<?php echo esc_url( admin_url( 'admin.php?page=gf_paystar&view=edit&fid=' . absint( rgget( "id" ) ) ) ) ?>"><?php esc_html_e( 'Add New Feed', 'paystar-payment-for-gravityforms' ) ?></a></span>
            <a class="add-new-h2"
               href="admin.php?page=gf_paystar&view=stats&id=<?php echo absint( rgget( "id" ) ) ?>"><?php _e( "charts", 'paystar-payment-for-gravityforms' ) ?></a>
        </h3>
		<?php self::list_page( 'per-form' ); ?>
		<?php GFFormSettings::page_footer();
	}

	public static function has_paystar_condition( $form, $config ) {
		if ( empty( $config['meta'] ) ) {
			return false;
		}
		if ( empty( $config['meta']['paystar_conditional_enabled'] ) ) {
			return true;
		}
		if ( ! empty( $config['meta']['paystar_conditional_field_id'] ) ) {
			$condition_field_ids = $config['meta']['paystar_conditional_field_id'];
			if ( ! is_array( $condition_field_ids ) ) {
				$condition_field_ids = array( '1' => $condition_field_ids );
			}
		} else {
			return true;
		}
		if ( ! empty( $config['meta']['paystar_conditional_value'] ) ) {
			$condition_values = $config['meta']['paystar_conditional_value'];
			if ( ! is_array( $condition_values ) ) {
				$condition_values = array( '1' => $condition_values );
			}
		} else {
			$condition_values = array( '1' => '' );
		}
		if ( ! empty( $config['meta']['paystar_conditional_operator'] ) ) {
			$condition_operators = $config['meta']['paystar_conditional_operator'];
			if ( ! is_array( $condition_operators ) ) {
				$condition_operators = array( '1' => $condition_operators );
			}
		} else {
			$condition_operators = array( '1' => 'is' );
		}
		$type = ! empty( $config['meta']['paystar_conditional_type'] ) ? strtolower( $config['meta']['paystar_conditional_type'] ) : '';
		$type = $type == 'all' ? 'all' : 'any';
		foreach ( $condition_field_ids as $i => $field_id ) {
			if ( empty( $field_id ) ) {
				continue;
			}
			$field = RGFormsModel::get_field( $form, $field_id );
			if ( empty( $field ) ) {
				continue;
			}
			$value    = ! empty( $condition_values[ '' . $i . '' ] ) ? $condition_values[ '' . $i . '' ] : '';
			$operator = ! empty( $condition_operators[ '' . $i . '' ] ) ? $condition_operators[ '' . $i . '' ] : 'is';
			$is_visible     = ! RGFormsModel::is_field_hidden( $form, $field, array() );
			$field_value    = RGFormsModel::get_field_value( $field, array() );
			$is_value_match = RGFormsModel::is_value_match( $field_value, $value, $operator );
			$check          = $is_value_match && $is_visible;
			if ( $type == 'any' && $check ) {
				return true;
			} else if ( $type == 'all' && ! $check ) {
				return false;
			}
		}
		if ( $type == 'any' ) {
			return false;
		} else {
			return true;
		}
	}

	public static function get_config_by_entry( $entry ) {
		$feed_id = gform_get_meta( $entry["id"], "paystar_feed_id" );
		$feed    = ! empty( $feed_id ) ? GFPersian_DB_PayStar::get_feed( $feed_id ) : '';
		$return  = ! empty( $feed ) ? $feed : false;
		return apply_filters( self::$author . '_gf_paystar_get_config_by_entry', apply_filters( self::$author . '_gf_gateway_get_config_by_entry', $return, $entry ), $entry );
	}

	public static function delay_posts( $is_disabled, $form, $entry ) {
		$config = self::get_active_config( $form );
		if ( ! empty( $config ) && is_array( $config ) && $config ) {
			return true;
		}
		return $is_disabled;
	}

	public static function delay_addons( $is_delayed, $form, $entry, $slug ) {
		$config = self::get_active_config( $form );
		if ( ! empty( $config["meta"] ) && is_array( $config["meta"] ) && $config = $config["meta"] ) {
			$user_registration_slug = apply_filters( 'gf_user_registration_slug', 'gravityformsuserregistration' );
			if ( $slug != $user_registration_slug && ! empty( $config["addon"] ) && $config["addon"] == 'true' ) {
				$flag = true;
			} elseif ( $slug == $user_registration_slug && ! empty( $config["type"] ) && $config["type"] == "subscription" ) {
				$flag = true;
			}
			if ( ! empty( $flag ) ) {
				$fulfilled = gform_get_meta( $entry['id'], $slug . '_is_fulfilled' );
				$processed = gform_get_meta( $entry['id'], 'processed_feeds' );
				$is_delayed = empty( $fulfilled ) && rgempty( $slug, $processed );
			}
		}
		return $is_delayed;
	}

	private static function redirect_confirmation( $url, $ajax ) {
		if ( headers_sent() || $ajax ) {
			$confirmation = "<script type=\"text/javascript\">" . apply_filters( 'gform_cdata_open', '' ) . " function gformRedirect(){document.location.href='$url';}";
			if ( ! $ajax ) {
				$confirmation .= 'gformRedirect();';
			}
			$confirmation .= apply_filters( 'gform_cdata_close', '' ) . '</script>';
		} else {
			$confirmation = array( 'redirect' => $url );
		}
		return $confirmation;
	}

	public static function get_active_config( $form ) {
		if ( ! empty( self::$config ) ) {
			return self::$config;
		}
		$configs = GFPersian_DB_PayStar::get_feed_by_form( $form["id"], true );
		$configs = apply_filters( self::$author . '_gf_paystar_get_active_configs', apply_filters( self::$author . '_gf_gateway_get_active_configs', $configs, $form ), $form );
		$return = false;
		if ( ! empty( $configs ) && is_array( $configs ) ) {
			foreach ( $configs as $config ) {
				if ( self::has_paystar_condition( $form, $config ) ) {
					$return = $config;
				}
				break;
			}
		}
		self::$config = apply_filters( self::$author . '_gf_paystar_get_active_config', apply_filters( self::$author . '_gf_gateway_get_active_config', $return, $form ), $form );
		return self::$config;
	}

	public static function paystar_page() {
		$view = sanitize_text_field(rgget( "view" ));
		if ( $view == "edit" ) {
			self::config_page();
		} else if ( $view == "stats" ) {
			GFPersian_Chart_PayStar::stats_page();
		} else {
			self::list_page( '' );
		}
	}

	private static function list_page( $arg ) {
		if ( ! self::is_gravityforms_supported() ) {
			die( sprintf( __( "this plugin require gravityforms version %s . to update gravityforms go to %s gravityforms persian website %s ", 'paystar-payment-for-gravityforms' ), self::$min_gravityforms_version, "<a href='http://gravityforms.ir/11378' target='_blank'>", "</a>" ) );
		}
		if ( rgpost( 'action' ) == "delete" ) {
			check_admin_referer( "list_action", "gf_paystar_list" );
			$id = absint( rgpost( "action_argument" ) );
			GFPersian_DB_PayStar::delete_feed( $id );
			?>
         <div class="updated fade" style="padding:6px"><?php _e( "feed deleted", 'paystar-payment-for-gravityforms' ) ?></div><?php
		} else if ( ! empty( $_POST["bulk_action"] ) ) {
			check_admin_referer( "list_action", "gf_paystar_list" );
			$selected_feeds = sanitize_text_field(rgpost( "feed" ));
			if ( is_array( $selected_feeds ) ) {
				foreach ( $selected_feeds as $feed_id ) {
					GFPersian_DB_PayStar::delete_feed( $feed_id );
				}
			}
			?>
         <div class="updated fade" style="padding:6px"><?php _e( "feeds deleted", 'paystar-payment-for-gravityforms' ) ?></div>
			<?php
		}
		?>
        <div class="wrap">
			<?php if ( $arg != 'per-form' ) { ?>
                <h2>
					<?php _e( "PayStar Forms", 'paystar-payment-for-gravityforms' );
					if ( get_option( "gf_paystar_configured" ) ) { ?>
                        <a class="add-new-h2"
                           href="admin.php?page=gf_paystar&view=edit"><?php _e( "Add New", 'paystar-payment-for-gravityforms' ) ?></a>
						<?php
					} ?>
                </h2>
			<?php } ?>
            <form id="confirmation_list_form" method="post">
				<?php wp_nonce_field( 'list_action', 'gf_paystar_list' ) ?>
                <input type="hidden" id="action" name="action"/>
                <input type="hidden" id="action_argument" name="action_argument"/>
                <div class="tablenav">
                    <div class="alignleft actions" style="padding:8px 0 7px 0;">
                        <label class="hidden"
                               for="bulk_action"><?php _e( "Bulk Action", 'paystar-payment-for-gravityforms' ) ?></label>
                        <select name="bulk_action" id="bulk_action">
                            <option value=''> <?php _e( "Bulk Actions", 'paystar-payment-for-gravityforms' ) ?> </option>
                            <option value='delete'><?php _e( "Delete", 'paystar-payment-for-gravityforms' ) ?></option>
                        </select>
						<?php
						echo '<input type="submit" class="button" value="' . __( "Apply", 'paystar-payment-for-gravityforms' ) . '" onclick="if( jQuery(\'#bulk_action\').val() == \'delete\' && !confirm(\'' . __( "are you sure?", 'paystar-payment-for-gravityforms' ) . '\')) { return false; } return true;"/>';
						?>
                        <a class="button button-primary" href="admin.php?page=gf_settings&subview=gf_paystar"><?php _e( 'PayStar Configuration settings', 'paystar-payment-for-gravityforms' ) ?></a>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped toplevel_page_gf_edit_forms" cellspacing="0">
                    <thead>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column"
                            style="padding:13px 3px;width:30px"><input type="checkbox"/></th>
                        <th scope="col" id="active" class="manage-column"
                            style="width:<?php echo $arg != 'per-form' ? '50px' : '20px' ?>"><?php echo $arg != 'per-form' ? __( 'Status', 'paystar-payment-for-gravityforms' ) : '' ?></th>
                        <th scope="col" class="manage-column"
                            style="width:<?php echo $arg != 'per-form' ? '65px' : '30%' ?>"><?php _e( "feed id", 'paystar-payment-for-gravityforms' ) ?></th>
						<?php if ( $arg != 'per-form' ) { ?>
                            <th scope="col"
                                class="manage-column"><?php _e( "form connected to gateway", 'paystar-payment-for-gravityforms' ) ?></th>
						<?php } ?>
                        <th scope="col" class="manage-column"><?php _e( "Transaction type", 'paystar-payment-for-gravityforms' ) ?></th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th scope="col" id="cb" class="manage-column column-cb check-column" style="padding:13px 3px;">
                            <input type="checkbox"/></th>
                        <th scope="col" id="active"
                            class="manage-column"><?php echo $arg != 'per-form' ? __( 'Status', 'paystar-payment-for-gravityforms' ) : '' ?></th>
                        <th scope="col" class="manage-column"><?php _e( "feed id", 'paystar-payment-for-gravityforms' ) ?></th>
						<?php if ( $arg != 'per-form' ) { ?>
                            <th scope="col"
                                class="manage-column"><?php _e( "form connected to gateway", 'paystar-payment-for-gravityforms' ) ?></th>
						<?php } ?>
                        <th scope="col" class="manage-column"><?php _e( "Transaction type", 'paystar-payment-for-gravityforms' ) ?></th>
                    </tr>
                    </tfoot>
                    <tbody class="list:user user-list">
					<?php
					if ( $arg != 'per-form' ) {
						$settings = GFPersian_DB_PayStar::get_feeds();
					} else {
						$settings = GFPersian_DB_PayStar::get_feed_by_form( sanitize_text_field(rgget( 'id' )), false );
					}
					if ( ! get_option( "gf_paystar_configured" ) ) {
						?>
                        <tr>
                            <td colspan="5" style="padding:20px;">
								<?php echo sprintf( __( "to start, you have to enable gateway. go to  %s PayStar Configuration settings %s ", 'paystar-payment-for-gravityforms' ), '<a href="admin.php?page=gf_settings&subview=gf_paystar">', "</a>" ); ?>
                            </td>
                        </tr>
						<?php
					} else if ( is_array( $settings ) && sizeof( $settings ) > 0 ) {
						foreach ( $settings as $setting ) {
							?>
                            <tr class='author-self status-inherit' valign="top">
                                <th scope="row" class="check-column"><input type="checkbox" name="feed[]" value="<?php echo esc_html($setting["id"]) ?>"/></th>
                                <td><img style="cursor:pointer;width:25px"
                                         src="<?php echo esc_url( GFCommon::get_base_url() ) ?>/images/active<?php echo intval( $setting["is_active"] ) ?>.png"
                                         alt="<?php echo $setting["is_active"] ? __( "Gateway is Enable", 'paystar-payment-for-gravityforms' ) : __( "Gateway is Disable", 'paystar-payment-for-gravityforms' ); ?>"
                                         title="<?php echo $setting["is_active"] ? __( "Gateway is Enable", 'paystar-payment-for-gravityforms' ) : __( "Gateway is Disable", 'paystar-payment-for-gravityforms' ); ?>"
                                         onclick="ToggleActive(this, <?php echo esc_html($setting['id']) ?>); "/></td>
                                <td><?php echo esc_html($setting["id"]) ?>
									<?php if ( $arg == 'per-form' ) { ?>
                                        <div class="row-actions">
                                                <span class="edit">
                                                    <a title="<?php _e( "edit feed", 'paystar-payment-for-gravityforms' ) ?>"
                                                       href="admin.php?page=gf_paystar&view=edit&id=<?php echo esc_html($setting["id"]) ?>"><?php _e( "edit feed", 'paystar-payment-for-gravityforms' ) ?></a>
                                                    |
                                                </span>
                                            <span class="trash">
                                                    <a title="<?php _e( "Delete", 'paystar-payment-for-gravityforms' ) ?>"
                                                       href="javascript: if(confirm('<?php _e( "are you sure?", 'paystar-payment-for-gravityforms' ) ?>')){ DeleteSetting(<?php echo esc_html($setting["id"]) ?>);}"><?php _e( "Delete", 'paystar-payment-for-gravityforms' ) ?></a>
                                                </span>
                                        </div>
									<?php } ?>
                                </td>
								<?php if ( $arg != 'per-form' ) { ?>
                                    <td class="column-title">
                                        <strong><a class="row-title"
                                                   href="admin.php?page=gf_paystar&view=edit&id=<?php echo esc_html($setting["id"]) ?>"
                                                   title="<?php _e( "reconfiguration Gateway settings", 'paystar-payment-for-gravityforms' ) ?>"><?php echo esc_html($setting["form_title"]) ?></a></strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a title="<?php _e( "edit feed", 'paystar-payment-for-gravityforms' ) ?>"
                                                   href="admin.php?page=gf_paystar&view=edit&id=<?php echo esc_html($setting["id"]) ?>"><?php _e( "edit feed", 'paystar-payment-for-gravityforms' ) ?></a>
                                                |
                                            </span>
                                            <span class="trash">
                                                <a title="<?php _e( "delete feed", 'paystar-payment-for-gravityforms' ) ?>"
                                                   href="javascript: if(confirm('<?php _e( "are you sure?", 'paystar-payment-for-gravityforms' ) ?>')){ DeleteSetting(<?php echo esc_html($setting["id"]) ?>);}"><?php _e( "Delete", 'paystar-payment-for-gravityforms' ) ?></a>
                                                |
                                            </span>
                                            <span class="view">
                                                <a title="<?php _e( "edit form", 'paystar-payment-for-gravityforms' ) ?>"
                                                   href="admin.php?page=gf_edit_forms&id=<?php echo esc_html($setting["form_id"]) ?>"><?php _e( "edit forms", 'paystar-payment-for-gravityforms' ) ?></a>
                                                |
                                            </span>
                                            <span class="view">
                                                <a title="<?php _e( "show entries", 'paystar-payment-for-gravityforms' ) ?>"
                                                   href="admin.php?page=gf_entries&view=entries&id=<?php echo esc_html($setting["form_id"]) ?>"><?php _e( "show entries", 'paystar-payment-for-gravityforms' ) ?></a>
                                                |
                                            </span>
                                            <span class="view">
                                                <a title="<?php _e( "form charts", 'paystar-payment-for-gravityforms' ) ?>"
                                                   href="admin.php?page=gf_paystar&view=stats&id=<?php echo esc_html($setting["form_id"]) ?>"><?php _e( "form charts", 'paystar-payment-for-gravityforms' ) ?></a>
                                            </span>
                                        </div>
                                    </td>
								<?php } ?>
                                <td class="column-date">
									<?php
									if ( isset( $setting["meta"]["type"] ) && $setting["meta"]["type"] == 'subscription' ) {
										_e( "registration", 'paystar-payment-for-gravityforms' );
									} else {
										_e( "simple product or submit post form", 'paystar-payment-for-gravityforms' );
									}
									?>
                                </td>
                            </tr>
							<?php
						}
					} else {
						?>
                        <tr>
                            <td colspan="5" style="padding:20px;">
								<?php
								if ( $arg == 'per-form' ) {
									echo sprintf( __( "you have not any paystar feed . %s create one %s .", 'paystar-payment-for-gravityforms' ), '<a href="admin.php?page=gf_paystar&view=edit&fid=' . absint( rgget( "id" ) ) . '">', "</a>" );
								} else {
									echo sprintf( __( "you have not any paystar feed . %s create one %s .", 'paystar-payment-for-gravityforms' ), '<a href="admin.php?page=gf_paystar&view=edit">', "</a>" );
								}
								?>
                            </td>
                        </tr>
						<?php
					}
					?>
                    </tbody>
                </table>
            </form>
        </div>
        <script type="text/javascript">
            function DeleteSetting(id) {
                jQuery("#action_argument").val(id);
                jQuery("#action").val("delete");
                jQuery("#confirmation_list_form")[0].submit();
            }
            function ToggleActive(img, feed_id) {
                var is_active = img.src.indexOf("active1.png") >= 0;
                if (is_active) {
                    img.src = img.src.replace("active1.png", "active0.png");
                    jQuery(img).attr('title', '<?php _e( "Gateway is Disable", 'paystar-payment-for-gravityforms' ) ?>').attr('alt', '<?php _e( "Gateway is Disable", 'paystar-payment-for-gravityforms' ) ?>');
                }
                else {
                    img.src = img.src.replace("active0.png", "active1.png");
                    jQuery(img).attr('title', '<?php _e( "Gateway is Enable", 'paystar-payment-for-gravityforms' ) ?>').attr('alt', '<?php _e( "Gateway is Enable", 'paystar-payment-for-gravityforms' ) ?>');
                }
                var mysack = new sack(ajaxurl);
                mysack.execute = 1;
                mysack.method = 'POST';
                mysack.setVar("action", "gf_paystar_update_feed_active");
                mysack.setVar("gf_paystar_update_feed_active", "<?php echo wp_create_nonce( "gf_paystar_update_feed_active" ) ?>");
                mysack.setVar("feed_id", feed_id);
                mysack.setVar("is_active", is_active ? 0 : 1);
                mysack.onError = function () {
                    alert('<?php _e( "an Ajax Error occurred", 'paystar-payment-for-gravityforms' ) ?>')
                };
                mysack.runAJAX();
                return true;
            }
        </script>
		<?php
	}

	public static function update_feed_active() {
		check_ajax_referer( 'gf_paystar_update_feed_active', 'gf_paystar_update_feed_active' );
		$id   = absint( rgpost( 'feed_id' ) );
		$feed = GFPersian_DB_PayStar::get_feed( $id );
		GFPersian_DB_PayStar::update_feed( $id, $feed["form_id"], sanitize_text_field($_POST["is_active"]), $feed["meta"] );
	}

	private static function Return_URL( $form_id, $entry_id ) {
		$pageURL = GFCommon::is_ssl() ? 'https://' : 'http://';
		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		$arr_params = array( 'id', 'entry', 'no' );
		$pageURL    = esc_url( remove_query_arg( $arr_params, $pageURL ) );
		$pageURL = str_replace( '#038;', '&', add_query_arg( array(
			'id'    => $form_id,
			'entry' => $entry_id
		), $pageURL ) );
		return apply_filters( self::$author . '_paystar_return_url', apply_filters( self::$author . '_gateway_return_url', $pageURL, $form_id, $entry_id, __CLASS__ ), $form_id, $entry_id, __CLASS__ );
	}

	public static function get_order_total( $form, $entry ) {
		$total = GFCommon::get_order_total( $form, $entry );
		$total = ( ! empty( $total ) && $total > 0 ) ? $total : 0;
		return apply_filters( self::$author . '_paystar_get_order_total', apply_filters( self::$author . '_gateway_get_order_total', $total, $form, $entry ), $form, $entry );
	}

	private static function get_mapped_field_list( $field_name, $selected_field, $fields ) {
		$str = "<select name='$field_name' id='$field_name'><option value=''></option>";
		if ( is_array( $fields ) ) {
			foreach ( $fields as $field ) {
				$field_id    = $field[0];
				$field_label = esc_html( GFCommon::truncate_middle( $field[1], 40 ) );
				$selected    = $field_id == $selected_field ? "selected='selected'" : "";
				$str         .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . "</option>";
			}
		}
		$str .= "</select>";
		return $str;
	}

	private static function get_form_fields( $form ) {
		$fields = array();
		if ( is_array( $form["fields"] ) ) {
			foreach ( $form["fields"] as $field ) {
				if ( isset( $field["inputs"] ) && is_array( $field["inputs"] ) ) {
					foreach ( $field["inputs"] as $input ) {
						$fields[] = array( $input["id"], GFCommon::get_label( $field, $input["id"] ) );
					}
				} else if ( ! rgar( $field, 'displayOnly' ) ) {
					$fields[] = array( $field["id"], GFCommon::get_label( $field ) );
				}
			}
		}
		return $fields;
	}

	private static function get_customer_information_desc( $form, $config = null ) {
		$form_fields    = self::get_form_fields( $form );
		$selected_field = ! empty( $config["meta"]["customer_fields_desc"] ) ? $config["meta"]["customer_fields_desc"] : '';
		return self::get_mapped_field_list( 'paystar_customer_field_desc', $selected_field, $form_fields );
	}

	private static function get_customer_information_name( $form, $config = null ) {
		$form_fields    = self::get_form_fields( $form );
		$selected_field = ! empty( $config["meta"]["customer_fields_name"] ) ? $config["meta"]["customer_fields_name"] : '';
		return self::get_mapped_field_list( 'paystar_customer_field_name', $selected_field, $form_fields );
	}

	private static function get_customer_information_family( $form, $config = null ) {
		$form_fields    = self::get_form_fields( $form );
		$selected_field = ! empty( $config["meta"]["customer_fields_family"] ) ? $config["meta"]["customer_fields_family"] : '';
		return self::get_mapped_field_list( 'paystar_customer_field_family', $selected_field, $form_fields );
	}

	private static function get_customer_information_email( $form, $config = null ) {
		$form_fields    = self::get_form_fields( $form );
		$selected_field = ! empty( $config["meta"]["customer_fields_email"] ) ? $config["meta"]["customer_fields_email"] : '';
		return self::get_mapped_field_list( 'paystar_customer_field_email', $selected_field, $form_fields );
	}

	private static function get_customer_information_mobile( $form, $config = null ) {
		$form_fields    = self::get_form_fields( $form );
		$selected_field = ! empty( $config["meta"]["customer_fields_mobile"] ) ? $config["meta"]["customer_fields_mobile"] : '';
		return self::get_mapped_field_list( 'paystar_customer_field_mobile', $selected_field, $form_fields );
	}

	public static function payment_entry_detail( $form_id, $entry ) {
		$payment_gateway = rgar( $entry, "payment_method" );
		if ( ! empty( $payment_gateway ) && $payment_gateway == "paystar" ) {
			do_action( 'gf_gateway_entry_detail' );
			?>
            <hr/>
            <strong>
				<?php _e( 'Transaction Details :', 'paystar-payment-for-gravityforms' ) ?>
            </strong>
            <br/>
            <br/>
			<?php
			$transaction_type = rgar( $entry, "transaction_type" );
			$payment_status   = rgar( $entry, "payment_status" );
			$payment_amount   = rgar( $entry, "payment_amount" );
			if ( empty( $payment_amount ) ) {
				$form           = RGFormsModel::get_form_meta( $form_id );
				$payment_amount = self::get_order_total( $form, $entry );
			}
			$transaction_id = rgar( $entry, "transaction_id" );
			$payment_date   = rgar( $entry, "payment_date" );
			$date = new DateTime( $payment_date );
			$tzb  = get_option( 'gmt_offset' );
			$tzn  = abs( $tzb ) * 3600;
			$tzh  = intval( gmdate( "H", $tzn ) );
			$tzm  = intval( gmdate( "i", $tzn ) );
			if ( intval( $tzb ) < 0 ) {
				$date->sub( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			} else {
				$date->add( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			}
			$payment_date = $date->format( 'Y-m-d H:i:s' );
			$payment_date = GF_jdate( 'Y-m-d H:i:s', strtotime( $payment_date ), '', date_default_timezone_get(), 'en' );
			if ( $payment_status == 'Paid' ) {
				$payment_status_persian = __( 'Successful', 'paystar-payment-for-gravityforms' );
			}
			if ( $payment_status == 'Active' ) {
				$payment_status_persian = __( 'Successful', 'paystar-payment-for-gravityforms' );
			}
			if ( $payment_status == 'Cancelled' ) {
				$payment_status_persian = __( 'Cancelled', 'paystar-payment-for-gravityforms' );
			}
			if ( $payment_status == 'Failed' ) {
				$payment_status_persian = __( 'Unsuccessful', 'paystar-payment-for-gravityforms' );
			}
			if ( $payment_status == 'Processing' ) {
				$payment_status_persian = __( 'Processing', 'paystar-payment-for-gravityforms' );
			}
			if ( ! strtolower( rgpost( "save" ) ) || RGForms::post( "screen_mode" ) != "edit" ) {
				echo __( 'Payment Status : ', 'paystar-payment-for-gravityforms' ) . esc_html($payment_status_persian) . '<br/><br/>';
				echo __( 'Payment Date : ', 'paystar-payment-for-gravityforms' ) . '<span style="">' . esc_html($payment_date) . '</span><br/><br/>';
				echo __( 'Payment Amount : ', 'paystar-payment-for-gravityforms' ) . esc_html(GFCommon::to_money( $payment_amount, rgar( $entry, "currency" ) )) . '<br/><br/>';
				echo __( 'RefNum : ', 'paystar-payment-for-gravityforms' ) . esc_html($transaction_id) . '<br/><br/>';
				echo __( 'Payment Gateway: PayStar', 'paystar-payment-for-gravityforms' );
			} else {
				$payment_string = '';
				$payment_string .= '<select id="payment_status" name="payment_status">';
				$payment_string .= '<option value="' . $payment_status . '" selected>' . $payment_status_persian . '</option>';
				if ( $transaction_type == 1 ) {
					if ( $payment_status != "Paid" ) {
						$payment_string .= '<option value="Paid">' . __( 'Successful', 'paystar-payment-for-gravityforms' ) . '</option>';
					}
				}
				if ( $transaction_type == 2 ) {
					if ( $payment_status != "Active" ) {
						$payment_string .= '<option value="Active">' . __( 'Successful', 'paystar-payment-for-gravityforms' ) . '</option>';
					}
				}
				if ( ! $transaction_type ) {
					if ( $payment_status != "Paid" ) {
						$payment_string .= '<option value="Paid">' . __( 'Successful', 'paystar-payment-for-gravityforms' ) . '</option>';
					}
					if ( $payment_status != "Active" ) {
						$payment_string .= '<option value="Active">' . __( 'Successful', 'paystar-payment-for-gravityforms' ) . '</option>';
					}
				}
				if ( $payment_status != "Failed" ) {
					$payment_string .= '<option value="Failed">' . __( 'Unsuccessful', 'paystar-payment-for-gravityforms' ) . '</option>';
				}
				if ( $payment_status != "Cancelled" ) {
					$payment_string .= '<option value="Cancelled">' . __( 'Cancelled', 'paystar-payment-for-gravityforms' ) . '</option>';
				}
				if ( $payment_status != "Processing" ) {
					$payment_string .= '<option value="Processing">' . __( 'Processing', 'paystar-payment-for-gravityforms' ) . '</option>';
				}
				$payment_string .= '</select>';
				echo __( 'Payment Status :', 'paystar-payment-for-gravityforms' ) . esc_html($payment_string) . '<br/><br/>';
				?>
                <div id="edit_payment_status_details" style="display:block">
                    <table>
                        <tr>
                            <td><?php _e( 'Payment Date :', 'paystar-payment-for-gravityforms' ) ?></td>
                            <td><input type="text" id="payment_date" name="payment_date"
                                       value="<?php echo esc_html( $payment_date) ?>"></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Payment Amount :', 'paystar-payment-for-gravityforms' ) ?></td>
                            <td><input type="text" id="payment_amount" name="payment_amount"
                                       value="<?php echo esc_html( $payment_amount) ?>"></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Transaction ID :', 'paystar-payment-for-gravityforms' ) ?></td>
                            <td><input type="text" id="paystar_transaction_id" name="paystar_transaction_id"
                                       value="<?php echo esc_html( $transaction_id) ?>"></td>
                        </tr>
                    </table>
                    <br/>
                </div>
				<?php
				echo __( 'Payment Gateway : PayStar (not editable)', 'paystar-payment-for-gravityforms' );
			}
			echo '<br/>';
		}
	}

	public static function update_payment_entry( $form, $entry_id ) {
		check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );
		do_action( 'gf_gateway_update_entry' );
		$entry = GFPersian_Payments::get_entry( $entry_id );
		$payment_gateway = rgar( $entry, "payment_method" );
		if ( empty( $payment_gateway ) ) {
			return;
		}
		if ( $payment_gateway != "paystar" ) {
			return;
		}
		$payment_status = sanitize_text_field(rgpost( "payment_status" ));
		if ( empty( $payment_status ) ) {
			$payment_status = rgar( $entry, "payment_status" );
		}
		$payment_amount       = sanitize_text_field(rgpost( "payment_amount" ));
		$payment_transaction  = sanitize_text_field(rgpost( "paystar_transaction_id" ));
		$payment_date_Checker = $payment_date = sanitize_text_field(rgpost( "payment_date" ));
		list( $date, $time ) = explode( " ", $payment_date );
		list( $Y, $m, $d ) = explode( "-", $date );
		list( $H, $i, $s ) = explode( ":", $time );
		$miladi = GF_jalali_to_gregorian( $Y, $m, $d );
		$date         = new DateTime( "$miladi[0]-$miladi[1]-$miladi[2] $H:$i:$s" );
		$payment_date = $date->format( 'Y-m-d H:i:s' );
		if ( empty( $payment_date_Checker ) ) {
			if ( ! empty( $entry["payment_date"] ) ) {
				$payment_date = $entry["payment_date"];
			} else {
				$payment_date = rgar( $entry, "date_created" );
			}
		} else {
			$payment_date = date( "Y-m-d H:i:s", strtotime( $payment_date ) );
			$date         = new DateTime( $payment_date );
			$tzb          = get_option( 'gmt_offset' );
			$tzn          = abs( $tzb ) * 3600;
			$tzh          = intval( gmdate( "H", $tzn ) );
			$tzm          = intval( gmdate( "i", $tzn ) );
			if ( intval( $tzb ) < 0 ) {
				$date->add( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			} else {
				$date->sub( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			}
			$payment_date = $date->format( 'Y-m-d H:i:s' );
		}
		global $current_user;
		$user_id   = 0;
		$user_name = __( "Guest", 'paystar-payment-for-gravityforms' );
		if ( $current_user && $user_data = get_userdata( $current_user->ID ) ) {
			$user_id   = $current_user->ID;
			$user_name = $user_data->display_name;
		}
		$entry["payment_status"] = $payment_status;
		$entry["payment_amount"] = $payment_amount;
		$entry["payment_date"]   = $payment_date;
		$entry["transaction_id"] = $payment_transaction;
		if ( $payment_status == 'Paid' || $payment_status == 'Active' ) {
			$entry["is_fulfilled"] = 1;
		} else {
			$entry["is_fulfilled"] = 0;
		}
		GFAPI::update_entry( $entry );
		$new_status = '';
		switch ( rgar( $entry, "payment_status" ) ) {
			case "Active" :
				$new_status = __( 'Successful', 'paystar-payment-for-gravityforms' );
				break;
			case "Paid" :
				$new_status = __( 'Successful', 'paystar-payment-for-gravityforms' );
				break;
			case "Cancelled" :
				$new_status = __( 'Cancelled', 'paystar-payment-for-gravityforms' );
				break;
			case "Failed" :
				$new_status = __( 'Unsuccessful', 'paystar-payment-for-gravityforms' );
				break;
			case "Processing" :
				$new_status = __( 'Processing', 'paystar-payment-for-gravityforms' );
				break;
		}
		RGFormsModel::add_note( $entry["id"], $user_id, $user_name, sprintf( __( "Transaction information was edited manually. Status : %s - Price : %s - RefNum : %s - Date : %s", 'paystar-payment-for-gravityforms' ), $new_status, GFCommon::to_money( $entry["payment_amount"], $entry["currency"] ), $payment_transaction, $entry["payment_date"] ) );
	}

	public static function settings_page() {
		if ( ! function_exists( 'curl_init' ) ) {
			_e( 'The Curl library method set is not enabled on your server. You must enable Curl to use this plugin. Contact the host manager', 'paystar-payment-for-gravityforms' );
			return;
		}
		if ( sanitize_text_field(rgpost( "uninstall" )) ) {
			check_admin_referer( "uninstall", "gf_paystar_uninstall" );
			self::uninstall();
			echo '<div class="updated fade" style="padding:20px;">' . __( "The plugin was successfully deactivated and the information related to it was lost. You can proceed to reactivate it through WordPress plugins", 'paystar-payment-for-gravityforms' ) . '</div>';
			return;
		} else if ( isset( $_POST["gf_paystar_submit"] ) ) {
			check_admin_referer( "update", "gf_paystar_update" );
			$settings = array(
				"terminal" => sanitize_text_field(rgpost( 'gf_paystar_terminal' )),
				"gname" => sanitize_text_field(rgpost( 'gf_paystar_gname' )),
			);
			update_option( "gf_paystar_settings", array_map( 'sanitize_text_field', $settings ) );
			if ( isset( $_POST["gf_paystar_configured"] ) ) {
				update_option( "gf_paystar_configured", sanitize_text_field( $_POST["gf_paystar_configured"] ) );
			} else {
				delete_option( "gf_paystar_configured" );
			}
		} else {
			$settings = get_option( "gf_paystar_settings" );
		}
		if ( ! empty( $_POST ) ) {
			if ( isset( $_POST["gf_paystar_configured"] ) && ( $Response = self::Request( 'valid_checker', '', '', '' ) ) && $Response != false ) {
				if ( $Response === true ) {
					echo '<div class="updated fade" style="padding:6px">' . __( "Communication with the gateway was established and the information entered is correct", 'paystar-payment-for-gravityforms' ) . '</div>';
				} else {
					echo '<div class="error fade" style="padding:6px">' . esc_html($Response) . '</div>';
				}
			} else {
				echo '<div class="updated fade" style="padding:6px">' . __( "Configuration Saved .", 'paystar-payment-for-gravityforms' ) . '</div>';
			}
		} else if ( isset( $_GET['subview'] ) && sanitize_text_field($_GET['subview']) == 'gf_paystar' && isset( $_GET['updated'] ) ) {
			echo '<div class="updated fade" style="padding:6px">' . __( "Configuration Saved .", 'paystar-payment-for-gravityforms' ) . '</div>';
		}
		?>
        <form action="" method="post">
			<?php wp_nonce_field( "update", "gf_paystar_update" ) ?>
            <h3>
				<span>
				<i class="fa fa-credit-card"></i>
					<?php _e( "PayStar Configuration", 'paystar-payment-for-gravityforms' ) ?>
				</span>
            </h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label
                                for="gf_paystar_configured"><?php _e( "Enable", 'paystar-payment-for-gravityforms' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="gf_paystar_configured"
                               id="gf_paystar_configured" <?php echo get_option( "gf_paystar_configured" ) ? "checked='checked'" : "" ?>/>
                        <label class="inline"
                               for="gf_paystar_configured"><?php _e( "Yes", 'paystar-payment-for-gravityforms' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="gf_paystar_terminal"><?php _e( "Terminal", 'paystar-payment-for-gravityforms' ); ?></label></th>
                    <td>
                        <input style="width:350px;text-align:left;direction:ltr !important" type="text"
                               id="gf_paystar_terminal" name="gf_paystar_terminal"
                               value="<?php echo sanitize_text_field( rgar( $settings, 'terminal' ) ) ?>"/>
                    </td>
                </tr>
				<?php
				$gateway_title = __( "PayStar", 'paystar-payment-for-gravityforms' );
				if ( sanitize_text_field( rgar( $settings, 'gname' ) ) ) {
					$gateway_title = sanitize_text_field( $settings["gname"] );
				}
				?>
                <tr>
                    <th scope="row">
                        <label for="gf_paystar_gname">
							<?php _e( "Title", 'paystar-payment-for-gravityforms' ); ?>
							<?php gform_tooltip( 'gateway_name' ) ?>
                        </label>
                    </th>
                    <td>
                        <input style="width:350px;" type="text" id="gf_paystar_gname" name="gf_paystar_gname"
                               value="<?php echo esc_html($gateway_title); ?>"/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><input style="font-family:tahoma !important;" type="submit"
                                           name="gf_paystar_submit" class="button-primary"
                                           value="<?php _e( "Save", 'paystar-payment-for-gravityforms' ) ?>"/></td>
                </tr>
            </table>
        </form>
        <form action="" method="post">
			<?php
			wp_nonce_field( "uninstall", "gf_paystar_uninstall" );
			if ( self::has_access( "gravityforms_paystar_uninstall" ) ) {
				?>
                <div class="hr-divider"></div>
                <div class="delete-alert alert_red">
                    <h3>
                        <i class="fa fa-exclamation-triangle gf_invalid"></i>
						<?php _e( "Disable PayStar Payment Gateway", 'paystar-payment-for-gravityforms' ); ?>
                    </h3>
                    <div
                            class="gf_delete_notice"><?php _e( "Note: After deactivating, all data about PayStar will be deleted", 'paystar-payment-for-gravityforms' ) ?></div>
					<?php
					$uninstall_button = '<input  style="font-family:tahoma !important;" type="submit" name="uninstall" value="' . __( "uninstall PayStar Payment plugin", 'paystar-payment-for-gravityforms' ) . '" class="button" onclick="return confirm(\'' . __( "Note: After deactivating, all data about PayStar will be deleted. are you sure?", 'paystar-payment-for-gravityforms' ) . '\');"/>';
					echo esc_html(apply_filters( "gform_paystar_uninstall_button", $uninstall_button ));
					?>
                </div>
			<?php } ?>
        </form>
		<?php
	}

	public static function get_gname() {
		$settings = get_option( "gf_paystar_settings" );
		if ( isset( $settings["gname"] ) ) {
			$gname = $settings["gname"];
		} else {
			$gname = __( 'PayStar', 'paystar-payment-for-gravityforms' );
		}
		return $gname;
	}

	private static function get_terminal() {
		$settings = get_option( "gf_paystar_settings" );
		$terminal = isset( $settings["terminal"] ) ? $settings["terminal"] : '';
		return trim( $terminal );
	}

	private static function config_page() {
		wp_register_style( 'gform_admin_paystar', GFCommon::get_base_url() . '/css/admin.css' );
		wp_print_styles( array( 'jquery-ui-styles', 'gform_admin_paystar', 'wp-pointer' ) ); ?>
		<?php if ( is_rtl() ) { ?>
            <style type="text/css">
                table.gforms_form_settings th {
                    text-align: right !important;
                }
            </style>
		<?php } ?>
        <div class="wrap gforms_edit_form gf_browser_gecko">
			<?php
			$id        = ! rgempty( "paystar_setting_id" ) ? sanitize_text_field(rgpost( "paystar_setting_id" )) : absint( rgget( "id" ) );
			$config    = empty( $id ) ? array(
				"meta"      => array(),
				"is_active" => true
			) : GFPersian_DB_PayStar::get_feed( $id );
			$get_feeds = GFPersian_DB_PayStar::get_feeds();
			$form_name = '';
			$_get_form_id = rgget( 'fid' ) ? sanitize_text_field(rgget( 'fid' )) : ( ! empty( $config["form_id"] ) ? $config["form_id"] : '' );
			foreach ( (array) $get_feeds as $get_feed ) {
				if ( $get_feed['id'] == $id ) {
					$form_name = $get_feed['form_title'];
				}
			}
			?>
            <h2 class="gf_admin_page_title"><?php _e( "PayStar Gateway Configuration", 'paystar-payment-for-gravityforms' ) ?>
				<?php if ( ! empty( $_get_form_id ) ) { ?>
                    <span class="gf_admin_page_subtitle">
					<span class="gf_admin_page_formid"><?php echo esc_html(sprintf( __( "feed: %s", 'paystar-payment-for-gravityforms' ), $id )) ?></span>
					<span class="gf_admin_page_formname"><?php echo esc_html(sprintf( __( "form: %s", 'paystar-payment-for-gravityforms' ), $form_name )) ?></span>
				</span>
				<?php } ?>
            </h2>
            <a class="button add-new-h2" href="admin.php?page=gf_settings&subview=gf_paystar" style="margin:8px 9px;"><?php _e( "PayStar Configuration settings", 'paystar-payment-for-gravityforms' ) ?></a>
			<?php
			if ( ! rgempty( "gf_paystar_submit" ) ) {
				check_admin_referer( "update", "gf_paystar_feed" );
				$config["form_id"]                     = absint( rgpost( "gf_paystar_form" ) );
				$config["meta"]["type"]                = sanitize_text_field(rgpost( "gf_paystar_type" ));
				$config["meta"]["addon"]               = sanitize_text_field(rgpost( "gf_paystar_addon" ));
				$config["meta"]["update_post_action1"] = sanitize_text_field(rgpost( 'gf_paystar_update_action1' ));
				$config["meta"]["update_post_action2"] = sanitize_text_field(rgpost( 'gf_paystar_update_action2' ));
				$config["meta"]["paystar_conditional_enabled"]  = sanitize_text_field(rgpost( 'gf_paystar_conditional_enabled' ));
				$config["meta"]["paystar_conditional_field_id"] = sanitize_text_field(rgpost( 'gf_paystar_conditional_field_id' ));
				$config["meta"]["paystar_conditional_operator"] = sanitize_text_field(rgpost( 'gf_paystar_conditional_operator' ));
				$config["meta"]["paystar_conditional_value"]    = sanitize_text_field(rgpost( 'gf_paystar_conditional_value' ));
				$config["meta"]["paystar_conditional_type"]     = sanitize_text_field(rgpost( 'gf_paystar_conditional_type' ));
				$config["meta"]["desc_pm"]                = sanitize_text_field(rgpost( "gf_paystar_desc_pm" ));
				$config["meta"]["customer_fields_desc"]   = sanitize_text_field(rgpost( "paystar_customer_field_desc" ));
				$config["meta"]["customer_fields_name"]   = sanitize_text_field(rgpost( "paystar_customer_field_name" ));
				$config["meta"]["customer_fields_family"] = sanitize_text_field(rgpost( "paystar_customer_field_family" ));
				$config["meta"]["customer_fields_email"]  = sanitize_text_field(rgpost( "paystar_customer_field_email" ));
				$config["meta"]["customer_fields_mobile"] = sanitize_text_field(rgpost( "paystar_customer_field_mobile" ));
				$safe_data = array();
				foreach ( $config["meta"] as $key => $val ) {
					if ( ! is_array( $val ) ) {
						$safe_data[ $key ] = sanitize_text_field( $val );
					} else {
						$safe_data[ $key ] = array_map( 'sanitize_text_field', $val );
					}
				}
				$config["meta"] = $safe_data;
				$config = apply_filters( self::$author . '_gform_gateway_save_config', $config );
				$config = apply_filters( self::$author . '_gform_paystar_save_config', $config );
				$id = GFPersian_DB_PayStar::update_feed( $id, $config["form_id"], $config["is_active"], $config["meta"] );
				if ( ! headers_sent() ) {
					wp_redirect( admin_url( 'admin.php?page=gf_paystar&view=edit&id=' . $id . '&updated=true' ) );
					exit;
				} else {
					echo "<script type='text/javascript'>window.onload = function () { top.location.href = '" . esc_url(admin_url( 'admin.php?page=gf_paystar&view=edit&id=' . $id . '&updated=true' )) . "'; };</script>";
					exit;
				}
				?>
                <div class="updated fade"
                     style="padding:6px"><?php echo esc_html(sprintf( __( "feed updated . %s return to the list %s.", 'paystar-payment-for-gravityforms' ), "<a href='?page=gf_paystar'>", "</a>" )) ?></div>
				<?php
			}
			$_get_form_id = rgget( 'fid' ) ? sanitize_text_field(rgget( 'fid' )) : ( ! empty( $config["form_id"] ) ? $config["form_id"] : '' );
			$form = array();
			if ( ! empty( $_get_form_id ) ) {
				$form = RGFormsModel::get_form_meta( $_get_form_id );
			}
			if ( rgget( 'updated' ) == 'true' ) {
				$id = empty( $id ) && isset( $_GET['id'] ) ? sanitize_text_field(rgget( 'id' )) : $id;
				$id = absint( $id ); ?>
                <div class="updated fade" style="padding:6px"><?php echo esc_html(sprintf( __( "feed updated . %s return to the list %s . ", 'paystar-payment-for-gravityforms' ), "<a href='?page=gf_paystar'>", "</a>" )) ?></div>
				<?php
			}
			if ( ! empty( $_get_form_id ) ) { ?>
                <div id="gf_form_toolbar">
                    <ul id="gf_form_toolbar_links">
						<?php
						$menu_items = apply_filters( 'gform_toolbar_menu', GFForms::get_toolbar_menu_items( $_get_form_id ), $_get_form_id );
						echo esc_html(GFForms::format_toolbar_menu_items( $menu_items )); ?>
                        <li class="gf_form_switcher">
                            <label for="export_form"><?php _e( 'select a feed', 'paystar-payment-for-gravityforms' ) ?></label>
							<?php
							$feeds = GFPersian_DB_PayStar::get_feeds();
							if ( RG_CURRENT_VIEW != 'entry' ) { ?>
                                <select name="form_switcher" id="form_switcher"
                                        onchange="GF_SwitchForm(jQuery(this).val());">
                                    <option value=""><?php _e( 'change PayStar feed', 'paystar-payment-for-gravityforms' ) ?></option>
									<?php foreach ( $feeds as $feed ) {
										$selected = $feed["id"] == $id ? "selected='selected'" : ""; ?>
                                        <option
                                                value="<?php echo esc_html($feed["id"]) ?>" <?php echo esc_html($selected) ?> ><?php echo esc_html(sprintf( __( 'form: %s (feed: %s)', 'paystar-payment-for-gravityforms' ), $feed["form_title"], $feed["id"] )) ?></option>
									<?php } ?>
                                </select>
								<?php
							}
							?>
                        </li>
                    </ul>
                </div>
			<?php } ?>
			<?php
			$condition_field_ids = array( '1' => '' );
			$condition_values    = array( '1' => '' );
			$condition_operators = array( '1' => 'is' );
			?>
            <div id="gform_tab_group" class="gform_tab_group vertical_tabs">
				<?php if ( ! empty( $_get_form_id ) ) { ?>
                    <ul id="gform_tabs" class="gform_tabs">
						<?php
						$title        = '';
						$get_form     = GFFormsModel::get_form_meta( $_get_form_id );
						$current_tab  = rgempty( 'subview', $_GET ) ? 'settings' : sanitize_text_field(rgget( 'subview' ));
						$current_tab  = ! empty( $current_tab ) ? $current_tab : ' ';
						$setting_tabs = GFFormSettings::get_tabs( $get_form['id'] );
						if ( ! $title ) {
							foreach ( $setting_tabs as $tab ) {
								$query = array(
									'page'    => 'gf_edit_forms',
									'view'    => 'settings',
									'subview' => $tab['name'],
									'id'      => $get_form['id']
								);
								$url   = add_query_arg( $query, admin_url( 'admin.php' ) );
								echo $tab['name'] == 'paystar' ? '<li class="active">' : '<li>';
								?>
                                <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $tab['label'] ) ?></a>
                                <span></span>
                                </li>
								<?php
							}
						}
						?>
                    </ul>
				<?php }
				$has_product = false;
				if ( isset( $form["fields"] ) ) {
					foreach ( $form["fields"] as $field ) {
						$shipping_field = GFAPI::get_fields_by_type( $form, array( 'shipping' ) );
						if ( $field["type"] == "product" || ! empty( $shipping_field ) ) {
							$has_product = true;
							break;
						}
					}
				} else if ( empty( $_get_form_id ) ) {
					$has_product = true;
				}
				?>
                <div id="gform_tab_container_<?php echo esc_html($_get_form_id ? $_get_form_id : 1) ?>"
                     class="gform_tab_container">
                    <div class="gform_tab_content" id="tab_<?php echo esc_html(! empty( $current_tab ) ? $current_tab : '') ?>">
                        <div id="form_settings" class="gform_panel gform_panel_form_settings">
                            <h3>
								<span>
									<i class="fa fa-credit-card"></i>
									<?php _e( "PayStar Payment Configuration", 'paystar-payment-for-gravityforms' ); ?>
								</span>
                            </h3>
                            <form method="post" action="" id="gform_form_settings">
								<?php wp_nonce_field( "update", "gf_paystar_feed" ) ?>
                                <input type="hidden" name="paystar_setting_id" value="<?php echo esc_html($id) ?>"/>
                                <table class="form-table gforms_form_settings" cellspacing="0" cellpadding="0">
                                    <tbody>
                                    <tr style="<?php echo rgget( 'id' ) || rgget( 'fid' ) ? 'display:none !important' : ''; ?>">
                                        <th>
											<?php _e( "select form", 'paystar-payment-for-gravityforms' ); ?>
                                        </th>
                                        <td>
                                            <select id="gf_paystar_form" name="gf_paystar_form"
                                                    onchange="GF_SwitchFid(jQuery(this).val());">
                                                <option
                                                        value=""><?php _e( "select a form", 'paystar-payment-for-gravityforms' ); ?> </option>
												<?php
												$available_forms = GFPersian_DB_PayStar::get_available_forms();
												foreach ( $available_forms as $current_form ) {
													$selected = absint( $current_form->id ) == $_get_form_id ? 'selected="selected"' : ''; ?>
                                                    <option
                                                            value="<?php echo absint( $current_form->id ) ?>" <?php echo esc_html($selected); ?>><?php echo esc_html( $current_form->title ) ?></option>
													<?php
												}
												?>
                                            </select>
                                            <img
                                                    src="<?php echo esc_url( GFCommon::get_base_url() ) ?>/images/spinner.gif"
                                                    id="paystar_wait" style="display: none;"/>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
								<?php if ( empty( $has_product ) || ! $has_product ) { ?>
                                    <div id="gf_paystar_invalid_product_form" class="gf_paystar_invalid_form"
                                         style="background-color:#FFDFDF; margin-top:4px; margin-bottom:6px;padding:18px; border:1px dotted #C89797;">
										<?php _e( "form has not any priceing field. try again", 'paystar-payment-for-gravityforms' ) ?>
                                    </div>
								<?php } else { ?>
                                    <table class="form-table gforms_form_settings"
                                           id="paystar_field_group" <?php echo empty( $_get_form_id ) ? "style='display:none;'" : "" ?>
                                           cellspacing="0" cellpadding="0">
                                        <tbody>
                                        <tr>
                                            <th>
												<?php _e( "registration form", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="gf_paystar_type"
                                                       id="gf_paystar_type_subscription"
                                                       value="subscription" <?php echo rgar( $config['meta'], 'type' ) == "subscription" ? "checked='checked'" : "" ?>/>
                                                <label for="gf_paystar_type"></label>
                                                <span
                                                        class="description"><?php _e( 'if you check this, registration will be done with User Registration addon after Successful payments' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "payment Description", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <input type="text" name="gf_paystar_desc_pm" id="gf_paystar_desc_pm"
                                                       class="fieldwidth-1"
                                                       value="<?php echo esc_html(rgar( $config["meta"], "desc_pm" )) ?>"/>
                                                <span class="description"><?php _e( "shortcodes : {form_id} , {form_title} , {entry_id}", 'paystar-payment-for-gravityforms' ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "Description", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td class="paystar_customer_fields_desc">
												<?php
												if ( ! empty( $form ) ) {
													echo esc_html(self::get_customer_information_desc( $form, $config ));
												}
												?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "payer name", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td class="paystar_customer_fields_name">
												<?php
												if ( ! empty( $form ) ) {
													echo esc_html(self::get_customer_information_name( $form, $config ));
												}
												?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "payer family", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td class="paystar_customer_fields_family">
												<?php
												if ( ! empty( $form ) ) {
													echo esc_html(self::get_customer_information_family( $form, $config ));
												}
												?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "Payer email", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td class="paystar_customer_fields_email">
												<?php
												if ( ! empty( $form ) ) {
													echo esc_html(self::get_customer_information_email( $form, $config ));
												}
												?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php _e( "Payer mobile", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td class="paystar_customer_fields_mobile">
												<?php
												if ( ! empty( $form ) ) {
													echo esc_html(self::get_customer_information_mobile( $form, $config ));
												}
												?>
                                            </td>
                                        </tr>
										<?php $display_post_fields = ! empty( $form ) ? GFCommon::has_post_field( $form["fields"] ) : false; ?>
                                        <tr <?php echo $display_post_fields ? "" : "style='display:none;'" ?>>
                                            <th>
												<?php _e( "create post after payment", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <select id="gf_paystar_update_action1"
                                                        name="gf_paystar_update_action1">
                                                    <option
                                                            value="default" <?php echo rgar( $config["meta"], "update_post_action1" ) == "default" ? "selected='selected'" : "" ?>><?php _e( "default", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="publish" <?php echo rgar( $config["meta"], "update_post_action1" ) == "publish" ? "selected='selected'" : "" ?>><?php _e( "publish", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="draft" <?php echo rgar( $config["meta"], "update_post_action1" ) == "draft" ? "selected='selected'" : "" ?>><?php _e( "draft", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="pending" <?php echo rgar( $config["meta"], "update_post_action1" ) == "pending" ? "selected='selected'" : "" ?>><?php _e( "pending", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="private" <?php echo rgar( $config["meta"], "update_post_action1" ) == "private" ? "selected='selected'" : "" ?>><?php _e( "private", 'paystar-payment-for-gravityforms' ) ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr <?php echo $display_post_fields ? "" : "style='display:none;'" ?>>
                                            <th>
												<?php _e( "create post before payment", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <select id="gf_paystar_update_action2"
                                                        name="gf_paystar_update_action2">
                                                    <option
                                                            value="dont" <?php echo rgar( $config["meta"], "update_post_action2" ) == "dont" ? "selected='selected'" : "" ?>><?php _e( "dont create post", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="default" <?php echo rgar( $config["meta"], "update_post_action2" ) == "default" ? "selected='selected'" : "" ?>><?php _e( "default", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="publish" <?php echo rgar( $config["meta"], "update_post_action2" ) == "publish" ? "selected='selected'" : "" ?>><?php _e( "publish post", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="draft" <?php echo rgar( $config["meta"], "update_post_action2" ) == "draft" ? "selected='selected'" : "" ?>><?php _e( "draft", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="pending" <?php echo rgar( $config["meta"], "update_post_action2" ) == "pending" ? "selected='selected'" : "" ?>><?php _e( "pending", 'paystar-payment-for-gravityforms' ) ?></option>
                                                    <option
                                                            value="private" <?php echo rgar( $config["meta"], "update_post_action2" ) == "private" ? "selected='selected'" : "" ?>><?php _e( "private", 'paystar-payment-for-gravityforms' ) ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
												<?php echo __( "Compatibility with add-ons", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="gf_paystar_addon"
                                                       id="gf_paystar_addon_true"
                                                       value="true" <?php echo rgar( $config['meta'], 'addon' ) == "true" ? "checked='checked'" : "" ?>/>
                                                <label for="gf_paystar_addon"></label>
                                                <span
                                                        class="description"><?php _e( 'some gravityforms addons has function add_delayed_payment_support . if you want this methods works only after Successful payment, check this', 'paystar-payment-for-gravityforms' ); ?></span>
                                            </td>
                                        </tr>
										<?php
										do_action( self::$author . '_gform_gateway_config', $config, $form );
										do_action( self::$author . '_gform_paystar_config', $config, $form );
										?>
                                        <tr id="gf_paystar_conditional_option">
                                            <th>
												<?php _e( "conditional logic", 'paystar-payment-for-gravityforms' ); ?>
                                            </th>
                                            <td>
                                                <input type="checkbox" id="gf_paystar_conditional_enabled"
                                                       name="gf_paystar_conditional_enabled" value="1"
                                                       onclick="if(this.checked){jQuery('#gf_paystar_conditional_container').fadeIn('fast');} else{ jQuery('#gf_paystar_conditional_container').fadeOut('fast'); }" <?php echo rgar( $config['meta'], 'paystar_conditional_enabled' ) ? "checked='checked'" : "" ?>/>
                                                <label for="gf_paystar_conditional_enabled"><?php _e( "Enable conditional logic", 'paystar-payment-for-gravityforms' ); ?></label><br/>
                                                <br>
                                                <table cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td>
                                                            <div id="gf_paystar_conditional_container" <?php echo ! rgar( $config['meta'], 'paystar_conditional_enabled' ) ? "style='display:none'" : "" ?>>
                                                                <span><?php _e( "Enable gateway if ", 'paystar-payment-for-gravityforms' ) ?></span>
                                                                <select name="gf_paystar_conditional_type">
                                                                    <option value="all" <?php echo rgar( $config['meta'], 'paystar_conditional_type' ) == 'all' ? "selected='selected'" : "" ?>><?php _e( "All", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                    <option value="any" <?php echo rgar( $config['meta'], 'paystar_conditional_type' ) == 'any' ? "selected='selected'" : "" ?>><?php _e( "at least one", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                </select>
                                                                <span><?php _e( "Comply with the following options:", 'paystar-payment-for-gravityforms' ) ?></span>
																<?php
																if ( ! empty( $config["meta"]["paystar_conditional_field_id"] ) ) {
																	$condition_field_ids = $config["meta"]["paystar_conditional_field_id"];
																	if ( ! is_array( $condition_field_ids ) ) {
																		$condition_field_ids = array( '1' => $condition_field_ids );
																	}
																}
																if ( ! empty( $config["meta"]["paystar_conditional_value"] ) ) {
																	$condition_values = $config["meta"]["paystar_conditional_value"];
																	if ( ! is_array( $condition_values ) ) {
																		$condition_values = array( '1' => $condition_values );
																	}
																}
																if ( ! empty( $config["meta"]["paystar_conditional_operator"] ) ) {
																	$condition_operators = $config["meta"]["paystar_conditional_operator"];
																	if ( ! is_array( $condition_operators ) ) {
																		$condition_operators = array( '1' => $condition_operators );
																	}
																}
																ksort( $condition_field_ids );
																foreach ( $condition_field_ids as $i => $value ):?>
                                                                    <div class="gf_paystar_conditional_div"
                                                                         id="gf_paystar_<?php echo esc_html($i); ?>__conditional_div">
                                                                        <select class="gf_paystar_conditional_field_id"
                                                                                id="gf_paystar_<?php echo esc_html($i); ?>__conditional_field_id"
                                                                                name="gf_paystar_conditional_field_id[<?php echo esc_html($i); ?>]"
                                                                                title="">
                                                                        </select>
                                                                        <select class="gf_paystar_conditional_operator"
                                                                                id="gf_paystar_<?php echo esc_html($i); ?>__conditional_operator"
                                                                                name="gf_paystar_conditional_operator[<?php echo esc_html($i); ?>]"
                                                                                style="font-family:tahoma,serif !important"
                                                                                title="">
                                                                            <option value="is"><?php _e( "is", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value="isnot"><?php _e( "is not", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value=">"><?php _e( "greater than", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value="<"><?php _e( "less than", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value="contains"><?php _e( "contains", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value="starts_with"><?php _e( "starts with", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                            <option value="ends_with"><?php _e( "ends with", 'paystar-payment-for-gravityforms' ) ?></option>
                                                                        </select>
                                                                        <div id="gf_paystar_<?php echo esc_html($i); ?>__conditional_value_container"
                                                                             style="display:inline;">
                                                                        </div>
                                                                        <a class="add_new_condition gficon_link"
                                                                           href="#">
                                                                            <i class="gficon-add"></i>
                                                                        </a>
                                                                        <a class="delete_this_condition gficon_link"
                                                                           href="#">
                                                                            <i class="gficon-subtract"></i>
                                                                        </a>
                                                                    </div>
																<?php endforeach; ?>
                                                                <input type="hidden"
                                                                       value="<?php echo esc_html(key( array_slice( $condition_field_ids, - 1, 1, true ) )); ?>"
                                                                       id="gf_paystar_conditional_counter">
                                                                <div id="gf_no_conditional_message"
                                                                     style="display:none;background-color:#FFDFDF; margin-top:4px; margin-bottom:6px; padding-top:6px; padding:18px; border:1px dotted #C89797;">
																	<?php _e( "To place conditional logic, your form fields must also have conditional logic", 'paystar-payment-for-gravityforms' ) ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input type="submit" class="button-primary gfbutton"
                                                       name="gf_paystar_submit"
                                                       value="<?php _e( "Save", 'paystar-payment-for-gravityforms' ); ?>"/>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
								<?php } ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style type="text/css">
            .gforms_form_settings select {
                width: 180px !important;
            }
            .delete_this_condition, .add_new_condition {
                text-decoration: none !important;
                color: #000;
                outline: none !important;
            }
            #gf_paystar_conditional_container *, .delete_this_condition *, .add_new_condition * {
                outline: none !important;
            }
            .condition_field_value {
                width: 150px !important;
            }
            table.gforms_form_settings th {
                font-weight: 600;
                line-height: 1.3;
                font-size: 14px;
            }
            .gf_paystar_conditional_div {
                margin: 3px;
            }
        </style>
        <script type="text/javascript">
            function GF_SwitchFid(fid) {
                jQuery("#paystar_wait").show();
                document.location = "?page=gf_paystar&view=edit&fid=" + fid;
            }
            function GF_SwitchForm(id) {
                if (id.length > 0) {
                    document.location = "?page=gf_paystar&view=edit&id=" + id;
                }
            }
            var form = [];
            form = <?php echo esc_html(! empty( $form ) ? GFCommon::json_encode( $form ) : GFCommon::json_encode( array() )) ?>;
            jQuery(document).ready(function ($) {
                var delete_link, selectedField, selectedValue, selectedOperator;
                delete_link = $('.delete_this_condition');
                if (delete_link.length === 1)
                    delete_link.hide();
                $(document.body).on('change', '.gf_paystar_conditional_field_id', function () {
                    var id = $(this).attr('id');
                    id = id.replace('gf_paystar_', '').replace('__conditional_field_id', '');
                    var selectedOperator = $('#gf_paystar_' + id + '__conditional_operator').val();
                    $('#gf_paystar_' + id + '__conditional_value_container').html(GetConditionalFieldValues("gf_paystar_" + id + "__conditional", jQuery(this).val(), selectedOperator, "", 20, id));
                }).on('change', '.gf_paystar_conditional_operator', function () {
                    var id = $(this).attr('id');
                    id = id.replace('gf_paystar_', '').replace('__conditional_operator', '');
                    var selectedOperator = $(this).val();
                    var field_id = $('#gf_paystar_' + id + '__conditional_field_id').val();
                    $('#gf_paystar_' + id + '__conditional_value_container').html(GetConditionalFieldValues("gf_paystar_" + id + "__conditional", field_id, selectedOperator, "", 20, id));
                }).on('click', '.add_new_condition', function () {
                    var parent_div = $(this).parent('.gf_paystar_conditional_div');
                    var counter = $('#gf_paystar_conditional_counter');
                    var new_id = parseInt(counter.val()) + 1;
                    var content = parent_div[0].outerHTML
                        .replace(new RegExp('gf_paystar_\\d+__', 'g'), ('gf_paystar_' + new_id + '__'))
                        .replace(new RegExp('\\[\\d+\\]', 'g'), ('[' + new_id + ']'));
                    counter.val(new_id);
                    counter.before(content);
                    //parent_div.after(content);
                    RefreshConditionRow("gf_paystar_" + new_id + "__conditional", "", "is", "", new_id);
                    $('.delete_this_condition').show();
                    return false;
                }).on('click', '.delete_this_condition', function () {
                    $(this).parent('.gf_paystar_conditional_div').remove();
                    var delete_link = $('.delete_this_condition');
                    if (delete_link.length === 1)
                        delete_link.hide();
                    return false;
                });
				<?php foreach ( $condition_field_ids as $i => $field_id ) : ?>
                selectedField = "<?php echo esc_html(str_replace( '"', '\"', $field_id )) ?>";
                selectedValue = "<?php echo esc_html(str_replace( '"', '\"', $condition_values[ '' . $i . '' ] )) ?>";
                selectedOperator = "<?php echo esc_html(str_replace( '"', '\"', $condition_operators[ '' . $i . '' ] )) ?>";
                RefreshConditionRow("gf_paystar_<?php echo esc_html($i); ?>__conditional", selectedField, selectedOperator, selectedValue, <?php echo esc_html($i); ?>);
				<?php endforeach;?>
            });
            function RefreshConditionRow(input, selectedField, selectedOperator, selectedValue, index) {
                var field_id = jQuery("#" + input + "_field_id");
                field_id.html(GetSelectableFields(selectedField, 20));
                var optinConditionField = field_id.val();
                var checked = jQuery("#" + input + "_enabled").attr('checked');
                if (optinConditionField) {
                    jQuery("#gf_no_conditional_message").hide();
                    jQuery("#" + input + "_div").show();
                    jQuery("#" + input + "_value_container").html(GetConditionalFieldValues("" + input + "", optinConditionField, selectedOperator, selectedValue, 20, index));
                    jQuery("#" + input + "_value").val(selectedValue);
                    jQuery("#" + input + "_operator").val(selectedOperator);
                }
                else {
                    jQuery("#gf_no_conditional_message").show();
                    jQuery("#" + input + "_div").hide();
                }
                if (!checked) jQuery("#" + input + "_container").hide();
            }
            /**
             * @return {string}
             */
            function GetConditionalFieldValues(input, fieldId, selectedOperator, selectedValue, labelMaxCharacters, index) {
                if (!fieldId)
                    return "";
                var str = "";
                var name = (input.replace(new RegExp('_\\d+__', 'g'), '_')) + "_value[" + index + "]";
                var field = GetFieldById(fieldId);
                if (!field)
                    return "";
                var is_text = false;
                if (selectedOperator == '' || selectedOperator == 'is' || selectedOperator == 'isnot') {
                    if (field["type"] == "post_category" && field["displayAllCategories"]) {
                        str += '<?php $dd = wp_dropdown_categories( array(
							"class"        => "condition_field_value",
							"orderby"      => "name",
							"id"           => "gf_dropdown_cat_id",
							"name"         => "gf_dropdown_cat_name",
							"hierarchical" => true,
							"hide_empty"   => 0,
							"echo"         => false
						) ); echo esc_html(str_replace( "\n", "", str_replace( "'", "\\'", $dd ) )); ?>';
                        str = str.replace("gf_dropdown_cat_id", "" + input + "_value").replace("gf_dropdown_cat_name", name);
                    }
                    else if (field.choices) {
                        var isAnySelected = false;
                        str += "<select class='condition_field_value' id='" + input + "_value' name='" + name + "'>";
                        for (var i = 0; i < field.choices.length; i++) {
                            var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
                            var isSelected = fieldValue == selectedValue;
                            var selected = isSelected ? "selected='selected'" : "";
                            if (isSelected)
                                isAnySelected = true;
                            str += "<option value='" + fieldValue.replace(/'/g, "&#039;") + "' " + selected + ">" + TruncateMiddle(field.choices[i].text, labelMaxCharacters) + "</option>";
                        }
                        if (!isAnySelected && selectedValue) {
                            str += "<option value='" + selectedValue.replace(/'/g, "&#039;") + "' selected='selected'>" + TruncateMiddle(selectedValue, labelMaxCharacters) + "</option>";
                        }
                        str += "</select>";
                    }
                    else {
                        is_text = true;
                    }
                }
                else {
                    is_text = true;
                }
                if (is_text) {
                    selectedValue = selectedValue ? selectedValue.replace(/'/g, "&#039;") : "";
                    str += "<input type='text' class='condition_field_value' style='padding:3px' placeholder='<?php _e( "enter a value", 'paystar-payment-for-gravityforms' ); ?>' id='" + input + "_value' name='" + name + "' value='" + selectedValue + "'>";
                }
                return str;
            }
            /**
             * @return {string}
             */
            function GetSelectableFields(selectedFieldId, labelMaxCharacters) {
                var str = "";
                if (typeof form.fields !== "undefined") {
                    var inputType;
                    var fieldLabel;
                    for (var i = 0; i < form.fields.length; i++) {
                        fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
                        inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
                        if (IsConditionalLogicField(form.fields[i])) {
                            var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
                            str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle(fieldLabel, labelMaxCharacters) + "</option>";
                        }
                    }
                }
                return str;
            }
            /**
             * @return {string}
             */
            function TruncateMiddle(text, maxCharacters) {
                if (!text)
                    return "";
                if (text.length <= maxCharacters)
                    return text;
                var middle = parseInt(maxCharacters / 2);
                return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);
            }
            /**
             * @return {object}
             */
            function GetFieldById(fieldId) {
                for (var i = 0; i < form.fields.length; i++) {
                    if (form.fields[i].id == fieldId)
                        return form.fields[i];
                }
                return null;
            }
            /**
             * @return {boolean}
             */
            function IsConditionalLogicField(field) {
                var inputType = field.inputType ? field.inputType : field.type;
                var supported_fields = ["checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
                    "post_tags", "post_custom_field", "post_content", "post_excerpt"];
                var index = jQuery.inArray(inputType, supported_fields);
                return index >= 0;
            }
        </script>
		<?php
	}

	private static function submit_form( $form, $ajax ) {
		$function = 'document.frmPayStarPayment.submit();';
		if ( headers_sent() || $ajax ) {
			$confirmation = "<script type=\"text/javascript\">" . apply_filters( 'gform_cdata_open', '' ) . " function gformSubmit(){ $function }";
			$confirmation .= 'gformSubmit();';
			$confirmation .= apply_filters( 'gform_cdata_close', '' ) . '</script>';
			echo "<script language='javascript'>$function</script>";
			return  __('redirecting ....' , 'paystar-payment-for-gravityforms') . $confirmation . $form;
		}
		else {
			$confirmation = "<script type=\"text/javascript\">$function</script>";
			return  __('redirecting ....' , 'paystar-payment-for-gravityforms') . $form . $confirmation;
		}	
	}

	public static function Request( $confirmation, $form, $entry, $ajax ) {
		do_action( 'gf_gateway_request_1', $confirmation, $form, $entry, $ajax );
		do_action( 'gf_paystar_request_1', $confirmation, $form, $entry, $ajax );
		if ( apply_filters( 'gf_paystar_request_return', apply_filters( 'gf_gateway_request_return', false, $confirmation, $form, $entry, $ajax ), $confirmation, $form, $entry, $ajax ) ) {
			return $confirmation;
		}
		$valid_checker = $confirmation == 'valid_checker';
		$custom        = $confirmation == 'custom';
		global $current_user;
		$user_id   = 0;
		$user_name = __( 'Guest', 'paystar-payment-for-gravityforms' );
		if ( $current_user && $user_data = get_userdata( $current_user->ID ) ) {
			$user_id   = $current_user->ID;
			$user_name = $user_data->display_name;
		}
		if ( ! $valid_checker ) {
			$entry_id = $entry['id'];
			if ( ! $custom ) {
				if ( RGForms::post( "gform_submit" ) != $form['id'] ) {
					return $confirmation;
				}
				$config = self::get_active_config( $form );
				if ( empty( $config ) ) {
					return $confirmation;
				}
				gform_update_meta( $entry['id'], 'paystar_feed_id', $config['id'] );
				gform_update_meta( $entry['id'], 'payment_type', 'form' );
				gform_update_meta( $entry['id'], 'payment_gateway', self::get_gname() );
				switch ( $config["meta"]["type"] ) {
					case "subscription" :
						$transaction_type = 2;
						break;
					default :
						$transaction_type = 1;
						break;
				}
				if ( GFCommon::has_post_field( $form["fields"] ) ) {
					if ( ! empty( $config["meta"]["update_post_action2"] ) ) {
						if ( $config["meta"]["update_post_action2"] != 'dont' ) {
							if ( $config["meta"]["update_post_action2"] != 'default' ) {
								$form['postStatus'] = $config["meta"]["update_post_action2"];
							}
						} else {
							$dont_create = true;
						}
					}
					if ( empty( $dont_create ) ) {
						RGFormsModel::create_post( $form, $entry );
					}
				}
				$Amount = self::get_order_total( $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_form_gateway_price_{$form['id']}", apply_filters( self::$author . "_gform_form_gateway_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_form_paystar_price_{$form['id']}", apply_filters( self::$author . "_gform_form_paystar_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_gateway_price_{$form['id']}", apply_filters( self::$author . "_gform_gateway_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_paystar_price_{$form['id']}", apply_filters( self::$author . "_gform_paystar_price", $Amount, $form, $entry ), $form, $entry );
				if ( empty( $Amount ) || ! $Amount || $Amount == 0 ) {
					unset( $entry["payment_status"], $entry["payment_method"], $entry["is_fulfilled"], $entry["transaction_type"], $entry["payment_amount"], $entry["payment_date"] );
					$entry["payment_method"] = "paystar";
					GFAPI::update_entry( $entry );
					return self::redirect_confirmation( add_query_arg( array( 'no' => 'true' ), self::Return_URL( $form['id'], $entry['id'] ) ), $ajax );
				} else {
					$Desc1 = '';
					if ( ! empty( $config["meta"]["desc_pm"] ) ) {
						$Desc1 = str_replace( array( '{entry_id}', '{form_title}', '{form_id}' ), array(
							$entry['id'],
							$form['title'],
							$form['id']
						), $config["meta"]["desc_pm"] );
					}
					$Desc2 = '';
					if ( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_desc"] ) )) ) {
						$Desc2 = sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_desc"] )) );
					}
					if ( ! empty( $Desc1 ) && ! empty( $Desc2 ) ) {
						$Description = $Desc1 . ' - ' . $Desc2;
					} else if ( ! empty( $Desc1 ) && empty( $Desc2 ) ) {
						$Description = $Desc1;
					} else if ( ! empty( $Desc2 ) && empty( $Desc1 ) ) {
						$Description = $Desc2;
					} else {
						$Description = ' ';
					}
					$Description = sanitize_text_field( $Description );
					$name = '';
					if ( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_name"] ) ) )) {
						$name = sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_name"] ) ));
					}
					$family = '';
					if ( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_family"] ) ) )) {
						$family = sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_family"] ) ));
					}
					$Paymenter = sanitize_text_field( $name . ' ' . $family );
					$Email = '';
					if ( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_email"] ) ) )) {
						$Email = sanitize_text_field( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_email"] ) ) ));
					}
					$Mobile = '';
					if ( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_mobile"] ) ) )) {
						$Mobile = sanitize_text_field( sanitize_text_field(rgpost( 'input_' . str_replace( ".", "_", $config["meta"]["customer_fields_mobile"] ) ) ));
					}
				}
			} else {
				$Amount = gform_get_meta( rgar( $entry, 'id' ), 'hannanstd_part_price_' . $form['id'] );
				$Amount = apply_filters( self::$author . "_gform_custom_gateway_price_{$form['id']}", apply_filters( self::$author . "_gform_custom_gateway_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_custom_paystar_price_{$form['id']}", apply_filters( self::$author . "_gform_custom_paystar_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_gateway_price_{$form['id']}", apply_filters( self::$author . "_gform_gateway_price", $Amount, $form, $entry ), $form, $entry );
				$Amount = apply_filters( self::$author . "_gform_paystar_price_{$form['id']}", apply_filters( self::$author . "_gform_paystar_price", $Amount, $form, $entry ), $form, $entry );
				$Description = gform_get_meta( rgar( $entry, 'id' ), 'hannanstd_part_desc_' . $form['id'] );
				$Description = apply_filters( self::$author . '_gform_paystar_gateway_desc_', apply_filters( self::$author . '_gform_custom_gateway_desc_', $Description, $form, $entry ), $form, $entry );
				$Paymenter = gform_get_meta( rgar( $entry, 'id' ), 'hannanstd_part_name_' . $form['id'] );
				$Email     = gform_get_meta( rgar( $entry, 'id' ), 'hannanstd_part_email_' . $form['id'] );
				$Mobile    = gform_get_meta( rgar( $entry, 'id' ), 'hannanstd_part_mobile_' . $form['id'] );
				$entry_id = GFAPI::add_entry( $entry );
				$entry    = GFPersian_Payments::get_entry( $entry_id );
				do_action( 'gf_gateway_request_add_entry', $confirmation, $form, $entry, $ajax );
				do_action( 'gf_paystar_request_add_entry', $confirmation, $form, $entry, $ajax );
				gform_update_meta( $entry_id, 'payment_gateway', self::get_gname() );
				gform_update_meta( $entry_id, 'payment_type', 'custom' );
			}
			unset( $entry["payment_status"] );
			unset( $entry["payment_method"] );
			unset( $entry["is_fulfilled"] );
			unset( $entry["transaction_type"] );
			unset( $entry["payment_amount"] );
			unset( $entry["payment_date"] );
			unset( $entry["transaction_id"] );
			$entry["payment_status"] = "Processing";
			$entry["payment_method"] = "paystar";
			$entry["is_fulfilled"]   = 0;
			if ( ! empty( $transaction_type ) ) {
				$entry["transaction_type"] = $transaction_type;
			}
			GFAPI::update_entry( $entry );
			$entry = GFPersian_Payments::get_entry( $entry_id );
			$ReturnPath = self::Return_URL( $form['id'], $entry_id );
			$ResNumber  = apply_filters( 'gf_paystar_res_number', apply_filters( 'gf_gateway_res_number', $entry_id, $entry, $form ), $entry, $form );
		} else {
			$Amount      = 10000;
			$ReturnPath  = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			$Email       = '';
			$Mobile      = '';
			$ResNumber   = rand( 1000, 9999 );
			$Paymenter   = $user_name;
			$Description = 'TEST Payment to check settings';
		}
		$Mobile = GFPersian_Payments::fix_mobile( $Mobile );
		do_action( 'gf_gateway_request_2', $confirmation, $form, $entry, $ajax );
		do_action( 'gf_paystar_request_2', $confirmation, $form, $entry, $ajax );
		if ( ! $custom ) {
			$Amount = GFPersian_Payments::amount( $Amount, 'IRT', $form, $entry );
		}
		//$Email = !filter_var($Email, FILTER_VALIDATE_EMAIL) === false ? $Email : '';
		//$Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';

		require_once(dirname(__FILE__) . '/paystar_payment_helper.class.php');
		$p = new PayStar_Payment_Helper(self::get_terminal());
		$r = $p->paymentRequest(array(
				'amount'      => $Amount * ($entry["currency"] == 'IRT' ? 10 : 1),
				'order_id'    => $form['id'] . '-#-' . $entry_id,
				'phone'       => $Mobile,
				'mail'        => $Email,
				'description' => $Description,
				'callback'    => add_query_arg(array('listener' => 'paystar-gf'), get_site_url().'/'),
			));
		if ($r)
		{
			if ( $valid_checker ) {
				return true;
			} else {
				$PaymentForm = '<form name="frmPayStarPayment" method="post" action="https://core.paystar.ir/api/pardakht/payment">
									<input type="hidden" name="token" value="'.$p->data->token.'" />
									<noscript><center><input type="submit" value="'.__('Pay', 'paystar-payment-for-gravityforms').'" /></center></noscript>
								</form>';
				return self::submit_form( $PaymentForm, $ajax );
			}
		}
		else
		{
			$Message = $p->error;
		}
		$Message = ! empty( $Message ) ? $Message : __( 'An error occurred', 'paystar-payment-for-gravityforms' );
		$confirmation = __( 'Sorry, we are unable to connect to the gateway. Reason : ', 'paystar-payment-for-gravityforms' ) . $Message;
		if ( $valid_checker ) {
			return $Message;
		}
		$entry = GFPersian_Payments::get_entry( $entry_id );
		$entry['payment_status'] = 'Failed';
		GFAPI::update_entry( $entry );
		RGFormsModel::add_note( $entry_id, $user_id, $user_name, sprintf( __( 'An error occurred while connecting to the gateway : %s', 'paystar-payment-for-gravityforms' ), $Message ) );
		if ( ! $custom ) {
			GFPersian_Payments::notification( $form, $entry );
		}
		$default_anchor = 0;
		$anchor         = gf_apply_filters( 'gform_confirmation_anchor', $form['id'], $default_anchor ) ? "<a id='gf_{$form['id']}' name='gf_{$form['id']}' class='gform_anchor' ></a>" : '';
		$nl2br          = ! empty( $form['confirmation'] ) && rgar( $form['confirmation'], 'disableAutoformat' ) ? false : true;
		$cssClass       = rgar( $form, 'cssClass' );
		return $confirmation = empty( $confirmation ) ? "{$anchor} " : "{$anchor}<div id='gform_confirmation_wrapper_{$form['id']}' class='gform_confirmation_wrapper {$cssClass}'><div id='gform_confirmation_message_{$form['id']}' class='gform_confirmation_message_{$form['id']} gform_confirmation_message'>" . GFCommon::replace_variables( $confirmation, $form, $entry, false, true, $nl2br ) . '</div></div>';
	}

	public static function Verify() {
		if (!(isset($_GET['listener'],$_POST['status'],$_POST['order_id'],$_POST['ref_num']) && sanitize_text_field($_GET['listener']) == 'paystar-gf')) {
			return false;
		}

		if ( apply_filters( 'gf_gateway_paystar_return', apply_filters( 'gf_gateway_verify_return', false ) ) ) {
			return;
		}
		if ( ! self::is_gravityforms_supported() ) {
			return;
		}

		$post_status = sanitize_text_field($_POST['status']);
		$post_order_id = sanitize_text_field($_POST['order_id']);
		$post_ref_num = sanitize_text_field($_POST['ref_num']);
		$post_tracking_code = sanitize_text_field($_POST['tracking_code']);

		list($form_id, $entry_id) = explode('-#-', $post_order_id);
		$entry = GFPersian_Payments::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			return;
		}
		if ( isset( $entry["payment_method"] ) && $entry["payment_method"] == 'paystar' ) {
			$form = RGFormsModel::get_form_meta( $form_id );
			$payment_type = gform_get_meta( $entry["id"], 'payment_type' );
			gform_delete_meta( $entry['id'], 'payment_type' );
			if ( $payment_type != 'custom' ) {
				$config = self::get_config_by_entry( $entry );
				if ( empty( $config ) ) {
					return;
				}
			} else {
				$config = apply_filters( self::$author . '_gf_paystar_config', apply_filters( self::$author . '_gf_gateway_config', array(), $form, $entry ), $form, $entry );
			}
			if ( ! empty( $entry["payment_date"] ) ) {
				/*
                if( ! class_exists("GFFormDisplay") )
                    require_once(GFCommon::get_base_path() . "/form_display.php");
                $default_anchor = 0;
                $anchor         = gf_apply_filters( 'gform_confirmation_anchor', $form['id'], $default_anchor ) ? "<a id='gf_{$form['id']}' name='gf_{$form['id']}' class='gform_anchor' ></a>" : '';
                $nl2br          = !empty( $form['confirmation'] ) && rgar( rgar( $form, 'confirmation' ), 'disableAutoformat' ) ? false : true;
                $cssClass       = rgar( $form, 'cssClass' );
                $confirmation = __('Duplicate Payment.' , 'paystar-payment-for-gravityforms');
                $confirmation   = empty( $confirmation ) ? "{$anchor} " : "{$anchor}<div id='gform_confirmation_wrapper_{$form['id']}' class='gform_confirmation_wrapper {$cssClass}'><div id='gform_confirmation_message_{$form['id']}' class='gform_confirmation_message_{$form['id']} gform_confirmation_message'>" . GFCommon::replace_variables( $confirmation, $form, $entry, false, true, $nl2br ) . '</div></div>';
                GFFormDisplay::$submission[$form_id] = array("is_confirmation" => true, "confirmation_message" => $confirmation, "form" => $form, "entry" => $entry, "lead" => $entry,"page_number"=> 1);
				*/
				return;
			}
			global $current_user;
			$user_id   = 0;
			$user_name = __( "Guest", 'paystar-payment-for-gravityforms' );
			if ( $current_user && $user_data = get_userdata( $current_user->ID ) ) {
				$user_id   = $current_user->ID;
				$user_name = $user_data->display_name;
			}
			$transaction_type = 1;
			if ( ! empty( $config["meta"]["type"] ) && $config["meta"]["type"] == 'subscription' ) {
				$transaction_type = 2;
			}
			if ( $payment_type == 'custom' ) {
				$Amount = $Total = gform_get_meta( $entry["id"], 'hannanstd_part_price_' . $form_id );
			} else {
				$Amount = $Total = self::get_order_total( $form, $entry );
			}
			$Total_Money = GFCommon::to_money( $Total, $entry["currency"] );
			$free = false;
			if ( empty( $_GET['no'] ) || sanitize_text_field($_GET['no']) != 'true' ) {
				if ( $payment_type != 'custom' ) {
					$Amount = GFPersian_Payments::amount( $Amount, 'IRT', $form, $entry );
				}
				$__params = $Amount . $post_order_id;
				if ( GFPersian_Payments::check_verification( $entry, __CLASS__, $__params ) ) {
					return;
				}
				require_once(dirname(__FILE__) . '/paystar_payment_helper.class.php');
				$p = new PayStar_Payment_Helper(self::get_terminal());
					$r = $p->paymentVerify($x = array(
							'status' => $post_status,
							'order_id' => $post_order_id,
							'ref_num' => $post_ref_num,
							'tracking_code' => $post_tracking_code,
							'amount' => ($Amount * ($entry["currency"] == 'IRT' ? 10 : 1))
						));
				if ($r)
				{
					$Message = '';
					$Status  = 'completed';
				}
				else
				{
					$Message = $p->error;
					$Status  = 'failed';
				}
				$Transaction_ID = ! empty( $p->txn_id ) ? $p->txn_id : '-';
			} else {
				$Status         = 'completed';
				$Message        = '';
				$Transaction_ID = apply_filters( self::$author . '_gf_rand_transaction_id', GFPersian_Payments::transaction_id( $entry ), $form, $entry );
				$free           = true;
			}
			$Status         = ! empty( $Status ) ? $Status : 'failed';
			$transaction_id = ! empty( $Transaction_ID ) ? $Transaction_ID : '';
			$transaction_id = apply_filters( self::$author . '_gf_real_transaction_id', $transaction_id, $Status, $form, $entry );
			$entry["payment_date"]     = gmdate( "Y-m-d H:i:s" );
			$entry["transaction_id"]   = $transaction_id;
			$entry["transaction_type"] = $transaction_type;
			if ( $Status == 'completed' ) {
				$entry["is_fulfilled"]   = 1;
				$entry["payment_amount"] = $Total;
				if ( $transaction_type == 2 ) {
					$entry["payment_status"] = "Active";
					RGFormsModel::add_note( $entry["id"], $user_id, $user_name, __( "Changes to data fields will only be applied to this entry message and will not affect the user status", 'paystar-payment-for-gravityforms' ) );
				} else {
					$entry["payment_status"] = "Paid";
				}
				if ( $free == true ) {
					//unset( $entry["payment_status"] );
					unset( $entry["payment_amount"] );
					unset( $entry["payment_method"] );
					unset( $entry["is_fulfilled"] );
					gform_delete_meta( $entry['id'], 'payment_gateway' );
					$Note = sprintf( __( 'Payment Status : Free - No Need to payment gateway', 'paystar-payment-for-gravityforms' ) );
				} else {
					$Note = sprintf( __( 'Payment Status : Successful - Paid Amount : %s - RefNum : %s - Card Number : %s', 'paystar-payment-for-gravityforms' ), $Total_Money, $transaction_id, (isset($_POST['card_number']) ? $_POST['card_number'] : '-') );
				}
				GFAPI::update_entry( $entry );
				if ( apply_filters( self::$author . '_gf_paystar_post', apply_filters( self::$author . '_gf_gateway_post', ( $payment_type != 'custom' ), $form, $entry ), $form, $entry ) ) {
					$has_post = GFCommon::has_post_field( $form["fields"] ) ? true : false;
					if ( ! empty( $config["meta"]["update_post_action1"] ) && $config["meta"]["update_post_action1"] != 'default' ) {
						$new_status = $config["meta"]["update_post_action1"];
					} else {
						$new_status = rgar( $form, 'postStatus' );
					}
					if ( empty( $entry["post_id"] ) && $has_post ) {
						$form['postStatus'] = $new_status;
						RGFormsModel::create_post( $form, $entry );
						$entry = GFPersian_Payments::get_entry( $entry_id );
					}
					if ( ! empty( $entry["post_id"] ) && $has_post ) {
						$post = get_post( $entry["post_id"] );
						if ( is_object( $post ) ) {
							if ( $new_status != $post->post_status ) {
								$post->post_status = $new_status;
								wp_update_post( $post );
							}
						}
					}
				}
				if ( ! empty( $__params ) ) {
					GFPersian_Payments::set_verification( $entry, __CLASS__, $__params );
				}
				$user_registration_slug = apply_filters( 'gf_user_registration_slug', 'gravityformsuserregistration' );
				$paypal_config          = array( 'meta' => array() );
				if ( ! empty( $config["meta"]["addon"] ) && $config["meta"]["addon"] == 'true' ) {
					if ( class_exists( 'GFAddon' ) && method_exists( 'GFAddon', 'get_registered_addons' ) ) {
						$addons = GFAddon::get_registered_addons();
						foreach ( (array) $addons as $addon ) {
							if ( is_callable( array( $addon, 'get_instance' ) ) ) {
								$addon = call_user_func( array( $addon, 'get_instance' ) );
								if ( is_object( $addon ) && method_exists( $addon, 'get_slug' ) ) {
									$slug = $addon->get_slug();
									if ( $slug != $user_registration_slug ) {
										$paypal_config['meta'][ 'delay_' . $slug ] = true;
									}
								}
							}
						}
					}
				}
				if ( ! empty( $config["meta"]["type"] ) && $config["meta"]["type"] == "subscription" ) {
					$paypal_config['meta'][ 'delay_' . $user_registration_slug ] = true;
				}
				do_action( "gform_paystar_fulfillment", $entry, $config, $transaction_id, $Total );
				do_action( "gform_gateway_fulfillment", $entry, $config, $transaction_id, $Total );
				do_action( "gform_paypal_fulfillment", $entry, $paypal_config, $transaction_id, $Total );
			} else if ( $Status == 'cancelled' ) {
				$entry["payment_status"] = "Cancelled";
				$entry["payment_amount"] = 0;
				$entry["is_fulfilled"]   = 0;
				GFAPI::update_entry( $entry );
				$Note = sprintf( __( 'Payment Status : Cancelled - Payable Amount : %s - RefNum : %s', 'paystar-payment-for-gravityforms' ), $Total_Money, $transaction_id );
			} else {
				$entry["payment_status"] = "Failed";
				$entry["payment_amount"] = 0;
				$entry["is_fulfilled"]   = 0;
				GFAPI::update_entry( $entry );
				$Note = sprintf( __( 'Payment Status : Unsuccessful - Payable Amount : %s - RefNum : %s - Reason : %s', 'paystar-payment-for-gravityforms' ), $Total_Money, $transaction_id, $Message );
			}
			$entry = GFPersian_Payments::get_entry( $entry_id );
			RGFormsModel::add_note( $entry["id"], $user_id, $user_name, $Note );
			do_action( 'gform_post_payment_status', $config, $entry, strtolower( $Status ), $transaction_id, '', $Total, '', '' );
			do_action( 'gform_post_payment_status_' . __CLASS__, $config, $form, $entry, strtolower( $Status ), $transaction_id, '', $Total, '', '' );
			if ( apply_filters( self::$author . '_gf_paystar_verify', apply_filters( self::$author . '_gf_gateway_verify', ( $payment_type != 'custom' ), $form, $entry ), $form, $entry ) ) {
				GFPersian_Payments::notification( $form, $entry );
				GFPersian_Payments::confirmation( $form, $entry, $Message );
			}
		}
	}

}
