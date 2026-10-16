<?php
/**
 * Plugin Name: urbanXgolf — Layout (Bildformat 3:2, Copyright)
 * Description: Einheitliches Fotoformat 3:2 (Parcours-Kacheln, Bilderraster "Über uns", CSS-Klasse ug-32) und der Copyright-Hinweis unter dem Logo: zweizeilig, klein, linksbündig, auf einer Höhe mit der Signatur. Dazu: lesbares Handy-Menü und saubere Adresszeile im Footer.
 * Version:     1.6.0
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
    if (widget && spalte && !spalte.contains(widget)) { spalte.appendChild(widget); }
    einzug(text, bild.querySelector('img'));
  }
  // Where the visible logo begins, measured from the notice's own left edge.
  var RAND = 47 / 660;   // transparent margin of logo-white.png, each side
  function einzug(text, img) {
    if (!img) { return; }
    var setzen = function () {
      var r = img.getBoundingClientRect();
      var t = text.getBoundingClientRect();
      if (!r.width) { return; }
      var px = Math.max(0, Math.round(r.left - t.left + r.width * RAND));
      text.style.setProperty('--ug-logo-einzug', px + 'px');
    };
    if (img.complete) { setzen(); } else { img.addEventListener('load', setzen); }
    var timer;
    window.addEventListener('resize', function () {
      clearTimeout(timer); timer = setTimeout(setzen, 120);
    }, { passive: true });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verschieben);
  } else {
    verschieben();
  }
}());
JS;
	wp_register_script( 'ug-format-copyright', false, [], '1.3.0', true );
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
	/* 1.3: left, not right — his call after seeing it on the page. Flush with
	   the left edge of the logo, which is also where the footer text begins. */
	text-align: left;
	/* The column is only as wide as the logo, so the notice takes two lines.
	   Without this it broke as "... Alle Rechte / vorbehalten." — one word left
	   on its own. Balanced, it splits at the sentence. */
	text-wrap: balance;
}
/* The logo file has a transparent margin of 7.1 % on each side (47 of 660 px),
   so its picture does not start where its box starts. Aligned to the box, the
   notice stuck out about 18 px to the left of the visible logo — measured as
   "0 px off" and plainly wrong in the screenshot. The script below sets the
   exact indent from the rendered logo; 7.121 % is the fallback without it. */
@media (min-width: 1025px) {
	footer .ug-copyright { padding-left: var(--ug-logo-einzug, 7.121%); }
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
	wp_register_style( 'ug-format-copyright', false, [], '1.3.0' );
	wp_enqueue_style( 'ug-format-copyright' );
	wp_add_inline_style( 'ug-format-copyright', $css );
}
add_action( 'wp_enqueue_scripts', 'ug_format_copyright_css', 99 );


/* -------------------------------------------------------------------------
 * The phone menu (1.4)
 *
 * Reported by a visitor on her phone: the menu opened, but showed nothing
 * except "Start". Every other item was there — white text on a white panel.
 * The menu colour was set to white for the dark desktop header; the dropdown
 * that replaces it below 1024 px keeps the plugin's default white background,
 * and only the current page's item, drawn in blue, could be seen. My build, and
 * never caught because I never looked at the menu opened on a phone.
 *
 * The panel now takes the header's own dark colour, so the white text reads
 * as it does on the desktop and the current page stays blue, as it is there.
 * The selector repeats .hfe-dropdown to outrank the plugin's generated rule,
 * which names the header by its post id — something this file cannot know.
 * Four repeats were not enough: that rule reaches the same weight (five classes,
 * three elements) and is printed later in the page, so it won the tie and the
 * panel stayed white. Six repeats win outright, wherever it is printed.
 * ---------------------------------------------------------------------- */
function ug_format_menu_css() {
	if ( is_admin() ) {
		return;
	}
	$css = '
@media (max-width: 1024px) {
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown,
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown .hfe-nav-menu,
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown li.menu-item {
		background-color: #191718;
	}
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown a.hfe-menu-item {
		background-color: #191718;
		color: #FFFFFF;
		border-bottom: 1px solid rgba(255, 255, 255, .12);
	}
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown li.current-menu-item > a.hfe-menu-item {
		color: #0A62D0;
	}
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown a.hfe-menu-item:hover,
	header nav.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown.hfe-dropdown a.hfe-menu-item:focus-visible {
		background-color: #242123;
	}
}';
	wp_register_style( 'ug-format-menu', false, [], '1.4.0' );
	wp_enqueue_style( 'ug-format-menu' );
	wp_add_inline_style( 'ug-format-menu', $css );
}
add_action( 'wp_enqueue_scripts', 'ug_format_menu_css', 99 );


/* -------------------------------------------------------------------------
 * The address line in the footer (1.4)
 *
 * The postcode was pushed onto its own line with a long row of spaces after
 * "Wertgasse 11". In the narrow desktop column the spaces fill the line and the
 * postcode wraps where intended; on a phone the column is wider, the spaces fit,
 * and the line broke as "Wertgasse 11 45468 Mülheim" / "an der Ruhr".
 *
 * A row of three or more spaces directly before a five-digit postcode is
 * turned into a real line break, which holds at every width. Trailing spaces
 * at the end of a paragraph go too. Text without such a row is untouched, and
 * the decision looks at this widget's own text only (see the cache note above).
 * ---------------------------------------------------------------------- */
