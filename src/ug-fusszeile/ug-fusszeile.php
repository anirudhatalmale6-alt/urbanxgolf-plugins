<?php
/**
 * Plugin Name: urbanXgolf — Signatur in der Fußzeile
 * Description: Setzt eine dezente Zeile "Website by Anirudha Talmale" unter die Spalte "Schnellzugriff" im Footer. Kann jederzeit deaktiviert werden.
 * Version:     1.2.0
 * Author:      Anirudha Talmale
 *
 * The client offered this himself. It is written as a separate plugin for one
 * reason: a credit line should be the easiest thing on the site to remove. One
 * click under Plugins and it is gone, without touching his footer design and
 * without me having to come back.
 *
 * 1.1: at his suggestion the line moves out of the bottom bar and under the
 * "Schnellzugriff" list, so the bottom bar can go. It still attaches to a
 * widget rather than being written into his Elementor data — his own edits
 * cannot collide with it, and it cannot survive the plugin being switched off.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UG_FUSSZEILE_TEXT', 'Website by Anirudha Talmale' );

/**
 * Is this the "Schnellzugriff" list?
 *
 * The footer has two icon lists: the contact block (phone, mail) and the quick
 * links. Only the quick links name both "Impressum" and "Kontakt", so those two
 * words together pick it out — the element id would not, because Elementor ids
 * are only unique within one document and the same id turns up on other pages.
 */
function ug_fusszeile_ist_schnellzugriff( $widget, $content ) {
	return $widget->get_name() === 'icon-list'
		&& stripos( $content, 'Impressum' ) !== false
		&& stripos( $content, 'Kontakt' ) !== false;
}

/**
 * Attach the line to the quick-links list — and nowhere else.
 *
 * Version 1.1 first kept the copyright notice as a fallback, with a static
 * "already placed" flag so the line could not appear twice. It appeared twice
 * anyway, from the second page view on. Elementor caches each widget's output
 * and serves it without calling this filter again, so the flag was only ever
 * set on the request that built the cache — later requests saw it unset and
 * stamped the copyright as well. Measured: one line on the first load, two on
 * every load after.
 *
 * So no decision here may depend on another widget. The line has exactly one
 * home. If the quick links are ever renamed or removed, it simply does not
 * appear — the right failure for a credit: it disappears, it breaks nothing.
 */
function ug_fusszeile_anhaengen( $content, $widget ) {
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}
	if ( strpos( $content, 'ug-signatur' ) !== false ) {
		return $content;
	}
	if ( ! ug_fusszeile_ist_schnellzugriff( $widget, $content ) ) {
		return $content;
	}
	return $content . '<p class="ug-signatur ug-signatur-links">' . esc_html( UG_FUSSZEILE_TEXT ) . '</p>';
}
add_filter( 'elementor/widget/render_content', 'ug_fusszeile_anhaengen', 20, 2 );

/**
 * Quieter than the links above it: smaller, grey, not in capitals.
 *
 * The colour is the grey of the existing copyright text (rgb(138,144,150)), so
 * it reads as a footnote of the footer rather than as one more link.
 */
function ug_fusszeile_css() {
	echo "\n<style id=\"ug-fusszeile-css\">"
		. '.ug-signatur{margin:6px 0 0;font-size:13px;line-height:1.6;'
		. 'color:#8a9096;letter-spacing:.01em;opacity:.85;text-transform:none}'
		. '.ug-signatur-links{margin-top:22px}'
		// 1.2: on one line with the copyright under the logo. The list widget
		// grows to the foot of its column and the line sits at its bottom; the
		// columns themselves are made equally tall by the layout plugin. Without
		// that plugin nothing changes here — the line simply stays under the list.
		. '@media (min-width:768px){'
		. 'footer .elementor-widget:has(> .ug-signatur-links){flex-grow:1;display:flex;flex-direction:column}'
		. 'footer .ug-signatur-links{margin-top:auto;padding-top:22px}}'
		. '@media (max-width:767px){.ug-signatur{font-size:12px;margin-top:4px}'
		. '.ug-signatur-links{margin-top:18px}}'
		. "</style>\n";
}
add_action( 'wp_head', 'ug_fusszeile_css', 20 );
