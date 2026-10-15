<?php
/**
 * Plugin Name: urbanXgolf — Bildschutz
 * Description: Erschwert das Speichern der Bilder: Rechtsklick, Ziehen und Halte-Menü sind auf Bildern abgeschaltet, und es wird nie die Originalauflösung ausgeliefert (max. 1536 Pixel).
 * Version:     1.0.0
 * Author:      Anirudha Talmale
 *
 * What this is, honestly: a hurdle, not a lock. A picture that a browser shows
 * is already on the visitor's device — in the cache, in a screenshot, in the
 * developer tools. Nothing running in that browser can change this, and any
 * plugin that promises otherwise is selling the same hurdle with a bigger claim.
 *
 * What it does do is stop the casual attempt: the right-click "save image as",
 * dragging the picture onto the desktop, and the press-and-hold menu on a phone.
 * That covers the person who would take a picture because it was easy, which is
 * most of them.
 *
 * Scoped to images on purpose. Disabling the context menu on the whole page
 * would also take away "open link in new tab" and "copy address" from people
 * who are simply reading the site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The widest picture the public side of the site will hand out.
 *
 * 1600 rather than something smaller: on a normal screen the enlarged view is
 * about 1300 pixels wide, so nobody sees a difference — but the print-quality
 * original stays on the server. WordPress keeps a 1536px copy of every large
 * upload, which is what gets served instead.
 */
define( 'UG_BILDSCHUTZ_MAX', 1600 );

/**
 * Keep the oversized candidates out of srcset.
 *
 * The enlargement reads the widest entry of srcset, so capping the list caps
 * the enlargement — without the lightbox having to know anything about it.
 */
function ug_bildschutz_srcset_max( $max ) {
	return is_admin() ? $max : UG_BILDSCHUTZ_MAX;
}
add_filter( 'max_srcset_image_width', 'ug_bildschutz_srcset_max', 10, 1 );

/**
 * Swap any picture wider than the limit for the 1536px copy.
 *
 * This one filter covers everything, which is why it is done here and not in
 * four places: Elementor's image widgets, the pictures in the course cards, the
 * photo in the map bubble and the enlarged view all end up asking WordPress for
 * the file through this function.
 *
 * Only on the front end. In the admin he has to see and pick his real files —
 * capping there would hand him a smaller picture every time he placed one, and
 * that would quietly degrade the originals in his own pages.
 */
function ug_bildschutz_src_begrenzen( $image, $attachment_id, $size, $icon ) {
	static $laeuft = false;

	if ( is_admin() || $laeuft || $icon || ! is_array( $image ) ) {
		return $image;
	}
	$breite = isset( $image[1] ) ? (int) $image[1] : 0;
	if ( $breite <= UG_BILDSCHUTZ_MAX ) {
		return $image;
	}

	// The guard matters: asking for another size runs this same filter again.
	$laeuft = true;
	$klein  = wp_get_attachment_image_src( $attachment_id, '1536x1536', false );
	$laeuft = false;

	if ( is_array( $klein ) && ! empty( $klein[1] ) && (int) $klein[1] < $breite ) {
		return $klein;
	}
	return $image;   // no smaller copy exists; better a big picture than none
}
add_filter( 'wp_get_attachment_image_src', 'ug_bildschutz_src_begrenzen', 10, 4 );

function ug_bildschutz_assets() {
	// Only the front end — inside the Elementor editor he has to be able to work
	// with his own pictures normally.
	if ( is_admin() ) {
		return;
	}

	$css = 'img, .ug-bildbox img, .elementor-widget-image img {'
		. '-webkit-touch-callout: none;'      /* the press-and-hold menu on iOS */
		. '-webkit-user-drag: none;'
		. 'user-select: none; -webkit-user-select: none;'
		. '}';

	$js = <<<'JS'
(function(){
  'use strict';
  function istBild(el){
    return el && (el.tagName === 'IMG' ||
      (el.closest && el.closest('.ug-bildbox, .elementor-widget-image, .ug-thumb')));
  }
  // The right-click menu, but only over a picture.
  document.addEventListener('contextmenu', function(e){
    if (istBild(e.target)) { e.preventDefault(); }
  });
  // Dragging a picture out of the page onto the desktop.
  document.addEventListener('dragstart', function(e){
    if (istBild(e.target)) { e.preventDefault(); }
  });
}());
JS;

	wp_register_style( 'ug-bildschutz', false, [], '1.0.0' );
	wp_enqueue_style( 'ug-bildschutz' );
	wp_add_inline_style( 'ug-bildschutz', $css );

	wp_register_script( 'ug-bildschutz', false, [], '1.0.0', true );
	wp_enqueue_script( 'ug-bildschutz' );
	wp_add_inline_script( 'ug-bildschutz', $js );
}
add_action( 'wp_enqueue_scripts', 'ug_bildschutz_assets', 20 );
