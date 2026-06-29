<?php
/**
 * UpSolution MCP — Media Library abilities.
 *
 * Five abilities plus one public one-time upload endpoint:
 *
 *   upsolution-list-media   — search the library, resolve a human description
 *                             ("the team photo", "logo.png") to an attachment id.
 *   upsolution-get-media    — full record for one attachment id.
 *   upsolution-upload-media — import a file into the library FROM A URL. The
 *                             server downloads the bytes itself (sideload), so
 *                             file content never travels through the MCP
 *                             transport — same pattern as Directus import-file.
 *   upsolution-update-media — edit alt / title / caption of an existing
 *                             attachment.
 *   upsolution-create-media-upload-url — mint a one-time signed URL for the
 *                             case where the file exists only on the agent's
 *                             side (no public URL to import from). The agent
 *                             POSTs raw bytes to the URL; the endpoint creates
 *                             the attachment and answers with the same record
 *                             shape upload-media returns.
 *
 * Type safety: for both upload paths the real file type is detected from the
 * downloaded / received BYTES (wp_check_filetype_and_ext + content sniffing),
 * never trusted from the URL extension or a client header. Extension-less CDN
 * URLs get the canonical extension grafted on; types outside
 * get_allowed_mime_types() are rejected with 415.
 *
 * Security model of the signed upload URL: the 32-char hex key in the URL is
 * the credential (bearer), exactly like the Theme Options preview links. The
 * key is single-use (consumed on first hit, even a failed one), expires after
 * 15 minutes, and replays the capability check of the minting user at redeem
 * time — the endpoint itself requires no cookies / headers.
 *
 * @package usCore\Mcp
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Lifetime of a one-time signed upload URL (15 minutes). Long enough for an
 * agent to mint + push in one turn, short enough that a leaked URL goes stale
 * before it is likely to be abused.
 */
const US_MCP_MEDIA_UPLOAD_URL_TTL = 15 * MINUTE_IN_SECONDS;

/**
 * Transient prefix for pending signed-upload claims. Full key:
 * us_mcp_media_upload_<32 hex chars>.
 */
const US_MCP_MEDIA_UPLOAD_TRANSIENT_PREFIX = 'us_mcp_media_upload_';

/**
 * Timeout for the server-side download in upload-media (seconds). Kept well
 * under typical PHP max_execution_time so a slow remote host fails cleanly
 * instead of fataling the request.
 */
const US_MCP_MEDIA_DOWNLOAD_TIMEOUT = 60;

