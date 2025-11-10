<?php
/**
 * The template for displaying all single posts and attachments.
 *
 * @package Hestia
 * @since Hestia 1.0
 * 
 * NOTE: Ce projet est un child theme de Hestia
 * Les hooks WordPress doivent être dans functions.php du thème enfant pour fonctionner correctement
 */

// Supprimer complètement les métadonnées "publié par" et les articles similaires pour le post type "game"
// IMPORTANT: Ces hooks doivent être dans functions.php du thème pour fonctionner correctement
// Pour l'instant, on les met ici mais ils devraient être déplacés dans functions.php du thème enfant

// Utiliser template_redirect pour intercepter avant le rendu
add_action('template_redirect', function() {
	if (is_singular('game')) {
		// Supprimer toutes les actions liées aux métadonnées du thème Hestia
		remove_all_actions('hestia_single_post_meta');
		remove_all_actions('hestia_blog_post_meta');
		remove_all_actions('hestia_before_single_post_content');
		remove_all_actions('hestia_before_single_post_wrapper');
		remove_all_actions('hestia_after_single_post_title');
		remove_all_actions('hestia_after_post_title');
		remove_all_actions('hestia_single_post_content_before');
		remove_all_actions('hestia_single_post_content_after');
		
		// Supprimer la section "Articles similaires"
		remove_all_actions('hestia_blog_related_posts');
		remove_all_actions('hestia_related_posts');
		remove_all_actions('hestia_after_single_post_content');
		
		// Utiliser des filtres pour supprimer complètement la sortie des métadonnées
		add_filter('hestia_single_post_meta', '__return_empty_string', 999);
		add_filter('hestia_blog_post_meta', '__return_empty_string', 999);
		add_filter('hestia_posted_on', '__return_empty_string', 999);
		add_filter('hestia_show_related_posts', '__return_false', 999);
		
		// Supprimer aussi les fonctions WordPress génériques
		add_filter('the_date', '__return_empty_string', 999);
		add_filter('get_the_date', '__return_empty_string', 999);
		add_filter('the_author', '__return_empty_string', 999);
		add_filter('get_the_author', '__return_empty_string', 999);
		add_filter('the_time', '__return_empty_string', 999);
		add_filter('get_the_time', '__return_empty_string', 999);
	}
}, 1);

