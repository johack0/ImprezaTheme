<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * Required for "Page Template" option
 */

get_header( 'shop' );

?>
<main id="page-content" class="l-main"<?= ( us_get_option( 'schema_markup' ) ) ? ' itemprop="mainContentOfPage"' : '' ?>>
	<?php
	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/titlebar' );
		us_load_template( 'templates/sidebar', array( 'place' => 'before' ) );
	}

	us_load_template( 'templates/content' );

	if ( us_get_option( 'enable_sidebar_titlebar' ) ) {
		us_load_template( 'templates/sidebar', array( 'place' => 'after' ) );
	}
	?>
</main>
<?php

get_footer( 'shop' );