add_action( 'wp_abilities_api_init', function () {

	// One output shape shared by list / get / upload / update AND the signed
	// upload endpoint response, so agents can treat all media payloads
	// interchangeably.
	$media_output_properties = array(
		'id'        => array( 'type' => 'integer', 'description' => 'Attachment id. Pass this as featured_image_id, or to shortcode image attributes that expect an id.' ),
		'title'     => array( 'type' => 'string' ),
		'slug'      => array( 'type' => 'string' ),
		'filename'  => array( 'type' => 'string', 'description' => 'Basename of the uploaded file (e.g. "hero.jpg").' ),
		'url'       => array( 'type' => 'string', 'description' => 'Full-size attachment URL.' ),
		'mime_type' => array( 'type' => 'string' ),
		'alt'       => array( 'type' => 'string', 'description' => 'Alt text from `_wp_attachment_image_alt` (empty string when not set).' ),
		'caption'   => array( 'type' => 'string', 'description' => 'Attachment caption (post_excerpt). Empty when not set.' ),
		'width'     => array( 'type' => array( 'integer', 'null' ) ),
		'height'    => array( 'type' => array( 'integer', 'null' ) ),
		'date'      => array( 'type' => 'string', 'description' => 'Upload date as ISO 8601.' ),
		'parent_id' => array( 'type' => 'integer', 'description' => 'Post id this attachment is uploaded against, or 0 when unattached.' ),
	);

	wp_register_ability( 'upsolution/list-media', array(
		'label'               => 'List Media Library images',
		'description'         => 'Search the WordPress Media Library and return attachment ids for images. Use this to resolve a human description ("hero photo", "logo.png") to the numeric id required by `featured_image_id` on upsolution-create-post / update-post and by id-based image attributes on shortcodes. Defaults to image attachments only; pass `mime_type` to widen (e.g. "image/svg+xml") or narrow ("image/png"). Filter further with `search` (substring match against title and caption) and `parent_id` (images attached to one post). Results are ordered newest first. If the image you need is not in the library yet, import it with upsolution-upload-media.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'search'    => array(
					'type'        => 'string',
					'description' => 'Optional case-insensitive substring filter applied to attachment title and caption.',
				),
				'mime_type' => array(
					'type'        => 'string',
					'description' => 'Optional MIME filter. Accepts a full type ("image/png", "image/jpeg", "image/svg+xml", "image/webp") or a top-level family ("image" — the default — matches every image/* type).',
					'default'     => 'image',
				),
				'parent_id' => array(
					'type'        => 'integer',
					'description' => 'Optional. Limit results to attachments uploaded against a specific post id (the "Uploaded to" column in wp-admin).',
					'minimum'     => 0,
				),
				'per_page'  => array(
					'type'        => 'integer',
					'description' => 'Maximum number of attachments to return (default 20, max 100).',
					'default'     => 20,
					'minimum'     => 1,
					'maximum'     => 100,
				),
				'page'      => array(
					'type'        => 'integer',
					'description' => 'Page number for pagination (default 1). Combine with `per_page` to walk large libraries.',
					'default'     => 1,
					'minimum'     => 1,
				),
			),
		),
		'output_schema'       => array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => $media_output_properties,
			),
		),
		'execute_callback'    => 'us_mcp_ability_list_media',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/get-media', array(
		'label'               => 'Get a single Media Library attachment',
		'description'         => 'Fetch the full record for one attachment by id — same shape as upsolution-list-media items (id / title / filename / url / mime_type / alt / caption / width / height / date / parent_id). Use it to confirm the state after upload-media / update-media, or to read the alt text and dimensions of a known id before referencing it.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'id' ),
			'properties' => array(
				'id' => array(
					'type'        => 'integer',
					'description' => 'Attachment id, e.g. from upsolution-list-media or upload-media.',
					'minimum'     => 1,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => $media_output_properties,
		),
		'execute_callback'    => 'us_mcp_ability_get_media',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/upload-media', array(
		'label'               => 'Import a file into the Media Library from a URL',
		'description'         => 'Import a file (typically an image) into the WordPress Media Library by URL. The server downloads the file itself — bytes never travel through the MCP transport — so this is the PREFERRED way to add media: pass any publicly reachable http(s) URL. Set `alt` / `title` / `caption` in the same call rather than patching afterwards (alt text is strongly recommended — describe what the image shows). Returns the same record shape as upsolution-list-media; pass the returned `id` to `featured_image_id` or to id-based shortcode image attributes. The real file type is detected from the downloaded bytes: extension-less CDN URLs (e.g. Unsplash "photo-123?fm=jpg") are handled automatically, and types the site does not allow are rejected (stock WordPress disallows SVG). URLs resolving to private / loopback hosts are rejected by the WordPress SSRF guard, and files above the server upload limit are rejected. Importing the same URL twice creates a duplicate — call upsolution-list-media first when the image may already exist. If the file exists only on your side (no public URL), use upsolution-create-media-upload-url instead.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'url' ),
			'properties' => array(
				'url'       => array(
					'type'        => 'string',
					'description' => 'Public http(s) URL to download. Hosts resolving to private / loopback addresses are rejected.',
				),
				'filename'  => array(
					'type'        => 'string',
					'description' => 'Optional filename override (e.g. "team-photo.jpg"). Defaults to the last URL path segment. The extension is corrected automatically when it does not match the real file contents.',
				),
				'title'     => array(
					'type'        => 'string',
					'description' => 'Optional attachment title. Defaults to the filename without extension.',
				),
				'alt'       => array(
					'type'        => 'string',
					'description' => 'Optional alt text (recommended). Plain text describing the image content.',
				),
				'caption'   => array(
					'type'        => 'string',
					'description' => 'Optional caption — themes show it under the image in galleries and single views.',
				),
				'parent_id' => array(
					'type'        => 'integer',
					'description' => 'Optional post id to attach the file to (the "Uploaded to" column in wp-admin). 0 / omitted = unattached.',
					'minimum'     => 0,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => $media_output_properties,
		),
		'execute_callback'    => 'us_mcp_ability_upload_media',
		'permission_callback' => 'us_mcp_media_upload_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/update-media', array(
		'label'               => 'Update alt / title / caption of an attachment',
		'description'         => 'Update the textual fields of an existing Media Library attachment: `title`, `alt` (the `_wp_attachment_image_alt` meta rendered as <img alt>) and `caption`. Pass only the fields you want to change; an empty string clears a field. The binary file itself is immutable — to replace an image, import a new one via upsolution-upload-media and re-point the references. Returns the updated record (same shape as upsolution-list-media).',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'id' ),
			'properties' => array(
				'id'      => array(
					'type'        => 'integer',
					'description' => 'Attachment id to update.',
					'minimum'     => 1,
				),
				'title'   => array(
					'type'        => 'string',
					'description' => 'New attachment title. Empty string clears it.',
				),
				'alt'     => array(
					'type'        => 'string',
					'description' => 'New alt text (plain text). Empty string removes the alt.',
				),
				'caption' => array(
					'type'        => 'string',
					'description' => 'New caption. Empty string clears it.',
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => $media_output_properties,
		),
		'execute_callback'    => 'us_mcp_ability_update_media',
		'permission_callback' => 'us_mcp_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );

	wp_register_ability( 'upsolution/create-media-upload-url', array(
		'label'               => 'Create a one-time signed upload URL',
		'description'         => 'Mint a one-time signed URL for pushing a LOCAL file into the Media Library — use it only when the file has no public URL for upsolution-upload-media to import from (e.g. a freshly generated image). Send the RAW file bytes as the HTTP request body of a POST (or PUT) to the returned `upload_url` within 15 minutes, e.g. `curl -X POST --data-binary @./photo.jpg "<upload_url>"`; a multipart form-data body with a single file field also works. No auth headers are needed — the URL itself is the single-use credential; treat it like a password. The endpoint responds with the same attachment JSON record upload-media returns (201 + id / url / alt / …). `filename`, `alt` / `title` / `caption` and `parent_id` are fixed NOW, at mint time, and applied when the bytes arrive; the real file type is verified from the bytes, with the extension corrected when it does not match. The URL is consumed even by a failed upload — mint a fresh one to retry.',
		'category'            => 'upsolution',
		'input_schema'        => array(
			'type'       => 'object',
			'required'   => array( 'filename' ),
			'properties' => array(
				'filename'  => array(
					'type'        => 'string',
					'description' => 'Filename for the upload, preferably with the right extension ("photo.jpg"). A wrong / missing extension is corrected from the actual bytes at redeem time.',
				),
				'title'     => array(
					'type'        => 'string',
					'description' => 'Optional attachment title, applied when the bytes arrive.',
				),
				'alt'       => array(
					'type'        => 'string',
					'description' => 'Optional alt text (recommended), applied when the bytes arrive.',
				),
				'caption'   => array(
					'type'        => 'string',
					'description' => 'Optional caption, applied when the bytes arrive.',
				),
				'parent_id' => array(
					'type'        => 'integer',
					'description' => 'Optional post id to attach the file to. 0 / omitted = unattached.',
					'minimum'     => 0,
				),
			),
		),
		'output_schema'       => array(
			'type'       => 'object',
			'required'   => array( 'upload_url', 'method', 'expires_at', 'expires_in_seconds', 'max_bytes' ),
			'properties' => array(
				'upload_url'         => array( 'type' => 'string', 'description' => 'One-time endpoint URL. Send the raw file bytes as the request body. The URL is the credential — single-use, no auth headers.' ),
				'method'             => array( 'type' => 'string', 'description' => 'HTTP method to use ("POST"; PUT is accepted too).' ),
				'expires_at'         => array( 'type' => 'integer', 'description' => 'Unix epoch seconds after which the URL stops working.' ),
				'expires_in_seconds' => array( 'type' => 'integer' ),
				'max_bytes'          => array( 'type' => 'integer', 'description' => 'Server upload limit — bigger bodies are rejected with 413.' ),
			),
		),
		'execute_callback'    => 'us_mcp_ability_create_media_upload_url',
		'permission_callback' => 'us_mcp_media_upload_permission_callback',
		'meta'                => array( 'mcp' => array( 'public' => TRUE ) ),
	) );
} );

/**
 * Permission gate for the two upload abilities. `upload_files` is the same
 * capability wp-admin requires for the Media Library uploader — narrower
 * than the transport-level edit_posts gate.
 *
 * @return bool
 */
function us_mcp_media_upload_permission_callback() {
	return current_user_can( 'upload_files' );
}

/**
 * Serialise an attachment to the output shape shared by every media ability.
 *
 * @param WP_Post $post
 * @return array
 */
function us_mcp_media_payload( WP_Post $post ) {
	$meta = wp_get_attachment_metadata( $post->ID );
	$file = get_attached_file( $post->ID );
	return array(
		'id'        => (int) $post->ID,
		'title'     => $post->post_title,
		'slug'      => $post->post_name,
		'filename'  => $file ? wp_basename( $file ) : '',
		'url'       => (string) wp_get_attachment_url( $post->ID ),
		'mime_type' => $post->post_mime_type,
		'alt'       => (string) get_post_meta( $post->ID, '_wp_attachment_image_alt', TRUE ),
		'caption'   => (string) $post->post_excerpt,
		'width'     => ( is_array( $meta ) AND isset( $meta['width'] ) )  ? (int) $meta['width']  : NULL,
		'height'    => ( is_array( $meta ) AND isset( $meta['height'] ) ) ? (int) $meta['height'] : NULL,
		'date'      => mysql2date( 'c', $post->post_date_gmt, FALSE ),
		'parent_id' => (int) $post->post_parent,
	);
}

/**
 * The sideload pipeline (download_url, wp_tempnam, media_handle_sideload,
 * wp_generate_attachment_metadata) lives in wp-admin includes that are NOT
 * loaded on REST / MCP requests.
 */
function us_mcp_media_require_admin_files() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
}

