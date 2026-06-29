<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Checkout Login Form
 *
 * Modified version of WooCommerce Login form. Based on the woocommerce/templates/global/form-login.php file.
 * It is sent via JS function, see common/js/plugins/woocommerce.js file in the theme folder.
 * Relies on the WooCommerce functionality to log a user in.
 *
 * Do not use any WooCommerce hooks in the form output.
 */

if ( ! class_exists( 'woocommerce' ) ) {
	return;

} elseif ( ! usb_is_post_preview() ) {
	if ( is_null( WC()->cart ) OR WC()->cart->is_empty() ) {
		return;
	}
	if ( function_exists( 'is_checkout' ) AND ! is_checkout() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		return;
	}
}

$_atts['class'] = 'w-checkout-login';
$_atts['class'] .= $classes ?? '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$notice_html = '';
if ( $notice_message OR usb_is_preview() ) {
	$notice_html .= '<span>' . esc_html( $notice_message ) . '</span> ';
}
$notice_html .= '<a href="#" class="showlogin">' . esc_html( us_translate( 'Click here to login', 'woocommerce' ) ) . '</a>';

$btn_params = array(
	'html_atts' => array(
		'class' => 'w-btn ' . us_get_btn_class( $btn_style ),
	),
	'label' => us_translate( 'Login', 'woocommerce' ),
);

?>
<div<?= us_implode_atts( $_atts ) ?>>
	<div class="woocommerce-form-login-toggle w-wc-notices style_<?= $notice_style ?>">
		<?php wc_print_notice( $notice_html, 'notice' ) ?>
	</div>
	<div class="woocommerce-form-login hidden">

		<?= ( $message ) ? wpautop( wptexturize( us_replace_dynamic_value( $message ) ) ) : '' ?>

		<div class="form-row form-row-first">
			<label for="us_checkout_login_username"><?= esc_html( us_translate( 'Username or email', 'woocommerce' ) ) ?>&nbsp;<span class="required">*</span></label>
			<input type="text" class="input-text" name="us_checkout_login_username" id="us_checkout_login_username" autocomplete="username" />
		</div>
		<div class="form-row form-row-last">
			<label for="us_checkout_login_password"><?= esc_html( us_translate( 'Password', 'woocommerce' ) ) ?>&nbsp;<span class="required">*</span></label>
			<input class="input-text woocommerce-Input" type="password" name="us_checkout_login_password" id="us_checkout_login_password" autocomplete="current-password" />
		</div>
		<div class="clear"></div>

		<div class="form-row">
			<?php wp_nonce_field( 'woocommerce-login', 'us_checkout_login_nonce' ) ?>
			<input type="hidden" name="us_checkout_login_redirect" id="us_checkout_login_redirect" value="<?= esc_url( wc_get_checkout_url() ) ?>" />
			<?= us_get_btn( $btn_params ) ?>
		</div>
		<div class="lost_password">
			<a href="<?= esc_url( wp_lostpassword_url() ) ?>"><?= esc_html( us_translate( 'Lost your password?', 'woocommerce' ) ) ?></a>
		</div>

		<div class="clear"></div>

	</div>
</div>
