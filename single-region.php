<?php
/**
 * The template for displaying single region posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

// Préparer les données SEO avant get_header()
if (have_posts()) {
	the_post();
	$region_id = get_the_ID();
	$region_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide - Optimisé avec "jeu de piste"
	if (empty($region_name_raw)) {
		$region_name = 'Nouvelle-Aquitaine';
		$region_title_seo = 'Jeu de piste en ' . esc_html($region_name) . ' | Urban Quest - Aventure connectée';
	} else {
		$region_name = $region_name_raw;
		$region_title_seo = 'Jeu de piste en ' . esc_html($region_name) . ' | Urban Quest - Aventure connectée';
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$region_description = get_field('description_region');
	if (empty($region_description)) {
		$region_description = get_the_content();
	}
	if (empty($region_description)) {
		$region_description = "Découvrez la région " . esc_html($region_name) . " à travers nos jeux de piste connectés Urban Quest. Ce jeu de piste innovant vous permet d'explorer les plus belles villes et sites historiques de " . esc_html($region_name) . " de manière ludique et interactive. Partez à l'aventure en famille ou entre amis pour résoudre des énigmes passionnantes et vivre une expérience de jeu de piste unique.";
	}
	
	// Meta description SEO (limité à 160 caractères)
	$region_meta_description = wp_strip_all_tags($region_description);
	if (strlen($region_meta_description) > 160) {
		$region_meta_description = substr($region_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph (1200x630px recommandé)
	$region_image_url = get_the_post_thumbnail_url($region_id, 'large');
	if (empty($region_image_url)) {
		$image_region = get_field('image_region');
		if ($image_region) {
			if (is_array($image_region) && isset($image_region['url'])) {
				$region_image_url = $image_region['url'];
			} elseif (is_string($image_region)) {
				$region_image_url = $image_region;
			} elseif (is_numeric($image_region)) {
				$region_image_url = wp_get_attachment_image_url($image_region, 'large');
			}
		}
	}
	if (empty($region_image_url)) {
		$region_image_url = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	}
	
	// Modifier le title via le filtre WordPress (meilleure pratique)
	add_filter('document_title_parts', function($title) use ($region_title_seo) {
		$title['title'] = $region_title_seo;
		return $title;
	}, 10);
	
	// Ajouter les balises meta SEO dans le head
	add_action('wp_head', function() use ($region_title_seo, $region_meta_description, $region_image_url) {
		// Meta description
		echo '<meta name="description" content="' . esc_attr($region_meta_description) . '" />' . "\n";
		
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr($region_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($region_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($region_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		
		// Twitter Cards
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($region_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($region_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($region_image_url) . '" />' . "\n";
		
		// Canonical
		echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
		
		// Robots
		echo '<meta name="robots" content="index, follow" />' . "\n";
	}, 1);
	
	// Ajouter le schéma JSON-LD CollectionPage/ItemList
	add_action('wp_head', function() use ($region_name, $region_meta_description, $region_image_url, $region_id) {
		// On récupère les jeux pour le schéma ItemList
		$games = array();
		$filtered_departements = array();
		
		try {
			$departements = get_posts(array(
				'post_type' => 'departement',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			if (!empty($departements)) {
				foreach ($departements as $departement) {
					$region_field = get_field('region', $departement->ID);
					$reg_id = is_object($region_field) && isset($region_field->ID) ? $region_field->ID : (is_numeric($region_field) ? $region_field : null);
					
					if ($reg_id == $region_id) {
						$filtered_departements[] = $departement;
					}
				}
			}
			
			// Récupérer les jeux (simplifié pour le schéma)
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => 20, // Limiter pour le schéma
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = is_object($city_field) && isset($city_field->ID) ? $city_field->ID : (is_numeric($city_field) ? $city_field : null);
				
				if ($city_id) {
					$departement_field = get_field('ville', $city_id);
					$departement_id = is_object($departement_field) && isset($departement_field->ID) ? $departement_field->ID : (is_numeric($departement_field) ? $departement_field : null);
					
					if ($departement_id) {
						foreach ($filtered_departements as $departement) {
							if ($departement->ID == $departement_id) {
								$games[] = $game;
								break;
							}
						}
					}
				}
			}
		} catch (Exception $e) {
			$games = array();
		}
		
		// Schéma CollectionPage avec ItemList
		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'CollectionPage',
			'name' => 'Jeu de piste Urban Quest en ' . esc_html($region_name) . ' - Aventure connectée',
			'description' => $region_meta_description,
			'url' => get_permalink(),
			'image' => $region_image_url,
			'about' => array(
				'@type' => 'State',
				'name' => $region_name
			),
			'publisher' => array(
				'@type' => 'Organization',
				'name' => 'Urban Quest',
				'url' => get_site_url()
			)
		);
		
		// Ajouter ItemList si des jeux sont disponibles
		if (!empty($games)) {
			$items = array();
			foreach ($games as $index => $game) {
				$game_url = get_permalink($game->ID);
				$game_title = get_the_title($game->ID);
				$game_image = get_the_post_thumbnail_url($game->ID, 'medium');
				
				$items[] = array(
					'@type' => 'ListItem',
					'position' => $index + 1,
					'item' => array(
						'@type' => 'Product',
						'name' => $game_title,
						'url' => $game_url,
						'image' => $game_image ?: $region_image_url
					)
				);
			}
			
			$schema['mainEntity'] = array(
				'@type' => 'ItemList',
				'numberOfItems' => count($games),
				'itemListElement' => $items
			);
		}
		
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		echo "\n" . '</script>' . "\n";
	}, 99);
	
	rewind_posts();
}

get_header();
?>

<?php
do_action( 'hestia_before_single_post_wrapper' );
?>

<div class="<?php echo hestia_layout(); ?>">
	<div class="blog-post blog-post-wrapper">
		<div class="container">

<?php if (have_posts()) : while (have_posts()) : the_post(); 
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
	
	/**
	 * Fonction helper pour récupérer tous les jeux de la France
	 * @return array Tableau de jeux WP_Post
	 */
	function get_france_games() {
		$games = array();
		
		try {
			// 1. Trouver le pays "France"
			$all_countries = get_posts(array(
				'post_type' => 'country',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$france_posts = array();
			foreach ($all_countries as $country) {
				if (stripos($country->post_title, 'France') !== false) {
					$france_posts[] = $country;
					break;
				}
			}
			
			if (empty($france_posts)) {
				return $games;
			}
			
			$france_id = $france_posts[0]->ID;
			
			// 2. Récupérer toutes les régions liées à la France
			// Selon acf.json : region.countries → country (relationship, return_format: object, max: 1)
			$all_regions = get_posts(array(
				'post_type' => 'region',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$region_ids = array();
			foreach ($all_regions as $region) {
				$country_field = get_field('countries', $region->ID);
				$country_id = extract_acf_relationship_id($country_field);
				
				if ($country_id == $france_id) {
					$region_ids[] = $region->ID;
				}
			}
			
			if (empty($region_ids)) {
				return $games;
			}
			
			// 3. Récupérer tous les départements de ces régions
			$all_departements = get_posts(array(
				'post_type' => 'departement',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$departement_ids = array();
			foreach ($all_departements as $departement) {
				$region_field = get_field('region', $departement->ID);
				$reg_id = extract_acf_relationship_id($region_field);
				
				if ($reg_id && in_array($reg_id, $region_ids)) {
					$departement_ids[] = $departement->ID;
				}
			}
			
			if (empty($departement_ids)) {
				return $games;
			}
			
			// 4. Récupérer toutes les villes de ces départements
			$all_villes = get_posts(array(
				'post_type' => 'ville',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			$villes_ids = array();
			foreach ($all_villes as $ville) {
				$ville_departement_field = get_field('ville', $ville->ID);
				$dep_id = extract_acf_relationship_id($ville_departement_field);
				
				if ($dep_id && in_array($dep_id, $departement_ids)) {
					$villes_ids[] = $ville->ID;
				}
			}
			
			if (empty($villes_ids)) {
				return $games;
			}
			
			// 5. Récupérer tous les jeux de ces villes
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = extract_acf_relationship_id($city_field);
				
				if ($city_id && in_array($city_id, $villes_ids)) {
					if (!in_array($game, $games, true)) {
						$games[] = $game;
					}
				}
			}
		} catch (Exception $e) {
			// En cas d'erreur, retourner un tableau vide
			$games = array();
		}
		
		return $games;
	}
	
	/**
	 * Fonction helper pour récupérer les données d'un jeu pour l'affichage dans les listes
	 * Utilise les champs ACF personnalisés avec fallback sur les valeurs par défaut
	 * @param WP_Post|int $game Le post du jeu ou son ID
	 * @return array Tableau avec les données du jeu (image, titre, description, payment_url, city_name)
	 */
	function get_game_display_data($game) {
		$game_id = is_object($game) ? $game->ID : $game;
		
		// Image : utilise image_principale ACF, sinon image_liste, sinon thumbnail, sinon image par défaut
		$image_principale = get_field('image_principale', $game_id);
		$game_image = '';
		if ($image_principale) {
			if (is_array($image_principale) && isset($image_principale['url'])) {
				$game_image = $image_principale['url'];
			} elseif (is_string($image_principale)) {
				$game_image = $image_principale;
			} elseif (is_numeric($image_principale)) {
				$game_image = wp_get_attachment_image_url($image_principale, 'medium');
			}
		}
		if (empty($game_image)) {
			$image_liste = get_field('image_liste', $game_id);
			if ($image_liste) {
				if (is_array($image_liste) && isset($image_liste['url'])) {
					$game_image = $image_liste['url'];
				} elseif (is_string($image_liste)) {
					$game_image = $image_liste;
				} elseif (is_numeric($image_liste)) {
					$game_image = wp_get_attachment_image_url($image_liste, 'medium');
				}
			}
		}
		if (empty($game_image)) {
			$game_image = get_the_post_thumbnail_url($game_id, 'medium');
		}
		if (empty($game_image)) {
			$game_image = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
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
	
	// Réutiliser les variables définies avant get_header()
	$region_id = get_the_ID();
	$region_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide
	if (empty($region_name_raw)) {
		$region_name = 'Nouvelle-Aquitaine';
	} else {
		$region_name = $region_name_raw;
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$region_description = get_field('description_region');
	if (empty($region_description)) {
		$region_description = get_the_content();
	}
	if (empty($region_description)) {
		$region_description = "Découvrez la région " . esc_html($region_name) . " à travers nos jeux de piste connectés Urban Quest. Ce jeu de piste innovant vous permet d'explorer les plus belles villes et sites historiques de " . esc_html($region_name) . " de manière ludique et interactive. Partez à l'aventure en famille ou entre amis pour résoudre des énigmes passionnantes et vivre une expérience de jeu de piste unique.";
	}
	
	// Récupérer tous les jeux de cette région via la chaîne de relations
	// Région → Départements → Villes → Jeux
	$games = array();
	$filtered_departements = array();
	
	try {
		// 1. Récupérer tous les départements liés à cette région
		$departements = get_posts(array(
			'post_type' => 'departement',
			'posts_per_page' => -1,
			'suppress_filters' => false
		));
		
		// Filtrer les départements qui ont cette région
		// Selon acf.json : departement.region → region (relationship, return_format: object, max: 1)
		if (!empty($departements)) {
			foreach ($departements as $departement) {
				$region_field = get_field('region', $departement->ID);
				$reg_id = extract_acf_relationship_id($region_field);
				
				if ($reg_id == $region_id) {
					$filtered_departements[] = $departement;
				}
			}
		}
		
		// 2. Pour chaque département, récupérer les villes qui ont ce département dans leur champ 'ville'
		// Selon acf.json : ville.ville → departement (relationship, return_format: object, max: 1)
		$villes_ids = array();
		$departement_ids = array();
		foreach ($filtered_departements as $departement) {
			$departement_ids[] = $departement->ID;
		}
		
		// Récupérer toutes les villes et vérifier si elles sont liées à nos départements
		$all_villes = get_posts(array(
			'post_type' => 'ville',
			'posts_per_page' => -1,
			'suppress_filters' => false
		));
		
		foreach ($all_villes as $ville) {
			$ville_departement_field = get_field('ville', $ville->ID);
			$dep_id = extract_acf_relationship_id($ville_departement_field);
			
			if ($dep_id && in_array($dep_id, $departement_ids)) {
				$villes_ids[] = $ville->ID;
			}
		}
		
		// 3. Pour chaque ville, récupérer les jeux
		// Selon acf.json : game.city → ville (relationship, return_format: object, max: 1)
		$villes_ids = array_unique($villes_ids);
		foreach ($villes_ids as $ville_id) {
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = extract_acf_relationship_id($city_field);
				
				if ($city_id == $ville_id) {
					if (!in_array($game, $games, true)) {
						$games[] = $game;
					}
				}
			}
		}
	} catch (Exception $e) {
		// En cas d'erreur, utiliser les jeux de la France
		$games = array();
	}
	
	// Si aucun jeu trouvé pour cette région, récupérer tous les jeux de la France
	if (empty($games)) {
		$games = get_france_games();
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-region-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
<!-- Breadcrumb Navigation -->
<?php 
					if (function_exists('urbanquest_display_breadcrumb_simple')) {
						urbanquest_display_breadcrumb_simple();
					}
					?>

					<!-- Titre -->
					<h1 style="margin-bottom: 30px;"><?php echo esc_html($region_name); ?></h1>
					
					<!-- Description -->
					<div class="region-description" style="margin-bottom: 60px;">
						<?php echo wpautop($region_description); ?>
					</div>
					
					<!-- Liste des jeux -->
					<?php if (!empty($games)) : ?>
						<h2 style="margin-bottom: 40px;">Jeu de piste en <?php echo esc_html($region_name); ?> - Nos aventures</h2>
						<div class="row">
							<?php 
							foreach ($games as $game) : 
								// Tous les jeux sont maintenant des vrais jeux (pas de fallback)
								$game_id = is_object($game) ? $game->ID : $game;
								$game_data = get_game_display_data($game);
								$game_image = $game_data['image'];
								$game_title = $game_data['title'];
								$game_excerpt = $game_data['excerpt'];
								$game_permalink = get_permalink($game_id);
							?>
							<div class="col-md-4" style="margin-bottom: 30px;">
								<div style="text-align: center;">
									<img src="<?php echo esc_url($game_image); ?>" alt="Jeu de piste connecté Urban Quest - <?php echo esc_attr($game_title); ?> à <?php echo esc_attr($game_data['city_name']); ?>" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;" loading="lazy" />
									<h3 style="margin: 10px 0;"><?php echo esc_html($game_title); ?></h3>
									<p style="margin-bottom: 20px;"><?php echo esc_html($game_excerpt); ?></p>
									<a href="<?php echo esc_url($game_permalink); ?>" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;">Découvrir le jeu</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<!-- Liste des départements -->
					<?php if (!empty($filtered_departements)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="margin-bottom: 30px;">Les départements de <?php echo esc_html($region_name); ?></h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($filtered_departements as $departement) : 
								$departement_id = $departement->ID;
								$departement_name = $departement->post_title;
								$departement_permalink = get_permalink($departement_id);
							?>
							<div class="col-md-4" style="margin-bottom: 20px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; padding: 20px; text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<h3 style="margin: 0 0 15px; font-size: 22px; color: #1f2a37;"><?php echo esc_html($departement_name); ?></h3>
									<a href="<?php echo esc_url($departement_permalink); ?>" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px; font-size: 14px;">
										Voir le jeu de piste du <?php echo esc_html($departement_name); ?>
									</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				</div>
			</div>
		</article>
<?php endwhile; endif; ?>

		</div>
	</div>
</div>

<?php
if ( ! is_singular( 'elementor_library' ) ) {
	do_action( 'hestia_blog_related_posts' );
}
?>
<div class="footer-wrapper">
<?php get_footer(); ?>