/**
 * Validate the optional `parent_id` input shared by upload-media and
 * create-media-upload-url.
 *
 * @param array $input
 * @return int|WP_Error Resolved parent id (0 = unattached) or an error.
 */
function us_mcp_media_validate_parent( array $input ) {
	if ( ! array_key_exists( 'parent_id', $input ) ) {
		return 0;
	}
	$parent_id = max( 0, (int) $input['parent_id'] );
	if ( $parent_id > 0 ) {
		$parent = get_post( $parent_id );
		if ( ! $parent OR $parent->post_type === 'attachment' ) {
			return new WP_Error(
				'us_mcp_media_bad_parent',
				sprintf( 'parent_id %d is not an existing (non-attachment) post.', $parent_id ),
				array( 'status' => 400 )
			);
		}
	}
	return $parent_id;
}

/**
 * Resolve and validate the `id` input shared by get-media and update-media to a
 * real attachment post.
 *
 * @param array $input
 * @return WP_Post|WP_Error
 */
function us_mcp_media_resolve_attachment( array $input ) {
	$id = isset( $input['id'] ) ? (int) $input['id'] : 0;
	if ( $id <= 0 ) {
		return new WP_Error( 'us_mcp_media_missing_id', 'Provide a positive integer `id`.', array( 'status' => 400 ) );
	}
	$post = get_post( $id );
	if ( ! $post OR $post->post_type !== 'attachment' ) {
		return new WP_Error(
			'us_mcp_media_not_found',
			sprintf( 'No attachment with id %d.', $id ),
			array( 'status' => 404 )
		);
	}
	return $post;
}