// ============================================================================
// SEO : Préparer les données SEO avant get_header()
// ============================================================================
if (have_posts()) {
	the_post();
	$game_id = get_the_ID();
	
	// Récupérer les données nécessaires pour le SEO
	$city_post = get_field('city');
	$ville_name = '';
	$ville_id = null;
	if ($city_post) {
		if (is_object($city_post) && isset($city_post->post_title)) {
			$ville_name = $city_post->post_title;
			$ville_id = $city_post->ID;
		} elseif (is_numeric($city_post)) {
			$ville_name = get_the_title($city_post);
			$ville_id = $city_post;
		} elseif (is_array($city_post) && !empty($city_post)) {
			$first_city = $city_post[0];
			if (is_object($first_city) && isset($first_city->post_title)) {
				$ville_name = $first_city->post_title;
				$ville_id = $first_city->ID;
			} elseif (is_numeric($first_city)) {
				$ville_name = get_the_title($first_city);
				$ville_id = $first_city;
			}
		}
	}
	
	// Récupérer la région pour le SEO
	$region_name = '';
	$departement_id = null;
	$region_id = null;
	if ($ville_id) {
		$departement_post = get_field('ville', $ville_id);
		$departement_id = is_object($departement_post) && isset($departement_post->ID) ? $departement_post->ID : (is_numeric($departement_post) ? $departement_post : null);
		
		if ($departement_id) {
			$region_post = get_field('region', $departement_id);
			$region_id = is_object($region_post) && isset($region_post->ID) ? $region_post->ID : (is_numeric($region_post) ? $region_post : null);
			
			if ($region_id) {
				$region_obj = get_post($region_id);
				if ($region_obj) {
					$region_name = $region_obj->post_title;
				}
			}
		}
	}
	
	// Titre SEO optimisé
	$game_title_seo = get_field('titre_seo');
	if (empty($game_title_seo)) {
		$game_title_seo = get_the_title() . ' - UrbanQuest';
		if (!empty($ville_name)) {
			$game_title_seo = 'Jeu de piste Urban Quest à ' . esc_html($ville_name) . ' - ' . get_the_title();
		}
	}
	
	// Meta description SEO
	$game_meta_description = get_field('description_principale');
	if (empty($game_meta_description)) {
		$game_meta_description = get_the_excerpt();
	}
	if (empty($game_meta_description)) {
		$game_meta_description = 'Découvrez le jeu de piste Urban Quest à ' . esc_html($ville_name) . '. Une expérience immersive qui mêle jeu de piste, exploration et esprit d\'équipe.';
	}
	$game_meta_description = wp_strip_all_tags($game_meta_description);
	if (strlen($game_meta_description) > 160) {
		$game_meta_description = substr($game_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph (1200x630px recommandé)
	$game_image_url = get_the_post_thumbnail_url($game_id, 'large');
	if (empty($game_image_url)) {
		$image_carte_offre = get_field('image_carte_offre');
		if ($image_carte_offre) {
			if (is_array($image_carte_offre) && isset($image_carte_offre['url'])) {
				$game_image_url = $image_carte_offre['url'];
			} elseif (is_string($image_carte_offre)) {
				$game_image_url = $image_carte_offre;
			} elseif (is_numeric($image_carte_offre)) {
				$game_image_url = wp_get_attachment_image_url($image_carte_offre, 'large');
			}
		}
	}
	if (empty($game_image_url)) {
		$game_image_url = get_site_url() . '/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
	}
	
	// Prix et durée pour Schema.org
	$prix = get_field('prix');
	if (empty($prix)) {
		$prix = '39€';
	}
	$prix_numeric = preg_replace('/[^0-9]/', '', $prix);
	
	$duree = get_field('duree');
	if (empty($duree)) {
		$duree = '60 minutes';
	}
	
	// Modifier le title via le filtre WordPress (meilleure pratique)
	add_filter('document_title_parts', function($title) use ($game_title_seo) {
		$title['title'] = $game_title_seo;
		return $title;
	}, 10);
	
	// Ajouter les meta tags Open Graph et Twitter Cards dans wp_head
	add_action('wp_head', function() use ($game_title_seo, $game_meta_description, $game_image_url) {
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr($game_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($game_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($game_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="product" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		
		// Twitter Cards
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($game_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($game_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($game_image_url) . '" />' . "\n";
	}, 1);
	
	// Ajouter le schéma JSON-LD Product
	add_action('wp_head', function() use ($prix_numeric, $duree, $ville_name, $region_name, $game_image_url, $game_meta_description) {
		$payment_url = get_field('payment_url');
		if (empty($payment_url)) {
			$payment_url = get_permalink();
		}
		
		// Récupérer les notes/avis (champs ACF optionnels avec valeurs par défaut)
		$rating_value = get_field('rating_value'); // Note sur 5 (ex: 4.8)
		$review_count = get_field('review_count'); // Nombre d'avis (ex: 254)
		
		// Valeurs par défaut si non définies (basées sur les données réelles observées)
		if (empty($rating_value)) {
			$rating_value = 4.8; // Note par défaut
		}
		if (empty($review_count)) {
			$review_count = 254; // Nombre d'avis par défaut
		}
		
		// S'assurer que les valeurs sont numériques
		$rating_value = floatval($rating_value);
		$review_count = intval($review_count);
		
		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => get_the_title(),
			'description' => $game_meta_description,
			'image' => $game_image_url,
			'offers' => array(
				'@type' => 'Offer',
				'price' => $prix_numeric,
				'priceCurrency' => 'EUR',
				'availability' => 'https://schema.org/InStock',
				'url' => $payment_url,
				'priceValidUntil' => date('Y-m-d', strtotime('+1 year'))
			),
			'brand' => array(
				'@type' => 'Organization',
				'name' => 'Urban Quest',
				'url' => get_site_url()
			)
		);
		
		// Ajouter les notes/avis si disponibles
		if ($rating_value > 0 && $review_count > 0) {
			$schema['aggregateRating'] = array(
				'@type' => 'AggregateRating',
				'ratingValue' => $rating_value,
				'reviewCount' => $review_count,
				'bestRating' => 5,
				'worstRating' => 1
			);
		}
		
		if (!empty($ville_name)) {
			$schema['locationCreated'] = array(
				'@type' => 'City',
				'name' => $ville_name
			);
		}
		
		if (!empty($region_name)) {
			$schema['areaServed'] = array(
				'@type' => 'State',
				'name' => $region_name
			);
		}
		
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		echo "\n" . '</script>' . "\n";
	}, 99);
	
	rewind_posts();
}

get_header();

do_action( 'hestia_before_single_post_wrapper' );
?>

<div class="<?php echo hestia_layout(); ?>">
	<div class="blog-post blog-post-wrapper">
		<div class="container">

<?php if (have_posts()) : while (have_posts()) : the_post(); 
	/**
	 * Fonction helper pour récupérer les données d'un jeu pour l'affichage dans les listes
	 * Utilise les champs ACF personnalisés avec fallback sur les valeurs par défaut
	 * @param WP_Post|int $game Le post du jeu ou son ID
	 * @return array Tableau avec les données du jeu (image, titre, description, payment_url, city_name)
	 */
	function get_game_display_data($game) {
		$game_id = is_object($game) ? $game->ID : $game;
		
		// Image : utilise image_liste ACF, sinon thumbnail, sinon image par défaut
		$image_liste = get_field('image_liste', $game_id);
		$game_image = '';
		if ($image_liste) {
			if (is_array($image_liste) && isset($image_liste['url'])) {
				$game_image = $image_liste['url'];
			} elseif (is_string($image_liste)) {
				$game_image = $image_liste;
			} elseif (is_numeric($image_liste)) {
				$game_image = wp_get_attachment_image_url($image_liste, 'medium');
			}
		}
		if (empty($game_image)) {
			$game_image = get_the_post_thumbnail_url($game_id, 'medium');
		}
		if (empty($game_image)) {
			$game_image = get_site_url() . '/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
		}
		
		// Titre : utilise titre_liste ACF, sinon post_title
		$titre_liste = get_field('titre_liste', $game_id);
		$game_title = !empty($titre_liste) ? $titre_liste : get_the_title($game_id);
		
		// Description : utilise description_liste ACF, sinon excerpt, sinon texte par défaut
		$description_liste = get_field('description_liste', $game_id);
		$game_excerpt = !empty($description_liste) ? $description_liste : get_the_excerpt($game_id);
		if (empty($game_excerpt)) {
			$game_excerpt = 'Découvrez ce jeu de piste unique dans cette ville.';
		}
		
		// URL de paiement
		$payment_url = get_field('payment_url', $game_id);
		if (empty($payment_url)) {
			$payment_url = get_permalink($game_id);
		}
		
		// Ville
		$related_city = get_field('city', $game_id);
		$related_city_name = '';
		if ($related_city) {
			if (is_object($related_city) && isset($related_city->post_title)) {
				$related_city_name = $related_city->post_title;
			} else {
				$city_id = extract_acf_relationship_id($related_city);
				if ($city_id) {
					$related_city_name = get_the_title($city_id);
				}
			}
		}
		
		return array(
			'image' => $game_image,
			'title' => $game_title,
			'excerpt' => $game_excerpt,
			'payment_url' => $payment_url,
			'city_name' => $related_city_name
		);
	}
	
	/**
	 * Fonction helper pour extraire l'ID d'un champ ACF relationship
	 * Selon acf.json, les champs relationship ont return_format: "object" et max: 1
	 * @param mixed $field_value Valeur du champ ACF (peut être objet, tableau ou ID)
	 * @return int|null ID extrait ou null
	 */
	function extract_acf_relationship_id($field_value) {
		if (!$field_value) {
			return null;
		}
		
		// Si c'est un objet WP_Post (format attendu selon ACF config)
		if (is_object($field_value) && isset($field_value->ID)) {
			return $field_value->ID;
		}
		
		// Si c'est un tableau (fallback pour compatibilité)
		if (is_array($field_value) && !empty($field_value)) {
			$first_item = $field_value[0];
			if (is_object($first_item) && isset($first_item->ID)) {
				return $first_item->ID;
			}
			if (is_numeric($first_item)) {
				return $first_item;
			}
		}
		
		// Si c'est directement un ID numérique
		if (is_numeric($field_value)) {
			return $field_value;
		}
		
		return null;
	}
	
	// Fonction helper pour récupérer le nom de la ville via la relation city
	// Selon acf.json : game.city → ville (relationship, return_format: object, max: 1)
	$city_post = get_field('city');
	$ville_name = '';
	$ville_id = null;
	if ($city_post) {
		// Si c'est un objet WP_Post (format attendu selon ACF config)
		if (is_object($city_post) && isset($city_post->post_title)) {
			$ville_name = $city_post->post_title;
			$ville_id = $city_post->ID;
		}
		// Si c'est un ID
		elseif (is_numeric($city_post)) {
			$ville_name = get_the_title($city_post);
			$ville_id = $city_post;
		}
		// Si c'est un tableau (fallback pour compatibilité)
		elseif (is_array($city_post) && !empty($city_post)) {
			$first_city = $city_post[0];
			if (is_object($first_city) && isset($first_city->post_title)) {
				$ville_name = $first_city->post_title;
				$ville_id = $first_city->ID;
			} elseif (is_numeric($first_city)) {
				$ville_name = get_the_title($first_city);
				$ville_id = $first_city;
			}
		}
	}
	
	// Fonction helper pour récupérer le nom de la région via la chaîne de relations : ville → département → région
	// Selon acf.json : ville.ville → departement (relationship, return_format: object, max: 1)
	// Selon acf.json : departement.region → region (relationship, return_format: object, max: 1)
	$region_name = '';
	$departement_id = null;
	$region_id = null;
	if ($ville_id) {
		// Récupérer le département depuis la ville
		$departement_post = get_field('ville', $ville_id);
		$departement_id = extract_acf_relationship_id($departement_post);
		
		// Récupérer la région depuis le département
		if ($departement_id) {
			$region_post = get_field('region', $departement_id);
			$region_id = extract_acf_relationship_id($region_post);
			
			if ($region_id) {
				$region_obj = get_post($region_id);
				if ($region_obj) {
					$region_name = $region_obj->post_title;
				}
			}
		}
	}
	
	/**
	 * Fonction helper pour récupérer les jeux pertinents selon la hiérarchie : ville → département → région
	 * Basé sur la configuration ACF (acf.json)
	 * @param int $current_game_id ID du jeu actuel à exclure
	 * @param int $ville_id ID de la ville
	 * @param int $departement_id ID du département
	 * @param int $region_id ID de la région
	 * @param int $limit Nombre maximum de jeux à retourner (défaut: 6)
	 * @return array Tableau de jeux WP_Post
	 */
	function get_related_games($current_game_id, $ville_id, $departement_id, $region_id, $limit = 6) {
		$related_games = array();
		$excluded_ids = array($current_game_id);
		
		// 1. Récupérer les jeux de la même ville
		// Selon acf.json : game.city → ville (relationship, return_format: object, max: 1)
		if ($ville_id) {
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => -1,
				'post__not_in' => $excluded_ids,
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = extract_acf_relationship_id($city_field);
				
				if ($city_id == $ville_id) {
					$related_games[] = $game;
					$excluded_ids[] = $game->ID;
					if (count($related_games) >= $limit) {
						return $related_games;
					}
				}
			}
		}
		
		// 2. Si pas assez, récupérer les jeux du même département
		// Selon acf.json : ville.ville → departement (relationship, return_format: object, max: 1)
		if (count($related_games) < $limit && $departement_id) {
			// Récupérer toutes les villes du département
			$villes_departement = get_posts(array(
				'post_type' => 'ville',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$villes_ids_departement = array();
			foreach ($villes_departement as $ville) {
				$ville_departement_field = get_field('ville', $ville->ID);
				$dep_id = extract_acf_relationship_id($ville_departement_field);
				
				if ($dep_id == $departement_id) {
					$villes_ids_departement[] = $ville->ID;
				}
			}
			
			// Récupérer les jeux de ces villes
			if (!empty($villes_ids_departement)) {
				$all_games = get_posts(array(
					'post_type' => 'game',
					'posts_per_page' => -1,
					'post__not_in' => $excluded_ids,
					'suppress_filters' => false
				));
				
				foreach ($all_games as $game) {
					$city_field = get_field('city', $game->ID);
					$city_id = extract_acf_relationship_id($city_field);
					
					if ($city_id && in_array($city_id, $villes_ids_departement)) {
						$related_games[] = $game;
						$excluded_ids[] = $game->ID;
						if (count($related_games) >= $limit) {
							return $related_games;
						}
					}
				}
			}
		}
		
		// 3. Si pas assez, récupérer les jeux de la même région
		// Selon acf.json : departement.region → region (relationship, return_format: object, max: 1)
		if (count($related_games) < $limit && $region_id) {
			// Récupérer tous les départements de la région
			$departements_region = get_posts(array(
				'post_type' => 'departement',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$departements_ids_region = array();
			foreach ($departements_region as $departement) {
				$region_field = get_field('region', $departement->ID);
				$reg_id = extract_acf_relationship_id($region_field);
				
				if ($reg_id == $region_id) {
					$departements_ids_region[] = $departement->ID;
				}
			}
			
			// Récupérer toutes les villes de ces départements
			$villes_ids_region = array();
			if (!empty($departements_ids_region)) {
				$all_villes = get_posts(array(
					'post_type' => 'ville',
					'posts_per_page' => -1,
					'suppress_filters' => false
				));
				
				foreach ($all_villes as $ville) {
					$ville_departement_field = get_field('ville', $ville->ID);
					$dep_id = extract_acf_relationship_id($ville_departement_field);
					
					if ($dep_id && in_array($dep_id, $departements_ids_region)) {
						$villes_ids_region[] = $ville->ID;
					}
				}
			}
			
			// Récupérer les jeux de ces villes
			if (!empty($villes_ids_region)) {
				$all_games = get_posts(array(
					'post_type' => 'game',
					'posts_per_page' => -1,
					'post__not_in' => $excluded_ids,
					'suppress_filters' => false
				));
				
				foreach ($all_games as $game) {
					$city_field = get_field('city', $game->ID);
					$city_id = extract_acf_relationship_id($city_field);
					
					if ($city_id && in_array($city_id, $villes_ids_region)) {
						$related_games[] = $game;
						$excluded_ids[] = $game->ID;
						if (count($related_games) >= $limit) {
							return $related_games;
						}
					}
				}
			}
		}
		
		return $related_games;
	}
	
	// Récupérer les jeux pertinents
	$related_games = get_related_games(get_the_ID(), $ville_id, $departement_id, $region_id, 6);
	
	// Récupérer les champs configurables avec valeurs par défaut
	$prix_original = get_field('prix_original');
	// Si pas de prix original défini, ne pas afficher de prix barré
	$afficher_prix_original = !empty($prix_original);
	
	$prix = get_field('prix');
	if (empty($prix)) {
		$prix = '39€';
	}
	
	$texte_offre = get_field('texte_offre');
	if (empty($texte_offre)) {
		$texte_offre = 'Offre du moment !';
	}
	
	$titre_offre = get_field('titre_offre');
	if (empty($titre_offre)) {
		$titre_offre = 'Offre du moment !';
	}
	
	$nombre_joueurs = get_field('nombre_joueurs');
	if (empty($nombre_joueurs)) {
		$nombre_joueurs = '2 – 5 joueurs';
	}
	
	$age_minimum = get_field('age_minimum');
	if (empty($age_minimum)) {
		$age_minimum = 'à partir de 8 ans';
	}
	
	$duree = get_field('duree');
	if (empty($duree)) {
		$duree = '60 minutes';
	}
	
	// Récupérer l'image de la carte d'offre avec fallback
	$image_carte_offre = get_field('image_carte_offre');
	$image_carte_offre_url = '';
	if ($image_carte_offre) {
		if (is_array($image_carte_offre) && isset($image_carte_offre['url'])) {
			$image_carte_offre_url = $image_carte_offre['url'];
		} elseif (is_string($image_carte_offre)) {
			$image_carte_offre_url = $image_carte_offre;
		} elseif (is_numeric($image_carte_offre)) {
			$image_carte_offre_url = wp_get_attachment_image_url($image_carte_offre, 'full');
		}
	}
	// Si pas d'image ACF, utiliser l'image par défaut
	if (empty($image_carte_offre_url)) {
		$image_carte_offre_url = get_site_url() . '/wp-content/uploads/2025/10/Group-10.png';
	}
	
	// Récupérer le titre principal
	$titre_principal = get_field('titre_principal');
	if (empty($titre_principal)) {
		$titre_principal = 'Jouez quand vous voulez à ' . $ville_name;
	} else {
		// Remplacer [ville] par le nom de la ville si présent dans le titre
		$titre_principal = str_replace('[ville]', $ville_name, $titre_principal);
	}
	
	// Récupérer la description principale (WYSIWYG)
	$description_principale = get_field('description_principale');
	
	// Récupérer les champs pour la section infos
	$titre_section_infos = get_field('titre_section_infos');
	if (empty($titre_section_infos)) {
		$titre_section_infos = "+ d'infos sur le jeu";
	}
	
	$tranche_age = get_field('tranche_age');
	if (empty($tranche_age)) {
		$tranche_age = "De 7 à 77 ans";
	}
	
	$distance = get_field('distance');
	if (empty($distance)) {
		$distance = "Env. 3,4km";
	}
	
	// Récupérer les niveaux des jauges (valeurs de 1 à 100)
	$jauge_fun = get_field('jauge_fun');
	if (empty($jauge_fun)) {
		$jauge_fun = 33;
	}
	$jauge_fun = max(1, min(100, intval($jauge_fun))); // S'assurer que c'est entre 1 et 100
	
	$jauge_histoire = get_field('jauge_histoire');
	if (empty($jauge_histoire)) {
		$jauge_histoire = 66;
	}
	$jauge_histoire = max(1, min(100, intval($jauge_histoire)));
	
	$jauge_reflexion = get_field('jauge_reflexion');
	if (empty($jauge_reflexion)) {
		$jauge_reflexion = 75;
	}
	$jauge_reflexion = max(1, min(100, intval($jauge_reflexion)));
	
	$jauge_culture_locale = get_field('jauge_culture_locale');
	if (empty($jauge_culture_locale)) {
		$jauge_culture_locale = 66;
	}
	$jauge_culture_locale = max(1, min(100, intval($jauge_culture_locale)));
	
	// Fonction helper pour générer le HTML de la jauge avec le rendu fidèle
	function render_jauge($valeur, $label) {
		$valeur = max(1, min(100, intval($valeur)));
		$pourcentage = $valeur . '%';
		
		ob_start();
		?>
		<div style="position: relative; width: 100%; height: 16px;">
			<!-- Barre de progression principale -->
			<div style="position: relative; width: 100%; height: 100%; border-radius: 10px; overflow: visible; background: white; ">
				<!-- Partie bleue remplie (bleu ciel clair #87CEEB) -->
				<div style="position: absolute; top: 0; left: 0; width: <?php echo esc_attr($pourcentage); ?>; height: 100%; background: #87CEEB; border-radius: 10px; z-index: 1;"></div>
				<!-- Partie blanche non remplie -->
				<div style="position: absolute; top: 0; left: <?php echo esc_attr($pourcentage); ?>; width: <?php echo esc_attr((100 - $valeur) . '%'); ?>; height: 100%; background: white; border-radius: <?php echo ($valeur <= 0) ? '10px' : '0 10px 10px 0'; ?>; z-index: 1;"></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-game-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
					<!-- Breadcrumb Navigation -->
					<?php 
						if (function_exists('urbanquest_display_breadcrumb_simple')) {
							urbanquest_display_breadcrumb_simple();
						}
						?>
					<div class="game-main-layout">
						<div class="game-main-content">
							<h2><?php echo esc_html($titre_principal); ?></h2>
							
							
							
							<?php 
							// Afficher la description principale (WYSIWYG)
							if (!empty($description_principale)) {
								echo '<div class="description-principale">' . wp_kses_post($description_principale) . '</div>';
							}
							?>

							<img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/10/notation-urbanquest-1024x219.png'); ?>" alt="Notation et avis Urban Quest - Jeu de piste à <?php echo esc_attr($ville_name); ?>" width="750" height="160" class="aligncenter size-large wp-image-26992" loading="lazy" />
							<h3>En groupe ?</h3>
							<p style="margin-top: 10px;">Pour les grands groupes nous proposons des options personnalisées sur demande pour rendre le jeu encore plus inoubliable, il suffit de nous <a href="<?php echo esc_url(get_site_url() . '/contact/'); ?>" rel="nofollow">contacter</a>.</p>

							<h3>Imaginés par nos game designers</h3>
							<p>Nos jeux sont conçus à la main par des game designers, en lien direct avec l'histoire et la géographie de <?php echo esc_html($ville_name); ?>... pour une expérience authentique et unique.</p>
						</div>

						<div class="game-sidebar-card">
							<section class="game-card-section">
								<!-- Image de fond bleue avec logo (image complète) -->
								<div class="game-card-image" style="background-image: url('<?php echo esc_url($image_carte_offre_url); ?>');">
									<!-- Badge de prix qui chevauche l'image en bas -->
									<div class="game-price-badge-wrapper">
										<div class="game-price-badge">
											<?php if ($afficher_prix_original) : ?>
												<span style="color: #1f2a37; font-size: 20px; text-decoration: line-through; font-weight: 500; opacity: 0.7;"><?php echo esc_html($prix_original); ?></span>
											<?php endif; ?>
											<span style="color: #1f2a37; font-size: 36px; font-weight: bold; line-height: 1;"><?php echo esc_html($prix); ?></span>
										</div>
									</div>
								</div>
								
								<!-- Titre "Offre du moment !" sous le badge -->
								<div style="text-align: center; margin-top: 35px; margin-bottom: 18px;">
									<span style="color: #1f2a37; font-size: 20px; font-weight: bold;"><?php echo esc_html($titre_offre); ?></span>
								</div>
								
								<img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/10/made-in-france-1.png'); ?>" alt="Made in France - Jeu de piste Urban Quest fabriqué en France" width="234" height="19" class="wp-image-26996 size-full aligncenter" loading="lazy" />
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="users"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;"><?php echo esc_html($nombre_joueurs); ?></div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;"><?php echo esc_html($age_minimum); ?></div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="clock"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;"><?php echo esc_html($duree); ?></div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">pour enchaîner les défis</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="smartphone"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">Votre smartphone</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">suffit pour jouer</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="trophy"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">Classement général</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Comparez votre score aux autres joueurs</div>
												</div></li>
										</ul>
									</li>
									<li style="list-style: none; display: center; align-items: flex-start; gap: 10px; width: 100%; margin: 0 auto; padding-top: 16px; padding-bottom: 24px;">
										<div style="text-align: center;"><a href="<?php echo esc_url(get_field('payment_url')); ?>" target="_blank" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;" rel="noopener sponsored">Réserve ton jeu d'exploration
										</a></div></li>
								</ul>
							</section>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<!-- ===================== SECTION VALEUR / POURQUOI NOUS ===================== -->
					<h2 style="text-align: center;">Pourquoi choisir Urban Quest à <?php echo esc_html($ville_name); ?> ?</h2>
					<img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/10/compo-photo-nice.png'); ?>" alt="Composition photo Urban Quest - Jeu de piste à <?php echo esc_attr($ville_name); ?>" width="561" height="101" class="center aligncenter wp-image-27010 size-full" loading="lazy" />
					<p style="text-align: center; max-width: 860px; margin: 0 auto;">Un savant mélange jeu de piste, chasse au trésor et visite insolite : observation, logique, audace et stratégie vous feront grimper au classement, tout en (re)découvrant <?php echo esc_html($ville_name); ?> et ses lieux emblématiques.</p>

					<div class="game-features-grid">
						<div class="game-feature-card"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="calendar-heart"></i>
							<strong>100% libre</strong>
							Vous lancez la session quand vous voulez, où vous voulez.</div>
						<div class="game-feature-card"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="smartphone"></i>
							<strong>Ultra simple</strong>
							Vos instructions de jeu par e-mail, votre smartphone… c'est tout.</div>
						<div class="game-feature-card"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="swords"></i>
							<strong>Fun &amp; challenge</strong>
							Défis variés, énigmes malignes, score et classement.</div>
					</div>
					<div style="text-align: center; margin: 18px 0 6px;">
						<?php 
						$payment_url_button = get_field('payment_url');
						$button_text_button = (empty($payment_url_button) || $payment_url_button === '#') ? 'Bientôt' : 'Réserve ton jeu d\'exploration';
						$button_href_button = (empty($payment_url_button) || $payment_url_button === '#') ? '#' : $payment_url_button;
						?>
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo ($button_href_button !== '#') ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text_button); ?>
						</a>
					</div>
					<div></div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<div class="game-info-section">
						<div class="game-info-sidebar">
							<section class="game-card-section">
								<h3 style="text-align: left;"><?php echo esc_html($titre_section_infos); ?></h3>
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;"><?php echo esc_html($tranche_age); ?></div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Tant que marcher 60 min n'est pas un challenger pour vous</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Nous avons aussi des jeux conçus spécialement pour les kids</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;"><?php echo esc_html($distance); ?></div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Pensez à prendre vos baskets!</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 0px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">Typologie de jeu</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Fun</div>
													<?php echo render_jauge($jauge_fun, 'Fun'); ?>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Histoire</div>
													<?php echo render_jauge($jauge_histoire, 'Histoire'); ?>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Réflexion</div>
													<?php echo render_jauge($jauge_reflexion, 'Réflexion'); ?>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Culture locale</div>
													<?php echo render_jauge($jauge_culture_locale, 'Culture locale'); ?>

												</div></li>
										</ul>
									</li>
								</ul>
							</section>
						</div>

						<div class="game-info-content">
							<?php
							// Récupérer les champs ACF de la ville avec valeurs par défaut
							$titre_section_terrain_de_jeu = '';
							$image_section_terrain_de_jeu_url = '';
							$description_section_terrain_de_jeu = '';
							$titre_section_jeu_unique = '';
							$description_section_jeu_unique = '';
							
							if ($ville_id) {
								// Titre section terrain de jeu
								$titre_section_terrain_de_jeu = get_field('titre_section_terrain_de_jeu', $ville_id);
								if (empty($titre_section_terrain_de_jeu)) {
									$titre_section_terrain_de_jeu = '[ville] devient votre terrain de jeu';
								}
								$titre_section_terrain_de_jeu = str_replace('[ville]', $ville_name, $titre_section_terrain_de_jeu);
								
								// Image section terrain de jeu
								$image_section_terrain_de_jeu = get_field('image_section_terrain_de_jeu', $ville_id);
								if ($image_section_terrain_de_jeu) {
									if (is_array($image_section_terrain_de_jeu) && isset($image_section_terrain_de_jeu['url'])) {
										$image_section_terrain_de_jeu_url = $image_section_terrain_de_jeu['url'];
									} elseif (is_string($image_section_terrain_de_jeu)) {
										$image_section_terrain_de_jeu_url = $image_section_terrain_de_jeu;
									} elseif (is_numeric($image_section_terrain_de_jeu)) {
										$image_section_terrain_de_jeu_url = wp_get_attachment_image_url($image_section_terrain_de_jeu, 'large');
									}
								}
								if (empty($image_section_terrain_de_jeu_url)) {
									$image_section_terrain_de_jeu_url = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
								}
								
								// Description section terrain de jeu
								$description_section_terrain_de_jeu = get_field('description_section_terrain_de_jeu', $ville_id);
								if (empty($description_section_terrain_de_jeu)) {
									$description_section_terrain_de_jeu = '<p style="margin: 10px 0;">Avec Urban Quest, oubliez les visites classiques : chaque rue peut cacher un indice, chaque monument peut être la clé d\'une énigme. Entre rires, stratégie et adrénaline, vous vivez une expérience intense où l\'observation et l\'esprit d\'équipe font toute la différence.</p><p style="margin: 10px 0;">Pendant 60 minutes, la ville s\'anime sous vos pas : explorez, déduisez, surprenez-vous… et laissez-vous porter par l\'énergie du jeu.</p><p style="margin: 10px 0;">En famille, entre amis ou pour un EVJF/EVG, préparez-vous à découvrir la ville autrement et à créer des souvenirs mémorables ✨</p>';
								}
								$description_section_terrain_de_jeu = str_replace('[ville]', $ville_name, $description_section_terrain_de_jeu);
								
								// Titre section jeu unique
								$titre_section_jeu_unique = get_field('titre_section_jeu_unique', $ville_id);
								if (empty($titre_section_jeu_unique)) {
									$titre_section_jeu_unique = 'Un jeu de piste unique à [ville]';
								}
								$titre_section_jeu_unique = str_replace('[ville]', $ville_name, $titre_section_jeu_unique);
								
								// Description section jeu unique
								$description_section_jeu_unique = get_field('description_section_jeu_unique', $ville_id);
								if (empty($description_section_jeu_unique)) {
									$description_section_jeu_unique = '<p style="margin: 10px 0;">Si vous cherchez une activité insolite à [ville], Urban Quest est le jeu parfait : une chasse au trésor moderne, ludique et connectée qui vous entraîne à travers les rues et les lieux emblématiques de [ville]. Idéal pour ceux qui veulent découvrir autrement [ville] en mêlant culture, divertissement et esprit de compétition.</p><p style="margin: 10px 0;">🔎 <em>Fun fact :</em> avec Urban Quest, c\'est à votre tour d\'apporter couleurs et énergie à [ville] en résolvant ses énigmes !</p>';
								}
								$description_section_jeu_unique = str_replace('[ville]', $ville_name, $description_section_jeu_unique);
							} else {
								// Fallback si pas de ville
								$titre_section_terrain_de_jeu = $ville_name . ' devient votre terrain de jeu';
								$image_section_terrain_de_jeu_url = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
								$description_section_terrain_de_jeu = '<p style="margin: 10px 0;">Avec Urban Quest, oubliez les visites classiques : chaque rue peut cacher un indice, chaque monument peut être la clé d\'une énigme. Entre rires, stratégie et adrénaline, vous vivez une expérience intense où l\'observation et l\'esprit d\'équipe font toute la différence.</p><p style="margin: 10px 0;">Pendant 60 minutes, la ville s\'anime sous vos pas : explorez, déduisez, surprenez-vous… et laissez-vous porter par l\'énergie du jeu.</p><p style="margin: 10px 0;">En famille, entre amis ou pour un EVJF/EVG, préparez-vous à découvrir la ville autrement et à créer des souvenirs mémorables ✨</p>';
								$titre_section_jeu_unique = 'Un jeu de piste unique à ' . $ville_name;
								$description_section_jeu_unique = '<p style="margin: 10px 0;">Si vous cherchez une activité insolite à ' . esc_html($ville_name) . ', Urban Quest est le jeu parfait : une chasse au trésor moderne, ludique et connectée qui vous entraîne à travers les rues et les lieux emblématiques de ' . esc_html($ville_name) . '. Idéal pour ceux qui veulent découvrir autrement ' . esc_html($ville_name) . ' en mêlant culture, divertissement et esprit de compétition.</p><p style="margin: 10px 0;">🔎 <em>Fun fact :</em> avec Urban Quest, c\'est à votre tour d\'apporter couleurs et énergie à ' . esc_html($ville_name) . ' en résolvant ses énigmes !</p>';
							}
							?>
							
							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_terrain_de_jeu); ?></h3>
							<div><img src="<?php echo esc_url($image_section_terrain_de_jeu_url); ?>" alt="<?php echo esc_attr($ville_name); ?> - Terrain de jeu Urban Quest" width="750" height="139" class="aligncenter size-large wp-image-26967" loading="lazy" /></div>
							<?php echo wp_kses_post($description_section_terrain_de_jeu); ?>

							<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_jeu_unique); ?></h3>
							<?php echo wp_kses_post($description_section_jeu_unique); ?>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					<p style="text-align: center;"><!-- ===================== COMMENT ÇA MARCHE (HOW-TO) ===================== --></p>

					<h2 style="text-align: center;">Comment se déroule une partie ?</h2>
					<p style="text-align: center;"><img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/08/newUQderoulement-2.png'); ?>" alt="Déroulement d'une partie Urban Quest - Comment jouer au jeu de piste" width="760" height="231" class="aligncenter size-full wp-image-26861" loading="lazy" /></p>
					<p class="p1" style="text-align: center;">Choisis ton parcours, pars à l'aventure dans la ville et mesure-toi aux autres équipes.</p>
					<p class="p1" style="text-align: center;">Une expérience fun, rapide à lancer et 100 % autonome !</p>
					<p style="text-align: center;">
						<?php 
						$payment_url_button = get_field('payment_url');
						$button_text_button = (empty($payment_url_button) || $payment_url_button === '#') ? 'Bientôt' : 'Réserve ton jeu d\'exploration';
						$button_href_button = (empty($payment_url_button) || $payment_url_button === '#') ? '#' : $payment_url_button;
						?>
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo ($button_href_button !== '#') ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text_button); ?>
						</a>
					</p>


					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<!-- ===================== JEUX QUI PEUVENT VOUS INTÉRESSER ===================== -->
					<?php if (!empty($related_games)) : ?>
						<h2 style="text-align: center; margin-bottom: 40px;">Jeux qui peuvent vous intéresser</h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($related_games as $related_game) : 
								$game_data = get_game_display_data($related_game);
							?>
							<div class="col-md-4" style="margin-bottom: 30px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<a href="<?php echo esc_url(get_permalink($related_game->ID)); ?>" style="text-decoration: none; color: inherit; display: block;">
										<img src="<?php echo esc_url($game_data['image']); ?>" alt="<?php echo esc_attr($game_data['title']); ?>" style="width: 100%; height: 200px; object-fit: cover;" />
										<div style="padding: 20px;">
											<h3 style="margin: 0 0 10px; font-size: 20px; color: #1f2a37;"><?php echo esc_html($game_data['title']); ?></h3>
											<?php if ($game_data['city_name']) : ?>
												<p style="margin: 0 0 10px; color: #6b7280; font-size: 14px; font-weight: 500;">
													<i style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;" data-lucide="map-pin"></i>
													<?php echo esc_html($game_data['city_name']); ?>
												</p>
											<?php endif; ?>
											<p style="margin: 0 0 15px; color: #6b7280; font-size: 14px; line-height: 1.5;"><?php echo esc_html(wp_trim_words($game_data['excerpt'], 20)); ?></p>
											<div style="text-align: center;">
												<span style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 8px 20px; border-radius: 999px; font-size: 14px;">
													Découvrir le jeu
												</span>
											</div>
										</div>
									</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					<!-- ===================== TABLE INFO CLÉ (SEO + Conversion) ===================== -->
					<h2>Informations clés</h2>
					<div class="info-table-wrapper">
						<table class="info-table">
						<tbody>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px; width: 35%;"><strong>Lieu</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html($ville_name); ?>, <?php echo esc_html($region_name); ?></td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Point de départ</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html(get_field('point_de_depart')); ?> (précisé dans les instructions de jeu)</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Durée</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html($duree); ?></td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Équipe</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html($nombre_joueurs); ?></td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Matériel</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;">Votre smartphone + connexion data</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Tarif</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><strong><?php echo esc_html($prix); ?> par équipe</strong></td>
							</tr>
						</tbody>
					</table>
					</div>
					<div style="text-align: center;">
						<?php 
						$payment_url_button = get_field('payment_url');
						$button_text_button = (empty($payment_url_button) || $payment_url_button === '#') ? 'Bientôt' : 'Réserve ton jeu d\'exploration';
						$button_href_button = (empty($payment_url_button) || $payment_url_button === '#') ? '#' : $payment_url_button;
						?>
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo ($button_href_button !== '#') ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text_button); ?>
						</a>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					
					

					<!-- ===================== FAQ (avec riche contenu & FAQ Schema) ===================== -->
					<h2>FAQ – Jeu de piste <?php echo esc_html($ville_name); ?></h2>
					<?php echo do_shortcode('[xyz-ihs snippet="FAQ"]'); ?>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<!-- ===================== FOOTER CTA + CONTACT ===================== -->
					<p style="font-size: 13px; color: #666; margin-top: 8px;">Besoin d'aide ? <a href="mailto:contact@urbanquest.fr">contact@urbanquest.fr</a> · <a href="tel:+33540248279">05 40 24 82 79</a></p>

				</div>
			</div>
		</article>
<?php endwhile; endif; ?>

		</div>
		</div>
		</div>
	</div>
</div>

<?php
if ( ! is_singular( 'elementor_library' ) ) {
	// Ne pas afficher les articles similaires pour le post type "game"
	if ( ! is_singular( 'game' ) ) {
		do_action( 'hestia_blog_related_posts' );
	}
}
?>
<div class="footer-wrapper">
<?php get_footer(); ?>


