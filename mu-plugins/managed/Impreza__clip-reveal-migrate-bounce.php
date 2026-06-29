<?php
/**
 * Plugin Name: Impreza - Clip Reveal Migration
 * Description: Migra le vecchie animazioni salvate come Bounce verso Clip Reveal, senza modifiche automatiche.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function impreza_clip_reveal_migration_target() {
	return defined( 'IMPREZA_CLIP_REVEAL_ANIMATION' )
		? IMPREZA_CLIP_REVEAL_ANIMATION
		: 'ht_clip_reveal';
}

function impreza_clip_reveal_migration_replace_string( $string, &$replacements ) {
	$target = impreza_clip_reveal_migration_target();
	$value  = (string) $string;

	$patterns = array(
		'/%22(animation-name|animate|load_animation)%22(?:%20|\+|\s)*%3A(?:%20|\+|\s)*%22bounce%22/i' => '%22$1%22%3A%22' . rawurlencode( $target ) . '%22',
		'/("((?:animation-name|animate|load_animation))"\s*:\s*")bounce(")/i' => '$1' . $target . '$3',
		'/(\\\\?"(?:animation-name|animate|load_animation)\\\\?"\s*:\s*\\\\?")bounce(\\\\?")/i' => '$1' . $target . '$2',
		'/(\b(?:animate|load_animation)=["\'])bounce(["\'])/i' => '$1' . $target . '$2',
		'/(\b(?:animate|load_animation)=\\\\")bounce(\\\\")/i' => '$1' . $target . '$2',
		'/\bus_animate_bounce\b/i' => 'us_animate_' . $target,
	);

	foreach ( $patterns as $pattern => $replacement ) {
		$value = preg_replace( $pattern, $replacement, $value, -1, $count );
		if ( $count ) {
			$replacements += $count;
		}
	}

	return $value;
}

function impreza_clip_reveal_migration_replace_value( $value, &$replacements ) {
	$target = impreza_clip_reveal_migration_target();
	$keys   = array( 'animation-name', 'animate', 'load_animation' );

	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			if ( in_array( (string) $key, $keys, true ) && $item === 'bounce' ) {
				$value[ $key ] = $target;
				$replacements++;
				continue;
			}

			$value[ $key ] = impreza_clip_reveal_migration_replace_value( $item, $replacements );
		}

		return $value;
	}

	if ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $key => $item ) {
			if ( in_array( (string) $key, $keys, true ) && $item === 'bounce' ) {
				$value->$key = $target;
				$replacements++;
				continue;
			}

			$value->$key = impreza_clip_reveal_migration_replace_value( $item, $replacements );
		}

		return $value;
	}

	if ( is_string( $value ) ) {
		return impreza_clip_reveal_migration_replace_string( $value, $replacements );
	}

	return $value;
}

function impreza_clip_reveal_migration_run( $execute = false, $sample_limit = 20 ) {
	global $wpdb;

	$result = array(
		'execute' => (bool) $execute,
		'posts_checked' => 0,
		'posts_matched' => 0,
		'posts_updated' => 0,
		'post_replacements' => 0,
		'meta_checked' => 0,
		'meta_matched' => 0,
		'meta_updated' => 0,
		'meta_replacements' => 0,
		'samples' => array(),
	);

	$post_rows = $wpdb->get_results(
		"SELECT ID, post_type, post_title, post_content
		FROM {$wpdb->posts}
		WHERE post_content LIKE '%bounce%'
		AND (
			post_content LIKE '%animation-name%'
			OR post_content LIKE '%animate=%'
			OR post_content LIKE '%load_animation%'
			OR post_content LIKE '%us_animate_bounce%'
		)"
	);

	foreach ( $post_rows as $post ) {
		$result['posts_checked']++;

		$replacements = 0;
		$new_content  = impreza_clip_reveal_migration_replace_string( $post->post_content, $replacements );

		if ( ! $replacements || $new_content === $post->post_content ) {
			continue;
		}

		$result['posts_matched']++;
		$result['post_replacements'] += $replacements;

		if ( count( $result['samples'] ) < $sample_limit ) {
			$result['samples'][] = sprintf(
				'post #%d (%s): %s - %d replacements',
				$post->ID,
				$post->post_type,
				$post->post_title ?: '(no title)',
				$replacements
			);
		}

		if ( $execute ) {
			$updated = $wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $new_content ),
				array( 'ID' => $post->ID ),
				array( '%s' ),
				array( '%d' )
			);

			if ( $updated !== false ) {
				clean_post_cache( (int) $post->ID );
				$result['posts_updated']++;
			}
		}
	}

	$meta_rows = $wpdb->get_results(
		"SELECT meta_id, post_id, meta_key, meta_value
		FROM {$wpdb->postmeta}
		WHERE meta_value LIKE '%bounce%'
		AND (
			meta_value LIKE '%animation-name%'
			OR meta_value LIKE '%animate%'
			OR meta_value LIKE '%load_animation%'
			OR meta_value LIKE '%us_animate_bounce%'
		)"
	);

	foreach ( $meta_rows as $meta ) {
		$result['meta_checked']++;

		$original_value = maybe_unserialize( $meta->meta_value );
		$replacements  = 0;
		$new_value     = impreza_clip_reveal_migration_replace_value( $original_value, $replacements );

		if ( ! $replacements ) {
			continue;
		}

		$result['meta_matched']++;
		$result['meta_replacements'] += $replacements;

		if ( count( $result['samples'] ) < $sample_limit ) {
			$result['samples'][] = sprintf(
				'postmeta #%d on post #%d (%s) - %d replacements',
				$meta->meta_id,
				$meta->post_id,
				$meta->meta_key,
				$replacements
			);
		}

		if ( $execute && update_metadata_by_mid( 'post', (int) $meta->meta_id, $new_value ) ) {
			clean_post_cache( (int) $meta->post_id );
			$result['meta_updated']++;
		}
	}

	return $result;
}

function impreza_clip_reveal_migration_admin_menu() {
	add_management_page(
		'Clip Reveal Migration',
		'Clip Reveal Migration',
		'manage_options',
		'impreza-clip-reveal-migration',
		'impreza_clip_reveal_migration_admin_page'
	);
}
add_action( 'admin_menu', 'impreza_clip_reveal_migration_admin_menu' );

function impreza_clip_reveal_migration_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
	}

	$executed = false;

	if ( isset( $_POST['impreza_clip_reveal_migration_execute'] ) ) {
		check_admin_referer( 'impreza_clip_reveal_migration_execute' );
		$executed = true;
	}

	$result = impreza_clip_reveal_migration_run( $executed );
	?>
	<div class="wrap">
		<h1>Clip Reveal Migration</h1>
		<p>
			<?php echo $executed ? 'Migrazione eseguita.' : 'Dry-run: nessuna modifica e stata salvata.'; ?>
		</p>
		<table class="widefat striped" style="max-width: 760px;">
			<tbody>
				<tr><th>Post controllati</th><td><?php echo esc_html( $result['posts_checked'] ); ?></td></tr>
				<tr><th>Post con modifiche</th><td><?php echo esc_html( $result['posts_matched'] ); ?></td></tr>
				<tr><th>Post aggiornati</th><td><?php echo esc_html( $result['posts_updated'] ); ?></td></tr>
				<tr><th>Sostituzioni nei post</th><td><?php echo esc_html( $result['post_replacements'] ); ?></td></tr>
				<tr><th>Meta controllati</th><td><?php echo esc_html( $result['meta_checked'] ); ?></td></tr>
				<tr><th>Meta con modifiche</th><td><?php echo esc_html( $result['meta_matched'] ); ?></td></tr>
				<tr><th>Meta aggiornati</th><td><?php echo esc_html( $result['meta_updated'] ); ?></td></tr>
				<tr><th>Sostituzioni nei meta</th><td><?php echo esc_html( $result['meta_replacements'] ); ?></td></tr>
			</tbody>
		</table>

		<?php if ( ! empty( $result['samples'] ) ) : ?>
			<h2>Esempi trovati</h2>
			<ul>
				<?php foreach ( $result['samples'] as $sample ) : ?>
					<li><?php echo esc_html( $sample ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! $executed ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'impreza_clip_reveal_migration_execute' ); ?>
				<p>
					<input type="submit" class="button button-primary" name="impreza_clip_reveal_migration_execute" value="Esegui migrazione">
				</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI && ! class_exists( 'Impreza_Clip_Reveal_Migrate_Bounce_Command' ) ) {
	class Impreza_Clip_Reveal_Migrate_Bounce_Command {
		public function __invoke( $args, $assoc_args ) {
			$execute = isset( $assoc_args['execute'] );
			$result  = impreza_clip_reveal_migration_run( $execute, 50 );

			WP_CLI::line( $execute ? 'Mode: execute' : 'Mode: dry-run' );
			WP_CLI::line( 'Posts matched: ' . $result['posts_matched'] . ' / replacements: ' . $result['post_replacements'] . ' / updated: ' . $result['posts_updated'] );
			WP_CLI::line( 'Meta matched: ' . $result['meta_matched'] . ' / replacements: ' . $result['meta_replacements'] . ' / updated: ' . $result['meta_updated'] );

			foreach ( $result['samples'] as $sample ) {
				WP_CLI::line( '- ' . $sample );
			}

			if ( ! $execute ) {
				WP_CLI::warning( 'Dry-run only. Re-run with --execute to save changes.' );
				return;
			}

			WP_CLI::success( 'Migration completed.' );
		}
	}

	WP_CLI::add_command( 'impreza clip-reveal-migrate-bounce', 'Impreza_Clip_Reveal_Migrate_Bounce_Command' );
}
