<?php
/**
 * Plugin Name: urbanXgolf — Signatur in der Fußzeile
 * Description: Setzt eine dezente Zeile "Website by Anirudha Talmale" unter den Copyright-Hinweis im Footer. Kann jederzeit deaktiviert werden.
 * Version:     1.0.0
 * Author:      Anirudha Talmale
 *
 * The client offered this himself. It is written as a separate plugin for one
 * reason: a credit line should be the easiest thing on the site to remove. One
 * click under Plugins and it is gone, without touching his footer design and
 * without me having to come back.
 *
 * It attaches to the copyright widget rather than to the footer template, so it
 * sits inside the existing bottom bar and inherits its spacing. Nothing is
 * written into his Elementor data — his own edits cannot collide with it, and it
 * cannot survive the plugin being switched off.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UG_FUSSZEILE_TEXT', 'Website by Anirudha Talmale' );

/**
 * Append the line to the copyright widget.
 *
 * Two conditions, because Elementor element ids are only unique within one
 * document: the footer's copyright widget and a button on the front page both
 * carry ug00018, so matching on the id alone would have stamped the credit onto
 * that button as well. Matching the widget type and its own text is unambiguous.
 *
 * If he ever rewrites the copyright sentence the line simply stops appearing.
 * That is the right failure for a credit: it disappears, it does not break
 * anything of his.
 */
function ug_fusszeile_anhaengen( $content, $widget ) {
	if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}
	if ( $widget->get_name() !== 'text-editor' ) {
		return $content;
	}
	if ( strpos( $content, 'Alle Rechte vorbehalten' ) === false ) {
		return $content;
	}
	if ( strpos( $content, 'ug-signatur' ) !== false ) {
		return $content;        // never twice on one page
	}

	return $content . '<p class="ug-signatur">' . esc_html( UG_FUSSZEILE_TEXT ) . '</p>';
}
add_filter( 'elementor/widget/render_content', 'ug_fusszeile_anhaengen', 20, 2 );

/**
 * Quieter than the line above it: smaller, same grey, a little space between.
 *
 * The colour is taken from the copyright text that is already there
 * (rgb(138,144,150)) so the two read as one block rather than as something
 * bolted on.
 */
function ug_fusszeile_css() {
	echo "\n<style id=\"ug-fusszeile-css\">"
		. '.ug-signatur{margin:6px 0 0;font-size:13px;line-height:1.6;'
		. 'color:#8a9096;letter-spacing:.01em;opacity:.85}'
		. '@media (max-width:767px){.ug-signatur{font-size:12px;margin-top:4px}}'
		. "</style>\n";
}
add_action( 'wp_head', 'ug_fusszeile_css', 20 );
