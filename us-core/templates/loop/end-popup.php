<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output popup html used for list items
 *
 * @param string $popup_width The popup width.
 * @param int $popup_arrows The Prev/Next arrows.
 * @param int $popup_page_template The Page Template ID.
 */

$preloader_type = us_get_option( 'preloader' );

if ( $preloader_type == 'disabled' ) {
	$preloader_type = 1;
}

if ( $preloader_type == 'custom' AND $preloader_image = us_get_option( 'preloader_image', '' ) ) {
	$preloader_image_html = wp_get_attachment_image( $preloader_image, 'medium' );
	if ( empty( $preloader_image_html ) ) {
		$preloader_image_html = us_get_img_placeholder( 'medium' );
	}
} else {
	$preloader_image_html = '';
}
?>
<div class="l-popup">
	<div class="l-popup-overlay"></div>
	<div class="l-popup-wrap">
		<button class="l-popup-closer" type="button" aria-label="<?= us_translate( 'Close' ) ?>"></button>
		<?php if ( ! empty( $popup_arrows ) ) { ?>
			<button class="l-popup-arrow to_prev hidden" type="button" title="<?= us_translate( 'Previous' ) ?>"></button>
			<button class="l-popup-arrow to_next hidden" type="button" title="<?= us_translate( 'Next' ) ?>"></button>
		<?php } ?>
		<div class="l-popup-box"<?= ( $popup_page_template ) ? " data-page-template='$popup_page_template'" : '' ?>>
			<div class="l-popup-box-content"<?= us_prepare_inline_css( array( 'max-width' => $popup_width ) ) ?>>
				<div class="g-preloader type_<?= $preloader_type ?>">
					<div><?= $preloader_image_html ?></div>
				</div>
				<iframe class="l-popup-box-content-frame" allowfullscreen></iframe>
			</div>
		</div>
	</div>
</div>
