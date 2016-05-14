<?php
/**
 * Plugin Name:       Embed PDF Viewer
 * Plugin URI:        https://github.com/afragen/embed-pdf-viewer
 * Description:       Embed a PDF from the Media Library or via oEmbed in a Google Doc Viewer.
 * Author:            Andy Fragen
 * Author URI:        https://github.com/afragen
 * Version:           1.0
 * License:           GPLv2+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/afragen/embed-pdf-viewer
 * GitHub Branch:     master
 * Requires PHP:      5.3
 * Requires WP:       4.0
 */

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_filter( 'media_send_to_editor', array( Embed_PDF_Viewer::instance(), 'embed_pdf_media_editor' ), 20, 2 );
wp_embed_register_handler( 'oembed_pdf_viewer', '#(^(https?)\:\/\/.+\.pdf$)#i', array(
	Embed_PDF_Viewer::instance(),
	'oembed_pdf_viewer',
) );

/**
 * Class Embed_PDF_Viewer
 */
class Embed_PDF_Viewer {

	/**
	 * For singleton.
	 *
	 * @var bool
	 */
	protected static $object = false;

	/**
	 * Create singleton.
	 *
	 * @return bool
	 */
	public static function instance() {
		$class = __CLASS__;
		if ( false === self::$object ) {
			self::$object = new $class();
		}

		return self::$object;
	}

	/**
	 * Create media library insertion code.
	 *
	 * @param string  $html an href link to the media.
	 * @param integer $id   post_id.
	 *
	 * @return string
	 */
	public function embed_pdf_media_editor( $html, $id ) {
		$post = get_post( $id );

		return $this->create_output( $post ) . "\n\n";
	}

	/**
	 * Create oEmbed code.
	 *
	 * @param array  $matches
	 * @param array  $atts array of media height/width.
	 * @param string $url  URI for media file.
	 *
	 * @return string
	 */
	public function oembed_pdf_viewer( $matches, $atts, $url ) {
		$post = get_post( $this->get_attachment_id_by_url( $url ) );

		return $this->create_output( $post, $atts );
	}

	/**
	 * Create output for Google Doc Viewer and href link to file.
	 *
	 * @param \WP_Post $post
	 * @param array    $atts
	 *
	 * @return bool|string
	 */
	private function create_output( WP_Post $post, $atts = array() ) {
		if ( 'application/pdf' !== $post->post_mime_type ) {
			return false;
		}

		$default = array(
			'height' => 900,
			'width'  => 800,
			'title'  => $post->post_title,
		);

		if ( ! empty( $atts ) ) {
			$atts['height'] = ( $atts['height'] / 2 );
		}
		$atts = array_merge( $default, $atts );

		/*
		 * Create title from filename.
		 */
		if ( empty( $atts['title'] ) ) {
			$atts['title'] = ucwords( preg_replace( '/(-|_)/', ' ', $post->post_name ) );
		}

		$embed = '<iframe src="https://docs.google.com/viewer?url=' . urlencode( $post->guid );
		$embed .= '&amp;embedded=true" frameborder="0" ';
		$embed .= 'style="height:' . $atts['height'] . 'px;width:' . $atts['width'] . 'px;" ';
		$embed .= 'title="' . $atts['title'] . '"></iframe>' . "\n";
		$embed .= '<a href="' . $post->guid . '">' . $atts['title'] . '</a>';

		return $embed;
	}

	/**
	 * Get attachment id by url. Thanks Pippin.
	 *
	 * @link  https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	 *
	 * @param string $url URI of attachment.
	 *
	 * @return mixed
	 */
	private function get_attachment_id_by_url( $url ) {
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url ) );

		return $attachment[0];
	}

}
