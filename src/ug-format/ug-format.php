<?php
/**
 * Plugin Name: urbanXgolf — Layout (Bildformat 3:2, Copyright)
 * Description: Einheitliches Fotoformat 3:2 (Parcours-Kacheln, Bilderraster "Über uns", CSS-Klasse ug-32) und der Copyright-Hinweis unter dem Logo: zweizeilig, klein, rechtsbündig, auf einer Höhe mit der Signatur.
 * Version:     1.2.0
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


/* -------------------------------------------------------------------------
 * The copyright notice under the logo (1.1)
 *
 * The client moved the notice out of the bottom bar into the logo column and
 * deleted the bar. What Elementor would not give him: the notice as small as
 * the signature, flush right under the logo, and on the same line as the
 * signature across the footer. Sizes are a setting; "on the same line across
 * two different columns" is not — it needs the columns to be equally tall and
 * both lines pushed to the bottom, which only CSS can say.
 * ---------------------------------------------------------------------- */

/**
 * Mark the copyright paragraph so the stylesheet can find it.
 *
 * By its own text, not by element id: he moved the widget himself, and whether
 * a drag in Elementor keeps the id or creates a copy is not something I can
 * see from here. The decision depends on this widget's own content only —
 * Elementor caches widget output and replays it without calling this filter,
 * so nothing here may depend on what another widget did.
 */
function ug_format_copyright_markieren( $content, $widget ) {
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}
	if ( $widget->get_name() !== 'text-editor' || strpos( $content, 'Alle Rechte vorbehalten' ) === false ) {
		return $content;
	}
	if ( strpos( $content, 'ug-copyright' ) !== false ) {
		return $content;
	}
	$neu = preg_replace( '~<p(\s|>)~', '<p class="ug-copyright"$1', $content, 1, $treffer );
	$neu = $treffer ? $neu : '<div class="ug-copyright">' . $content . '</div>';

	// Two lines, and the break exactly where he asked for it: "Alle Rechte
	// vorbehalten" on a line of its own. Balanced wrapping got there on my copy
	// of the site, but only by width — on his, where the notice was not yet in
	// the narrow logo column, it stayed on one line. A real break does not
	// depend on how wide anything is.
	if ( stripos( $neu, '<br' ) === false ) {
		$neu = preg_replace( '~\s*(Alle Rechte vorbehalten)~u', '<br>$1', $neu, 1 );
	}
	return $neu;
}

/**
 * Put the notice under the logo, wherever it was placed in Elementor.
 *
 * On his site the notice ended up as its own element below the whole footer
 * row, not inside the logo column — his screenshot shows it at the far right
 * under everything. Elementor's drag and drop between nested containers is
 * exactly the fiddly part he could not get to work, so rather than send him
 * back into it, the page moves the element into the logo column as it loads.
 *
 * The logo column is found as the container of the footer's first image — the
 * logo is the only picture in the footer. If the notice is already there,
 * nothing happens. Without JavaScript it stays where Elementor put it, which is
 * still a correct, readable footer.
 */
function ug_format_copyright_js() {
	if ( is_admin() ) {
		return;
	}
	$js = <<<'JS'
(function(){
  'use strict';
  function verschieben(){
    var f = document.querySelector('footer');
    if (!f) { return; }
    var text = f.querySelector('.ug-copyright');
    var bild = f.querySelector('.elementor-widget-image');
    if (!text || !bild) { return; }
    var widget = text.closest('.elementor-widget');
    var spalte = bild.parentElement;
    if (!widget || !spalte || spalte.contains(widget)) { return; }
    spalte.appendChild(widget);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verschieben);
  } else {
    verschieben();
  }
}());
JS;
	wp_register_script( 'ug-format-copyright', false, [], '1.2.0', true );
	wp_enqueue_script( 'ug-format-copyright' );
	wp_add_inline_script( 'ug-format-copyright', $js );
}
add_action( 'wp_enqueue_scripts', 'ug_format_copyright_js', 99 );
add_filter( 'elementor/widget/render_content', 'ug_format_copyright_markieren', 20, 2 );

function ug_format_copyright_css() {
	if ( is_admin() ) {
		return;
	}
	/*
	 * Same size, colour and line height as the signature, so that once both sit
	 * at the bottom their last lines share a baseline.
	 *
	 * Right-aligned only where the logo fills its column (above 1024px): there
	 * the right edge of the column is the right edge of the logo. Below that the
	 * logo is centred, and a notice flush right under a centred logo would look
	 * like a mistake — so it is centred with it.
	 *
	 * The bottom alignment needs a row, so it starts at 768px, where the footer
	 * columns stop stacking. Stretching a stacked column would change its width.
	 */
	$css = '
footer .ug-copyright {
	margin: 0;
	font-size: 13px;
	line-height: 1.6;
	color: #8a9096;
	opacity: .85;
	letter-spacing: .01em;
	text-align: right;
	/* The column is only as wide as the logo, so the notice takes two lines.
	   Without this it broke as "... Alle Rechte / vorbehalten." — one word left
	   on its own. Balanced, it splits at the sentence. */
	text-wrap: balance;
}
@media (max-width: 1024px) {
	footer .ug-copyright { text-align: center; }
}
@media (max-width: 767px) {
	footer .ug-copyright { font-size: 12px; }
}
@media (min-width: 768px) {
	footer .e-con:has(> .e-con .ug-copyright) {
		--align-items: stretch;
		align-items: stretch;
	}
	footer .elementor-widget:has(> .ug-copyright) {
		margin-top: auto;
		padding-top: 18px;
	}
}';
	wp_register_style( 'ug-format-copyright', false, [], '1.2.0' );
	wp_enqueue_style( 'ug-format-copyright' );
	wp_add_inline_style( 'ug-format-copyright', $css );
}
add_action( 'wp_enqueue_scripts', 'ug_format_copyright_css', 99 );
