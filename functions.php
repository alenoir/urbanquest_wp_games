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


add_action( 'wp_enqueue_scripts', 'urbanquest_enqueue_responsive_styles', 20 );

// END ENQUEUE PARENT ACTION

// ============================================================================
// FONCTIONS PERSONNALISÉES URBAN QUEST
// ============================================================================
// IMPORTANT: Ce projet est un child theme de Hestia
// 
// Fonctions:
// - Supprime les métadonnées "publié par" pour les post types personnalisés (game, country, region, departement, ville)
// - Supprime la section "Articles similaires" pour les post types personnalisés
// - Cache l'éditeur de contenu pour les post types personnalisés
// ============================================================================

/**
 * Cacher l'éditeur de contenu pour les post types personnalisés
 * Les éditeurs WordPress n'ont pas besoin de l'éditeur Gutenberg/classique
 * car tout le contenu est géré via les champs ACF
 */
function urbanquest_remove_editor_support() {
	$post_types = array('game', 'country', 'region', 'departement', 'ville');
	
	foreach ($post_types as $post_type) {
		remove_post_type_support($post_type, 'editor');
	}
}
add_action('init', 'urbanquest_remove_editor_support', 100);

// Supprimer uniquement les métadonnées "publié par" pour les post types personnalisés
add_action('template_redirect', function() {
	$post_types = array('game', 'country', 'region', 'departement', 'ville');
	foreach ($post_types as $post_type) {
		if (is_singular($post_type)) {
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
			break; // Sortir de la boucle une fois qu'on a trouvé le bon post type
		}
	}
}, 1);

// Supprimer aussi via wp hook
add_action('wp', function() {
	$post_types = array('game', 'country', 'region', 'departement', 'ville');
	foreach ($post_types as $post_type) {
		if (is_singular($post_type)) {
			remove_all_actions('hestia_single_post_meta');
			remove_all_actions('hestia_blog_post_meta');
			remove_all_actions('hestia_after_single_post_title');
			remove_all_actions('hestia_after_post_title');
			remove_all_actions('hestia_blog_related_posts');
			remove_all_actions('hestia_related_posts');
			break; // Sortir de la boucle une fois qu'on a trouvé le bon post type
		}
	}
}, 1);

// Supprimer les articles similaires via le filtre
add_filter('hestia_show_related_posts', function($show) {
	$post_types = array('game', 'country', 'region', 'departement', 'ville');
	foreach ($post_types as $post_type) {
		if (is_singular($post_type)) {
			return false;
		}
	}
	return $show;
}, 999);

// ============================================================================
// RÉÉCRITURE D'URL POUR LES CUSTOM POST TYPES
// ============================================================================
// Structures d'URL souhaitées :
// - jeu : /jeux-de-piste/%ville%/%game_title%
// - ville : /jeux-de-piste/%ville%
// - région : /jeux-de-piste/%region%
// - département : /jeux-de-piste/%departement%
// - pays : /jeux-de-piste/%pays%
// ============================================================================

/**
 * Fonction helper pour obtenir le slug d'un post
 */
function urbanquest_get_post_slug($post_id) {
	$post = get_post($post_id);
	if (!$post) {
		return '';
	}
	// Utiliser post_name si disponible, sinon générer depuis le titre
	if (!empty($post->post_name)) {
		return $post->post_name;
	}
	return sanitize_title($post->post_title);
}

/**
 * Fonction helper pour extraire l'ID d'un champ ACF relationship (OPTIMISÉ pour return_format: "id")
 * Gère les formats : ID numérique (nouveau format optimisé), objet WP_Post, ou tableau
 */
function urbanquest_extract_acf_relationship_id($field_value) {
	if (!$field_value) {
		return null;
	}
	
	// Format ID numérique (nouveau format optimisé avec return_format: "id")
	if (is_numeric($field_value)) {
		return intval($field_value);
	}
	
	// Format objet WP_Post (ancien format avec return_format: "object")
	if (is_object($field_value) && isset($field_value->ID)) {
		return intval($field_value->ID);
	}
	
	// Format tableau (pour compatibilité)
	if (is_array($field_value) && !empty($field_value)) {
		$first_item = $field_value[0];
		if (is_object($first_item) && isset($first_item->ID)) {
			return intval($first_item->ID);
		}
		if (is_numeric($first_item)) {
			return intval($first_item);
		}
	}
	
	return null;
}

/**
 * Ajouter les tags de réécriture personnalisés
 */
function urbanquest_add_rewrite_tags() {
	add_rewrite_tag('%ville%', '([^/]+)', 'ville=');
	add_rewrite_tag('%region%', '([^/]+)', 'region=');
	add_rewrite_tag('%departement%', '([^/]+)', 'departement=');
	add_rewrite_tag('%pays%', '([^/]+)', 'pays=');
}
add_action('init', 'urbanquest_add_rewrite_tags', 1);

/**
 * Ajouter les règles de réécriture d'URL
 */
function urbanquest_add_rewrite_rules() {
	// Règle pour les jeux : /jeux-de-piste/%ville%/%game_title%
	// Cette règle doit être en premier car elle a 2 segments
	add_rewrite_rule(
		'^jeux-de-piste/([^/]+)/([^/]+)/?$',
		'index.php?post_type=game&name=$matches[2]&ville=$matches[1]',
		'top'
	);
	
	// Règle pour les villes, régions, départements et pays : /jeux-de-piste/%slug%
	// WordPress va utiliser parse_request pour déterminer le bon post type
	add_rewrite_rule(
		'^jeux-de-piste/([^/]+)/?$',
		'index.php?urbanquest_slug=$matches[1]',
		'top'
	);
}
add_action('init', 'urbanquest_add_rewrite_rules', 10);

/**
 * Intercepter la requête très tôt avec le hook 'request'
 * Ce hook est appelé avant que WordPress ne décide de rediriger
 */
function urbanquest_request($query_vars) {
	// Vérifier si c'est une URL de jeu en analysant directement REQUEST_URI
	if (isset($_SERVER['REQUEST_URI'])) {
		$request_uri = $_SERVER['REQUEST_URI'];
		$path = parse_url($request_uri, PHP_URL_PATH);
		
		// Vérifier si c'est une URL de jeu (2 segments après jeux-de-piste)
		if ($path && preg_match('#^/jeux-de-piste/([^/]+)/([^/]+)/?$#', $path, $matches)) {
			$ville_slug = $matches[1];
			$game_slug = $matches[2];
			
			// Chercher le jeu avec ce slug exact
			$game = get_page_by_path($game_slug, OBJECT, 'game');
			
			if (!$game) {
				// Si le jeu n'est pas trouvé avec le slug exact, essayer de le chercher autrement
				// Peut-être que le slug dans la base de données est différent
				$all_games = get_posts(array(
					'post_type' => 'game',
					'posts_per_page' => -1,
					'post_status' => 'publish',
					'suppress_filters' => false
				));
				
				foreach ($all_games as $potential_game) {
					// Comparer avec le slug ou avec le titre sanitizé
					if ($potential_game->post_name === $game_slug || 
						sanitize_title($potential_game->post_title) === $game_slug ||
						strpos($potential_game->post_name, $game_slug) !== false ||
						strpos($game_slug, $potential_game->post_name) !== false) {
						$game = $potential_game;
						break;
					}
				}
			}
			
			if ($game) {
				// Forcer les query vars pour ce jeu
				$query_vars = array(
					'post_type' => 'game',
					'name' => $game->post_name, // Utiliser le slug réel du jeu
					'p' => $game->ID,
					'ville' => $ville_slug
				);
				
				// S'assurer que WordPress ne va pas chercher ailleurs
				unset($query_vars['error']);
				unset($query_vars['urbanquest_slug']);
				
				return $query_vars;
			}
		}
	}
	
	return $query_vars;
}
add_filter('request', 'urbanquest_request', 1);

/**
 * Ajouter les query vars personnalisées
 */
function urbanquest_add_query_vars($vars) {
	$vars[] = 'ville';
	$vars[] = 'region';
	$vars[] = 'departement';
	$vars[] = 'pays';
	$vars[] = 'urbanquest_slug';
	return $vars;
}
add_filter('query_vars', 'urbanquest_add_query_vars');

/**
 * Générer les permaliens personnalisés pour les jeux
 * Format : /jeux-de-piste/%ville%/%game_title%
 */
function urbanquest_game_permalink($permalink, $post, $leavename) {
	if ($post->post_type != 'game' || 'publish' != $post->post_status) {
		return $permalink;
	}
	
	// Récupérer la ville associée via ACF
	$city_post = get_field('city', $post->ID);
	$ville_id = urbanquest_extract_acf_relationship_id($city_post);
	
	if (!$ville_id) {
		return $permalink;
	}
	
	$ville_slug = urbanquest_get_post_slug($ville_id);
	$game_slug = $leavename ? '%postname%' : $post->post_name;
	
	if (empty($ville_slug) || empty($game_slug)) {
		return $permalink;
	}
	
	$permalink = home_url('/jeux-de-piste/' . $ville_slug . '/' . $game_slug . '/');
	
	return $permalink;
}
add_filter('post_type_link', 'urbanquest_game_permalink', 10, 3);

/**
 * Modifier le lien canonique Yoast SEO pour les jeux
 * Utilise la nouvelle URL réécrite au lieu de l'ancienne
 */
function urbanquest_yoast_canonical($canonical) {
	if (!is_singular('game')) {
		return $canonical;
	}
	
	global $post;
	if (!$post || $post->post_type !== 'game' || 'publish' != $post->post_status) {
		return $canonical;
	}
	
	// Construire l'URL personnalisée comme dans urbanquest_game_permalink
	$city_post = get_field('city', $post->ID);
	$ville_id = urbanquest_extract_acf_relationship_id($city_post);
	
	if (!$ville_id) {
		return $canonical;
	}
	
	$ville_slug = urbanquest_get_post_slug($ville_id);
	$game_slug = $post->post_name;
	
	if (empty($ville_slug) || empty($game_slug)) {
		return $canonical;
	}
	
	$custom_permalink = home_url('/jeux-de-piste/' . $ville_slug . '/' . $game_slug . '/');
	
	return $custom_permalink;
}
add_filter('wpseo_canonical', 'urbanquest_yoast_canonical', 10, 1);

/**
 * Modifier l'URL Open Graph Yoast SEO pour les jeux
 */
function urbanquest_yoast_opengraph_url($url) {
	if (!is_singular('game')) {
		return $url;
	}
	
	global $post;
	if (!$post || $post->post_type !== 'game' || 'publish' != $post->post_status) {
		return $url;
	}
	
	// Construire l'URL personnalisée comme dans urbanquest_game_permalink
	$city_post = get_field('city', $post->ID);
	$ville_id = urbanquest_extract_acf_relationship_id($city_post);
	
	if (!$ville_id) {
		return $url;
	}
	
	$ville_slug = urbanquest_get_post_slug($ville_id);
	$game_slug = $post->post_name;
	
	if (empty($ville_slug) || empty($game_slug)) {
		return $url;
	}
	
	$custom_permalink = home_url('/jeux-de-piste/' . $ville_slug . '/' . $game_slug . '/');
	
	return $custom_permalink;
}
add_filter('wpseo_opengraph_url', 'urbanquest_yoast_opengraph_url', 10, 1);

/**
 * Générer les permaliens personnalisés pour les villes
 * Format : /jeux-de-piste/%ville%
 */
function urbanquest_ville_permalink($permalink, $post, $leavename) {
	if ($post->post_type != 'ville' || 'publish' != $post->post_status) {
		return $permalink;
	}
	
	$ville_slug = $leavename ? '%postname%' : $post->post_name;
	
	if (empty($ville_slug)) {
		return $permalink;
	}
	
	$permalink = home_url('/jeux-de-piste/' . $ville_slug . '/');
	
	return $permalink;
}
add_filter('post_type_link', 'urbanquest_ville_permalink', 10, 3);

/**
 * Générer les permaliens personnalisés pour les régions
 * Format : /jeux-de-piste/%region%
 */
function urbanquest_region_permalink($permalink, $post, $leavename) {
	if ($post->post_type != 'region' || 'publish' != $post->post_status) {
		return $permalink;
	}
	
	$region_slug = $leavename ? '%postname%' : $post->post_name;
	
	if (empty($region_slug)) {
		return $permalink;
	}
	
	$permalink = home_url('/jeux-de-piste/' . $region_slug . '/');
	
	return $permalink;
}
add_filter('post_type_link', 'urbanquest_region_permalink', 10, 3);

/**
 * Générer les permaliens personnalisés pour les départements
 * Format : /jeux-de-piste/%departement%
 */
function urbanquest_departement_permalink($permalink, $post, $leavename) {
	if ($post->post_type != 'departement' || 'publish' != $post->post_status) {
		return $permalink;
	}
	
	$departement_slug = $leavename ? '%postname%' : $post->post_name;
	
	if (empty($departement_slug)) {
		return $permalink;
	}
	
	$permalink = home_url('/jeux-de-piste/' . $departement_slug . '/');
	
	return $permalink;
}
add_filter('post_type_link', 'urbanquest_departement_permalink', 10, 3);

/**
 * Générer les permaliens personnalisés pour les pays
 * Format : /jeux-de-piste/%pays%
 */
function urbanquest_country_permalink($permalink, $post, $leavename) {
	if ($post->post_type != 'country' || 'publish' != $post->post_status) {
		return $permalink;
	}
	
	$pays_slug = $leavename ? '%postname%' : $post->post_name;
	
	if (empty($pays_slug)) {
		return $permalink;
	}
	
	$permalink = home_url('/jeux-de-piste/' . $pays_slug . '/');
	
	return $permalink;
}
add_filter('post_type_link', 'urbanquest_country_permalink', 10, 3);

/**
 * Parser les requêtes pour les URLs personnalisées
 * Cette fonction détermine quel post type afficher selon l'URL
 */
