<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;

add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'bootstrap','hestia-font-sizes' ) );
    }
endif;

add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION

// ============================================================================
// FONCTIONS PERSONNALISÉES URBAN QUEST
// ============================================================================
// IMPORTANT: Ce projet est un child theme de Hestia
// 
// Fonctions:
// - Supprime les métadonnées "publié par" pour le post type "game"
// - Supprime la section "Articles similaires" pour le post type "game"
// ============================================================================

// Supprimer uniquement les métadonnées "publié par" pour le post type "game"
add_action('template_redirect', function() {
	if (is_singular('game')) {
		// Supprimer uniquement les hooks qui affichent "publié par"
		remove_all_actions('hestia_single_post_meta');
		remove_all_actions('hestia_blog_post_meta');
		remove_all_actions('hestia_after_single_post_title');
		remove_all_actions('hestia_after_post_title');
		
		// Supprimer la section "Articles similaires"
		remove_all_actions('hestia_blog_related_posts');
		remove_all_actions('hestia_related_posts');
		remove_all_actions('hestia_after_single_post_content');
		
		// Utiliser des filtres pour supprimer uniquement la sortie "publié par"
		add_filter('hestia_single_post_meta', '__return_empty_string', 999);
		add_filter('hestia_blog_post_meta', '__return_empty_string', 999);
		add_filter('hestia_posted_on', '__return_empty_string', 999);
	}
}, 1);

// Supprimer aussi via wp hook
add_action('wp', function() {
	if (is_singular('game')) {
		remove_all_actions('hestia_single_post_meta');
		remove_all_actions('hestia_blog_post_meta');
		remove_all_actions('hestia_after_single_post_title');
		remove_all_actions('hestia_after_post_title');
		remove_all_actions('hestia_blog_related_posts');
		remove_all_actions('hestia_related_posts');
	}
}, 1);

// Supprimer les articles similaires via le filtre
add_filter('hestia_show_related_posts', function($show) {
	if (is_singular('game')) {
		return false;
	}
	return $show;
}, 999);

