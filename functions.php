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
 * Fonction helper pour extraire l'ID d'un champ ACF relationship
 */
function urbanquest_extract_acf_relationship_id($field_value) {
	if (!$field_value) {
		return null;
	}
	
	if (is_object($field_value) && isset($field_value->ID)) {
		return $field_value->ID;
	}
	
	if (is_array($field_value) && !empty($field_value)) {
		$first_item = $field_value[0];
		if (is_object($first_item) && isset($first_item->ID)) {
			return $first_item->ID;
		}
		if (is_numeric($first_item)) {
			return $first_item;
		}
	}
	
	if (is_numeric($field_value)) {
		return $field_value;
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
	<nav class="urbanquest-breadcrumb" style="background: #1f2a37 !important; padding: 15px 0 !important; margin: 0 !important; border-bottom: 2px solid #00bbff !important; position: relative !important; z-index: 9999 !important; width: 100% !important; display: block !important;">
		<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
			<div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; color: white !important; font-size: 14px; line-height: 1.5;">
				<?php foreach ($items as $index => $item) : ?>
					<?php if ($index > 0) : ?>
						<span style="color: rgba(255, 255, 255, 0.6) !important; margin: 0 8px; font-size: 18px;">›</span>
					<?php endif; ?>
					<?php if (!empty($item['url'])) : ?>
						<a href="<?php echo esc_url($item['url']); ?>" style="color: #ffffff !important; text-decoration: none; font-weight: 500; transition: opacity 0.2s ease;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
							<?php echo esc_html($item['label']); ?>
						</a>
					<?php else : ?>
						<span style="color: #ffffff !important; font-weight: 700;">
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
	<div class="urbanquest-breadcrumb-simple" style="margin: 10px 0 20px 0; font-size: 14px; color: #666;">
		<?php foreach ($items as $index => $item) : ?>
			<?php if ($index > 0) : ?>
				<span style="margin: 0 6px; color: #999;">›</span>
			<?php endif; ?>
			<?php if (!empty($item['url'])) : ?>
				<a href="<?php echo esc_url($item['url']); ?>" style="color: #666; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
					<?php echo esc_html($item['label']); ?>
				</a>
			<?php else : ?>
				<span style="color: #333; font-weight: 500;">
					<?php echo esc_html($item['label']); ?>
				</span>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
	<?php
}
