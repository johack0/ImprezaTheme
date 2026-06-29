<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Facet filter cache.
 */

if ( ! interface_exists( 'US_Filter_Cache_Interface' ) ) {

	interface US_Filter_Cache_Interface {

		/**
		 * Save the value into cache.
		 *
		 * @param string $key
		 * @param mixed $post_count
		 * @param string $filter_html
		 */
		public function set( string $key, $post_count = '', $filter_html = '' );

		/**
		 * Get post count from cache by key.
		 *
		 * @param strint $key
		 *
		 * @return string
		 */
		public function get_post_count( string $key );

		/**
		 * Get filter html from cache by key.
		 *
		 * @param strint $key
		 *
		 * @return string
		 */
		public function get_filter_html( string $key );

		/**
		 * Clear all cache.
		 */
		public function clear_all();

		/**
		 * Get the cache size.
		 *
		 * @return string
		 */
		public function get_size();
	}
}

if ( ! class_exists( 'US_Filter_Cache' ) ) {

	/**
	 * Class that implements cache storage in the database.
	 */
	class US_Filter_Cache implements US_Filter_Cache_Interface {

		/**
		 * Constructs a new instance.
		 */
		function __construct() {
			if ( us_get_option( 'enable_filter_cache' ) ) {
				$this->check_create_table();
			}
		}

		/**
		 * Determines if table created.
		 *
		 * @return bool True if table created, False otherwise.
		 */
		private function is_table_created() {
			static $is_created;
			if ( ! isset( $is_created ) ) {
				global $wpdb;
				$is_created = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}us_filter_cache'" ) !== NULL;
			}

			return $is_created;
		}

		/**
		 * Check if the table exists, and create it if it doesn’t.
		 */
		private function check_create_table() {
			global $wpdb;

			if ( get_option( 'us_filter_cache_table_created', FALSE ) ) {
				return;
			}

			if ( $this->is_table_created() ) {
				update_option( 'us_filter_cache_table_created', TRUE, TRUE );
				return;
			}

			$int = apply_filters( 'us_filter_cache_use_bigint', FALSE ) ? 'bigint' : 'int';

			$charset_collate = $wpdb->get_charset_collate();

			$table_structure = "
				CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}us_filter_cache` (
					`id` $int UNSIGNED NOT NULL AUTO_INCREMENT,
					`key` varchar(32) DEFAULT NULL,
					`post_count` mediumtext DEFAULT NULL,
					`filter_html` mediumtext DEFAULT NULL,
					`expire` datetime NOT NULL,
					PRIMARY KEY (`id`),
					INDEX `key` (`key`)
				) $charset_collate
			";

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $table_structure );

			update_option( 'us_filter_cache_table_created', TRUE, TRUE );
		}

		/**
		 * Get the cache size.
		 *
		 * @return string
		 */
		public function get_size() {
			global $wpdb;
			if ( static::is_table_created() ) {
				return number_format( $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}us_filter_cache`" ) );
			}
			return 0;
		}

		/**
		 * Save the value into cache.
		 *
		 * @param string $key
		 * @param mixed $post_count
		 * @param string $filter_html
		 */
		public function set( string $key, $post_count = '', $filter_html = '' ) {

			$cache_lifetime = us_get_option( 'filter_cache_lifetime' );

			if ( $cache_lifetime == 'custom' ) {
				$cache_lifetime = us_get_option( 'filter_cache_lifetime_custom' );
			}

			$cache_lifetime = (int) $cache_lifetime;
			$cache_lifetime = $cache_lifetime ?: 30; // minimum value

			if ( ! is_string( $post_count ) ) {
				$post_count = json_encode( $post_count );
			}

			global $wpdb;

			$sql = $wpdb->prepare( "SELECT 1 FROM `{$wpdb->prefix}us_filter_cache` WHERE `key` = %s", $key );

			if ( $post_count AND empty( $wpdb->get_var( $sql ) ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'us_filter_cache',
					array(
						'key' => $key,
						'post_count' => $post_count,
						'expire' => date( 'Y-m-d H:i:s', time() + $cache_lifetime ),
					)
				);

			} elseif ( $filter_html ) {
				$wpdb->update(
					$wpdb->prefix . 'us_filter_cache',
					array(
						'filter_html' => $filter_html,
						'expire' => date( 'Y-m-d H:i:s', time() + $cache_lifetime ),
					),
					array(
						'key' => $key,
					)
				);
			}
		}

		/**
		 * Get post count from cache by key.
		 *
		 * @param strint $key
		 *
		 * @return string
		 */
		public function get_post_count( string $key ) {

			global $wpdb;

			$sql = $wpdb->prepare(
				"
					SELECT `post_count`
					FROM `{$wpdb->prefix}us_filter_cache`
					WHERE `key` = %s
					LIMIT 1;
				",
				$key
			);

			if ( $value = $wpdb->get_var( $sql ) ) {
				return $value;
			}

			return '';
		}

		/**
		 * Get filter html from cache by key.
		 *
		 * @param strint $key
		 *
		 * @return string
		 */
		public function get_filter_html( string $key ) {

			global $wpdb;

			$current_time = date( 'Y-m-d H:i:s' );

			// First delete all expired cached filters
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}us_filter_cache` WHERE `expire` <= %s", $current_time ) );

			$sql = $wpdb->prepare(
				"
					SELECT `filter_html`
					FROM `{$wpdb->prefix}us_filter_cache`
					WHERE `key` = %s
					LIMIT 1;
				",
				$key
			);

			if ( $value = $wpdb->get_var( $sql ) ) {
				return $value;
			}

			return '';
		}

		/**
		 * Clear all cache.
		 *
		 * @return bool
		 */
		public function clear_all() {

			global $wpdb;

			if ( $this->is_table_created() ) {
				return $wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}us_filter_cache`;" );
			}

			return FALSE;
		}
	}
}

if ( ! function_exists( 'us_filter_cache' ) ) {

	/**
	 * Cache object.
	 *
	 * @return US_Filter_Cache
	 */
	function us_filter_cache() {
		// Here you can choose other caching implementations: database, files, Memcached, Redis, etc.
		return new US_Filter_Cache();
	}
}

if ( ! function_exists( 'us_filter_clear_cache' ) ) {

	add_action( 'wp_ajax_us_filter_clear_cache', 'us_filter_clear_cache' );

	/**
	 * Clear the cache from the admin via AJAX.
	 */
	function us_filter_clear_cache() {
		if ( ! check_ajax_referer( __FUNCTION__, '_nonce', FALSE ) ) {
			wp_send_json_error(
				array(
					'message' => us_translate( 'An error has occurred. Please reload the page and try again.' ),
				)
			);
		}

		$response = array();

		us_filter_cache()->clear_all();

		wp_send_json_success(
			array(
				'message' => __( 'Cache cleared', 'us' ),
			)
		);
	}
}