function ug_format_adresse( $content, $widget ) {
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || $widget->get_name() !== 'text-editor' ) {
		return $content;
	}
	$leer = '(?:\x{00A0}|&nbsp;|&#160;|\s)';
	$neu  = preg_replace( '~' . $leer . '{3,}(?=\d{5}\b)~u', '<br>', $content );
	$neu  = preg_replace( '~' . $leer . '{2,}(</p>)~u', '$1', (string) $neu );
	return is_string( $neu ) ? $neu : $content;
}
add_filter( 'elementor/widget/render_content', 'ug_format_adresse', 20, 2 );


/* -------------------------------------------------------------------------
 * "Über uns": the picture grid beside "Gute Idee. Neu gedacht." (1.5)
 *
 * His wish: room for two more rows of photos beside the long text, without
 * placeholders standing in them. A row exists only once a photo is in it; he
 * adds one by duplicating a photo in Elementor and swapping the picture. The
 * last, part-filled row is centred — one photo in the middle, two close
 * together in the middle (his choice, 16 Oct).
 *
 * A three-column grid cannot put anything "between" two columns, so it gets
 * six narrow tracks and every photo spans two. A full row is 1-2, 3-4, 5-6;
 * one left over starts at 3, two left over at 2 and 4.
 *
 * !important on purpose, and the reason is measured, not assumed: the theme's
 * thumbs.js writes grid-column inline on these photos and rewrites it on every
 * resize, and its phone rule carries !important as well. Built and tested
 * against his own page from the export of 16 Oct (nine photos, id ugr0007),
 * not against my outdated copy.
 * ---------------------------------------------------------------------- */
function ug_format_raster_css() {
	if ( is_admin() ) {
		return;
	}
	$css = '
#page > .elementor .elementor-element-ugr0007 { grid-template-columns: repeat(6, minmax(0, 1fr)) !important; }

/* "Kontakt" (1.6): the photo began level with the yellow rule, 56 px above the
   heading — measured on his page at 1400, 1100 and 820 px. Same remedy as the
   grid on "Über uns", which is what he pointed to. Only while text and photo
   sit side by side; stacked on a phone the offset would just be a gap. */
@media (min-width: 768px) {
	#page > .elementor .elementor-element-ug00197 { margin-top: 56px; }
}
#page > .elementor .elementor-element-ugr0007 > * { grid-column: span 2 !important; }
#page > .elementor .elementor-element-ugr0007 > .ug-rest-mitte  { grid-column: 3 / span 2 !important; }
#page > .elementor .elementor-element-ugr0007 > .ug-rest-links  { grid-column: 2 / span 2 !important; }
#page > .elementor .elementor-element-ugr0007 > .ug-rest-rechts { grid-column: 4 / span 2 !important; }

/* "Unterwegs seit 2026": the five photos sit on a photo band — a picture on a
   picture, so they get the white frame the thumbnails on "Spielen" have. 4:3
   rather than 3:2: his files are 800 x 600, and in 4:3 the preview shows the
   whole photograph instead of cutting off top and bottom (his call, 16 Oct). */
#page > .elementor .elementor-element-ugr0011 img {
	display: block;
	width: 100%;
	height: auto;
	aspect-ratio: 4 / 3;
	object-fit: cover;
	max-height: none;
	border: 5px solid #FFFFFF;
	border-radius: 12px;
	box-shadow: 0 8px 22px rgba(0, 0, 0, .28);
	box-sizing: border-box;
}
@media (max-width: 767px) {
	#page > .elementor .elementor-element-ugr0011 img { border-width: 4px; border-radius: 10px; }
}';
	$js = <<<'JS'
(function(){
  'use strict';
  function ordnen(){
    [].forEach.call(document.querySelectorAll('#page > .elementor .elementor-element-ugr0007'), function(raster){
      // Only the photos: inside the editor Elementor adds overlays and handles.
      var b = [].slice.call(raster.children).filter(function(el){ return !!el.querySelector('img'); });
      b.forEach(function(el){ el.classList.remove('ug-rest-mitte','ug-rest-links','ug-rest-rechts'); });
      var rest = b.length % 3;
      if (rest === 1) { b[b.length - 1].classList.add('ug-rest-mitte'); }
      if (rest === 2) { b[b.length - 2].classList.add('ug-rest-links'); b[b.length - 1].classList.add('ug-rest-rechts'); }
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', ordnen); } else { ordnen(); }
}());
JS;
	wp_register_style( 'ug-format-raster', false, [], '1.5.0' );
	wp_enqueue_style( 'ug-format-raster' );
	wp_add_inline_style( 'ug-format-raster', $css );
	wp_register_script( 'ug-format-raster', false, [], '1.5.0', true );
	wp_enqueue_script( 'ug-format-raster' );
	wp_add_inline_script( 'ug-format-raster', $js );
}
add_action( 'wp_enqueue_scripts', 'ug_format_raster_css', 99 );