function urbanquest_parse_request($wp) {
	// Si on a déjà un post_type défini (par exemple pour les jeux), vérifier que le jeu existe
	if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'game') {
		// S'assurer que le jeu existe
		if (isset($wp->query_vars['name'])) {
			$game_slug = sanitize_text_field($wp->query_vars['name']);
			$game = get_page_by_path($game_slug, OBJECT, 'game');
			if ($game) {
				$wp->query_vars['p'] = $game->ID;
				$wp->query_vars['post_type'] = 'game';
				$wp->query_vars['name'] = $game_slug;
				// S'assurer que WordPress ne va pas chercher ailleurs
				unset($wp->query_vars['error']);
				return;
			} else {
				// Si le jeu n'existe pas avec ce slug exact, essayer de le chercher autrement
				// Peut-être que le slug dans la base de données est différent
				$all_games = get_posts(array(
					'post_type' => 'game',
					'posts_per_page' => -1,
					'post_status' => 'publish',
					'suppress_filters' => false
				));
				
				foreach ($all_games as $potential_game) {
					if ($potential_game->post_name === $game_slug || sanitize_title($potential_game->post_title) === $game_slug) {
						$wp->query_vars['p'] = $potential_game->ID;
						$wp->query_vars['post_type'] = 'game';
						$wp->query_vars['name'] = $potential_game->post_name;
						unset($wp->query_vars['error']);
						return;
					}
				}
			}
		}
		// Si le jeu n'existe pas, ne pas rediriger automatiquement
		return;
	}
	
	// Vérifier si on a le slug personnalisé
	if (!isset($wp->query_vars['urbanquest_slug'])) {
		return;
	}
	
	$slug = sanitize_text_field($wp->query_vars['urbanquest_slug']);
	
	// Chercher dans cet ordre : ville, région, département, pays
	$post_types = array('ville', 'region', 'departement', 'country');
	
	foreach ($post_types as $post_type) {
		$post = get_page_by_path($slug, OBJECT, $post_type);
		if ($post) {
			$wp->query_vars['post_type'] = $post_type;
			$wp->query_vars['name'] = $slug;
			$wp->query_vars['p'] = $post->ID;
			unset($wp->query_vars['urbanquest_slug']);
			unset($wp->query_vars['error']);
			return;
		}
	}
	
	// Si aucun post trouvé, laisser WordPress gérer (404)
	unset($wp->query_vars['urbanquest_slug']);
}
add_action('parse_request', 'urbanquest_parse_request', 1);

/**
 * Alternative : utiliser pre_get_posts pour mieux gérer les requêtes
 */
function urbanquest_pre_get_posts($query) {
	// Ne s'applique qu'aux requêtes principales
	if (!$query->is_main_query() || is_admin()) {
		return;
	}
	
	// Si c'est déjà une requête pour un jeu, vérifier que le jeu existe
	if ($query->get('post_type') === 'game' && $query->get('name')) {
		$game = get_page_by_path($query->get('name'), OBJECT, 'game');
		if ($game) {
			$query->set('p', $game->ID);
			return;
		}
		// Si le jeu n'existe pas, ne pas modifier la requête pour éviter les redirections
		return;
	}
	
	// Vérifier si on a le slug personnalisé dans la requête
	$urbanquest_slug = get_query_var('urbanquest_slug');
	if (empty($urbanquest_slug)) {
		return;
	}
	
	// Chercher dans cet ordre : ville, région, département, pays
	$post_types = array('ville', 'region', 'departement', 'country');
	
	foreach ($post_types as $post_type) {
		$post = get_page_by_path($urbanquest_slug, OBJECT, $post_type);
		if ($post) {
			$query->set('post_type', $post_type);
			$query->set('name', $urbanquest_slug);
			$query->set('p', $post->ID);
			$query->set('urbanquest_slug', '');
			return;
		}
	}
}
add_action('pre_get_posts', 'urbanquest_pre_get_posts', 1);

/**
 * Forcer le bon template pour les post types personnalisés
 */
function urbanquest_template_include($template) {
	// Vérifier si on a le slug personnalisé dans la requête
	$urbanquest_slug = get_query_var('urbanquest_slug');
	if (empty($urbanquest_slug)) {
		return $template;
	}
	
	// Chercher dans cet ordre : ville, région, département, pays
	$post_types = array('ville', 'region', 'departement', 'country');
	
	foreach ($post_types as $post_type) {
		$post = get_page_by_path($urbanquest_slug, OBJECT, $post_type);
		if ($post) {
			// Chercher le template spécifique
			$template_name = 'single-' . $post_type . '.php';
			$template_path = locate_template($template_name);
			if ($template_path) {
				return $template_path;
			}
			// Fallback sur single.php
			$template_path = locate_template('single.php');
			if ($template_path) {
				return $template_path;
			}
			break;
		}
	}
	
	return $template;
}
add_filter('template_include', 'urbanquest_template_include', 99);

/**
 * S'assurer que la requête est correctement résolue pour les post types personnalisés
 * Et empêcher les redirections automatiques de WordPress
 */
function urbanquest_wp($wp) {
	// Si c'est une requête pour un jeu, vérifier que le jeu existe
	if (isset($wp->query_vars['post_type']) && $wp->query_vars['post_type'] === 'game') {
		if (isset($wp->query_vars['name'])) {
			$game = get_page_by_path($wp->query_vars['name'], OBJECT, 'game');
			if ($game) {
				$wp->query_vars['p'] = $game->ID;
				return;
			}
		}
		// Si le jeu n'existe pas, ne pas modifier la requête
		return;
	}
	
	// Vérifier si on a le slug personnalisé dans la requête
	if (!isset($wp->query_vars['urbanquest_slug'])) {
		return;
	}
	
	$slug = sanitize_text_field($wp->query_vars['urbanquest_slug']);
	
	// Chercher dans cet ordre : ville, région, département, pays
	$post_types = array('ville', 'region', 'departement', 'country');
	
	foreach ($post_types as $post_type) {
		$post = get_page_by_path($slug, OBJECT, $post_type);
		if ($post) {
			// S'assurer que la requête est correctement configurée
			$wp->query_vars['post_type'] = $post_type;
			$wp->query_vars['name'] = $slug;
			$wp->query_vars['p'] = $post->ID;
			unset($wp->query_vars['urbanquest_slug']);
			break;
		}
	}
}

/**
 * Rediriger les anciennes URLs /jeu/... vers /jeux-de-piste/%ville%/...
 * Redirection 301 pour éviter la duplication de contenu
 */
function urbanquest_redirect_old_game_urls() {
	// Ne s'applique qu'aux requêtes frontend
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
		return;
	}
	
	// Vérifier si c'est une URL /jeu/...
	if (!isset($_SERVER['REQUEST_URI'])) {
		return;
	}
	
	$request_uri = $_SERVER['REQUEST_URI'];
	$path = parse_url($request_uri, PHP_URL_PATH);
	
	// Vérifier si c'est une URL /jeu/slug/
	if ($path && preg_match('#^/jeu/([^/]+)/?$#', $path, $matches)) {
		$game_slug = $matches[1];
		
		// Chercher le jeu avec ce slug
		$game = get_page_by_path($game_slug, OBJECT, 'game');
		
		if ($game) {
			// Récupérer la ville associée via ACF
			$city_post = get_field('city', $game->ID);
			$ville_id = urbanquest_extract_acf_relationship_id($city_post);
			
			if ($ville_id) {
				$ville_slug = urbanquest_get_post_slug($ville_id);
				
				if (!empty($ville_slug)) {
					// Construire la nouvelle URL
					$new_url = home_url('/jeux-de-piste/' . $ville_slug . '/' . $game_slug . '/');
					
					// Redirection 301 permanente
					wp_redirect($new_url, 301);
					exit;
				}
			}
		}
	}
}
add_action('template_redirect', 'urbanquest_redirect_old_game_urls', 1);

/**
 * Empêcher les redirections automatiques de WordPress pour les jeux
 * Cette fonction doit retourner false pour empêcher les redirections canoniques
 */
function urbanquest_redirect_canonical($redirect_url, $requested_url) {
	// Si c'est une URL de jeu, ne pas rediriger
	$requested_path = parse_url($requested_url, PHP_URL_PATH);
	if ($requested_path && preg_match('#^/jeux-de-piste/[^/]+/[^/]+/?$#', $requested_path)) {
		return false;
	}
	return $redirect_url;
}
add_filter('redirect_canonical', 'urbanquest_redirect_canonical', 1, 2);

/**
 * Désactiver complètement redirect_canonical pour les URLs de jeux
 */
function urbanquest_disable_canonical_redirect() {
	if (isset($_SERVER['REQUEST_URI'])) {
		$request_uri = $_SERVER['REQUEST_URI'];
		if (preg_match('#^/jeux-de-piste/[^/]+/[^/]+/?$#', $request_uri)) {
			remove_action('template_redirect', 'redirect_canonical');
		}
	}
}
add_action('init', 'urbanquest_disable_canonical_redirect', 1);

/**
 * Empêcher les redirections avant qu'elles ne se produisent
 * Hook template_redirect pour intercepter les redirections
 */
function urbanquest_prevent_redirect() {
	// Vérifier si c'est une URL de jeu
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	if (preg_match('#^/jeux-de-piste/[^/]+/[^/]+/?$#', $request_uri)) {
		// Extraire les segments de l'URL
		$path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
		$segments = explode('/', $path);
		
		if (count($segments) >= 3 && $segments[0] === 'jeux-de-piste') {
			$ville_slug = $segments[1];
			$game_slug = $segments[2];
			
			// Vérifier que le jeu existe
			$game = get_page_by_path($game_slug, OBJECT, 'game');
			if ($game) {
				// Empêcher toute redirection canonique
				remove_action('template_redirect', 'redirect_canonical');
				
				// Forcer la requête pour ce jeu
				global $wp_query;
				if ($wp_query) {
					$wp_query->set('post_type', 'game');
					$wp_query->set('name', $game_slug);
					$wp_query->set('p', $game->ID);
					$wp_query->is_singular = true;
					$wp_query->is_single = true;
					$wp_query->is_404 = false;
					$wp_query->queried_object = $game;
					$wp_query->queried_object_id = $game->ID;
				}
				
				// S'assurer que la requête globale est correcte
				global $wp;
				if ($wp) {
					$wp->query_vars['post_type'] = 'game';
					$wp->query_vars['name'] = $game_slug;
					$wp->query_vars['p'] = $game->ID;
					unset($wp->query_vars['error']);
				}
				
				return;
			}
		}
	}
}
add_action('template_redirect', 'urbanquest_prevent_redirect', 1);
add_action('wp', 'urbanquest_wp', 1);

/**
 * Flush les règles de réécriture lors de l'activation du thème
 * IMPORTANT: Après avoir ajouté ce code, allez dans Réglages > Permaliens
 * et cliquez sur "Enregistrer les modifications" pour activer les nouvelles règles
 */
function urbanquest_flush_rewrite_rules() {
	urbanquest_add_rewrite_tags();
	urbanquest_add_rewrite_rules();
	flush_rewrite_rules();
}
add_action('after_switch_theme', 'urbanquest_flush_rewrite_rules');

// ============================================================================
// BREADCRUMB NAVIGATION
// ============================================================================
// Affiche un breadcrumb dans le header avec la hiérarchie :
// Pays → Région → Département → Ville → Jeu
// ============================================================================

/**
 * Fonction pour générer le breadcrumb selon la hiérarchie
 * @return array Tableau avec les éléments du breadcrumb ['label' => '...', 'url' => '...']
 */