/**
 * Sniff a MIME type from file CONTENTS. wp_get_image_mime covers every image
 * format WP knows (incl. WebP / AVIF / HEIC); fileinfo is the fallback for
 * non-image media.
 *
 * @param string $path
 * @return string Empty string when undeterminable.
 */
function us_mcp_media_detect_mime( $path ) {
	$mime = wp_get_image_mime( $path );
	if ( $mime ) {
		return (string) $mime;
	}
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( $finfo ) {
			$mime = finfo_file( $finfo, $path );
			finfo_close( $finfo );
			if ( is_string( $mime ) AND $mime !== '' AND $mime !== 'application/octet-stream' ) {
				return $mime;
			}
		}
	}
	return '';
}

/**
 * Shared tail of both upload paths: take a temp file that already holds the
 * bytes, validate size + real type, create the attachment (sub-sizes included
 * via media_handle_sideload → wp_generate_attachment_metadata), apply alt /
 * title / caption, return the standard payload.
 *
 * The temp file is always consumed — moved into the uploads dir on success,
 * deleted on every error path.
 *
 * @param string $tmp_file   Absolute path to the temp copy of the bytes.
 * @param string $filename   Desired filename; extension is corrected from the
 *                           contents when missing or wrong.
 * @param array  $fields     Optional title / alt / caption strings.
 * @param int    $parent_id  Post id to attach to, 0 = unattached.
 * @param string $source_url Original URL for URL imports — stored as
 *                           `_source_url` meta (same provenance key core's
 *                           media_sideload_image uses). '' for byte uploads.
 * @return array|WP_Error
 */
