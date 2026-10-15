<?php
/**
 * Plugin Name: urbanXgolf — Bildformat 3:2
 * Description: Einheitliches Fotoformat 3:2 — für die Parcours-Kacheln, das Bilderraster auf "Über uns" und jedes Bild-Widget mit der CSS-Klasse ug-32.
 * Version:     1.0.0
 * Author:      Anirudha Talmale
 *
 * The client wants every photograph on the site in one format, 3:2. Most already
 * were; two places were not:
 *
 *   - the course cards, which I had built at 4:3 to match the hand-made cards
 *     they replaced — the first thing he noticed when he entered a new plan;
 *   - the picture grid on "Über uns", which never had a ratio at all and showed
 *     each picture in whatever shape it was uploaded.
 *
 * Written as a plugin rather than into the theme's stylesheet because the
 * theme files cannot be reached from the admin on this host. That is also why
 * it is separate from the image protection and the footer line: each can be
 * switched off without taking the others with it.
 *
 * The frame crops, it never squashes (object-fit: cover). The enlarged view
 * still opens the whole picture, so a course plan cut at the edges on the card
 * is complete one click later.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ug_format_css() {
	if ( is_admin() ) {
		return;
	}

	/*
	 * Specificity, not load order: the theme stylesheet says
	 * `.ug-karte-bild { aspect-ratio: 4/3 }`, and whether this style lands before
	 * or after it depends on the theme. `.ug-karten .ug-karte-bild` outranks it
	 * either way.
	 *
	 * No object-position here. A default stated in this file would outrank the
	 * crop classes (ug-oben, ug-unten, ...) he sets himself in Elementor — I
	 * made exactly that mistake once already on this site.
	 */
	$css = '
.ug-karten .ug-karte-bild { aspect-ratio: 3 / 2; }

#page > .elementor .elementor-element-ugr0007 .elementor-widget-image img,
#page > .elementor .elementor-element-ugr0007 img {
	aspect-ratio: 3 / 2;
	width: 100%;
	height: auto;
	object-fit: cover;
}

/* For any image widget he adds himself: Erweitert > CSS-Klassen > ug-32
   The #page selector is not decoration: the theme has
   `#page > .elementor .elementor-widget-image img { object-fit: contain }`,
   which beat a plain `.ug-32 img` — measured: right ratio, but the photo
   letterboxed inside it instead of filling the frame. */
#page > .elementor .elementor-widget-image.ug-32 img,
.ug-32 img,
img.ug-32 {
	aspect-ratio: 3 / 2;
	width: 100%;
	height: auto;
	object-fit: cover;
}';

	wp_register_style( 'ug-format', false, [], '1.0.0' );
	wp_enqueue_style( 'ug-format' );
	wp_add_inline_style( 'ug-format', $css );
}
// Late, so it follows the theme and Elementor in the head.
add_action( 'wp_enqueue_scripts', 'ug_format_css', 99 );
