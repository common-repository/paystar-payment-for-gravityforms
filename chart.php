<?php if ( ! defined( 'ABSPATH' ) ) exit;

class GFPersian_Chart_PayStar {

	public static function stats_page() {

		if ( ! empty( $_POST ) ) {
			check_admin_referer( "search", "gf_paystar_chart" );
		}

		$form_id = sanitize_text_field(rgget( "id" ));
		$form    = RGFormsModel::get_form_meta( $form_id );
		if ( empty( $form ) || ! is_numeric( $form_id ) || intval( $form_id ) != $form_id ) {
			die( __( 'Selected form not exists', 'paystar-payment-for-gravityforms' ) );
		}
		?>
        <style type="text/css">
            .paystar_graph_container {
                clear: both;
                padding-left: 5px;
                min-width: 789px;
                margin-right: 50px;
            }

            .paystar_message_container {
                clear: both;
                padding-left: 5px;
                text-align: center;
                padding-top: 120px;
                border: 1px solid #CCC;
                background-color: #FFF;
                width: 100%;
                height: 200px;
            }

            .paystar_summary_container {
                margin: 30px 60px;
                text-align: center;
                min-width: 740px;
                margin-left: 50px;
            }

            .paystar_summary_item {
                width: 180px;
                height: 70px;
                border-radius: 5px;
                background-color: #FFF;
                border: 1px solid #CCC;
                padding: 14px 8px;
                margin: 6px 3px 6px 0;
                display: -moz-inline-stack;
                display: inline-block;
                zoom: 1;
                *display: inline;
                text-align: center;
            }

            .paystar_summary_value {
                font-size: 20px;
                margin: 5px 0;
            }

            .paystar_summary_title {
                height: 40px;
            }

            .tooltipbox_blue {
                background: #0074A2;
                padding: 5px 10px 5px 5px;
                border-radius: 4px;
                color: #fff;
            }

            .tooltipbox_green {
                background: #50B432;
                padding: 5px 10px 5px 5px;
                border-radius: 4px;
                color: #fff;
            }

            .tooltipbox_orang {
                background: #EDC240;
                padding: 5px 10px 5px 5px;
                border-radius: 4px;
                color: #fff;
            }

            .tooltipbox_red {
                background: #AA4643;
                padding: 5px 10px 5px 5px;
                border-radius: 4px;
                color: #fff;
            }

            .ui-datepicker-title select, .ui-datepicker-title option {
                font-size: 11px !important;
            }

            .ui-datepicker th {
                font-size: 12px !important;
            }
        </style>
		<?php
		wp_dequeue_script( 'jquery-ui-datepicker' );
		wp_dequeue_script( 'gform_datepicker_init' );

		wp_register_script( 'jquery-ui-jdatepicker', esc_url(GFPersian_Payments::get_base_url()) . '/assets/js/jalali-datepicker.js', array(
			'jquery',
			'jquery-migrate',
			'jquery-ui-core',
		), GFCommon::$version, true );
		wp_enqueue_script( 'jquery-ui-jdatepicker' );

		wp_enqueue_style( "gform_datepicker_init", esc_url(GFCommon::get_base_url()) . "/css/datepicker.css", null, GFCommon::$version );
		do_action( 'gf_gateway_js' );

		wp_register_script( 'shamsi_chart', esc_url(GFPersian_Payments::get_base_url()) . '/assets/js/shamsi_chart.js', '', '', false );
		wp_enqueue_script( 'shamsi_chart' );
		/*
        <script type="text/javascript" src="<?php echo esc_url(GFPersian_Payments::get_base_url()) ?>/"></script>
        */

		?>

        <script type="text/javascript">
            var dp = jQuery.noConflict();
            dp(document).ready(function () {
                jQuery('.datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    showButtonPanel: true,
                    changeMonth: true,
                    changeYear: true
                });
            });
        </script>
        <div class="wrap">
            <ul class="subsubsub">
                <li><a class="<?php echo ( ! rgget( "tab" ) || rgget( "tab" ) == "today" ) ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>"><?php _e( "Today", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "yesterday" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=yesterday"><?php _e( "Last Day", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last7days" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last7days"><?php _e( "Last 7 Days", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "thisweek" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=thisweek"><?php _e( "This Week", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last30days" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last30days"><?php _e( "Last 30 Days", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "thismonth" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=thismonth"><?php _e( "This Month", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "lastmonth" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=lastmonth"><?php _e( "Last Month", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last2month" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last2month"><?php _e( "Last 2 Month", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last3month" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last3month"><?php _e( "Last 3 Month", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last6month" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last6month"><?php _e( "Last 6 Month", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last9month" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last9month"><?php _e( "Last 9 Month", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "last12month" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=last12month"><?php _e( "Last Year", 'paystar-payment-for-gravityforms' ); ?></a>
                    |
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "spring" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=spring"><?php _e( "Spring", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "summer" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=summer"><?php _e( "Summer", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "fall" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=fall"><?php _e( "Autumn", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "winter" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=winter"><?php _e( "Winter", 'paystar-payment-for-gravityforms' ); ?></a>|
                </li>
                <li><a class="<?php echo rgget( "tab" ) == "thisyear" ? "current" : "" ?>"
                       href="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( 'id' )) ?>&tab=thisyear"><?php _e( "This Year", 'paystar-payment-for-gravityforms' ); ?></a>
                </li>
                <br/><br/>
                <form method="post"
                      action="?page=gf_paystar&view=stats&id=<?php echo esc_html(rgget( "id" )) ?>&tab=selection"><?php wp_nonce_field( "search", "gf_paystar_chart" ) ?>
                    <span><?php _e( 'From Date', 'paystar-payment-for-gravityforms' ) ?></span>
                    <input type="text" name="min" class="datepicker"
                           value="<?php echo sanitize_text_field( rgpost( 'min' ) ); ?>" autocomplete="off"/>
                    <span style="margin-right:15px"><?php _e( 'To Date', 'paystar-payment-for-gravityforms' ) ?></span>
                    <input type="text" name="max" class="datepicker"
                           value="<?php echo sanitize_text_field( rgpost( 'max' ) ); ?>" autocomplete="off"/>
                    <input type="submit" class="button-primary button" name="submit"
                           value="<?php _e( 'Select', 'paystar-payment-for-gravityforms' ) ?>"><br>
                </form>
            </ul>

            <div class="clear"></div>
			<?php
			switch ( sanitize_text_field(rgget( "tab" )) ) {

				case "spring" :
					$chart_info          = self::season_chart_info( $form_id, 1, 1 );
					$chart_info_gateways = self::season_chart_info( $form_id, 2, 1 );
					$chart_info_hannan   = self::season_chart_info( $form_id, 3, 1 );
					$chart_info_site     = self::season_chart_info( $form_id, 4, 1 );
					break;

				case "summer" :
					$chart_info          = self::season_chart_info( $form_id, 1, 2 );
					$chart_info_gateways = self::season_chart_info( $form_id, 2, 2 );
					$chart_info_hannan   = self::season_chart_info( $form_id, 3, 2 );
					$chart_info_site     = self::season_chart_info( $form_id, 4, 2 );
					break;

				case "fall" :
					$chart_info          = self::season_chart_info( $form_id, 1, 3 );
					$chart_info_gateways = self::season_chart_info( $form_id, 2, 3 );
					$chart_info_hannan   = self::season_chart_info( $form_id, 3, 3 );
					$chart_info_site     = self::season_chart_info( $form_id, 4, 3 );
					break;

				case "winter" :
					$chart_info          = self::season_chart_info( $form_id, 1, 4 );
					$chart_info_gateways = self::season_chart_info( $form_id, 2, 4 );
					$chart_info_hannan   = self::season_chart_info( $form_id, 3, 4 );
					$chart_info_site     = self::season_chart_info( $form_id, 4, 4 );
					break;

				case "thisyear" :
					$chart_info          = self::yearly_chart_info( $form_id, 1 );
					$chart_info_gateways = self::yearly_chart_info( $form_id, 2 );
					$chart_info_hannan   = self::yearly_chart_info( $form_id, 3 );
					$chart_info_site     = self::yearly_chart_info( $form_id, 4 );
					break;

				case "last7days" :
					$chart_info          = self::lastxdays_chart_info( $form_id, 1, 7 );
					$chart_info_gateways = self::lastxdays_chart_info( $form_id, 2, 7 );
					$chart_info_hannan   = self::lastxdays_chart_info( $form_id, 3, 7 );
					$chart_info_site     = self::lastxdays_chart_info( $form_id, 4, 7 );
					break;

				case "thisweek" :
					$chart_info          = self::thisweek_chart_info( $form_id, 1 );
					$chart_info_gateways = self::thisweek_chart_info( $form_id, 2 );
					$chart_info_hannan   = self::thisweek_chart_info( $form_id, 3 );
					$chart_info_site     = self::thisweek_chart_info( $form_id, 4 );
					break;

				case "last30days" :
					$chart_info          = self::lastxdays_chart_info( $form_id, 1, 30 );
					$chart_info_gateways = self::lastxdays_chart_info( $form_id, 2, 30 );
					$chart_info_hannan   = self::lastxdays_chart_info( $form_id, 3, 30 );
					$chart_info_site     = self::lastxdays_chart_info( $form_id, 4, 30 );
					break;

				case "thismonth" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 1 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 1 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 1 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 1 );
					break;

				case "lastmonth" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 2 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 2 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 2 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 2 );
					break;

				case "last2month" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 60 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 60 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 60 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 60 );
					break;

				case "last3month" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 3 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 3 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 3 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 3 );
					break;

				case "last6month" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 6 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 6 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 6 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 6 );
					break;

				case "last9month" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 9 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 9 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 9 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 9 );
					break;

				case "last12month" :
					$chart_info          = self::targetmdays_chart_info( $form_id, 1, 12 );
					$chart_info_gateways = self::targetmdays_chart_info( $form_id, 2, 12 );
					$chart_info_hannan   = self::targetmdays_chart_info( $form_id, 3, 12 );
					$chart_info_site     = self::targetmdays_chart_info( $form_id, 4, 12 );
					break;

				case "selection" :
					$chart_info          = self::selection_chart_info( $form_id, 1, sanitize_text_field( rgpost( 'min' ) ), sanitize_text_field( rgpost( 'max' ) ) );
					$chart_info_gateways = self::selection_chart_info( $form_id, 2, sanitize_text_field( rgpost( 'min' ) ), sanitize_text_field( rgpost( 'max' ) ) );
					$chart_info_hannan   = self::selection_chart_info( $form_id, 3, sanitize_text_field( rgpost( 'min' ) ), sanitize_text_field( rgpost( 'max' ) ) );
					$chart_info_site     = self::selection_chart_info( $form_id, 4, sanitize_text_field( rgpost( 'min' ) ), sanitize_text_field( rgpost( 'max' ) ) );
					break;

				case "yesterday" :
					$chart_info          = self::tyday_chart_info( $form_id, 1, 2 );
					$chart_info_gateways = self::tyday_chart_info( $form_id, 2, 2 );
					$chart_info_hannan   = self::tyday_chart_info( $form_id, 3, 2 );
					$chart_info_site     = self::tyday_chart_info( $form_id, 4, 2 );
					break;

				default :
					$chart_info          = self::tyday_chart_info( $form_id, 1, 1 );
					$chart_info_gateways = self::tyday_chart_info( $form_id, 2, 1 );
					$chart_info_hannan   = self::tyday_chart_info( $form_id, 3, 1 );
					$chart_info_site     = self::tyday_chart_info( $form_id, 4, 1 );
					break;
			}
			?>

            <hr>

            <div class="clear"></div>
            <h2><?php _e( "PayStar Earning for form ", 'paystar-payment-for-gravityforms' ) ?><?php echo '"' . esc_html($form["title"]) . '"'; ?></h2>
            <div>
				<?php if ( empty( $chart_info["series"] ) ) { ?>
                    <div
                            class="paystar_message_container"><?php _e( "Not Found . ", 'paystar-payment-for-gravityforms' ) ?></div>
				<?php } else { ?>
                    <div class="paystar_graph_container">
                        <div id="graph_placeholder" style="width:100%;height:300px;"></div>
                    </div>
					<?php
				}

				$sales_label = __( "Count All PayStar Payments for this form", 'paystar-payment-for-gravityforms' );

				$transaction_totals = GFPersian_DB_PayStar::get_transaction_totals( $form_id );
				$total_sales        = empty( $transaction_totals["active"]["transactions"] ) ? 0 : $transaction_totals["active"]["transactions"];
				$total_revenue      = empty( $transaction_totals["active"]["revenue"] ) ? 0 : $transaction_totals["active"]["revenue"];
				?>
                <div class="paystar_summary_container">
                    <div class="paystar_summary_item">
                        <div
                                class="paystar_summary_title"><?php _e( 'Total PayStar Earning This form', 'paystar-payment-for-gravityforms' ) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( GFCommon::to_money( $total_revenue ), 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info["revenue_label"]) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info["revenue"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info["mid_label"]) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info["mid"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($sales_label) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $total_sales, 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info["sales_label"]) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info["sales"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info["midt_label"]) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info["midt"], 'fa' )) ?></div>
                    </div>


                </div>
            </div>

            <hr>
            <div class="clear"></div>
            <h2><?php _e( "Total Earning for form", 'paystar-payment-for-gravityforms' ) ?><?php echo '"' . esc_html($form["title"]) . '"'; ?></h2>
            <div>
				<?php if ( ! $chart_info_gateways["series"] ) { ?>
                    <div
                            class="paystar_message_container"><?php _e( "Not Found . ", 'paystar-payment-for-gravityforms' ) ?></div>
				<?php } else { ?>
                    <div class="paystar_graph_container">
                        <div id="graph_placeholder2" style="width:100%;height:300px;"></div>
                    </div>
					<?php
				}

				$sales_label = __( "Count All Payments for this form", 'paystar-payment-for-gravityforms' );

				$transaction_totals = GFPersian_DB_PayStar::get_transaction_totals_gateways( $form_id );
				$total_sales        = empty( $transaction_totals["active"]["transactions"] ) ? 0 : $transaction_totals["active"]["transactions"];
				$total_revenue      = empty( $transaction_totals["active"]["revenue"] ) ? 0 : $transaction_totals["active"]["revenue"];
				?>

                <div class="paystar_summary_container">

                    <div class="paystar_summary_item">
                        <div
                                class="paystar_summary_title"><?php _e( "Total All Earning this form", 'paystar-payment-for-gravityforms' ) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( GFCommon::to_money( $total_revenue ), 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_gateways["revenue_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_gateways["revenue"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_gateways["mid_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_gateways["mid"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($sales_label) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $total_sales, 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_gateways["sales_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_gateways["sales"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_gateways["midt_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_gateways["midt"], 'fa' )) ?></div>
                    </div>

                </div>
            </div>
            <hr>
            <div class="clear"></div>
            <h2><?php _e( "Total PayStar Earning", 'paystar-payment-for-gravityforms' ) ?></h2>
            <div>
				<?php if ( ! $chart_info_hannan["series"] ) { ?>
                    <div
                            class="paystar_message_container"><?php _e( "Not Found . ", 'paystar-payment-for-gravityforms' ) ?></div>
				<?php } else { ?>
                    <div class="paystar_graph_container">
                        <div id="graph_placeholder1" style="width:100%;height:300px;"></div>
                    </div>
					<?php
				}

				$sales_label = __( "Payments count of PayStar", 'paystar-payment-for-gravityforms' );

				$transaction_totals = GFPersian_DB_PayStar::get_transaction_totals_this_gateway();
				$total_sales        = empty( $transaction_totals["active"]["transactions"] ) ? 0 : $transaction_totals["active"]["transactions"];
				$total_revenue      = empty( $transaction_totals["active"]["revenue"] ) ? 0 : $transaction_totals["active"]["revenue"];
				?>
                <div class="paystar_summary_container">
                    <div class="paystar_summary_item">
                        <div
                                class="paystar_summary_title"><?php _e( "Payments Count of All PayStar Forms", 'paystar-payment-for-gravityforms' ) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( GFCommon::to_money( $total_revenue ), 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_hannan["revenue_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_hannan["revenue"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_hannan["mid_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_hannan["mid"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($sales_label) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $total_sales, 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_hannan["sales_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_hannan["sales"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_hannan["midt_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_hannan["midt"], 'fa' )) ?></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="clear"></div>
            <h2><?php _e( " Total Site Earning (All Forms, All gateways)", 'paystar-payment-for-gravityforms' ) ?></h2>
            <div>
				<?php if ( ! $chart_info_site["series"] ) { ?>
                    <div
                            class="paystar_message_container"><?php _e( "Not Found . ", 'paystar-payment-for-gravityforms' ) ?></div>
				<?php } else { ?>
                    <div class="paystar_graph_container">
                        <div id="graph_placeholder3" style="width:100%;height:300px;"></div>
                    </div>
					<?php
				}

				$sales_label = __( "Payments count of All Site Forms", 'paystar-payment-for-gravityforms' );

				$transaction_totals = GFPersian_DB_PayStar::get_transaction_totals_site();
				$total_sales        = empty( $transaction_totals["active"]["transactions"] ) ? 0 : $transaction_totals["active"]["transactions"];
				$total_revenue      = empty( $transaction_totals["active"]["revenue"] ) ? 0 : $transaction_totals["active"]["revenue"];
				?>
                <div class="paystar_summary_container">
                    <div class="paystar_summary_item">
                        <div
                                class="paystar_summary_title"><?php _e( "Total Payments of All Site Forms", 'paystar-payment-for-gravityforms' ) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( GFCommon::to_money( $total_revenue ), 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_site["revenue_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_site["revenue"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_site["mid_label"]) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_site["mid"], 'fa' )) ?></div>
                    </div>
                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($sales_label) ?></div>
                        <div class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $total_sales, 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_site["sales_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_site["sales"], 'fa' )) ?></div>
                    </div>

                    <div class="paystar_summary_item">
                        <div class="paystar_summary_title"><?php echo esc_html($chart_info_site["midt_label"]) ?></div>
                        <div
                                class="paystar_summary_value"><?php echo esc_html(GF_tr_num( $chart_info_site["midt"], 'fa' )) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">

            function startShowTooltip(item, graph) {
                if (item) {
                    if (!previousPoint || previousPoint[0] != item.datapoint[0]) {
                        previousPoint = item.datapoint;
                        jQuery("#paystar_graph_tooltip").remove();
                        var x = item.datapoint[0].toFixed(2),
                            y = item.datapoint[1].toFixed(2);
                        showTooltip(item.pageX, item.pageY, graph[item.dataIndex]);
                    }
                }
                else {
                    jQuery("#paystar_graph_tooltip").remove();
                    previousPoint = null;
                }
            }

			<?php if (! empty( $chart_info["series"] )) :?>
            var paystar_graph_tooltips = <?php echo esc_html(GF_tr_num( $chart_info["tooltips"], 'fa' )) ?>;
            jQuery.plot(jQuery("#graph_placeholder"), [<?php echo esc_html($chart_info["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            jQuery(window).resize(function () {
                jQuery.plot(jQuery("#graph_placeholder"), [<?php echo esc_html($chart_info["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            });
            var previousPoint = null;
            jQuery("#graph_placeholder").bind("plothover", function (event, pos, item) {
                startShowTooltip(item, paystar_graph_tooltips);
            });
			<?php endif;?>


			<?php if (! empty( $chart_info_hannan["series"] )) :?>
            var paystar_graph_tooltip1s1 = <?php echo esc_html(GF_tr_num( $chart_info_hannan["tooltips"], 'fa' )) ?>;
            jQuery.plot(jQuery("#graph_placeholder1"), [<?php echo esc_html($chart_info_hannan["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            jQuery(window).resize(function () {
                jQuery.plot(jQuery("#graph_placeholder1"), [<?php echo esc_html($chart_info_hannan["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            });
            var previousPoint = null;
            jQuery("#graph_placeholder1").bind("plothover", function (event, pos, item) {
                startShowTooltip(item, paystar_graph_tooltip1s1);
            });
			<?php endif;?>


			<?php if (! empty( $chart_info_gateways["series"] )) :?>
            var paystar_graph_tooltip2s2 = <?php echo esc_html(GF_tr_num( $chart_info_gateways["tooltips"], 'fa' )) ?>;
            jQuery.plot(jQuery("#graph_placeholder2"), [<?php echo esc_html($chart_info_gateways["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            jQuery(window).resize(function () {
                jQuery.plot(jQuery("#graph_placeholder2"), [<?php echo esc_html($chart_info_gateways["series"]) ?>], <?php echo esc_html($chart_info["options"]) ?>);
            });
            var previousPoint = null;
            jQuery("#graph_placeholder2").bind("plothover", function (event, pos, item) {
                startShowTooltip(item, paystar_graph_tooltip2s2);
            });
			<?php endif;?>


			<?php if (! empty( $chart_info_site["series"] )) :?>
            var paystar_graph_tooltip3s3 = <?php echo esc_html(GF_tr_num( $chart_info_site["tooltips"], 'fa' )) ?>;
            jQuery.plot(jQuery("#graph_placeholder3"), [<?php echo esc_html( $chart_info_site["series"]) ?>], <?php echo esc_html( $chart_info["options"]) ?>);
            jQuery(window).resize(function () {
                jQuery.plot(jQuery("#graph_placeholder3"), [<?php echo esc_html( $chart_info_site["series"]) ?>], <?php echo esc_html( $chart_info["options"]) ?>);
            });
            var previousPoint = null;
            jQuery("#graph_placeholder3").bind("plothover", function (event, pos, item) {
                startShowTooltip(item, paystar_graph_tooltip3s3);
            });
			<?php endif;?>

            function showTooltip(x, y, contents) {
                jQuery('<div id="paystar_graph_tooltip">' + contents + '<div class="tooltip_tip1"></div></div>').css({
                    position: 'absolute',
                    display: 'none',
                    opacity: 1,
                    width: '150px',
                    height: '60px',
                    top: y - 89,
                    left: x - 79
                }).appendTo("body").fadeIn(200);
            }

            function convertToMoney(number) {
                var currency = getCurrentCurrency();
                return currency.toMoney(number);
            }

            function getCurrentCurrency() {
				<?php if ( ! class_exists( "RGCurrency" ) ) {
				require_once( ABSPATH . "/" . PLUGINDIR . "/gravityforms/currency.php" );
			}
				$current_currency = RGCurrency::get_currency( GFCommon::get_currency() );
				?>
                var currency = new Currency(<?php echo esc_html(GFCommon::json_encode( $current_currency )) ?>);
                return currency;
            }

            function weekday(val, axis) {
                var g_y = new Date(val).getFullYear();
                var g_m = new Date(val).getMonth() + 1;
                var g_d = new Date(val).getDate();
                shamsi = gregorian_to_jalali(g_y, g_m, g_d);
                sh_month = ["-", "فروردین", "اردیبهشت", "خرداد", "تير", "مرداد", "شهريور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
                week = ["يكشنبه", "دوشنبه", "سه شنبه", "چهارشنبه", "پنج شنبه", "جمعه", "شنبه"];
                week = week[new Date(val).getDay()];
                return week + ' - ' + shamsi[2] + ' ' + sh_month[shamsi[1]] + ' ' + shamsi[0];
            }

            function shamsi_1(val, axis) {
                var g_y = new Date(val).getFullYear();
                var g_m = new Date(val).getMonth() + 1;
                var g_d = new Date(val).getDate();
                shamsi = gregorian_to_jalali(g_y, g_m, g_d);
                sh_month = ["-", "فروردین", "اردیبهشت", "خرداد", "تير", "مرداد", "شهريور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
                return shamsi[2] + ' ' + sh_month[shamsi[1]] + ' ' + shamsi[0];
            }

            function shamsi_2(val, axis) {
                var g_y = new Date(val).getFullYear();
                var g_m = new Date(val).getMonth() + 1;
                var g_d = new Date(val).getDate();
                shamsi = gregorian_to_jalali(g_y, g_m, g_d);
                sh_month = ["-", "فروردین", "اردیبهشت", "خرداد", "تير", "مرداد", "شهريور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"];
                H = new Date(val).getHours();
                H = (H < 10) ? "0" + H : H;
                i = new Date(val).getMinutes();
                i = (i < 10) ? "0" + i : i;
                s = new Date(val).getSeconds();
                s = (s < 10) ? "0" + s : s;
                return ' ساعت ' + H;
            }
        </script>
		<?php
	}

	public static function get_graph_timestamp( $local_datetime ) {
		$local_timestamp      = mysql2date( "G", $local_datetime );
		$local_date_timestamp = mysql2date( "G", gmdate( "Y-m-d 23:59:59", $local_timestamp ) );
		$timestamp            = ( $local_date_timestamp - ( 24 * 60 * 60 ) + 1 ) * 1000;

		return $timestamp;
	}

	public static function get_mysql_tz_offset() {

		$time_zone_orig = $time_zone = get_option( 'gmt_offset' );

		$prefix    = intval( $time_zone ) > 0 ? '+' : '-';
		$time_zone = abs( $time_zone ) * 3600;
		$time_zone = gmdate( 'H:i', $time_zone );
		$time_zone = $prefix . $time_zone;

		$today = date( 'Y-m-d H:i:s' );
		$date  = new DateTime( $today );

		$tzn = abs( $time_zone_orig ) * 3600;
		$tzh = intval( gmdate( 'H', $tzn ) );
		$tzm = intval( gmdate( 'i', $tzn ) );
		try {
			if ( intval( $time_zone_orig ) < 0 ) {
				$date->sub( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			} else {
				$date->add( new DateInterval( 'P0DT' . $tzh . 'H' . $tzm . 'M' ) );
			}
		} catch ( Exception $e ) {
			return array( 'tz' => $time_zone, 'today' => time() );
		}
		$today = $date->format( 'Y-m-d H:i:s' );
		$today = strtotime( $today );

		return array( 'tz' => $time_zone, 'today' => $today );
	}

	public static function lastxdays_chart_info( $form_id, $chart, $x ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];
		$n         = $t = '';
		$series    = $options = $datat = $tooltips = $revenue_label = $revenue_week = $sales_label = $sales_week = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( 'PayStar this form', 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( 'All methods this form', 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "PayStar All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_week   = 0;
		$revenue_week = 0;
		$tooltips     = "";

		$today    = date( 'Y-m-d', $tday );
		$today_n  = date( 'Ymd', $tday );
		$targetdb = $today_n;

		if ( ! empty( $results ) ) {

			$data = "[";
			foreach ( $results as $result ) {

				$timeX_tooltips = GF_jdate( 'l - d F', strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );

				$target  = date( 'Ymd', strtotime( $result->date ) );
				$target2 = date( 'Y-m-d', strtotime( $result->date ) );
				$date    = new DateTime( $today_n );
				if ( $x == 7 ) {
					$date->sub( new DateInterval( 'P6DT0H0M' ) );
				}
				if ( $x == 30 ) {
					$date->sub( new DateInterval( 'P29DT0H0M' ) );
				}
				$lastxt  = $date->format( 'Y-m-d' );
				$lastxtf = $date->format( 'Ymd' );
				if ( $target > $targetdb ) {
					$targetdb = $target;
					$today    = $target2;
				}
				if ( $target >= $lastxtf && $today_n >= $target ) {
					$sales_week   += $result->new_sales;
					$revenue_week += $result->amount_sold;
					$datat        = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}
				if ( $target >= $lastxtf && $targetdb >= $target ) {
					$datat = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}
				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";
			$options  = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time', timeformat: '%d',tickFormatter: shamsi_1, minTickSize:[1, 'day'],min: (new Date('$lastxt')).getTime(),max: (new Date('$today')).getTime()},
				yaxis: {tickFormatter: convertToMoney}
			}";
		}
		if ( $x == 7 ) {
			$n   = __( '7 Days', 'paystar-payment-for-gravityforms' );
			$mid = 7;
		}
		if ( $x == 30 ) {
			$n   = __( '30 Days', 'paystar-payment-for-gravityforms' );
			$mid = 30;
		}

		$sales_label = sprintf( __( "Count payments %s past %s", 'paystar-payment-for-gravityforms' ), $n, $t );

		$midt          = $mid ? $sales_week / $mid : 0;
		$mid           = ( $mid ? GFCommon::to_money( $revenue_week / $mid ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt          = number_format( $midt, 3, '.', '' ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label    = sprintf( __( "Average Count Payments %s past %s", 'paystar-payment-for-gravityforms' ), $n, $t );
		$mid_label     = sprintf( __( "Average Payments %s past %s", 'paystar-payment-for-gravityforms' ), $n, $t );
		$revenue_week  = GFCommon::to_money( $revenue_week );
		$revenue_label = sprintf( __( "Total Payments %s past %s", 'paystar-payment-for-gravityforms' ), $n, $t );

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_week,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_week,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}


	public static function thisweek_chart_info( $form_id, $chart ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];

		$series = $options = $datat = $tooltips = $revenue_label = $revenue_week = $sales_label = $sales_week = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( "PayStar this form", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "All gateways this form", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}


		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "PayStar All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_week   = 0;
		$revenue_week = 0;
		$tooltips     = "";

		$today_n = date( 'Y-m-d H:i:s', $tday );
		$today_w = date( 'w', $tday );
		if ( $today_w < 6 ) {
			$today_w = $today_w + 1;
		} else if ( $today_w == 6 ) {
			$today_w = 0;
		}

		switch ( $today_w ) {
			case "0" : // شنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P0DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P6DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "1" : //یکشنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P1DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P5DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "2" : //دوشنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P2DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P4DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "3" : //سه شنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P3DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P3DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "4" : //چهار شنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P4DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P2DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "5" : // پنجشنبه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P5DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P1DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;

			case "6" : //جمعه
				$date = new DateTime( $today_n );
				$date->sub( new DateInterval( 'P6DT0H0M' ) );
				$abz   = $date->format( 'm d, Y' );
				$abz_t = $date->format( 'Ymd' );
				$date  = new DateTime( $today_n );
				$date->add( new DateInterval( 'P0DT0H0M' ) );
				$ebz   = $date->format( 'm d, Y' );
				$ebz_t = $date->format( 'Ymd' );
				break;
		}

		if ( ! empty( $results ) ) {

			$data = "[";
			foreach ( $results as $result ) {

				$timeX_tooltips = GF_jdate( 'l - d F Y', strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );

				$target = date( 'Ymd', strtotime( $result->date ) );
				if ( $target >= $abz_t && $ebz_t >= $target ) {
					$sales_week   += $result->new_sales;
					$revenue_week += $result->amount_sold;
					$datat        = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}
				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";
			$options  = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: weekday, tickSize:[1, 'day'],min: (new Date('$abz 00:00:00')).getTime(),max: (new Date('$ebz 23:59:59')).getTime()},
				yaxis: {tickFormatter: convertToMoney}
			}";
		}

		$sales_label = __( "Payments Count of This Week ", 'paystar-payment-for-gravityforms' ) . $t;

		$midt          = $sales_week / 7;
		$midt          = ( $midt ? number_format( $midt, 3, '.', '' ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label    = __( "Average Payments Count of This Week ", 'paystar-payment-for-gravityforms' ) . $t;
		$mid           = GFCommon::to_money( $revenue_week / 7 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$mid_label     = __( "Average Payments This Week ", 'paystar-payment-for-gravityforms' ) . $t;
		$revenue_week  = GFCommon::to_money( $revenue_week );
		$revenue_label = __( "Total Payments This Week ", 'paystar-payment-for-gravityforms' ) . $t;

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_week,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_week,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}


	public static function targetmdays_chart_info( $form_id, $chart, $xmonth ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];
		$n         = $t = '';
		$strd      = '';
		$series    = $options = $datat = $tooltips = $revenue_label = $revenue_thistday = $sales_label = $sales_thistday = $mid_label = $mid = $midt_label = $midt = '';
		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( "PayStar this form", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "All gateways this form", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "PayStar All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "PayStar All forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_thistday   = 0;
		$revenue_thistday = 0;
		$tooltips         = "";

		$today          = date( 'Y-m-d', $tday );
		$saremaheshamsi = strtotime( $today ) + ( ( GF_jdate( 't', strtotime( $today ), '', date_default_timezone_get(), 'en' ) - GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 );
		$entbaz         = date( 'm d , Y', $saremaheshamsi );
		$endd           = date( 'Y-m-d', $saremaheshamsi );

		if ( ! empty( $results ) ) {
			if ( $xmonth == 2 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P1M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) + ( ( GF_jdate( 't', strtotime( $today ), '', date_default_timezone_get(), 'en' ) - GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 );
				$entbaz         = date( 'm d , Y', $saremaheshamsi );
				$endd           = date( 'Y-m-d', $saremaheshamsi );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 1 ) {
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 60 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P1M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 3 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P2M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 6 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P5M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 9 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P8M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			if ( $xmonth == 12 ) {
				$date = new DateTime( $today );
				$date->sub( new DateInterval( 'P11M' ) );
				$today          = $date->format( 'Y-m-d' );
				$saremaheshamsi = strtotime( $today ) - ( ( GF_jdate( 'j', strtotime( $today ), '', date_default_timezone_get(), 'en' ) ) * 86400 ) + 86400;
				$ebtbaz         = date( 'm d , Y', $saremaheshamsi );
				$strd           = date( 'Y-m-d', $saremaheshamsi );
			}

			list( $m, $d, $n, $y ) = explode( " ", $ebtbaz );
			$date     = new DateTime( "$y-$m-$d" );
			$ebtbaz_w = $date->format( 'Ymd' );
			list( $m, $d, $n, $y ) = explode( " ", $entbaz );
			$date     = new DateTime( "$y-$m-$d" );
			$entbaz_w = $date->format( 'Ymd' );
			$data     = "[";

			foreach ( $results as $result ) {

				$timeX_tooltips = GF_jdate( 'l - d F Y', strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );

				$target = date( 'Ymd', strtotime( $result->date ) );
				if ( $entbaz_w >= $target && $target >= $ebtbaz_w ) {
					$sales_thistday   += $result->new_sales;
					$revenue_thistday += $result->amount_sold;
					$datat            = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}

				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( " تعداد پرداخت", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";

			if ( $xmonth == 1 || $xmonth == 2 ) {
				$n = GF_jdate( 'F', strtotime( $today ), '', date_default_timezone_get(), 'en' );
				$n = $n . __( " Month", 'paystar-payment-for-gravityforms' );
			}
			if ( $xmonth == 60 || $xmonth == 3 || $xmonth == 6 || $xmonth == 9 || $xmonth == 12 ) {
				$n = $xmonth;

				if ( $xmonth == 60 ) {
					$n = 2;
				}
				$n = $n . __( " Last Month", 'paystar-payment-for-gravityforms' );

				if ( $xmonth == 12 ) {
					$n = __( " Last Year", 'paystar-payment-for-gravityforms' );
				}
			}
			if ( $xmonth == 1 || $xmonth == 2 || $xmonth == 60 ) {
				$mt = 1;
			}
			if ( $xmonth == 3 || $xmonth == 6 ) {
				$mt = 5;
			}
			if ( $xmonth == 9 || $xmonth == 12 ) {
				$mt = 10;
			}

			$series  = "{data:" . $data . ", " . $dt . "";
			$options = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: shamsi_1, minTickSize:[$mt, 'day'],min: (new Date('$ebtbaz 00:00:00')).getTime(),max: (new Date('$entbaz 23:59:59')).getTime()},
				yaxis: {tickFormatter: convertToMoney}
			}";
		}

		if ( $xmonth == 1 || $xmonth == 2 ) {
			$n = GF_jdate( 'F', strtotime( $today ), '', date_default_timezone_get(), 'en' );
			$n = $n . __( " Month", 'paystar-payment-for-gravityforms' );
		}

		if ( $xmonth == 60 || $xmonth == 3 || $xmonth == 6 || $xmonth == 9 || $xmonth == 12 ) {

			$n = $xmonth;

			if ( $xmonth == 60 ) {
				$n = 2;
			}

			$n = $n . __( ' Last Month', 'paystar-payment-for-gravityforms' );

			if ( $xmonth == 12 ) {
				$n = __( 'Last Year', 'paystar-payment-for-gravityforms' );
			}
		}

		$sales_label = __( 'Payments Count of  ', 'paystar-payment-for-gravityforms' ) . $n . ' ' . $t;

		$strd             = date_create( $strd );
		$endd             = date_create( $endd );
		$diff             = date_diff( $strd, $endd );
		$midd             = $diff->format( "%a" ) + 1;
		$midt             = $midd ? $sales_thistday / $midd : 0;
		$midt             = number_format( $midt, 3, '.', '' ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label       = __( 'Average Payment Count of ', 'paystar-payment-for-gravityforms' ) . $n . ' ' . $t;
		$mid              = ( $midd ? GFCommon::to_money( $revenue_thistday / $midd ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$mid_label        = __( 'Average Payments of ', 'paystar-payment-for-gravityforms' ) . $n . ' ' . $t;
		$revenue_label    = __( 'Payments Count of  ', 'paystar-payment-for-gravityforms' ) . $n . ' ' . $t;
		$revenue_thistday = GFCommon::to_money( $revenue_thistday );

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_thistday,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_thistday,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}


	public static function tyday_chart_info( $form_id, $chart, $day ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];

		$series = $options = $datat = $tooltips = $revenue_label = $revenue_today = $sales_label = $sales_today = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( "پی استار این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY hour(date) , day(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "همه روشهای این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY hour(date) , day(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "color: '#EDC240'}";
			$t       = __( "All PayStar Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY hour(date) , day(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c = 'red';
			$dt = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "همه فرم های سایت", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY hour(date) , day(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_today   = 0;
		$revenue_today = 0;
		$tooltips      = "";
		$n             = '';
		$today         = date( 'Y-m-d H:i:s', $tday );
		$date          = new DateTime( $today );
		if ( $day == 1 ) {
			$n    = __( "Today", 'paystar-payment-for-gravityforms' );
			$baze = date( 'm d , Y', $tday );
			$ty   = date( 'Ymd', $tday );
		} else if ( $day == 2 ) {
			$n = __( "Last Day", 'paystar-payment-for-gravityforms' );
			$date->sub( new DateInterval( 'P1DT0H0M' ) );
			$baze = $date->format( 'm d , Y' );
			$ty   = $date->format( 'Ymd' );
		}

		if ( ! empty( $results ) ) {

			$data = "[";
			foreach ( $results as $result ) {

				$h = GF_jdate( 'H', strtotime( $result->date ), '', date_default_timezone_get(), 'en' );
				$h = intval( $h ) + 1;
				if ( $h < 10 ) {
					$h = "0" . $h;
				}

				$timeX_tooltips = GF_jdate( "l - d F Y ساعت H تا $h", strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$target         = date( 'Ymd', strtotime( $result->date ) );
				$date           = new DateTime( $result->date );
				$H              = $date->format( 'H' );
				$H              = intval( $H ) + 1;

				if ( $H < 10 ) {
					$H = "0" . $H;
				}
				$d = $date->format( 'd' );
				$m = $date->format( 'm' );
				$y = $date->format( 'Y' );

				if ( $target == $ty ) {
					$sales_today   += $result->new_sales;
					$revenue_today += $result->amount_sold;
					$datat         = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}

				$data .= "[(new Date('$m $d , $y $H:00:30')).getTime(),{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";
			$options  = "{
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: shamsi_2, tickSize:[1, 'hour'],
				min: (new Date('$baze 00:00:00')).getTime(),max: (new Date('$baze 24:59:00')).getTime()},
				yaxis: {tickFormatter: convertToMoney},
				bars: {show:true, align:'right', barWidth: (1 * 59 * 60 * 1000)},
				colors: ['#a3bcd3', '#14568a'],
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'}
			}";
		}

		$sales_label = __( "Payment Count of ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;

		$midt          = $sales_today / 24;
		$midt          = number_format( $midt, 3, '.', '' ) . __( " در ساعت", 'paystar-payment-for-gravityforms' );
		$midt_label    = __( "Average Payment Count of ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;
		$mid           = GFCommon::to_money( $revenue_today / 24 ) . __( " در ساعت", 'paystar-payment-for-gravityforms' );
		$mid_label     = __( "میانگین پرداخت های ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;
		$revenue_today = GFCommon::to_money( $revenue_today );
		$revenue_label = __( "Payments Count of ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_today,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_today,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}

	public static function yearly_chart_info( $form_id, $chart ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];

		$t      = '';
		$series = $options = $datat = $tooltips = $revenue_label = $revenue_season = $sales_label = $sales_season = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c  = 'blue';
			$dt = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t  = __( "PayStar This Form", 'paystar-payment-for-gravityforms' );

			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
											FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
											WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                            group by date
                                            order by date desc" );
		}
		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "همه روشهای این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
											FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
											WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                            group by date
                                            order by date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "All PayStar Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
											FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
											WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                            group by date
                                            order by date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "All Site Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
											FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
											WHERE l.status='active' AND l.is_fulfilled=1
                                            group by date
                                            order by date desc" );
		}

		$sales_yearly   = 0;
		$revenue_yearly = 0;
		$tooltips       = "";

		$emsal  = date( 'Y', $tday );
		$kabise = GF_jdate( 'L', $tday, '', date_default_timezone_get(), 'en' );
		if ( $kabise == 1 ) {
			$avalesal = new DateTime( "$emsal-03-20" );
			$emsal ++;
			$akharesal = new DateTime( "$emsal-03-19" );
		} else {
			$avalesal = new DateTime( "$emsal-03-21" );
			$emsal ++;
			$akharesal = new DateTime( "$emsal-03-20" );
		}

		$avalesal_w  = $avalesal->format( 'Ymd' );
		$strd        = $avalesal->format( 'Y-m-d' );
		$avalesal    = $avalesal->format( 'm d , Y' );
		$akharesal_w = $akharesal->format( 'Ymd' );
		$endd        = $akharesal->format( 'Y-m-d' );
		$akharesal   = $akharesal->format( 'm d , Y' );

		if ( ! empty( $results ) ) {
			$data = "[";
			foreach ( $results as $result ) {
				//
				$timeX_tooltips = GF_jdate( "d F Y", strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );
				//
				$target = date( 'Ymd', strtotime( $result->date ) );
				if ( $akharesal_w >= $target && $target >= $avalesal_w ) {
					$sales_yearly   += $result->new_sales;
					$revenue_yearly += $result->amount_sold;
					$datat          = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}
				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}
			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";
			$options  = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: shamsi_1,  minTickSize:[10, 'day'],min: (new Date('$avalesal 00:00:00')).getTime(),max: (new Date('$akharesal 00:00:00')).getTime()},
                yaxis: {tickFormatter: convertToMoney}
			}";
		}

		$sales_label = __( "Payments Count of This Year ", 'paystar-payment-for-gravityforms' ) . $t;

		$strd           = date_create( $strd );
		$endd           = date_create( $endd );
		$diff           = date_diff( $strd, $endd );
		$midd           = $diff->format( "%a" ) + 1;
		$midt           = $midd ? $sales_yearly / $midd : 0;
		$midt           = number_format( $midt, 3, '.', '' ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label     = __( "Average Payments Count of This Year ", 'paystar-payment-for-gravityforms' ) . $t;
		$mid            = ( $midd ? GFCommon::to_money( $revenue_yearly / $midd ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$mid_label      = __( "Average Payments This Year ", 'paystar-payment-for-gravityforms' ) . $t;
		$revenue_yearly = GFCommon::to_money( $revenue_yearly );
		$revenue_label  = __( "Total Payments Count of This Year ", 'paystar-payment-for-gravityforms' ) . $t;

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_yearly,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_yearly,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}


	public static function season_chart_info( $form_id, $chart, $season ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );
		$tday      = $tz['today'];

		$t      = '';
		$midd   = '';
		$series = $options = $tooltips = $revenue_label = $revenue_today = $sales_label = $sales_today = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( "پی استار این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "همه روشهای این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "All PayStar Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "همه فرم های سایت", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_season   = 0;
		$revenue_season = 0;
		$tooltips       = "";

		$today     = date( 'Y-m-d', $tday );
		$avalesal  = strtotime( $today ) - ( GF_jdate( 'z', $tday, '', date_default_timezone_get(), 'en' ) * 86400 );
		$avalesal  = date( 'm d , Y', $avalesal );
		$akharesal = strtotime( $today ) + ( GF_jdate( 'Q', $tday, '', date_default_timezone_get(), 'en' ) * 86400 );
		$akharesal = date( 'm d , Y', $akharesal );
		list( $m, $d, $n, $y ) = explode( " ", $avalesal );

		$date       = new DateTime( "$y-$m-$d" );
		$avalesal_w = $date->format( 'Ymd' );
		$avalesal_t = $date->format( 'Y-m-d' );
		list( $m, $d, $n, $y ) = explode( " ", $akharesal );

		$date        = new DateTime( "$y-$m-$d" );
		$akharesal_w = $date->format( 'Ymd' );
		$akharesal_t = $date->format( 'Y-m-d' );
		$endd        = $akharesal_t;

		if ( $season == 1 ) {
			$n      = __( 'Spring', 'paystar-payment-for-gravityforms' );
			$ebtda  = $avalesal_t;
			$enteha = strtotime( $ebtda ) + ( 93 * 86400 ) - 86400;
			$enteha = date( 'm d , Y', $enteha );
			$ebtda  = $avalesal;
			$midd   = 93;
		}

		if ( $season == 2 ) {
			$n      = __( 'Summer', 'paystar-payment-for-gravityforms' );
			$ebtda  = $avalesal_t;
			$ebtda  = strtotime( $ebtda ) + ( 93 * 86400 );
			$ebtda  = date( 'm d , Y', $ebtda );
			$enteha = $avalesal_t;
			$enteha = strtotime( $enteha ) + ( 186 * 86400 ) - 86400;
			$enteha = date( 'm d , Y', $enteha );
			$midd   = 93;
		}

		if ( $season == 3 ) {
			$n      = __( 'Autumn', 'paystar-payment-for-gravityforms' );
			$ebtda  = $avalesal_t;
			$ebtda  = strtotime( $ebtda ) + ( 186 * 86400 );
			$ebtda  = date( 'm d , Y', $ebtda );
			$enteha = $avalesal_t;
			$enteha = strtotime( $enteha ) + ( 276 * 86400 ) - 86400;
			$enteha = date( 'm d , Y', $enteha );
			$midd   = 90;
		}

		if ( $season == 4 ) {
			$n      = __( 'Winter', 'paystar-payment-for-gravityforms' );
			$ebtda  = $avalesal_t;
			$ebtda  = strtotime( $ebtda ) + ( 276 * 86400 );
			$strd   = date( 'Y-m-d', $ebtda );
			$ebtda  = date( 'm d , Y', $ebtda );
			$strd   = date_create( $strd );
			$endd   = date_create( $endd );
			$diff   = date_diff( $strd, $endd );
			$midd   = $diff->format( "%a" ) + 1;
			$enteha = $akharesal;
		}

		if ( ! empty( $results ) ) {

			$data = "[";
			foreach ( $results as $result ) {

				$timeX_tooltips = GF_jdate( 'l - d F Y', strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );

				$faslt  = GF_jdate( 'b', strtotime( $result->date ), '', date_default_timezone_get(), 'en' );
				$target = date( 'Ymd', strtotime( $result->date ) );
				if ( ( $akharesal_w >= $target && $target >= $avalesal_w && $faslt == $season ) || ( $enteha >= $target && $target >= $ebtda ) ) {
					$sales_season   += $result->new_sales;
					$revenue_season += $result->amount_sold;
					$datat          = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}

				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";
			$options  = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: shamsi_1, minTickSize:[3, 'day'],min: (new Date('$ebtda 00:00:00')).getTime(),max: (new Date('$enteha 23:59:59')).getTime()},
				yaxis: {tickFormatter: convertToMoney}
			}";
		}


		$sales_label = __( "Payment Count of  ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;

		$midt           = $midt ? $sales_season / $midd : 0;
		$midt           = ( $midt ? number_format( $midt, 3, '.', '' ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label     = __( "Average Payment Count of  ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;
		$mid            = ( $midd ? GFCommon::to_money( $revenue_season / $midd ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$mid_label      = __( "Average Payments of  ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;
		$revenue_label  = __( "Payments Count of  ", 'paystar-payment-for-gravityforms' ) . $n . " " . $t;
		$revenue_season = GFCommon::to_money( $revenue_season );

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_season,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_season,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}


	public static function selection_chart_info( $form_id, $chart, $min, $max ) {

		global $wpdb;
		$tz        = self::get_mysql_tz_offset();
		$tz_offset = esc_sql( $tz['tz'] );


		$midd = '';
		$t    = $n = '';

		$series = $options = $datat = $tooltips = $revenue_label = $revenue_today = $sales_label = $sales_today = $mid_label = $mid = $midt_label = $midt = '';

		if ( $chart == 1 ) {
			$c       = 'blue';
			$dt      = "points: { symbol: 'diamond', fillColor: '#058DC7' }, color: '#058DC7'}";
			$t       = __( "پی استار این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 2 ) {
			$c       = 'green';
			$dt      = "points: { symbol: 'square', fillColor: '#50B432' }, color: '#50B432'}";
			$t       = __( "همه روشهای این فرم", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE form_id={$form_id} AND l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 3 ) {
			$c       = 'orang';
			$dt      = "}";
			$t       = __( "All PayStar Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1 AND l.payment_method='paystar'
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		if ( $chart == 4 ) {
			$c       = 'red';
			$dt      = "points: { symbol: 'triangle', fillColor: '#AA4643' }, color: '#AA4643'}";
			$t       = __( "All Site Forms", 'paystar-payment-for-gravityforms' );
			$results = $wpdb->get_results( "SELECT CONVERT_TZ(l.payment_date, '+00:00', '{$tz_offset}') as date, sum(l.payment_amount) as amount_sold, count(l.id) as new_sales
                                        FROM " . GFPersian_DB_PayStar::get_entry_table_name() . " l
                                        WHERE l.status='active' AND l.is_fulfilled=1
                                        GROUP BY date(date)
                                        ORDER BY payment_date desc" );
		}

		$sales_today   = 0;
		$revenue_today = 0;
		$tooltips      = "";
		if ( ! empty( $results ) && isset( $_POST['submit'] ) && $max && $min ) {

			list( $y2, $m2, $d2 ) = explode( "-", $max );

			if ( $y2 < 2000 ) {
				$max  = GF_jalali_to_gregorian( $y2, $m2, $d2 );
				$date = new DateTime( "$max[0]-$max[1]-$max[2]" );
			} else {
				$date = new DateTime( "$y2-$m2-$d2" );
			}

			$max_w = $date->format( 'Ymd' );
			$max_t = $date->format( 'm d , Y' );
			$endd  = $date->format( 'Y-m-d' );

			list( $y1, $m1, $d1 ) = explode( "-", $min );

			if ( $y1 < 2000 ) {
				$min  = GF_jalali_to_gregorian( $y1, $m1, $d1 );
				$date = new DateTime( "$min[0]-$min[1]-$min[2]" );
			} else {
				$date = new DateTime( "$y1-$m1-$d1" );
			}

			$min_w = $date->format( 'Ymd' );
			$min_t = $date->format( 'm d , Y' );
			$strd  = $date->format( 'Y-m-d' );
			$data  = "[";
			foreach ( $results as $result ) {

				$timeX_tooltips = GF_jdate( 'l - d F Y', strtotime( $result->date ), '', date_default_timezone_get(), 'fa' );
				$timeX          = self::get_graph_timestamp( $result->date );

				$target = date( 'Ymd', strtotime( $result->date ) );
				if ( $max_w >= $target && $target >= $min_w ) {
					$sales_today   += $result->new_sales;
					$revenue_today += $result->amount_sold;
					$datat         = isset( $result->amount_sold ) ? $result->amount_sold : 0;
				}
				$datat = isset( $datat ) ? $datat : '';
				$data  .= "[{$timeX},{$datat}],";

				$sales_line = "<div class='paystar_tooltip_sales'><span class='paystar_tooltip_heading'>" . __( "Payment Count ", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . $result->new_sales . "</span></div>";

				$tooltips .= "\"<div class='tooltipbox_" . $c . "'><div class='paystar_tooltip_date'>" . $timeX_tooltips . "</div>{$sales_line}<div class='paystar_tooltip_revenue'><span class='paystar_tooltip_heading'>" . __( "Due Pay", 'paystar-payment-for-gravityforms' ) . ": </span><span class='paystar_tooltip_value'>" . GFCommon::to_money( $result->amount_sold ) . "</span></div></div>\",";
			}

			$data     = substr( $data, 0, strlen( $data ) - 1 );
			$tooltips = substr( $tooltips, 0, strlen( $tooltips ) - 1 );
			$data     .= "]";
			$series   = "{data:" . $data . ", " . $dt . "";

			$strd = date_create( $strd );
			$endd = date_create( $endd );
			$diff = date_diff( $strd, $endd );
			$midd = $diff->format( "%a" ) + 1;
			$mt   = 1;
			$tt   = 'day';
			if ( $midd > 30 ) {
				$mt = 5;
			}
			if ( $midd > 63 ) {
				$mt = 10;
			}
			if ( $midd > 100 ) {
				$mt = 20;
			}
			if ( $midd > 364 ) {
				$mt = 1;
				$tt = 'month';
			}
			$options = "{
				series: {lines: {show: true},
				points: {show: true}},
				grid: {hoverable: true, clickable: true, tickColor: '#F1F1F1', backgroundColor:'#FFF', borderWidth: 1, borderColor: '#CCC'},
				xaxis: {mode: 'time',timeformat: '%d',tickFormatter: shamsi_1, minTickSize:[$mt, '$tt'],min: (new Date('$min_t 00:00:00')).getTime(),max: (new Date('$max_t 23:59:59')).getTime()},
				yaxis: {tickFormatter: convertToMoney}
			}";
		}

		$sales_label = __( "Payment Count of Selected Period ", 'paystar-payment-for-gravityforms' ) . $t;

		$midt          = $midd ? $sales_today / $midd : 0;
		$midt          = number_format( $midt, 3, '.', '' ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$midt_label    = __( "Average Payment Count of  ", 'paystar-payment-for-gravityforms' ) . $t . "";
		$mid           = ( $midd ? GFCommon::to_money( $revenue_today / $midd ) : 0 ) . __( " in day", 'paystar-payment-for-gravityforms' );
		$mid_label     = __( "Average Payments of  ", 'paystar-payment-for-gravityforms' ) . $t . "";
		$revenue_today = GFCommon::to_money( $revenue_today );
		$revenue_label = __( "Payments Count of بازه انتخابی ", 'paystar-payment-for-gravityforms' ) . $t;

		return array( "series"        => $series,
		              "options"       => $options,
		              "tooltips"      => "[$tooltips]",
		              "revenue_label" => $revenue_label,
		              "revenue"       => $revenue_today,
		              "sales_label"   => $sales_label,
		              "sales"         => $sales_today,
		              "mid_label"     => $mid_label,
		              "mid"           => $mid,
		              "midt_label"    => $midt_label,
		              "midt"          => $midt
		);
	}

}