function us_mcp_media_create_attachment_from_file( $tmp_file, $filename, array $fields, $parent_id, $source_url = '' ) {
	us_mcp_media_require_admin_files();

	$size = (int) @filesize( $tmp_file );
	if ( $size <= 0 ) {
		@unlink( $tmp_file );
		return new WP_Error(
			'us_mcp_media_empty_file',
			'The received file is empty (0 bytes).',
			array( 'status' => 400 )
		);
	}
	$max_bytes = (int) wp_max_upload_size();
	if ( $max_bytes > 0 AND $size > $max_bytes ) {
		@unlink( $tmp_file );
		return new WP_Error(
			'us_mcp_media_too_large',
			sprintf( 'File is %s — the server upload limit is %s.', size_format( $size ), size_format( $max_bytes ) ),
			array( 'status' => 413 )
		);
	}

	$filename = sanitize_file_name( (string) $filename );
	if ( $filename === '' ) {
		$filename = 'import';
	}

	// Resolve the real type from the BYTES, never from what the URL / client
	// claimed. wp_check_filetype_and_ext also hands back a corrected filename
	// when the extension lies about an image's actual format (.jpg → .png).
	$real_mime = '';
	$check = wp_check_filetype_and_ext( $tmp_file, $filename );
	if ( ! empty( $check['proper_filename'] ) ) {
		$filename = $check['proper_filename'];
	}
	if ( empty( $check['type'] ) ) {
		// No usable extension — common for CDN URLs like ".../photo-123?fm=jpg".
		// Sniff the mime from the contents and graft the canonical extension on.
		$real_mime = us_mcp_media_detect_mime( $tmp_file );
		$real_ext  = $real_mime ? wp_get_default_extension_for_mime_type( $real_mime ) : FALSE;
		if ( $real_ext ) {
			$base     = pathinfo( $filename, PATHINFO_FILENAME );
			$filename = ( $base !== '' ? $base : 'import' ) . '.' . $real_ext;
			$check    = wp_check_filetype_and_ext( $tmp_file, $filename );
		}
	}
	if ( empty( $check['type'] ) ) {
		@unlink( $tmp_file );
		return new WP_Error(
			'us_mcp_media_bad_type',
			sprintf(
				'The file type is not allowed on this site (detected "%s" from the file contents, filename "%s"). Stock WordPress accepts common image / document / AV types; SVG and other extras need an upload_mimes filter or plugin.',
				$real_mime !== '' ? $real_mime : 'unknown',
				$filename
			),
			array( 'status' => 415 )
		);
	}

	$post_data = array();
	if ( isset( $fields['title'] ) AND trim( (string) $fields['title'] ) !== '' ) {
		$post_data['post_title'] = (string) $fields['title'];
	}
	if ( isset( $fields['caption'] ) AND (string) $fields['caption'] !== '' ) {
		$post_data['post_excerpt'] = (string) $fields['caption'];
	}

	$file_array = array(
		'name'     => $filename,
		'tmp_name' => $tmp_file,
	);
	$id = media_handle_sideload( $file_array, (int) $parent_id, NULL, $post_data );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp_file );
		// Sideload errors (disk full, failed move, …) carry no HTTP status —
		// promote to 400 so the transport reports a request-level failure.
		$data = $id->get_error_data();
		if ( ! is_array( $data ) OR ! isset( $data['status'] ) ) {
			$id->add_data( array( 'status' => 400 ), $id->get_error_code() );
		}
		return $id;
	}

	if ( isset( $fields['alt'] ) AND trim( (string) $fields['alt'] ) !== '' ) {
		update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( (string) $fields['alt'] ) );
	}
	if ( $source_url !== '' ) {
		// Provenance — same meta key core's media_sideload_image writes. Lets
		// later tooling answer "where did this image come from" / dedupe.
		add_post_meta( $id, '_source_url', esc_url_raw( $source_url ) );
	}

	$post = get_post( $id );
	if ( ! $post ) {
		return new WP_Error(
			'us_mcp_media_reread_failed',
			'The attachment was created but could not be re-read.',
			array( 'status' => 500 )
		);
	}
	return us_mcp_media_payload( $post );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_list_media( $input ) {
	$input = (array) $input;

	$per_page = isset( $input['per_page'] ) ? (int) $input['per_page'] : 20;
	$per_page = max( 1, min( 100, $per_page ) );
	$page     = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;

	$args = array(
		'post_type'              => 'attachment',
		'post_status'            => 'inherit',
		'posts_per_page'         => $per_page,
		'paged'                  => $page,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		// The ability returns only the items array (no total / page count), so
		// skip the FOUND_ROWS() count query; attachments need no term cache here.
		'no_found_rows'          => TRUE,
		'update_post_term_cache' => FALSE,
	);

	// MIME filter. "image" is the default and matches the whole image/* family;
	// "image/png" etc. matches one specific type. Anything else is forwarded to
	// WP_Query verbatim so the agent can pull non-image media if it really
	// needs to (e.g. "application/pdf").
	$mime = isset( $input['mime_type'] ) ? trim( (string) $input['mime_type'] ) : 'image';
	if ( $mime === '' ) {
		$mime = 'image';
	}
	$args['post_mime_type'] = $mime;

	if ( isset( $input['search'] ) AND trim( (string) $input['search'] ) !== '' ) {
		$args['s'] = (string) $input['search'];
	}
	if ( array_key_exists( 'parent_id', $input ) ) {
		$args['post_parent'] = (int) $input['parent_id'];
	}

	$query = new WP_Query( $args );
	$out   = array();
	foreach ( $query->posts as $post ) {
		$out[] = us_mcp_media_payload( $post );
	}
	return $out;
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_get_media( $input ) {
	$post = us_mcp_media_resolve_attachment( (array) $input );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	return us_mcp_media_payload( $post );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_upload_media( $input ) {
	$input = (array) $input;

	$url = isset( $input['url'] ) ? trim( (string) $input['url'] ) : '';
	if ( $url === '' ) {
		return new WP_Error( 'us_mcp_media_missing_url', 'Provide a non-empty `url`.', array( 'status' => 400 ) );
	}
	$scheme = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
	if ( ! in_array( $scheme, array( 'http', 'https' ), TRUE ) ) {
		return new WP_Error(
			'us_mcp_media_bad_scheme',
			'Only http(s) URLs can be imported. For a local file, use upsolution-create-media-upload-url instead.',
			array( 'status' => 400 )
		);
	}

	$parent_id = us_mcp_media_validate_parent( $input );
	if ( is_wp_error( $parent_id ) ) {
		return $parent_id;
	}

	us_mcp_media_require_admin_files();

	// download_url streams to a temp file via wp_safe_remote_get — the
	// reject_unsafe_urls path (wp_http_validate_url) is the SSRF guard that
	// refuses loopback / private hosts and exotic ports.
	$tmp_file = download_url( $url, US_MCP_MEDIA_DOWNLOAD_TIMEOUT );
	if ( is_wp_error( $tmp_file ) ) {
		return new WP_Error(
			'us_mcp_media_download_failed',
			sprintf(
				'Could not download "%s": %s (WordPress also rejects URLs resolving to private / loopback hosts — the URL must be publicly reachable.)',
				$url,
				$tmp_file->get_error_message()
			),
			array( 'status' => 502 )
		);
	}

	if ( isset( $input['filename'] ) AND trim( (string) $input['filename'] ) !== '' ) {
		$filename = trim( (string) $input['filename'] );
	} else {
		$filename = rawurldecode( wp_basename( (string) parse_url( $url, PHP_URL_PATH ) ) );
	}

	$fields = array();
	foreach ( array( 'title', 'alt', 'caption' ) as $key ) {
		if ( isset( $input[ $key ] ) ) {
			$fields[ $key ] = (string) $input[ $key ];
		}
	}

	return us_mcp_media_create_attachment_from_file( $tmp_file, $filename, $fields, $parent_id, $url );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_update_media( $input ) {
	$input = (array) $input;

	$post = us_mcp_media_resolve_attachment( $input );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	$id = $post->ID;
	if ( ! current_user_can( 'edit_post', $id ) ) {
		return new WP_Error(
			'us_mcp_media_forbidden',
			sprintf( 'You do not have permission to edit attachment %d.', $id ),
			array( 'status' => 403 )
		);
	}

	$has_title   = array_key_exists( 'title', $input );
	$has_alt     = array_key_exists( 'alt', $input );
	$has_caption = array_key_exists( 'caption', $input );
	if ( ! $has_title AND ! $has_alt AND ! $has_caption ) {
		return new WP_Error(
			'us_mcp_media_no_changes',
			'No mutable fields supplied (title / alt / caption).',
			array( 'status' => 400 )
		);
	}

	$args = array( 'ID' => $id );
	if ( $has_title ) {
		$args['post_title'] = (string) $input['title'];
	}
	if ( $has_caption ) {
		$args['post_excerpt'] = (string) $input['caption'];
	}
	if ( count( $args ) > 1 ) {
		$result = wp_update_post( $args, /* wp_error */ TRUE );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
	}

	if ( $has_alt ) {
		$alt = sanitize_text_field( (string) $input['alt'] );
		if ( $alt === '' ) {
			delete_post_meta( $id, '_wp_attachment_image_alt' );
		} else {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		}
	}

	$post = get_post( $id );
	return us_mcp_media_payload( $post );
}

/**
 * @param array|object $input
 * @return array|WP_Error
 */
function us_mcp_ability_create_media_upload_url( $input ) {
	$input = (array) $input;

	$filename = isset( $input['filename'] ) ? sanitize_file_name( (string) $input['filename'] ) : '';
	if ( $filename === '' ) {
		return new WP_Error(
			'us_mcp_media_missing_filename',
			'Provide `filename` (preferably with the right extension, e.g. "photo.jpg").',
			array( 'status' => 400 )
		);
	}

	$parent_id = us_mcp_media_validate_parent( $input );
	if ( is_wp_error( $parent_id ) ) {
		return $parent_id;
	}

	try {
		$key = bin2hex( random_bytes( 16 ) );
	} catch ( \Throwable $e ) {
		return new WP_Error(
			'us_mcp_media_rng_unavailable',
			'Failed to generate a secure upload key — random_bytes() threw.',
			array( 'status' => 500 )
		);
	}

	$expires_at = time() + US_MCP_MEDIA_UPLOAD_URL_TTL;
	$claims = array(
		'user_id'    => get_current_user_id(),
		'filename'   => $filename,
		'parent_id'  => (int) $parent_id,
		'created_at' => time(),
		'expires_at' => $expires_at,
	);
	foreach ( array( 'title', 'alt', 'caption' ) as $field ) {
		if ( isset( $input[ $field ] ) ) {
			$claims[ $field ] = (string) $input[ $field ];
		}
	}

	$stored = set_transient( US_MCP_MEDIA_UPLOAD_TRANSIENT_PREFIX . $key, $claims, US_MCP_MEDIA_UPLOAD_URL_TTL );
	if ( $stored === FALSE ) {
		return new WP_Error(
			'us_mcp_media_store_failed',
			'set_transient() returned FALSE — the upload token was not persisted.',
			array( 'status' => 500 )
		);
	}

	return array(
		'upload_url'         => rest_url( 'upsolution/v1/media-upload/' . $key ),
		'method'             => 'POST',
		'expires_at'         => $expires_at,
		'expires_in_seconds' => (int) US_MCP_MEDIA_UPLOAD_URL_TTL,
		'max_bytes'          => (int) wp_max_upload_size(),
	);
}

// -----------------------------------------------------------------
// Signed-upload redemption endpoint.
//
// Registered only while the MCP feature is on (bootstrap gates this file).
// The 32-hex key in the path is the credential; permission_callback is
// intentionally __return_true and all validation happens against the
// transient claims inside the handler.
// -----------------------------------------------------------------
add_action( 'rest_api_init', function () {
	register_rest_route( 'upsolution/v1', '/media-upload/(?P<key>[a-f0-9]{32})', array(
		'methods'             => array( 'POST', 'PUT' ),
		'callback'            => 'us_mcp_media_upload_redeem',
		'permission_callback' => '__return_true',
		'show_in_index'       => FALSE,
	) );
} );

/**
 * Redeem a one-time signed upload URL: consume the token, impersonate the
 * minting user, stream the raw request body to a temp file (capped at the
 * server upload limit), then run the shared sideload tail.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function us_mcp_media_upload_redeem( WP_REST_Request $request ) {
	$transient_key = US_MCP_MEDIA_UPLOAD_TRANSIENT_PREFIX . (string) $request['key'];

	// Consume BEFORE doing any work — single-use even when the upload fails,
	// and delete_transient()'s return value arbitrates near-simultaneous
	// redeems (only the caller that actually deleted proceeds). The claim-
	// content checks share the same gate: any failure returns the same 410.
	$claims = get_transient( $transient_key );
	if (
		! is_array( $claims )
		OR ! delete_transient( $transient_key )
		OR empty( $claims['user_id'] )
		OR empty( $claims['filename'] )
		OR ( isset( $claims['expires_at'] ) AND time() > (int) $claims['expires_at'] )
	) {
		return new WP_Error(
			'us_mcp_media_upload_bad_token',
			'Unknown, expired or already-used upload URL. Mint a fresh one with upsolution-create-media-upload-url.',
			array( 'status' => 410 )
		);
	}

	// Re-run the capability check as of NOW (the minting user may have been
	// demoted / deleted since), then impersonate so authorship, kses level and
	// per-cap filters match a normal upload by that user.
	$user = get_user_by( 'id', (int) $claims['user_id'] );
	if ( ! $user OR ! user_can( $user, 'upload_files' ) ) {
		return new WP_Error(
			'us_mcp_media_upload_forbidden',
			'The user this upload URL was minted for can no longer upload files.',
			array( 'status' => 403 )
		);
	}
	wp_set_current_user( $user->ID );

	us_mcp_media_require_admin_files();

	$tmp_file = wp_tempnam( (string) $claims['filename'] );
	if ( ! $tmp_file ) {
		return new WP_Error(
			'us_mcp_media_tmp_failed',
			'Could not create a temp file for the upload.',
			array( 'status' => 500 )
		);
	}

	$max_bytes = (int) wp_max_upload_size();

	// Primary path: raw bytes in the request body, streamed in 1MB chunks so
	// an oversized body is cut off at the cap instead of buffered to memory.
	$copied = 0;
	$in  = @fopen( 'php://input', 'rb' );
	$out = @fopen( $tmp_file, 'wb' );
	if ( $in AND $out ) {
		while ( ! feof( $in ) ) {
			$chunk = fread( $in, MB_IN_BYTES );
			if ( $chunk === FALSE OR $chunk === '' ) {
				break;
			}
			$copied += strlen( $chunk );
			if ( $max_bytes > 0 AND $copied > $max_bytes ) {
				fclose( $in );
				fclose( $out );
				@unlink( $tmp_file );
				return new WP_Error(
					'us_mcp_media_too_large',
					sprintf( 'Request body exceeds the server upload limit of %s.', size_format( $max_bytes ) ),
					array( 'status' => 413 )
				);
			}
			if ( fwrite( $out, $chunk ) === FALSE ) {
				fclose( $in );
				fclose( $out );
				@unlink( $tmp_file );
				return new WP_Error(
					'us_mcp_media_tmp_failed',
					'Failed writing the upload to a temp file.',
					array( 'status' => 500 )
				);
			}
		}
	}
	if ( $in ) {
		fclose( $in );
	}
	if ( $out ) {
		fclose( $out );
	}

	// Fallback: multipart form-data (php://input is empty then — PHP already
	// parsed the body into $_FILES). Take the first file field.
	if ( $copied === 0 ) {
		$files = $request->get_file_params();
		if ( is_array( $files ) AND ! empty( $files ) ) {
			$first = reset( $files );
			if ( is_array( $first ) AND empty( $first['error'] ) AND ! empty( $first['tmp_name'] ) AND @is_readable( $first['tmp_name'] ) ) {
				$size = (int) @filesize( $first['tmp_name'] );
				if ( $max_bytes > 0 AND $size > $max_bytes ) {
					@unlink( $tmp_file );
					return new WP_Error(
						'us_mcp_media_too_large',
						sprintf( 'Uploaded file exceeds the server upload limit of %s.', size_format( $max_bytes ) ),
						array( 'status' => 413 )
					);
				}
				if ( $size > 0 AND @copy( $first['tmp_name'], $tmp_file ) ) {
					$copied = $size;
				}
			}
		}
	}

	if ( $copied === 0 ) {
		@unlink( $tmp_file );
		return new WP_Error(
			'us_mcp_media_empty_body',
			'Empty request body. Send the RAW file bytes as the body (curl --data-binary @file) or a multipart form-data body with one file field. Note: the upload URL was consumed by this attempt — mint a fresh one.',
			array( 'status' => 400 )
		);
	}

	$fields = array_intersect_key( $claims, array( 'title' => 1, 'alt' => 1, 'caption' => 1 ) );
	$result = us_mcp_media_create_attachment_from_file(
		$tmp_file,
		(string) $claims['filename'],
		$fields,
		isset( $claims['parent_id'] ) ? (int) $claims['parent_id'] : 0,
		/* source_url */ ''
	);
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$response = rest_ensure_response( $result );
	$response->set_status( 201 );
	return $response;
}