function urbanquest_get_breadcrumb_items() {
	$items = array();
	
	// Toujours commencer par l'accueil
	$items[] = array(
		'label' => 'Accueil',
		'url' => home_url('/')
	);
	
	// Si on est sur une page de jeu
	if (is_singular('game')) {
		$game_id = get_the_ID();
		
		// Récupérer la ville via le champ ACF 'city'
		$city_post = get_field('city', $game_id);
		$ville_id = urbanquest_extract_acf_relationship_id($city_post);
		
		if ($ville_id) {
			// Récupérer le département via le champ ACF 'ville' de la ville
			$departement_post = get_field('ville', $ville_id);
			$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
			
			if ($departement_id) {
				// Récupérer la région via le champ ACF 'region' du département
				$region_post = get_field('region', $departement_id);
				$region_id = urbanquest_extract_acf_relationship_id($region_post);
				
				if ($region_id) {
					// Récupérer le pays via le champ ACF 'countries' de la région
					$country_post = get_field('countries', $region_id);
					$country_id = urbanquest_extract_acf_relationship_id($country_post);
					
					if ($country_id) {
						$items[] = array(
							'label' => get_the_title($country_id),
							'url' => get_permalink($country_id)
						);
					}
					
					$items[] = array(
						'label' => get_the_title($region_id),
						'url' => get_permalink($region_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($departement_id),
					'url' => get_permalink($departement_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($ville_id),
				'url' => get_permalink($ville_id)
			);
		}
		
		// Ajouter le jeu actuel (sans lien car c'est la page courante)
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de ville
	elseif (is_singular('ville')) {
		$ville_id = get_the_ID();
		
		// Récupérer le département
		$departement_post = get_field('ville', $ville_id);
		$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
		
		if ($departement_id) {
			// Récupérer la région
			$region_post = get_field('region', $departement_id);
			$region_id = urbanquest_extract_acf_relationship_id($region_post);
			
			if ($region_id) {
				// Récupérer le pays
				$country_post = get_field('countries', $region_id);
				$country_id = urbanquest_extract_acf_relationship_id($country_post);
				
				if ($country_id) {
					$items[] = array(
						'label' => get_the_title($country_id),
						'url' => get_permalink($country_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($region_id),
					'url' => get_permalink($region_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($departement_id),
				'url' => get_permalink($departement_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de département
	elseif (is_singular('departement')) {
		$departement_id = get_the_ID();
		
		// Récupérer la région
		$region_post = get_field('region', $departement_id);
		$region_id = urbanquest_extract_acf_relationship_id($region_post);
		
		if ($region_id) {
			// Récupérer le pays
			$country_post = get_field('countries', $region_id);
			$country_id = urbanquest_extract_acf_relationship_id($country_post);
			
			if ($country_id) {
				$items[] = array(
					'label' => get_the_title($country_id),
					'url' => get_permalink($country_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($region_id),
				'url' => get_permalink($region_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de région
	elseif (is_singular('region')) {
		$region_id = get_the_ID();
		
		// Récupérer le pays
		$country_post = get_field('countries', $region_id);
		$country_id = urbanquest_extract_acf_relationship_id($country_post);
		
		if ($country_id) {
			$items[] = array(
				'label' => get_the_title($country_id),
				'url' => get_permalink($country_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de pays
	elseif (is_singular('country')) {
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	
	return $items;
}

/**
 * Affiche le breadcrumb dans le header
 * Cette fonction peut être appelée directement depuis les templates
 */
function urbanquest_display_breadcrumb() {
	// Variable statique pour éviter l'affichage multiple
	static $breadcrumb_displayed = false;
	if ($breadcrumb_displayed) {
		return;
	}
	
	// Récupérer les items du breadcrumb
	$items = array();
	
	// Toujours commencer par Accueil
	$items[] = array('label' => 'Accueil', 'url' => home_url('/'));
	
	// Si on est sur un des post types concernés, construire la hiérarchie
	if (is_singular('game')) {
		$game_id = get_the_ID();
		
		// Récupérer la ville via le champ ACF 'city'
		$city_post = get_field('city', $game_id);
		$ville_id = urbanquest_extract_acf_relationship_id($city_post);
		
		if ($ville_id) {
			// Récupérer le département via le champ ACF 'ville' de la ville
			$departement_post = get_field('ville', $ville_id);
			$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
			
			if ($departement_id) {
				// Récupérer la région via le champ ACF 'region' du département
				$region_post = get_field('region', $departement_id);
				$region_id = urbanquest_extract_acf_relationship_id($region_post);
				
				if ($region_id) {
					// Récupérer le pays via le champ ACF 'countries' de la région
					$country_post = get_field('countries', $region_id);
					$country_id = urbanquest_extract_acf_relationship_id($country_post);
					
					if ($country_id) {
						$items[] = array(
							'label' => get_the_title($country_id),
							'url' => get_permalink($country_id)
						);
					}
					
					$items[] = array(
						'label' => get_the_title($region_id),
						'url' => get_permalink($region_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($departement_id),
					'url' => get_permalink($departement_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($ville_id),
				'url' => get_permalink($ville_id)
			);
		}
		
		// Ajouter le jeu actuel (sans lien car c'est la page courante)
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de ville
	elseif (is_singular('ville')) {
		$ville_id = get_the_ID();
		
		// Récupérer le département
		$departement_post = get_field('ville', $ville_id);
		$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
		
		if ($departement_id) {
			// Récupérer la région
			$region_post = get_field('region', $departement_id);
			$region_id = urbanquest_extract_acf_relationship_id($region_post);
			
			if ($region_id) {
				// Récupérer le pays
				$country_post = get_field('countries', $region_id);
				$country_id = urbanquest_extract_acf_relationship_id($country_post);
				
				if ($country_id) {
					$items[] = array(
						'label' => get_the_title($country_id),
						'url' => get_permalink($country_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($region_id),
					'url' => get_permalink($region_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($departement_id),
				'url' => get_permalink($departement_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de département
	elseif (is_singular('departement')) {
		$departement_id = get_the_ID();
		
		// Récupérer la région
		$region_post = get_field('region', $departement_id);
		$region_id = urbanquest_extract_acf_relationship_id($region_post);
		
		if ($region_id) {
			// Récupérer le pays
			$country_post = get_field('countries', $region_id);
			$country_id = urbanquest_extract_acf_relationship_id($country_post);
			
			if ($country_id) {
				$items[] = array(
					'label' => get_the_title($country_id),
					'url' => get_permalink($country_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($region_id),
				'url' => get_permalink($region_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de région
	elseif (is_singular('region')) {
		$region_id = get_the_ID();
		
		// Récupérer le pays
		$country_post = get_field('countries', $region_id);
		$country_id = urbanquest_extract_acf_relationship_id($country_post);
		
		if ($country_id) {
			$items[] = array(
				'label' => get_the_title($country_id),
				'url' => get_permalink($country_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de pays
	elseif (is_singular('country')) {
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Fallback : au moins afficher Accueil > Page actuelle
	else {
		$current_title = get_the_title();
		if (!empty($current_title)) {
			$items[] = array(
				'label' => $current_title,
				'url' => ''
			);
		}
	}
	
	// Si on n'a qu'un seul item (Accueil), ne pas afficher
	if (count($items) < 2) {
		return;
	}
	
	// Marquer comme affiché
	$breadcrumb_displayed = true;
	
	// Générer le HTML du breadcrumb avec un style très visible
	?>
	<nav class="urbanquest-breadcrumb">
		<div class="container">
			<div>
				<?php foreach ($items as $index => $item) : ?>
					<?php if ($index > 0) : ?>
						<span class="breadcrumb-separator">›</span>
					<?php endif; ?>
					<?php if (!empty($item['url'])) : ?>
						<a href="<?php echo esc_url($item['url']); ?>">
							<?php echo esc_html($item['label']); ?>
						</a>
					<?php else : ?>
						<span class="breadcrumb-current">
							<?php echo esc_html($item['label']); ?>
						</span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</nav>
	<?php
}

/**
 * Affiche le breadcrumb simple (juste des liens, sans barre de fond)
 * À placer sous le titre de la page
 */
function urbanquest_display_breadcrumb_simple() {
	// Variable statique pour éviter l'affichage multiple
	static $breadcrumb_displayed = false;
	if ($breadcrumb_displayed) {
		return;
	}
	
	// Récupérer les items du breadcrumb
	$items = array();
	
	// Toujours commencer par Accueil
	$items[] = array('label' => 'Accueil', 'url' => home_url('/'));
	
	// Si on est sur un des post types concernés, construire la hiérarchie
	if (is_singular('game')) {
		$game_id = get_the_ID();
		
		// Récupérer la ville via le champ ACF 'city'
		$city_post = get_field('city', $game_id);
		$ville_id = urbanquest_extract_acf_relationship_id($city_post);
		
		if ($ville_id) {
			// Récupérer le département via le champ ACF 'ville' de la ville
			$departement_post = get_field('ville', $ville_id);
			$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
			
			if ($departement_id) {
				// Récupérer la région via le champ ACF 'region' du département
				$region_post = get_field('region', $departement_id);
				$region_id = urbanquest_extract_acf_relationship_id($region_post);
				
				if ($region_id) {
					// Récupérer le pays via le champ ACF 'countries' de la région
					$country_post = get_field('countries', $region_id);
					$country_id = urbanquest_extract_acf_relationship_id($country_post);
					
					if ($country_id) {
						$items[] = array(
							'label' => get_the_title($country_id),
							'url' => get_permalink($country_id)
						);
					}
					
					$items[] = array(
						'label' => get_the_title($region_id),
						'url' => get_permalink($region_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($departement_id),
					'url' => get_permalink($departement_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($ville_id),
				'url' => get_permalink($ville_id)
			);
		}
		
		// Ajouter le jeu actuel (sans lien car c'est la page courante)
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de ville
	elseif (is_singular('ville')) {
		$ville_id = get_the_ID();
		
		// Récupérer le département
		$departement_post = get_field('ville', $ville_id);
		$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
		
		if ($departement_id) {
			// Récupérer la région
			$region_post = get_field('region', $departement_id);
			$region_id = urbanquest_extract_acf_relationship_id($region_post);
			
			if ($region_id) {
				// Récupérer le pays
				$country_post = get_field('countries', $region_id);
				$country_id = urbanquest_extract_acf_relationship_id($country_post);
				
				if ($country_id) {
					$items[] = array(
						'label' => get_the_title($country_id),
						'url' => get_permalink($country_id)
					);
				}
				
				$items[] = array(
					'label' => get_the_title($region_id),
					'url' => get_permalink($region_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($departement_id),
				'url' => get_permalink($departement_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de département
	elseif (is_singular('departement')) {
		$departement_id = get_the_ID();
		
		// Récupérer la région
		$region_post = get_field('region', $departement_id);
		$region_id = urbanquest_extract_acf_relationship_id($region_post);
		
		if ($region_id) {
			// Récupérer le pays
			$country_post = get_field('countries', $region_id);
			$country_id = urbanquest_extract_acf_relationship_id($country_post);
			
			if ($country_id) {
				$items[] = array(
					'label' => get_the_title($country_id),
					'url' => get_permalink($country_id)
				);
			}
			
			$items[] = array(
				'label' => get_the_title($region_id),
				'url' => get_permalink($region_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de région
	elseif (is_singular('region')) {
		$region_id = get_the_ID();
		
		// Récupérer le pays
		$country_post = get_field('countries', $region_id);
		$country_id = urbanquest_extract_acf_relationship_id($country_post);
		
		if ($country_id) {
			$items[] = array(
				'label' => get_the_title($country_id),
				'url' => get_permalink($country_id)
			);
		}
		
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Si on est sur une page de pays
	elseif (is_singular('country')) {
		$items[] = array(
			'label' => get_the_title(),
			'url' => ''
		);
	}
	// Fallback : au moins afficher Accueil > Page actuelle
	else {
		$current_title = get_the_title();
		if (!empty($current_title)) {
			$items[] = array(
				'label' => $current_title,
				'url' => ''
			);
		}
	}
	
	// Si on n'a qu'un seul item (Accueil), ne pas afficher
	if (count($items) < 2) {
		return;
	}
	
	// Marquer comme affiché
	$breadcrumb_displayed = true;
	
	// Générer le HTML du breadcrumb simple (juste des liens)
	?>
	<div class="urbanquest-breadcrumb-simple">
		<?php foreach ($items as $index => $item) : ?>
			<?php if ($index > 0) : ?>
				<span class="breadcrumb-separator">›</span>
			<?php endif; ?>
			<?php if (!empty($item['url'])) : ?>
				<a href="<?php echo esc_url($item['url']); ?>">
					<?php echo esc_html($item['label']); ?>
				</a>
			<?php else : ?>
				<span class="breadcrumb-current">
					<?php echo esc_html($item['label']); ?>
				</span>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}

// ============================================================================
// PERSONNALISATION DU HEADER HESTIA
// ============================================================================
// Cette section permet de personnaliser le header du thème Hestia
// Vous pouvez décommenter et modifier les fonctions selon vos besoins
// ============================================================================

/**
 * Modifier le logo du header
 * Remplace le logo par défaut par un logo personnalisé
 */
/*
function urbanquest_custom_header_logo() {
	// Retirer le logo par défaut de Hestia
	remove_action('hestia_before_header_content', 'hestia_the_header_content', 10);
	
	// Ajouter votre logo personnalisé
	add_action('hestia_before_header_content', function() {
		?>
		<div class="custom-logo-wrapper">
			<a href="<?php echo esc_url(home_url('/')); ?>">
				<img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/images/logo.png'); ?>" alt="<?php bloginfo('name'); ?>" />
			</a>
		</div>
		<?php
	}, 10);
}
add_action('init', 'urbanquest_custom_header_logo');
*/

/**
 * Modifier le menu de navigation du header
 * Personnaliser les éléments du menu principal
 */
/*
function urbanquest_custom_header_menu($items, $args) {
	// Modifier les items du menu ici
	// Par exemple, ajouter un élément personnalisé
	if ($args->theme_location == 'primary') {
		$items .= '<li class="custom-menu-item"><a href="/contact">Contact</a></li>';
	}
	return $items;
}
add_filter('wp_nav_menu_items', 'urbanquest_custom_header_menu', 10, 2);
*/

/**
 * Ajouter du contenu personnalisé dans le header
 * Par exemple : bouton CTA, barre de recherche, etc.
 */
/*
function urbanquest_add_header_content() {
	?>
	<div class="urbanquest-header-custom-content" style="display: flex; align-items: center; gap: 15px;">
		<!-- Exemple : Bouton CTA -->
		<a href="/reservation" class="btn btn-primary" style="padding: 10px 20px; background: #00bbff; color: white; text-decoration: none; border-radius: 5px;">
			Réserver maintenant
		</a>
	</div>
	<?php
}
add_action('hestia_before_header_content', 'urbanquest_add_header_content', 20);
*/

/**
 * Modifier les classes CSS du header
 * Permet d'ajouter des classes personnalisées pour le styling
 */
/*
function urbanquest_header_classes($classes) {
	$classes[] = 'urbanquest-custom-header';
	return $classes;
}
add_filter('hestia_header_classes', 'urbanquest_header_classes');
*/

/**
 * Modifier la hauteur du header
 * Via CSS personnalisé - Réduit la hauteur en ciblant le padding ET la hauteur
 */
function urbanquest_custom_header_styles() {
	?>
	<style>
		/* Personnalisation du header Hestia - Réduction de la hauteur */
		
		/* Réduire la hauteur minimale du header */
		.hestia-top-bar,
		.navbar.navbar-default {
			min-height: 60px !important;
			height: auto !important;
		}
		
		/* Réduire le padding du conteneur principal du header */
		.navbar.navbar-default .navbar-header,
		.navbar.navbar-default .navbar-collapse {
			padding-top: 8px !important;
			padding-bottom: 8px !important;
		}
		
		/* Réduire le padding du logo/brand */
		.navbar .navbar-brand {
			padding-top: 10px !important;
			padding-bottom: 10px !important;
			height: auto !important;
			line-height: 1.2 !important;
		}
		
		/* Réduire la taille du logo */
		.navbar .navbar-brand img {
			max-height: 40px !important;
			height: auto !important;
		}
		
		/* Réduire le padding des items du menu */
		.navbar .navbar-nav > li > a {
			font-weight: 500;
			padding-top: 10px !important;
			padding-bottom: 10px !important;
			padding-left: 15px !important;
			padding-right: 15px !important;
			line-height: 1.4 !important;
		}
		
		/* Réduire le padding du conteneur du menu */
		.navbar .navbar-nav {
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Réduire le padding de la navbar elle-même */
		.navbar.navbar-default {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
		}
		
		/* Réduire la top bar si elle existe */
		.hestia-top-bar {
			padding-top: 5px !important;
			padding-bottom: 5px !important;
			min-height: 30px !important;
		}
		
		/* Réduire le padding du conteneur interne */
		.navbar .container,
		.navbar .container-fluid {
			padding-top: 5px !important;
			padding-bottom: 5px !important;
		}
		
		/* Ajouter un fond personnalisé au header */
		.navbar.navbar-default {
			background-color: #ffffff;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		
		/* Personnaliser les couleurs du menu */
		.navbar .navbar-nav > li > a {
			color: #333;
		}
		
		.navbar .navbar-nav > li > a:hover {
			color: #00bbff;
		}
		
		/* Sticky header (header fixe lors du scroll) */
		.navbar.navbar-default.navbar-fixed-top {
			position: fixed;
			top: 0;
			width: 100%;
			z-index: 1000;
		}
		
		/* Ajouter un padding au body pour compenser le header fixe */
		body.admin-bar .navbar-fixed-top {
			top: 32px;
		}
		
		/* Version mobile - encore plus compact */
		@media (max-width: 768px) {
			.navbar.navbar-default {
				min-height: 50px !important;
			}
			
			.navbar .navbar-brand {
				padding-top: 8px !important;
				padding-bottom: 8px !important;
			}
			
			.navbar .navbar-brand img {
				max-height: 35px !important;
			}
			
			.navbar .navbar-toggle {
				margin-top: 8px !important;
				margin-bottom: 8px !important;
				padding: 6px 10px !important;
			}
			
			.navbar .navbar-nav > li > a {
				padding-top: 8px !important;
				padding-bottom: 8px !important;
			}
		}
		
		/* Réduire la hauteur du titre dans le conteneur (ex: "Place de la bourse bordeaux bis") */
		.blog-post-wrapper .container,
		.single-post .container,
		.hestia-single-post .container {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
		}
		
		/* Réduire le padding autour du titre de la page */
		.hestia-title,
		.entry-title,
		.single-post .entry-title,
		.blog-post-wrapper .entry-title {
			margin-top: 0 !important;
			margin-bottom: 0 !important;
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			line-height: 1.1 !important;
		}
		
		/* Réduire le padding du conteneur qui affiche le titre */
		.hestia-single-post-header,
		.single-post-header,
		.entry-header {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Cibler spécifiquement les conteneurs avec titre dans le contenu */
		.blog-post-wrapper > .container > .row,
		.single-post-content .container {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Réduire l'espacement général du conteneur principal */
		.blog-post-wrapper {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Réduire encore plus le padding du conteneur principal */
		.blog-post-wrapper .container {
			padding-top: 0 !important;
			padding-bottom: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Réduire les marges des sections */
		.blog-post-wrapper .row {
			margin-top: 0 !important;
			margin-bottom: 0 !important;
		}
		
		/* Réduire tous les espacements supplémentaires */
		.blog-post-wrapper .col-xs-12,
		.blog-post-wrapper .col-md-12,
		.blog-post-wrapper [class*="col-"] {
			padding-top: 0 !important;
			margin-top: 0 !important;
		}
		
		/* Cibler directement le conteneur avec le titre */
		.container:has(.entry-title),
		.container:has(.hestia-title) {
			padding-top: 140px !important;
			padding-bottom: 40px !important;
		}
		
		/* Réduire le padding-top pour mobile uniquement */
		@media (max-width: 768px) {
			.container:has(.entry-title),
			.container:has(.hestia-title) {
				padding-top: 60px !important;
				padding-bottom: 30px !important;
			}
		}
		
		.page-header.header-small {
			min-height: 140px !important;
		}
	</style>
	<?php
}
add_action('wp_head', 'urbanquest_custom_header_styles', 20);

/**
 * Ajouter le breadcrumb dans le header (déjà implémenté)
 * Cette fonction est déjà définie plus haut dans le fichier
 * Vous pouvez l'appeler avec : do_action('hestia_before_header_content', 'urbanquest_display_breadcrumb');
 */

/**
 * Modifier le texte du bouton "Call to Action" du header (si présent)
 */
/*
function urbanquest_custom_header_cta($text) {
	return 'Réserver maintenant';
}
add_filter('hestia_header_cta_text', 'urbanquest_custom_header_cta');
*/

/**
 * Désactiver certains éléments du header
 * Par exemple : désactiver la barre supérieure (top bar)
 */
/*
function urbanquest_remove_header_elements() {
	// Retirer la top bar
	remove_action('hestia_before_header', 'hestia_the_header_top_bar', 10);
	
	// Retirer le bouton CTA du header
	remove_action('hestia_before_header_content', 'hestia_the_header_cta', 20);
}
add_action('wp', 'urbanquest_remove_header_elements');
*/

/**
 * Ajouter un élément personnalisé après le menu de navigation
 */
/*
function urbanquest_add_after_nav_menu() {
	?>
	<div class="urbanquest-header-extra">
		<!-- Votre contenu personnalisé ici -->
	</div>
	<?php
}
add_action('hestia_after_navigation', 'urbanquest_add_after_nav_menu');
*/

// ============================================================================
// SCHÉMA ORGANIZATION (SCHEMA.ORG)
// ============================================================================
// Ajoute le schéma Organization sur la page d'accueil et les pages contact/à propos
// Ce schéma est requis pour le SEO et permet à Google d'identifier l'organisation
// ============================================================================

/**
 * Ajouter le schéma JSON-LD Organization sur la page d'accueil et les pages contact/à propos
 */
function urbanquest_add_organization_schema() {
	// Vérifier si on est sur la page d'accueil
	$is_front_page = is_front_page();
	
	// Vérifier si on est sur une page contact ou à propos
	$is_contact_page = false;
	$is_about_page = false;
	
	if (is_page()) {
		$page_slug = get_post_field('post_name', get_the_ID());
		$is_contact_page = in_array(strtolower($page_slug), array('contact', 'contactez-nous', 'nous-contacter'));
		$is_about_page = in_array(strtolower($page_slug), array('a-propos', 'about', 'qui-sommes-nous', 'a-propos-de-nous'));
	}
	
	// Ajouter le schéma uniquement sur la page d'accueil ou les pages contact/à propos
	if (!$is_front_page && !$is_contact_page && !$is_about_page) {
		return;
	}
	
	// Récupérer les informations du site
	$site_name = get_bloginfo('name');
	$site_url = get_site_url();
	$site_description = get_bloginfo('description');
	
	// Valeurs par défaut si non définies
	if (empty($site_name)) {
		$site_name = 'Urban Quest';
	}
	
	// Récupérer le logo du site
	$logo_url = '';
	
	// Essayer de récupérer le logo personnalisé WordPress
	$custom_logo_id = get_theme_mod('custom_logo');
	if ($custom_logo_id) {
		$logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
	}
	
	// Si pas de logo personnalisé, essayer le logo du thème
	if (empty($logo_url)) {
		// Logo du thème enfant
		$child_logo = get_stylesheet_directory_uri() . '/images/logo.png';
		if (file_exists(get_stylesheet_directory() . '/images/logo.png')) {
			$logo_url = $child_logo;
		}
	}
	
	// Si toujours pas de logo, utiliser l'image par défaut du site
	if (empty($logo_url)) {
		$logo_url = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	}
	
	// Construire le schéma Organization
	$schema = array(
		'@context' => 'https://schema.org',
		'@type' => 'Organization',
		'name' => $site_name,
		'url' => $site_url
	);
	
	// Ajouter le logo si disponible
	if (!empty($logo_url)) {
		$schema['logo'] = array(
			'@type' => 'ImageObject',
			'url' => $logo_url
		);
	}
	
	// Ajouter les réseaux sociaux si disponibles (à personnaliser selon vos besoins)
	$same_as = array();
	// Exemple d'ajout de réseaux sociaux :
	// $same_as[] = 'https://www.facebook.com/urbanquest';
	// $same_as[] = 'https://www.instagram.com/urbanquest';
	// $same_as[] = 'https://twitter.com/urbanquest';
	
	if (!empty($same_as)) {
		$schema['sameAs'] = $same_as;
	}
	
	// Ajouter la description si disponible
	if (!empty($site_description)) {
		$schema['description'] = $site_description;
	} else {
		$schema['description'] = 'Urban Quest propose des jeux de piste connectés pour explorer les villes autrement. Découvrez nos aventures ludiques et interactives en famille ou entre amis.';
	}
	
	// Ajouter les informations de contact si disponibles (optionnel mais recommandé)
	// Vous pouvez récupérer ces informations depuis des options WordPress ou des champs ACF
	$contact_email = get_option('admin_email'); // Email par défaut de WordPress
	$contact_phone = ''; // À remplir si vous avez un numéro de téléphone
	
	if (!empty($contact_email)) {
		$schema['email'] = $contact_email;
	}
	
	if (!empty($contact_phone)) {
		$schema['telephone'] = $contact_phone;
	}
	
	// Ajouter l'adresse si disponible (optionnel mais recommandé pour le SEO local)
	// Exemple de structure d'adresse :
	/*
	$schema['address'] = array(
		'@type' => 'PostalAddress',
		'streetAddress' => '123 Rue de la Ville',
		'addressLocality' => 'Bordeaux',
		'postalCode' => '33000',
		'addressCountry' => 'FR'
	);
	*/
	
	// Ajouter le schéma dans le head
	echo '<script type="application/ld+json">' . "\n";
	echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	echo "\n" . '</script>' . "\n";
}
add_action('wp_head', 'urbanquest_add_organization_schema', 5);

// ============================================================================
// FONCTIONS HELPER POUR OPTIMISATION DES TEMPLATES
// ============================================================================

/**
 * Récupère un champ ACF avec valeur par défaut
 * @param string $field_name Nom du champ ACF
 * @param mixed $default Valeur par défaut
 * @param int|null $post_id ID du post (optionnel)
 * @return mixed Valeur du champ ou valeur par défaut
 */
function urbanquest_get_field_with_default($field_name, $default = '', $post_id = null) {
	$value = get_field($field_name, $post_id);
	return !empty($value) ? $value : $default;
}

/**
 * Extrait l'URL d'une image depuis un champ ACF
 * @param mixed $image_field Champ ACF image (peut être array, string ou ID)
 * @param string $size Taille d'image WordPress (default: 'full')
 * @param string $default_url URL par défaut si aucune image trouvée
 * @return string URL de l'image
 */
function urbanquest_get_image_url($image_field, $size = 'full', $default_url = '') {
	if (!$image_field) {
		return $default_url;
	}
	
	if (is_array($image_field) && isset($image_field['url'])) {
		return $image_field['url'];
	}
	
	if (is_string($image_field)) {
		return $image_field;
	}
	
	if (is_numeric($image_field)) {
		$url = wp_get_attachment_image_url($image_field, $size);
		return $url ? $url : $default_url;
	}
	
	return $default_url;
}

/**
 * Récupère les données de ville/département/région depuis un jeu
 * @param int $game_id ID du jeu
 * @return array ['ville_id', 'ville_name', 'departement_id', 'region_id', 'region_name']
 */
function urbanquest_get_game_location_data($game_id) {
	$city_post = get_field('city', $game_id);
	$ville_id = urbanquest_extract_acf_relationship_id($city_post);
	$ville_name = $ville_id ? get_the_title($ville_id) : '';
	
	$departement_id = null;
	$region_id = null;
	$region_name = '';
	
	if ($ville_id) {
		$departement_post = get_field('ville', $ville_id);
		$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
		
		if ($departement_id) {
			$region_post = get_field('region', $departement_id);
			$region_id = urbanquest_extract_acf_relationship_id($region_post);
			
			if ($region_id) {
				$region_obj = get_post($region_id);
				$region_name = $region_obj ? $region_obj->post_title : '';
			}
		}
	}
	
	return compact('ville_id', 'ville_name', 'departement_id', 'region_id', 'region_name');
}

/**
 * Récupère les données d'affichage d'un jeu
 * Utilise uniquement l'image principale et le titre du jeu
 * @param WP_Post|int $game Le post du jeu ou son ID
 * @return array ['image', 'title', 'excerpt', 'payment_url', 'city_name']
 */
function urbanquest_get_game_display_data($game) {
	$game_id = is_object($game) ? $game->ID : $game;
	$default_image = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	
	// Image : utilise uniquement image_principale, sinon thumbnail, sinon image par défaut
	$image = urbanquest_get_image_url(get_field('image_principale', $game_id), 'medium', '');
	if (empty($image)) {
		$image = get_the_post_thumbnail_url($game_id, 'medium') ?: $default_image;
	}
	
	// Titre : utilise uniquement le titre du post
	$title = get_the_title($game_id);
	
	// Description : utilise l'extrait du post
	$excerpt = get_the_excerpt($game_id);
	if (empty($excerpt)) {
		$excerpt = 'Découvrez ce jeu de piste unique dans cette ville.';
	}
	
	// Payment URL
	$payment_url = urbanquest_get_field_with_default('payment_url', get_permalink($game_id), $game_id);
	
	// Ville
	$city_field = get_field('city', $game_id);
	$city_id = urbanquest_extract_acf_relationship_id($city_field);
	$city_name = $city_id ? get_the_title($city_id) : '';
	
	return compact('image', 'title', 'excerpt', 'payment_url', 'city_name');
}

/**
 * Récupère les jeux pertinents selon la hiérarchie géographique (OPTIMISÉ)
 * Utilise meta_query pour des requêtes SQL directes au lieu de boucles PHP
 * @param int $current_game_id ID du jeu actuel
 * @param int $ville_id ID de la ville
 * @param int $departement_id ID du département
 * @param int $region_id ID de la région
 * @param int $limit Nombre maximum de jeux (défaut: 6)
 * @return array Tableau de jeux WP_Post
 */
function urbanquest_get_related_games($current_game_id, $ville_id, $departement_id, $region_id, $limit = 6) {
	$related_games = [];
	$excluded_ids = [$current_game_id];
	
	// 1. Jeux de la même ville (OPTIMISÉ avec meta_query)
	if ($ville_id) {
		$games = get_posts([
			'post_type' => 'game',
			'posts_per_page' => $limit,
			'post__not_in' => $excluded_ids,
			'meta_query' => [
				[
					'key' => 'city',
					'value' => '"' . $ville_id . '"',
					'compare' => 'LIKE'
				]
			],
			'orderby' => 'rand'
		]);
		
		foreach ($games as $game) {
			$related_games[] = $game;
			$excluded_ids[] = $game->ID;
		}
		
		if (count($related_games) >= $limit) {
			return $related_games;
		}
	}
	
	// 2. Jeux du même département (OPTIMISÉ)
	if (count($related_games) < $limit && $departement_id) {
		// Récupérer les villes du département avec meta_query
		$villes_departement = get_posts([
			'post_type' => 'ville',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => [
				[
					'key' => 'ville',
					'value' => '"' . $departement_id . '"',
					'compare' => 'LIKE'
				]
			]
		]);
		
		if (!empty($villes_departement)) {
			// Construire une meta_query pour trouver les jeux de ces villes
			$meta_query = ['relation' => 'OR'];
			foreach ($villes_departement as $ville_id_dept) {
				$meta_query[] = [
					'key' => 'city',
					'value' => '"' . $ville_id_dept . '"',
					'compare' => 'LIKE'
				];
			}
			
			$games = get_posts([
				'post_type' => 'game',
				'posts_per_page' => $limit - count($related_games),
				'post__not_in' => $excluded_ids,
				'meta_query' => $meta_query,
				'orderby' => 'rand'
			]);
			
			foreach ($games as $game) {
				$related_games[] = $game;
				$excluded_ids[] = $game->ID;
			}
			
			if (count($related_games) >= $limit) {
				return $related_games;
			}
		}
	}
	
	// 3. Jeux de la même région (OPTIMISÉ)
	if (count($related_games) < $limit && $region_id) {
		// Récupérer les départements de la région
		$departements_region = get_posts([
			'post_type' => 'departement',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => [
				[
					'key' => 'region',
					'value' => '"' . $region_id . '"',
					'compare' => 'LIKE'
				]
			]
		]);
		
		if (!empty($departements_region)) {
			// Récupérer les villes de ces départements
			$meta_query_villes = ['relation' => 'OR'];
			foreach ($departements_region as $dep_id) {
				$meta_query_villes[] = [
					'key' => 'ville',
					'value' => '"' . $dep_id . '"',
					'compare' => 'LIKE'
				];
			}
			
			$villes_region = get_posts([
				'post_type' => 'ville',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_query' => $meta_query_villes
			]);
			
			if (!empty($villes_region)) {
				// Récupérer les jeux de ces villes
				$meta_query_games = ['relation' => 'OR'];
				foreach ($villes_region as $ville_id_reg) {
					$meta_query_games[] = [
						'key' => 'city',
						'value' => '"' . $ville_id_reg . '"',
						'compare' => 'LIKE'
					];
				}
				
				$games = get_posts([
					'post_type' => 'game',
					'posts_per_page' => $limit - count($related_games),
					'post__not_in' => $excluded_ids,
					'meta_query' => $meta_query_games,
					'orderby' => 'rand'
				]);
				
				foreach ($games as $game) {
					$related_games[] = $game;
				}
			}
		}
	}
	
	return $related_games;
}

/**
 * Récupère les jeux d'une ville (OPTIMISÉ avec meta_query)
 * @param int $ville_id ID de la ville
 * @param array $args Arguments WP_Query additionnels
 * @return array Tableau de jeux WP_Post
 */
function urbanquest_get_games_by_city($ville_id, $args = []) {
	if (!$ville_id) {
		return [];
	}
	
	$defaults = [
		'post_type' => 'game',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => [
			[
				'key' => 'city',
				'value' => '"' . $ville_id . '"',
				'compare' => 'LIKE'
			]
		]
	];
	
	$query_args = wp_parse_args($args, $defaults);
	return get_posts($query_args);
}

/**
 * Récupère les jeux d'un département (OPTIMISÉ)
 * @param int $departement_id ID du département
 * @param array $args Arguments WP_Query additionnels
 * @return array Tableau de jeux WP_Post
 */
function urbanquest_get_games_by_departement($departement_id, $args = []) {
	if (!$departement_id) {
		return [];
	}
	
	// Récupérer les villes du département
	$villes = get_posts([
		'post_type' => 'ville',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => [
			[
				'key' => 'ville',
				'value' => '"' . $departement_id . '"',
				'compare' => 'LIKE'
			]
		]
	]);
	
	if (empty($villes)) {
		return [];
	}
	
	// Construire meta_query pour les jeux
	$meta_query = ['relation' => 'OR'];
	foreach ($villes as $ville_id) {
		$meta_query[] = [
			'key' => 'city',
			'value' => '"' . $ville_id . '"',
			'compare' => 'LIKE'
		];
	}
	
	$defaults = [
		'post_type' => 'game',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => $meta_query
	];
	
	$query_args = wp_parse_args($args, $defaults);
	return get_posts($query_args);
}

/**
 * Récupère les jeux d'une région (OPTIMISÉ)
 * @param int $region_id ID de la région
 * @param array $args Arguments WP_Query additionnels
 * @return array Tableau de jeux WP_Post
 */
function urbanquest_get_games_by_region($region_id, $args = []) {
	if (!$region_id) {
		return [];
	}
	
	// Récupérer les départements de la région
	// Utiliser d'abord la relation bidirectionnelle si disponible
	$departements_ids = get_field('departements', $region_id);
	if ($departements_ids) {
		if (!is_array($departements_ids)) {
			$departements_ids = [$departements_ids];
		}
		$departements = array_map('intval', $departements_ids);
	} else {
		// Fallback avec meta_query
		$departements = get_posts([
			'post_type' => 'departement',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => [
				[
					'key' => 'region',
					'value' => '"' . $region_id . '"',
					'compare' => 'LIKE'
				]
			]
		]);
	}
	
	if (empty($departements)) {
		return [];
	}
	
	// Récupérer les villes de ces départements
	// Utiliser d'abord la relation bidirectionnelle si disponible
	$villes = [];
	foreach ($departements as $dep_id) {
		$villes_ids = get_field('villes', $dep_id);
		if ($villes_ids) {
			if (!is_array($villes_ids)) {
				$villes_ids = [$villes_ids];
			}
			$villes = array_merge($villes, array_map('intval', $villes_ids));
		}
	}
	
	// Si aucune ville trouvée via relation bidirectionnelle, utiliser meta_query
	if (empty($villes)) {
		$meta_query_villes = ['relation' => 'OR'];
		foreach ($departements as $dep_id) {
			$meta_query_villes[] = [
				'key' => 'ville',
				'value' => '"' . $dep_id . '"',
				'compare' => 'LIKE'
			];
		}
		
		$villes = get_posts([
			'post_type' => 'ville',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => $meta_query_villes
		]);
	}
	
	// Supprimer les doublons
	$villes = array_unique($villes);
	
	if (empty($villes)) {
		return [];
	}
	
	// Construire meta_query pour les jeux
	$meta_query_games = ['relation' => 'OR'];
	foreach ($villes as $ville_id) {
		$meta_query_games[] = [
			'key' => 'city',
			'value' => '"' . $ville_id . '"',
			'compare' => 'LIKE'
		];
	}
	
	$defaults = [
		'post_type' => 'game',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => $meta_query_games
	];
	
	$query_args = wp_parse_args($args, $defaults);
	return get_posts($query_args);
}

/**
 * Affiche une carte de jeu réutilisable
 * @param WP_Post|int $game Le post du jeu ou son ID
 * @param array $args Arguments d'affichage (voir template-parts/game-card.php)
 */
function urbanquest_display_game_card($game, $args = []) {
	$template_path = locate_template('template-parts/game-card.php');
	if ($template_path) {
		include $template_path;
	} else {
		// Fallback si le template n'existe pas
		$game_id = is_object($game) ? $game->ID : $game;
		$game_data = urbanquest_get_game_display_data($game_id);
		$game_permalink = get_permalink($game_id);
		
		echo '<div class="urbanquest-game-card-fallback">';
		echo '<a href="' . esc_url($game_permalink) . '">';
		echo '<h3>' . esc_html($game_data['title']) . '</h3>';
		echo '</a>';
		echo '</div>';
	}
}

/**
 * Récupère les jeux d'un pays (OPTIMISÉ)
 * @param int $country_id ID du pays
 * @param array $args Arguments WP_Query additionnels
 * @return array Tableau de jeux WP_Post
 */
function urbanquest_get_games_by_country($country_id, $args = []) {
	if (!$country_id) {
		return [];
	}
	
	// Récupérer les régions du pays
	$regions = get_posts([
		'post_type' => 'region',
		'posts_per_page' => -1,
		'fields' => 'ids',
		'meta_query' => [
			[
				'key' => 'countries',
				'value' => '"' . $country_id . '"',
				'compare' => 'LIKE'
			]
		]
	]);
	
	if (empty($regions)) {
		return [];
	}
	
	// Utiliser la fonction pour récupérer les jeux de toutes ces régions
	$all_games = [];
	foreach ($regions as $region_id) {
		$region_games = urbanquest_get_games_by_region($region_id);
		foreach ($region_games as $game) {
			if (!in_array($game, $all_games, true)) {
				$all_games[] = $game;
			}
		}
	}
	
	return $all_games;
}

/**
 * Affiche une grille de cartes de jeux
 * @param array $games Tableau de jeux WP_Post
 * @param array $args Arguments d'affichage
 *   - 'columns' => int (défaut: 3) - Nombre de colonnes
 *   - 'layout' => string (défaut: 'card') - Layout des cartes
 *   - 'show_city' => bool (défaut: true)
 */
function urbanquest_display_games_grid($games, $args = []) {
	if (empty($games)) {
		return;
	}
	
	$defaults = [
		'columns' => 3,
		'layout' => 'card',
		'show_city' => true,
		'excerpt_length' => 20
	];
	
	$args = wp_parse_args($args, $defaults);
	$col_class = 'col-md-' . (12 / $args['columns']);
	
	echo '<div class="row urbanquest-games-grid">';
	foreach ($games as $game) {
		echo '<div class="' . esc_attr($col_class) . '" style="display: flex; margin-bottom: 30px;">';
		urbanquest_display_game_card($game, $args);
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Génère le HTML d'une jauge de progression
 * @param int $valeur Valeur entre 1 et 100
 * @param string $label Label de la jauge (non utilisé mais gardé pour compatibilité)
 * @return string HTML de la jauge
 */
function urbanquest_render_jauge($valeur, $label = '') {
	$valeur = max(1, min(100, intval($valeur)));
	$pourcentage = $valeur . '%';
	
	ob_start();
	?>
	<div class="urbanquest-progress-wrapper">
		<div class="urbanquest-progress-bar">
			<div class="urbanquest-progress-fill" style="width: <?php echo esc_attr($pourcentage); ?>;"></div>
			<div class="urbanquest-progress-empty" style="left: <?php echo esc_attr($pourcentage); ?>; width: <?php echo esc_attr((100 - $valeur) . '%'); ?>; border-radius: <?php echo ($valeur <= 0) ? '10px' : '0 10px 10px 0'; ?>;"></div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

// ============================================================================
// GÉNÉRATION DE TEXTE AVEC OPENAI POUR LES CHAMPS ACF
// ============================================================================
// Permet d'ajouter un bouton "Générer avec IA" sur certains champs ACF
// qui appelle l'API OpenAI pour générer du contenu avec un prompt spécifique
// ============================================================================

/**
 * Configuration des champs ACF qui peuvent utiliser la génération IA
 * Format: 'field_key' => 'prompt_template'
 * Le prompt peut contenir des variables comme {ville}, {titre}, etc.
 */
function urbanquest_get_ai_field_config() {
	// Valeurs par défaut
	$defaults = array(
		'field_game_description_principale' => 'Tu es un expert en rédaction web SEO. Écris une description principale accrocheuse et engageante pour un jeu de piste urbain appelé "{titre}" dans la ville de "{ville}". La description doit être de 150-200 mots, convaincante, mettre en avant l\'aspect ludique et l\'expérience unique. Utilise un ton enthousiaste mais professionnel. Format HTML avec paragraphes.',
		'field_city_description_terrain_de_jeu' => 'Écris une description engageante (150-200 mots) expliquant comment {ville} devient un terrain de jeu avec Urban Quest. Mettez en avant l\'aspect exploration, découverte et expérience immersive. Format HTML avec paragraphes.',
		'field_city_description_jeu_unique' => 'Écris une description (150-200 mots) expliquant pourquoi Urban Quest est un jeu de piste unique à {ville}. Mettez en avant l\'aspect culturel, historique et ludique. Format HTML avec paragraphes.',
		'field_690e2db6eb47d' => 'Écris une description de région (150-200 mots) pour {titre}. Mettez en avant les caractéristiques géographiques, culturelles et touristiques de la région. Format HTML avec paragraphes.',
		'field_690e2db6eb47d_dept' => 'Écris une description de département (150-200 mots) pour {titre}. Mettez en avant les caractéristiques géographiques et culturelles du département. Format HTML avec paragraphes.',
	);
	
	// Récupérer les prompts sauvegardés depuis les options
	$saved_prompts = get_option('urbanquest_ai_prompts', array());
	
	// Fusionner avec les valeurs par défaut (les valeurs sauvegardées écrasent les défauts)
	$config = array();
	foreach ($defaults as $field_key => $default_prompt) {
		$config[$field_key] = isset($saved_prompts[$field_key]) && !empty($saved_prompts[$field_key]) 
			? $saved_prompts[$field_key] 
			: $default_prompt;
	}
	
	return $config;
}

/**
 * Ajouter la page de réglages pour OpenAI
 */
function urbanquest_add_openai_settings_page() {
	add_options_page(
		'Génération IA OpenAI',
		'Génération IA',
		'manage_options',
		'urbanquest-openai-settings',
		'urbanquest_openai_settings_page'
	);
}
add_action('admin_menu', 'urbanquest_add_openai_settings_page');

/**
 * Afficher la page de réglages OpenAI
 */
function urbanquest_openai_settings_page() {
	// Traitement de la sauvegarde
	if (isset($_POST['urbanquest_save_settings']) && check_admin_referer('urbanquest_openai_settings')) {
		// Sauvegarder la clé API
		if (isset($_POST['urbanquest_openai_api_key'])) {
			update_option('urbanquest_openai_api_key', sanitize_text_field($_POST['urbanquest_openai_api_key']));
		}
		
		// Sauvegarder les prompts
		if (isset($_POST['urbanquest_ai_prompts']) && is_array($_POST['urbanquest_ai_prompts'])) {
			$prompts = array();
			foreach ($_POST['urbanquest_ai_prompts'] as $field_key => $prompt) {
				$prompts[sanitize_text_field($field_key)] = wp_kses_post($prompt);
			}
			update_option('urbanquest_ai_prompts', $prompts);
		}
		
		echo '<div class="notice notice-success"><p>Paramètres enregistrés avec succès.</p></div>';
	}
	
	$api_key = get_option('urbanquest_openai_api_key', '');
	$field_config = urbanquest_get_ai_field_config();
	
	// Labels lisibles pour les champs
	$field_labels = array(
		'field_game_description_principale' => 'Description principale du jeu',
		'field_city_description_terrain_de_jeu' => 'Description terrain de jeu (ville)',
		'field_city_description_jeu_unique' => 'Description jeu unique (ville)',
		'field_690e2db6eb47d' => 'Description région',
		'field_690e2db6eb47d_dept' => 'Description département',
	);
	?>
	<div class="wrap">
		<h1>Génération IA avec OpenAI</h1>
		
		<form method="post" action="">
			<?php wp_nonce_field('urbanquest_openai_settings'); ?>
			
			<h2>Configuration API</h2>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="urbanquest_openai_api_key">Clé API OpenAI</label>
					</th>
					<td>
						<input type="password" id="urbanquest_openai_api_key" name="urbanquest_openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
						<p class="description">Votre clé API OpenAI. Vous pouvez la créer sur <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com/api-keys</a></p>
					</td>
				</tr>
			</table>
			
			<h2>Prompts de génération</h2>
			<p class="description">Modifiez les prompts utilisés pour générer le texte. Vous pouvez utiliser les variables suivantes : <code>{titre}</code>, <code>{ville}</code>, <code>{region}</code></p>
			
			<?php foreach ($field_config as $field_key => $prompt) : ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="prompt_<?php echo esc_attr($field_key); ?>">
							<?php echo esc_html(isset($field_labels[$field_key]) ? $field_labels[$field_key] : $field_key); ?>
						</label>
						<p><code style="font-size: 11px;"><?php echo esc_html($field_key); ?></code></p>
					</th>
					<td>
						<textarea 
							id="prompt_<?php echo esc_attr($field_key); ?>" 
							name="urbanquest_ai_prompts[<?php echo esc_attr($field_key); ?>]" 
							rows="4" 
							class="large-text code"
							style="font-family: monospace; font-size: 13px;"
						><?php echo esc_textarea($prompt); ?></textarea>
					</td>
				</tr>
			</table>
			<?php endforeach; ?>
			
			<?php submit_button('Enregistrer les modifications', 'primary', 'urbanquest_save_settings'); ?>
		</form>
	</div>
	<?php
}

/**
 * Endpoint AJAX pour générer du texte avec OpenAI
 */
function urbanquest_generate_text_with_openai() {
	// Vérifier les permissions
	if (!current_user_can('edit_posts')) {
		wp_send_json_error(array('message' => 'Permissions insuffisantes'));
		return;
	}
	
	// Vérifier le nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'urbanquest_ai_generate')) {
		wp_send_json_error(array('message' => 'Nonce invalide'));
		return;
	}
	
	// Récupérer les paramètres
	$field_key = isset($_POST['field_key']) ? sanitize_text_field($_POST['field_key']) : '';
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	
	if (empty($field_key) || empty($post_id)) {
		wp_send_json_error(array('message' => 'Paramètres manquants'));
		return;
	}
	
	// Récupérer la clé API
	$api_key = get_option('urbanquest_openai_api_key', '');
	if (empty($api_key)) {
		wp_send_json_error(array('message' => 'Clé API OpenAI non configurée. Allez dans Réglages > Génération IA pour la configurer.'));
		return;
	}
	
	// Récupérer la configuration du champ
	$field_config = urbanquest_get_ai_field_config();
	if (!isset($field_config[$field_key])) {
		wp_send_json_error(array('message' => 'Champ non configuré pour la génération IA'));
		return;
	}
	
	$prompt_template = $field_config[$field_key];
	
	// Récupérer les données du post pour remplacer les variables
	$post = get_post($post_id);
	if (!$post) {
		wp_send_json_error(array('message' => 'Post introuvable'));
		return;
	}
	
	$titre = $post->post_title;
	$ville = '';
	$region = '';
	
	// Récupérer la ville selon le type de post
	if ($post->post_type === 'game') {
		$city_post = get_field('city', $post_id);
		$ville_id = urbanquest_extract_acf_relationship_id($city_post);
		if ($ville_id) {
			$ville = get_the_title($ville_id);
			
			// Récupérer aussi la région via la hiérarchie
			$departement_post = get_field('ville', $ville_id);
			$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
			if ($departement_id) {
				$region_post = get_field('region', $departement_id);
				$region_id = urbanquest_extract_acf_relationship_id($region_post);
				if ($region_id) {
					$region = get_the_title($region_id);
				}
			}
		}
	} elseif ($post->post_type === 'ville') {
		$ville = $titre;
		$departement_post = get_field('ville', $post_id);
		$departement_id = urbanquest_extract_acf_relationship_id($departement_post);
		if ($departement_id) {
			$region_post = get_field('region', $departement_id);
			$region_id = urbanquest_extract_acf_relationship_id($region_post);
			if ($region_id) {
				$region = get_the_title($region_id);
			}
		}
	} elseif ($post->post_type === 'region') {
		$region = $titre;
	} elseif ($post->post_type === 'departement') {
		$region_post = get_field('region', $post_id);
		$region_id = urbanquest_extract_acf_relationship_id($region_post);
		if ($region_id) {
			$region = get_the_title($region_id);
		}
	}
	
	// Remplacer les variables dans le prompt
	$prompt = str_replace(
		array('{titre}', '{ville}', '{region}'),
		array($titre, $ville, $region),
		$prompt_template
	);
	
	// Récupérer les données personnalisées
	$custom_context = isset($_POST['custom_context']) ? sanitize_textarea_field($_POST['custom_context']) : '';
	$custom_tone = isset($_POST['custom_tone']) ? sanitize_text_field($_POST['custom_tone']) : '';
	$custom_length = isset($_POST['custom_length']) ? sanitize_text_field($_POST['custom_length']) : '';
	
	// Construire les instructions personnalisées
	$custom_instructions = array();
	
	if (!empty($custom_context)) {
		$custom_instructions[] = "Informations supplémentaires : " . $custom_context;
	}
	
	if (!empty($custom_tone)) {
		$tone_map = array(
			'enthousiaste' => 'Utilisez un ton enthousiaste et dynamique.',
			'professionnel' => 'Utilisez un ton professionnel et sérieux.',
			'décontracté' => 'Utilisez un ton décontracté et familier.',
			'formel' => 'Utilisez un ton formel et respectueux.',
			'amical' => 'Utilisez un ton amical et chaleureux.'
		);
		if (isset($tone_map[$custom_tone])) {
			$custom_instructions[] = $tone_map[$custom_tone];
		}
	}
	
	if (!empty($custom_length)) {
		$length_map = array(
			'court' => 'Le texte doit faire entre 50 et 100 mots.',
			'moyen' => 'Le texte doit faire entre 100 et 200 mots.',
			'long' => 'Le texte doit faire entre 200 et 300 mots.'
		);
		if (isset($length_map[$custom_length])) {
			$custom_instructions[] = $length_map[$custom_length];
		}
	}
	
	// Ajouter les instructions personnalisées au prompt
	if (!empty($custom_instructions)) {
		$prompt .= "\n\n" . implode("\n", $custom_instructions);
	}
	
	// Appeler l'API OpenAI
	$response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type' => 'application/json',
		),
		'body' => json_encode(array(
			'model' => 'gpt-4o-mini',
		'messages' => array(
			array(
				'role' => 'system',
				'content' => 'Tu es un expert en rédaction web SEO et marketing digital. IMPORTANT: Retourne uniquement le texte demandé, sans balises markdown, sans ```html, sans ```, sans formatage de code. Retourne directement le contenu HTML/text brut.'
			),
			array(
				'role' => 'user',
				'content' => $prompt . "\n\nIMPORTANT: Retourne uniquement le texte, sans balises markdown (pas de ```html ou ```). Retourne directement le contenu."
			)
		),
			'temperature' => 0.7,
			'max_tokens' => 1000,
		)),
		'timeout' => 30,
	));
	
	if (is_wp_error($response)) {
		wp_send_json_error(array('message' => 'Erreur lors de l\'appel à OpenAI: ' . $response->get_error_message()));
		return;
	}
	
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	
	if (isset($data['error'])) {
		wp_send_json_error(array('message' => 'Erreur OpenAI: ' . $data['error']['message']));
		return;
	}
	
	if (!isset($data['choices'][0]['message']['content'])) {
		wp_send_json_error(array('message' => 'Réponse OpenAI invalide'));
		return;
	}
	
	$generated_text = $data['choices'][0]['message']['content'];
	
	// Nettoyer le texte : retirer les balises markdown de code (```html, ```, etc.)
	$generated_text = preg_replace('/^```[a-z]*\s*\n?/i', '', $generated_text); // Retirer ```html ou ``` au début
	$generated_text = preg_replace('/\n?```\s*$/i', '', $generated_text); // Retirer ``` à la fin
	$generated_text = trim($generated_text);
	
	// Retirer aussi les balises markdown de code qui pourraient être au milieu
	$generated_text = preg_replace('/```[a-z]*\s*\n?/i', '', $generated_text);
	$generated_text = preg_replace('/\n?```\s*/i', '', $generated_text);
	
	wp_send_json_success(array('text' => $generated_text));
}
add_action('wp_ajax_urbanquest_generate_text', 'urbanquest_generate_text_with_openai');

/**
 * Enqueue les scripts et styles pour les boutons IA dans ACF
 */
function urbanquest_enqueue_acf_ai_scripts($hook) {
	// Ne charger que sur les pages d'édition de posts
	if (!in_array($hook, array('post.php', 'post-new.php'))) {
		return;
	}
	
	// Vérifier que ACF est actif
	if (!function_exists('get_field')) {
		return;
	}
	
	wp_enqueue_script(
		'urbanquest-acf-ai',
		get_stylesheet_directory_uri() . '/js/acf-ai-generate.js',
		array('jquery', 'acf-input'),
		'1.4.0', // Version mise à jour pour forcer le rechargement
		true
	);
	
	wp_enqueue_style(
		'urbanquest-acf-ai',
		get_stylesheet_directory_uri() . '/css/admin-style.css',
		array(),
		'1.5.0' // Version mise à jour pour forcer le rechargement
	);
	
	// Passer les données au JavaScript
	wp_localize_script('urbanquest-acf-ai', 'urbanquestAI', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('urbanquest_ai_generate'),
		'fieldConfig' => urbanquest_get_ai_field_config(),
		'i18n' => array(
			'generate' => 'Générer avec IA',
			'generating' => 'Génération en cours...',
			'error' => 'Erreur lors de la génération',
			'success' => 'Texte généré avec succès',
		),
	));
}
add_action('admin_enqueue_scripts', 'urbanquest_enqueue_acf_ai_scripts');

// ============================================================================
// INITIALISATION PAR DÉFAUT DES FONCTIONNALITÉS POUR LES NOUVEAUX JEUX
// ============================================================================
// Initialise automatiquement 3 fonctionnalités par défaut dans le champ
// "pourquoi_choisir_features" lors de la création d'un nouveau jeu
// ============================================================================

/**
 * Initialise les fonctionnalités par défaut pour un nouveau jeu lors du chargement dans l'admin
 * Utilise acf/load_value UNIQUEMENT pour les nouveaux posts non encore initialisés
 * Cela permet d'afficher les valeurs par défaut dans l'interface admin lors de la création
 */
function urbanquest_init_default_features($value, $post_id, $field) {
	// Ne s'applique qu'au champ pourquoi_choisir_features
	if ($field['name'] !== 'pourquoi_choisir_features') {
		return $value;
	}
	
	// Si le champ a déjà une valeur avec au moins une entrée valide, la retourner telle quelle
	if (!empty($value) && is_array($value) && count($value) > 0) {
		$has_valid_entry = false;
		foreach ($value as $feature) {
			if (!empty($feature['icone']) || !empty($feature['titre']) || !empty($feature['description'])) {
				$has_valid_entry = true;
				break;
			}
		}
		if ($has_valid_entry) {
			return $value;
		}
	}
	
	// Vérifier si c'est un post de type 'game'
	$post_type = '';
	if (is_numeric($post_id)) {
		$post = get_post($post_id);
		if ($post) {
			$post_type = $post->post_type;
		}
	} else {
		// Pour les nouveaux posts, vérifier le type depuis la requête globale
		global $typenow;
		if (isset($typenow) && $typenow === 'game') {
			$post_type = 'game';
		}
	}
	
	// Ne s'applique qu'aux posts de type 'game'
	if ($post_type !== 'game') {
		return $value;
	}
	
	// IMPORTANT : Ne retourner les valeurs par défaut QUE si le post n'a jamais été initialisé
	// Cela permet d'afficher les valeurs par défaut dans l'admin lors de la création
	// mais empêche toute réinitialisation après que l'admin ait modifié ou supprimé les fonctionnalités
	if (is_numeric($post_id)) {
		$already_initialized = get_post_meta($post_id, '_features_default_initialized', true);
		if ($already_initialized === 'yes') {
			// Le post a déjà été initialisé, retourner la valeur telle quelle (même si vide)
			// pour permettre à l'admin de supprimer toutes les fonctionnalités s'il le souhaite
			return $value;
		}
	}
	
	// Valeurs par défaut des 3 fonctionnalités (uniquement pour les nouveaux posts non initialisés)
	$default_features = array(
		array(
			'icone' => 'calendar-heart',
			'titre' => '100% libre',
			'description' => 'Vous lancez la session quand vous voulez, où vous voulez.'
		),
		array(
			'icone' => 'smartphone',
			'titre' => 'Ultra simple',
			'description' => 'Vos instructions de jeu par e-mail, votre smartphone… c\'est tout.'
		),
		array(
			'icone' => 'swords',
			'titre' => 'Fun & challenge',
			'description' => 'Défis variés, énigmes malignes, score et classement.'
		)
	);
	
	return $default_features;
}
add_filter('acf/load_value/name=pourquoi_choisir_features', 'urbanquest_init_default_features', 10, 3);

/**
 * Marque les nouveaux posts de type 'game' lors de leur création
 */
function urbanquest_mark_new_game_post($post_id, $post, $update) {
	// Ne s'exécute QUE lors de la création (pas lors de la mise à jour)
	if ($update) {
		return;
	}
	
	// Ne s'applique qu'aux posts de type 'game'
	if ($post->post_type !== 'game') {
		return;
	}
	
	// Marquer ce post comme nouveau pour l'initialisation des fonctionnalités
	update_post_meta($post_id, '_is_new_game_post', 'yes');
}
add_action('wp_insert_post', 'urbanquest_mark_new_game_post', 10, 3);

/**
 * Initialise les fonctionnalités par défaut UNIQUEMENT lors de la création d'un nouveau jeu
 * S'exécute après que ACF ait sauvegardé ses champs
 */
function urbanquest_init_default_features_on_create($post_id) {
	// Ne s'applique qu'aux posts de type 'game'
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'game') {
		return;
	}
	
	// VÉRIFICATION PRINCIPALE : Si le post a déjà été initialisé, ne JAMAIS toucher
	// Cette vérification doit être faite EN PREMIER pour éviter toute réinitialisation
	$already_initialized = get_post_meta($post_id, '_features_default_initialized', true);
	if ($already_initialized === 'yes') {
		// Nettoyer le flag temporaire s'il existe encore
		delete_post_meta($post_id, '_is_new_game_post');
		return; // Déjà initialisé, ne jamais toucher - même si le champ est vide
	}
	
	// Vérifier si c'est un nouveau post (marqué lors de wp_insert_post)
	$is_new_post = get_post_meta($post_id, '_is_new_game_post', true) === 'yes';
	
	// Si ce n'est pas un nouveau post, ne rien faire
	if (!$is_new_post) {
		return;
	}
	
	// Valeurs par défaut des 3 fonctionnalités
	$default_features = array(
		array(
			'icone' => 'calendar-heart',
			'titre' => '100% libre',
			'description' => 'Vous lancez la session quand vous voulez, où vous voulez.'
		),
		array(
			'icone' => 'smartphone',
			'titre' => 'Ultra simple',
			'description' => 'Vos instructions de jeu par e-mail, votre smartphone… c\'est tout.'
		),
		array(
			'icone' => 'swords',
			'titre' => 'Fun & challenge',
			'description' => 'Défis variés, énigmes malignes, score et classement.'
		)
	);
	
	// Vérifier si le champ existe déjà avec des données
	$features = get_field('pourquoi_choisir_features', $post_id);
	
	// Compter les fonctionnalités valides (au moins un champ rempli)
	$valid_features_count = 0;
	$has_empty_items = false;
	if (!empty($features) && is_array($features)) {
		foreach ($features as $feature) {
			if (!empty($feature['icone']) || !empty($feature['titre']) || !empty($feature['description'])) {
				$valid_features_count++;
			} else {
				$has_empty_items = true;
			}
		}
	}
	
	// Si aucune fonctionnalité valide OU si le champ contient des items vides,
	// initialiser/remplacer avec les valeurs par défaut
	if ($valid_features_count === 0) {
		// Sauvegarder les valeurs par défaut (remplace les items vides si nécessaire)
		update_field('pourquoi_choisir_features', $default_features, $post_id);
		
		// Marquer comme initialisé seulement après avoir réellement initialisé
		update_post_meta($post_id, '_features_default_initialized', 'yes');
	} else {
		// Si des fonctionnalités valides existent déjà (l'admin les a peut-être créées manuellement),
		// marquer aussi comme initialisé pour éviter toute réinitialisation future
		update_post_meta($post_id, '_features_default_initialized', 'yes');
	}
	
	// Nettoyer le flag temporaire
	delete_post_meta($post_id, '_is_new_game_post');
}
add_action('acf/save_post', 'urbanquest_init_default_features_on_create', 30);

// ============================================================================
// CRÉATION AUTOMATIQUE DES RÉGIONS FRANÇAISES
// ============================================================================

/**
 * Liste de toutes les régions françaises (13 régions métropolitaines + 5 régions d'outre-mer)
 */
function urbanquest_get_french_regions() {
	return array(
		// Régions métropolitaines (13)
		'Auvergne-Rhône-Alpes',
		'Bourgogne-Franche-Comté',
		'Bretagne',
		'Centre-Val de Loire',
		'Corse',
		'Grand Est',
		'Hauts-de-France',
		'Île-de-France',
		'Normandie',
		'Nouvelle-Aquitaine',
		'Occitanie',
		'Pays de la Loire',
		'Provence-Alpes-Côte d\'Azur',
		// Régions d'outre-mer (5)
		'Guadeloupe',
		'Martinique',
		'Guyane',
		'La Réunion',
		'Mayotte'
	);
}

/**
 * Préremplit automatiquement le champ "Pays" pour une région lors de sa création
 * Si aucun pays n'est défini, assigne le premier pays disponible (généralement "France")
 */
function urbanquest_prefill_region_country($post_id) {
	// Ne s'applique qu'aux posts de type 'region'
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'region') {
		return;
	}
	
	// Vérifier si le champ "countries" est déjà rempli
	$current_country = get_field('countries', $post_id);
	if (!empty($current_country)) {
		// Le champ est déjà rempli, ne rien faire
		return;
	}
	
	// Récupérer le premier pays disponible (généralement "France")
	$countries = get_posts(array(
		'post_type' => 'country',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC'
	));
	
	if (!empty($countries)) {
		$country_id = $countries[0]->ID;
		// Assigner le pays à la région
		update_field('countries', $country_id, $post_id);
	}
}
add_action('acf/save_post', 'urbanquest_prefill_region_country', 10);

/**
 * Crée toutes les régions françaises dans la base de données
 * Ne crée que les régions qui n'existent pas déjà
 * 
 * @param int|null $country_id ID du pays à assigner (null = utilise le premier pays disponible ou "France")
 * @return array Résultat avec le nombre de régions créées
 */
function urbanquest_create_all_french_regions($country_id = null) {
	// Si aucun pays n'est spécifié, chercher "France" ou le premier pays disponible
	if ($country_id === null) {
		// Récupérer tous les pays et chercher "France"
		$all_countries = get_posts(array(
			'post_type' => 'country',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC'
		));
		
		if (empty($all_countries)) {
			return array(
				'success' => false,
				'message' => 'Aucun pays trouvé dans la base de données. Veuillez créer le pays "France" d\'abord.',
				'created' => 0,
				'skipped' => 0,
				'total' => 0
			);
		}
		
		// Chercher "France" dans la liste
		$france_found = false;
		foreach ($all_countries as $country) {
			if (strtolower(trim($country->post_title)) === 'france') {
				$country_id = $country->ID;
				$france_found = true;
				break;
			}
		}
		
		// Si "France" n'est pas trouvé, utiliser le premier pays disponible
		if (!$france_found) {
			$country_id = $all_countries[0]->ID;
		}
	}
	
	// Vérifier que le pays existe
	$country_post = get_post($country_id);
	if (!$country_post || $country_post->post_type !== 'country') {
		return array(
			'success' => false,
			'message' => 'Le pays spécifié n\'existe pas.',
			'created' => 0,
			'skipped' => 0,
			'total' => 0
		);
	}
	
	// Récupérer toutes les régions existantes (par titre)
	$existing_regions = get_posts(array(
		'post_type' => 'region',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$existing_titles = array();
	foreach ($existing_regions as $region) {
		$existing_titles[] = strtolower(trim($region->post_title));
	}
	
	// Liste des régions françaises à créer
	$french_regions = urbanquest_get_french_regions();
	
	$created = 0;
	$skipped = 0;
	$created_regions = array();
	
	foreach ($french_regions as $region_name) {
		$region_name_lower = strtolower(trim($region_name));
		
		// Vérifier si la région existe déjà
		if (in_array($region_name_lower, $existing_titles)) {
			$skipped++;
			continue;
		}
		
		// Créer la région
		$region_id = wp_insert_post(array(
			'post_title' => $region_name,
			'post_type' => 'region',
			'post_status' => 'publish',
			'post_content' => '' // Contenu vide par défaut
		));
		
		if (is_wp_error($region_id)) {
			continue;
		}
		
		// Assigner le pays à la région
		update_field('countries', $country_id, $region_id);
		
		$created++;
		$created_regions[] = $region_name;
	}
	
	return array(
		'success' => true,
		'message' => sprintf(
			'%d région(s) créée(s) avec succès. %d région(s) existante(s) ignorée(s).',
			$created,
			$skipped
		),
		'created' => $created,
		'skipped' => $skipped,
		'total' => count($french_regions),
		'country_id' => $country_id,
		'country_name' => get_the_title($country_id),
		'created_regions' => $created_regions
	);
}

/**
 * Ajoute une page d'administration pour créer toutes les régions françaises
 */
function urbanquest_add_create_regions_admin_page() {
	add_submenu_page(
		'edit.php?post_type=region',
		'Créer les régions françaises',
		'Créer toutes les régions',
		'manage_options',
		'create-french-regions',
		'urbanquest_create_regions_admin_page_callback'
	);
}
add_action('admin_menu', 'urbanquest_add_create_regions_admin_page');

/**
 * Callback pour la page d'administration de création des régions
 */
function urbanquest_create_regions_admin_page_callback() {
	// Vérifier les permissions
	if (!current_user_can('manage_options')) {
		wp_die('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
	}
	
	$result = null;
	
	// Traiter la soumission du formulaire
	if (isset($_POST['create_regions']) && check_admin_referer('create_regions_action', 'create_regions_nonce')) {
		$country_id = isset($_POST['country_id']) && !empty($_POST['country_id']) ? intval($_POST['country_id']) : null;
		$result = urbanquest_create_all_french_regions($country_id);
	}
	
	// Récupérer tous les pays disponibles
	$countries = get_posts(array(
		'post_type' => 'country',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC'
	));
	
	// Récupérer les statistiques actuelles
	$all_regions = get_posts(array(
		'post_type' => 'region',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$french_regions = urbanquest_get_french_regions();
	$existing_region_titles = array();
	foreach ($all_regions as $region) {
		$existing_region_titles[] = strtolower(trim($region->post_title));
	}
	
	$missing_regions = array();
	foreach ($french_regions as $region_name) {
		if (!in_array(strtolower(trim($region_name)), $existing_region_titles)) {
			$missing_regions[] = $region_name;
		}
	}
	
	?>
	<div class="wrap">
		<h1>Créer toutes les régions françaises</h1>
		
		<?php if ($result) : ?>
			<?php if ($result['success']) : ?>
				<div class="notice notice-success is-dismissible">
					<p><strong>✅ Succès :</strong> <?php echo esc_html($result['message']); ?></p>
					<?php if (!empty($result['created_regions'])) : ?>
						<p><strong>Régions créées :</strong></p>
						<ul style="margin-left: 20px;">
							<?php foreach ($result['created_regions'] as $region_name) : ?>
								<li><?php echo esc_html($region_name); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="notice notice-error is-dismissible">
					<p><strong>❌ Erreur :</strong> <?php echo esc_html($result['message']); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Statistiques actuelles</h2>
			<ul>
				<li><strong>Total de régions dans la base :</strong> <?php echo count($all_regions); ?></li>
				<li><strong>Régions françaises à créer :</strong> <?php echo count($french_regions); ?></li>
				<li><strong>Régions déjà présentes :</strong> <span style="color: #00a32a;"><?php echo count($french_regions) - count($missing_regions); ?></span></li>
				<li><strong>Régions manquantes :</strong> <span style="color: <?php echo count($missing_regions) > 0 ? '#d63638' : '#00a32a'; ?>;"><?php echo count($missing_regions); ?></span></li>
			</ul>
			
			<?php if (!empty($missing_regions)) : ?>
				<p><strong>Régions qui seront créées :</strong></p>
				<ul style="margin-left: 20px;">
					<?php foreach ($missing_regions as $region_name) : ?>
						<li><?php echo esc_html($region_name); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color: #00a32a;"><strong>✅ Toutes les régions françaises sont déjà présentes dans la base de données.</strong></p>
			<?php endif; ?>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Action</h2>
			<p>Cette fonction va créer toutes les 18 régions françaises (13 métropolitaines + 5 d'outre-mer) qui n'existent pas encore dans la base de données.</p>
			
			<?php if (empty($countries)) : ?>
				<div class="notice notice-warning">
					<p><strong>⚠️ Attention :</strong> Aucun pays trouvé dans la base de données. Veuillez créer le pays "France" d'abord.</p>
				</div>
			<?php else : ?>
				<form method="post" action="">
					<?php wp_nonce_field('create_regions_action', 'create_regions_nonce'); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="country_id">Pays à assigner</label>
							</th>
							<td>
								<select name="country_id" id="country_id" style="min-width: 300px;">
									<option value="">-- Chercher "France" ou utiliser le premier pays disponible --</option>
									<?php foreach ($countries as $country) : ?>
										<option value="<?php echo esc_attr($country->ID); ?>" <?php selected(strtolower($country->post_title), 'france'); ?>>
											<?php echo esc_html($country->post_title); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">Si aucun pays n'est sélectionné, le système cherchera "France" ou utilisera le premier pays disponible.</p>
							</td>
						</tr>
					</table>
					
					<?php submit_button('Créer toutes les régions françaises', 'primary', 'create_regions'); ?>
				</form>
			<?php endif; ?>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Liste des 18 régions françaises</h2>
			<p><strong>Régions métropolitaines (13) :</strong></p>
			<ol style="margin-left: 20px;">
				<?php 
				$metropolitan_regions = array_slice($french_regions, 0, 13);
				foreach ($metropolitan_regions as $region_name) : ?>
					<li><?php echo esc_html($region_name); ?></li>
				<?php endforeach; ?>
			</ol>
			<p style="margin-top: 20px;"><strong>Régions d'outre-mer (5) :</strong></p>
			<ol start="14" style="margin-left: 20px;">
				<?php 
				$overseas_regions = array_slice($french_regions, 13);
				foreach ($overseas_regions as $region_name) : ?>
					<li><?php echo esc_html($region_name); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Note</h2>
			<p>Seules les régions qui n'existent pas encore seront créées. Les régions existantes ne seront pas modifiées.</p>
			<p>Chaque région créée sera automatiquement associée au pays sélectionné (ou "France" par défaut).</p>
		</div>
	</div>
	<?php
}

// ============================================================================
// CRÉATION AUTOMATIQUE DES DÉPARTEMENTS FRANÇAIS
// ============================================================================

/**
 * Liste de tous les départements français avec leur région associée
 * Format: array('numéro' => array('nom' => 'Nom du département', 'region' => 'Nom de la région'))
 */
function urbanquest_get_french_departments() {
	return array(
		'01' => array('nom' => 'Ain', 'region' => 'Auvergne-Rhône-Alpes'),
		'02' => array('nom' => 'Aisne', 'region' => 'Hauts-de-France'),
		'03' => array('nom' => 'Allier', 'region' => 'Auvergne-Rhône-Alpes'),
		'04' => array('nom' => 'Alpes-de-Haute-Provence', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'05' => array('nom' => 'Hautes-Alpes', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'06' => array('nom' => 'Alpes-Maritimes', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'07' => array('nom' => 'Ardèche', 'region' => 'Auvergne-Rhône-Alpes'),
		'08' => array('nom' => 'Ardennes', 'region' => 'Grand Est'),
		'09' => array('nom' => 'Ariège', 'region' => 'Occitanie'),
		'10' => array('nom' => 'Aube', 'region' => 'Grand Est'),
		'11' => array('nom' => 'Aude', 'region' => 'Occitanie'),
		'12' => array('nom' => 'Aveyron', 'region' => 'Occitanie'),
		'13' => array('nom' => 'Bouches-du-Rhône', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'14' => array('nom' => 'Calvados', 'region' => 'Normandie'),
		'15' => array('nom' => 'Cantal', 'region' => 'Auvergne-Rhône-Alpes'),
		'16' => array('nom' => 'Charente', 'region' => 'Nouvelle-Aquitaine'),
		'17' => array('nom' => 'Charente-Maritime', 'region' => 'Nouvelle-Aquitaine'),
		'18' => array('nom' => 'Cher', 'region' => 'Centre-Val de Loire'),
		'19' => array('nom' => 'Corrèze', 'region' => 'Nouvelle-Aquitaine'),
		'21' => array('nom' => 'Côte-d\'Or', 'region' => 'Bourgogne-Franche-Comté'),
		'22' => array('nom' => 'Côtes-d\'Armor', 'region' => 'Bretagne'),
		'23' => array('nom' => 'Creuse', 'region' => 'Nouvelle-Aquitaine'),
		'24' => array('nom' => 'Dordogne', 'region' => 'Nouvelle-Aquitaine'),
		'25' => array('nom' => 'Doubs', 'region' => 'Bourgogne-Franche-Comté'),
		'26' => array('nom' => 'Drôme', 'region' => 'Auvergne-Rhône-Alpes'),
		'27' => array('nom' => 'Eure', 'region' => 'Normandie'),
		'28' => array('nom' => 'Eure-et-Loir', 'region' => 'Centre-Val de Loire'),
		'29' => array('nom' => 'Finistère', 'region' => 'Bretagne'),
		'2A' => array('nom' => 'Corse-du-Sud', 'region' => 'Corse'),
		'2B' => array('nom' => 'Haute-Corse', 'region' => 'Corse'),
		'30' => array('nom' => 'Gard', 'region' => 'Occitanie'),
		'31' => array('nom' => 'Haute-Garonne', 'region' => 'Occitanie'),
		'32' => array('nom' => 'Gers', 'region' => 'Occitanie'),
		'33' => array('nom' => 'Gironde', 'region' => 'Nouvelle-Aquitaine'),
		'34' => array('nom' => 'Hérault', 'region' => 'Occitanie'),
		'35' => array('nom' => 'Ille-et-Vilaine', 'region' => 'Bretagne'),
		'36' => array('nom' => 'Indre', 'region' => 'Centre-Val de Loire'),
		'37' => array('nom' => 'Indre-et-Loire', 'region' => 'Centre-Val de Loire'),
		'38' => array('nom' => 'Isère', 'region' => 'Auvergne-Rhône-Alpes'),
		'39' => array('nom' => 'Jura', 'region' => 'Bourgogne-Franche-Comté'),
		'40' => array('nom' => 'Landes', 'region' => 'Nouvelle-Aquitaine'),
		'41' => array('nom' => 'Loir-et-Cher', 'region' => 'Centre-Val de Loire'),
		'42' => array('nom' => 'Loire', 'region' => 'Auvergne-Rhône-Alpes'),
		'43' => array('nom' => 'Haute-Loire', 'region' => 'Auvergne-Rhône-Alpes'),
		'44' => array('nom' => 'Loire-Atlantique', 'region' => 'Pays de la Loire'),
		'45' => array('nom' => 'Loiret', 'region' => 'Centre-Val de Loire'),
		'46' => array('nom' => 'Lot', 'region' => 'Occitanie'),
		'47' => array('nom' => 'Lot-et-Garonne', 'region' => 'Nouvelle-Aquitaine'),
		'48' => array('nom' => 'Lozère', 'region' => 'Occitanie'),
		'49' => array('nom' => 'Maine-et-Loire', 'region' => 'Pays de la Loire'),
		'50' => array('nom' => 'Manche', 'region' => 'Normandie'),
		'51' => array('nom' => 'Marne', 'region' => 'Grand Est'),
		'52' => array('nom' => 'Haute-Marne', 'region' => 'Grand Est'),
		'53' => array('nom' => 'Mayenne', 'region' => 'Pays de la Loire'),
		'54' => array('nom' => 'Meurthe-et-Moselle', 'region' => 'Grand Est'),
		'55' => array('nom' => 'Meuse', 'region' => 'Grand Est'),
		'56' => array('nom' => 'Morbihan', 'region' => 'Bretagne'),
		'57' => array('nom' => 'Moselle', 'region' => 'Grand Est'),
		'58' => array('nom' => 'Nièvre', 'region' => 'Bourgogne-Franche-Comté'),
		'59' => array('nom' => 'Nord', 'region' => 'Hauts-de-France'),
		'60' => array('nom' => 'Oise', 'region' => 'Hauts-de-France'),
		'61' => array('nom' => 'Orne', 'region' => 'Normandie'),
		'62' => array('nom' => 'Pas-de-Calais', 'region' => 'Hauts-de-France'),
		'63' => array('nom' => 'Puy-de-Dôme', 'region' => 'Auvergne-Rhône-Alpes'),
		'64' => array('nom' => 'Pyrénées-Atlantiques', 'region' => 'Nouvelle-Aquitaine'),
		'65' => array('nom' => 'Hautes-Pyrénées', 'region' => 'Occitanie'),
		'66' => array('nom' => 'Pyrénées-Orientales', 'region' => 'Occitanie'),
		'67' => array('nom' => 'Bas-Rhin', 'region' => 'Grand Est'),
		'68' => array('nom' => 'Haut-Rhin', 'region' => 'Grand Est'),
		'69' => array('nom' => 'Rhône', 'region' => 'Auvergne-Rhône-Alpes'),
		'70' => array('nom' => 'Haute-Saône', 'region' => 'Bourgogne-Franche-Comté'),
		'71' => array('nom' => 'Saône-et-Loire', 'region' => 'Bourgogne-Franche-Comté'),
		'72' => array('nom' => 'Sarthe', 'region' => 'Pays de la Loire'),
		'73' => array('nom' => 'Savoie', 'region' => 'Auvergne-Rhône-Alpes'),
		'74' => array('nom' => 'Haute-Savoie', 'region' => 'Auvergne-Rhône-Alpes'),
		'75' => array('nom' => 'Paris', 'region' => 'Île-de-France'),
		'76' => array('nom' => 'Seine-Maritime', 'region' => 'Normandie'),
		'77' => array('nom' => 'Seine-et-Marne', 'region' => 'Île-de-France'),
		'78' => array('nom' => 'Yvelines', 'region' => 'Île-de-France'),
		'79' => array('nom' => 'Deux-Sèvres', 'region' => 'Nouvelle-Aquitaine'),
		'80' => array('nom' => 'Somme', 'region' => 'Hauts-de-France'),
		'81' => array('nom' => 'Tarn', 'region' => 'Occitanie'),
		'82' => array('nom' => 'Tarn-et-Garonne', 'region' => 'Occitanie'),
		'83' => array('nom' => 'Var', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'84' => array('nom' => 'Vaucluse', 'region' => 'Provence-Alpes-Côte d\'Azur'),
		'85' => array('nom' => 'Vendée', 'region' => 'Pays de la Loire'),
		'86' => array('nom' => 'Vienne', 'region' => 'Nouvelle-Aquitaine'),
		'87' => array('nom' => 'Haute-Vienne', 'region' => 'Nouvelle-Aquitaine'),
		'88' => array('nom' => 'Vosges', 'region' => 'Grand Est'),
		'89' => array('nom' => 'Yonne', 'region' => 'Bourgogne-Franche-Comté'),
		'90' => array('nom' => 'Territoire de Belfort', 'region' => 'Bourgogne-Franche-Comté'),
		'91' => array('nom' => 'Essonne', 'region' => 'Île-de-France'),
		'92' => array('nom' => 'Hauts-de-Seine', 'region' => 'Île-de-France'),
		'93' => array('nom' => 'Seine-Saint-Denis', 'region' => 'Île-de-France'),
		'94' => array('nom' => 'Val-de-Marne', 'region' => 'Île-de-France'),
		'95' => array('nom' => 'Val-d\'Oise', 'region' => 'Île-de-France'),
		'971' => array('nom' => 'Guadeloupe', 'region' => 'Guadeloupe'),
		'972' => array('nom' => 'Martinique', 'region' => 'Martinique'),
		'973' => array('nom' => 'Guyane', 'region' => 'Guyane'),
		'974' => array('nom' => 'La Réunion', 'region' => 'La Réunion'),
		'976' => array('nom' => 'Mayotte', 'region' => 'Mayotte')
	);
}

/**
 * Crée tous les départements français dans la base de données
 * Ne crée que les départements qui n'existent pas déjà
 * Assigne automatiquement la région correspondante à chaque département
 * 
 * @return array Résultat avec le nombre de départements créés
 */
function urbanquest_create_all_french_departments() {
	// Récupérer toutes les régions existantes pour les mapper
	$all_regions = get_posts(array(
		'post_type' => 'region',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$regions_map = array();
	foreach ($all_regions as $region) {
		$region_name_lower = strtolower(trim($region->post_title));
		$regions_map[$region_name_lower] = $region->ID;
	}
	
	// Récupérer tous les départements existants (par titre)
	$existing_departments = get_posts(array(
		'post_type' => 'departement',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$existing_titles = array();
	foreach ($existing_departments as $dept) {
		$existing_titles[] = strtolower(trim($dept->post_title));
	}
	
	// Liste des départements français à créer
	$french_departments = urbanquest_get_french_departments();
	
	$created = 0;
	$skipped = 0;
	$created_departments = array();
	$errors = array();
	
	foreach ($french_departments as $code => $dept_data) {
		$dept_name = $dept_data['nom'];
		$region_name = $dept_data['region'];
		$dept_name_lower = strtolower(trim($dept_name));
		
		// Vérifier si le département existe déjà (par nom uniquement)
		if (in_array($dept_name_lower, $existing_titles)) {
			$skipped++;
			continue;
		}
		
		// Trouver la région correspondante
		$region_name_lower = strtolower(trim($region_name));
		$region_id = isset($regions_map[$region_name_lower]) ? $regions_map[$region_name_lower] : null;
		
		if (!$region_id) {
			$errors[] = sprintf('Région "%s" introuvable pour le département "%s"', $region_name, $dept_name);
			continue;
		}
		
		// Créer le département avec uniquement le nom (ex: "Ain")
		$dept_id = wp_insert_post(array(
			'post_title' => $dept_name,
			'post_type' => 'departement',
			'post_status' => 'publish',
			'post_content' => '' // Contenu vide par défaut
		));
		
		if (is_wp_error($dept_id)) {
			$errors[] = sprintf('Erreur lors de la création du département "%s": %s', $dept_name, $dept_id->get_error_message());
			continue;
		}
		
		// Assigner la région au département
		update_field('region', $region_id, $dept_id);
		
		$created++;
		$created_departments[] = $dept_name;
	}
	
	$message = sprintf(
		'%d département(s) créé(s) avec succès. %d département(s) existant(s) ignoré(s).',
		$created,
		$skipped
	);
	
	if (!empty($errors)) {
		$message .= ' ' . count($errors) . ' erreur(s) rencontrée(s).';
	}
	
	return array(
		'success' => $created > 0 || empty($errors),
		'message' => $message,
		'created' => $created,
		'skipped' => $skipped,
		'total' => count($french_departments),
		'created_departments' => $created_departments,
		'errors' => $errors
	);
}

/**
 * Ajoute une page d'administration pour créer tous les départements français
 */
function urbanquest_add_create_departments_admin_page() {
	add_submenu_page(
		'edit.php?post_type=departement',
		'Créer les départements français',
		'Créer tous les départements',
		'manage_options',
		'create-french-departments',
		'urbanquest_create_departments_admin_page_callback'
	);
}
add_action('admin_menu', 'urbanquest_add_create_departments_admin_page');

/**
 * Callback pour la page d'administration de création des départements
 */
function urbanquest_create_departments_admin_page_callback() {
	// Vérifier les permissions
	if (!current_user_can('manage_options')) {
		wp_die('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
	}
	
	$result = null;
	
	// Traiter la soumission du formulaire
	if (isset($_POST['create_departments']) && check_admin_referer('create_departments_action', 'create_departments_nonce')) {
		$result = urbanquest_create_all_french_departments();
	}
	
	// Récupérer les statistiques actuelles
	$all_departments = get_posts(array(
		'post_type' => 'departement',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$french_departments = urbanquest_get_french_departments();
	$existing_dept_titles = array();
	foreach ($all_departments as $dept) {
		$existing_dept_titles[] = strtolower(trim($dept->post_title));
	}
	
	$missing_departments = array();
	foreach ($french_departments as $code => $dept_data) {
		$dept_name = $dept_data['nom'];
		if (!in_array(strtolower(trim($dept_name)), $existing_dept_titles)) {
			$missing_departments[] = $dept_name;
		}
	}
	
	// Vérifier que les régions existent
	$all_regions = get_posts(array(
		'post_type' => 'region',
		'posts_per_page' => -1,
		'post_status' => 'any'
	));
	
	$regions_map = array();
	foreach ($all_regions as $region) {
		$regions_map[strtolower(trim($region->post_title))] = $region->ID;
	}
	
	$missing_regions = array();
	$unique_regions = array_unique(array_column($french_departments, 'region'));
	foreach ($unique_regions as $region_name) {
		if (!isset($regions_map[strtolower(trim($region_name))])) {
			$missing_regions[] = $region_name;
		}
	}
	
	?>
	<div class="wrap">
		<h1>Créer tous les départements français</h1>
		
		<?php if ($result) : ?>
			<?php if ($result['success']) : ?>
				<div class="notice notice-success is-dismissible">
					<p><strong>✅ Succès :</strong> <?php echo esc_html($result['message']); ?></p>
					<?php if (!empty($result['created_departments'])) : ?>
						<p><strong>Départements créés :</strong></p>
						<ul style="margin-left: 20px; max-height: 300px; overflow-y: auto;">
							<?php foreach ($result['created_departments'] as $dept_name) : ?>
								<li><?php echo esc_html($dept_name); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="notice notice-error is-dismissible">
					<p><strong>❌ Erreur :</strong> <?php echo esc_html($result['message']); ?></p>
					<?php if (!empty($result['errors'])) : ?>
						<p><strong>Détails des erreurs :</strong></p>
						<ul style="margin-left: 20px;">
							<?php foreach ($result['errors'] as $error) : ?>
								<li><?php echo esc_html($error); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		
		<?php if (!empty($missing_regions)) : ?>
			<div class="notice notice-warning is-dismissible">
				<p><strong>⚠️ Attention :</strong> Les régions suivantes sont manquantes et doivent être créées d'abord :</p>
				<ul style="margin-left: 20px;">
					<?php foreach ($missing_regions as $region_name) : ?>
						<li><?php echo esc_html($region_name); ?></li>
					<?php endforeach; ?>
				</ul>
				<p>Veuillez d'abord créer toutes les régions françaises via la page <a href="<?php echo admin_url('edit.php?post_type=region&page=create-french-regions'); ?>">"Créer toutes les régions"</a>.</p>
			</div>
		<?php endif; ?>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Statistiques actuelles</h2>
			<ul>
				<li><strong>Total de départements dans la base :</strong> <?php echo count($all_departments); ?></li>
				<li><strong>Départements français à créer :</strong> <?php echo count($french_departments); ?></li>
				<li><strong>Départements déjà présents :</strong> <span style="color: #00a32a;"><?php echo count($french_departments) - count($missing_departments); ?></span></li>
				<li><strong>Départements manquants :</strong> <span style="color: <?php echo count($missing_departments) > 0 ? '#d63638' : '#00a32a'; ?>;"><?php echo count($missing_departments); ?></span></li>
			</ul>
			
			<?php if (!empty($missing_departments)) : ?>
				<p><strong>Départements qui seront créés (<?php echo count($missing_departments); ?>) :</strong></p>
				<ul style="margin-left: 20px; max-height: 300px; overflow-y: auto;">
					<?php foreach ($missing_departments as $dept_name) : ?>
						<li><?php echo esc_html($dept_name); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p style="color: #00a32a;"><strong>✅ Tous les départements français sont déjà présents dans la base de données.</strong></p>
			<?php endif; ?>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Action</h2>
			<p>Cette fonction va créer tous les <?php echo count($french_departments); ?> départements français qui n'existent pas encore dans la base de données.</p>
			<p>Chaque département sera automatiquement associé à sa région correspondante.</p>
			
			<?php if (!empty($missing_regions)) : ?>
				<div class="notice notice-error">
					<p><strong>❌ Impossible de créer les départements :</strong> Certaines régions sont manquantes. Veuillez d'abord créer toutes les régions.</p>
				</div>
			<?php else : ?>
				<form method="post" action="">
					<?php wp_nonce_field('create_departments_action', 'create_departments_nonce'); ?>
					
					<?php submit_button('Créer tous les départements français', 'primary', 'create_departments'); ?>
				</form>
			<?php endif; ?>
		</div>
		
		<div class="card" style="max-width: 800px; margin-top: 20px;">
			<h2>Note</h2>
			<p>Seuls les départements qui n'existent pas encore seront créés. Les départements existants ne seront pas modifiés.</p>
			<p>Chaque département créé sera automatiquement associé à sa région correspondante.</p>
			<p>Les départements seront créés avec uniquement leur nom (ex: "Ain", "Paris", "Guadeloupe").</p>
		</div>
	</div>
	<?php
}
