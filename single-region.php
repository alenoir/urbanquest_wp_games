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
	// Utiliser les fonctions centralisées de functions.php au lieu de les dupliquer
	
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
	
	// Récupérer tous les jeux de cette région (OPTIMISÉ avec meta_query)
	$games = urbanquest_get_games_by_region($region_id);
	
	// Récupérer les départements de la région pour l'affichage
	$filtered_departements = array();
	if ($region_id) {
		// Utiliser la relation bidirectionnelle si disponible
		$departements_ids = get_field('departements', $region_id);
		if ($departements_ids) {
			if (!is_array($departements_ids)) {
				$departements_ids = [$departements_ids];
			}
			$filtered_departements = get_posts([
				'post_type' => 'departement',
				'post__in' => array_map('intval', $departements_ids),
				'posts_per_page' => -1,
				'post_status' => 'publish'
			]);
		}
		// Fallback avec meta_query
		if (empty($filtered_departements)) {
			$departements_ids = get_posts([
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
			if (!empty($departements_ids)) {
				$filtered_departements = get_posts([
					'post_type' => 'departement',
					'post__in' => $departements_ids,
					'posts_per_page' => -1
				]);
			}
		}
		
		// Filtrer pour ne garder que les départements qui ont des villes
		if (!empty($filtered_departements)) {
			$departements_with_villes = array();
			foreach ($filtered_departements as $departement) {
				$departement_id = $departement->ID;
				$has_villes = false;
				
				// Vérifier via la relation bidirectionnelle
				$villes_ids = get_field('villes', $departement_id);
				if ($villes_ids) {
					// Convertir en tableau si c'est un ID unique
					if (!is_array($villes_ids)) {
						$villes_ids = [$villes_ids];
					}
					if (!empty($villes_ids)) {
						$has_villes = true;
					}
				}
				
				// Fallback : vérifier via meta_query si pas trouvé via relation bidirectionnelle
				if (!$has_villes) {
					$villes_count = get_posts([
						'post_type' => 'ville',
						'posts_per_page' => 1,
						'fields' => 'ids',
						'meta_query' => [
							[
								'key' => 'ville',
								'value' => '"' . $departement_id . '"',
								'compare' => 'LIKE'
							]
						]
					]);
					
					if (!empty($villes_count)) {
						$has_villes = true;
					}
				}
				
				if ($has_villes) {
					$departements_with_villes[] = $departement;
				}
			}
			$filtered_departements = $departements_with_villes;
		}
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
					<h1 class="urbanquest-page-title"><?php echo esc_html($region_name); ?></h1>
					
					<!-- Description -->
					<div class="region-description urbanquest-section-description">
						<?php echo wpautop($region_description); ?>
					</div>
					
					<!-- Liste des jeux -->
					<?php if (!empty($games)) : ?>
						<h2 class="urbanquest-section-title">Jeu de piste en <?php echo esc_html($region_name); ?> - Nos aventures</h2>
						<?php urbanquest_display_games_grid($games, ['columns' => 4, 'layout' => 'simple', 'show_city' => true]); ?>
					<?php endif; ?>
					
					<!-- Section Autres jeux (anciennes pages) -->
					<?php 
					$autres_jeux = get_field('autres_jeux', $region_id);
					if (!empty($autres_jeux) && is_array($autres_jeux)) : 
					?>
						<hr class="urbanquest-section-divider" />
						<h2 class="urbanquest-section-title-left">Autres jeux</h2>
						<div class="row urbanquest-games-grid">
							<?php foreach ($autres_jeux as $item) : 
								// Récupérer la page depuis le répéteur ACF
								$page = isset($item['page']) ? $item['page'] : null;
								
								// Gérer le cas où c'est un objet WP_Post ou un ID
								if (is_numeric($page)) {
									$page_id = intval($page);
									$page_obj = get_post($page_id);
									if (!$page_obj) {
										continue;
									}
								} elseif (is_object($page) && isset($page->ID)) {
									$page_id = $page->ID;
									$page_obj = $page;
								} else {
									continue;
								}
								
								$page_title = get_the_title($page_id);
								$page_permalink = get_permalink($page_id);
								$page_excerpt = get_the_excerpt($page_id);
								if (empty($page_excerpt)) {
									$page_content = get_post_field('post_content', $page_id);
									// Enlever le HTML du contenu avant de créer l'extrait
									$page_content = wp_strip_all_tags($page_content);
									$page_excerpt = wp_trim_words($page_content, 20);
								}
								if (empty($page_excerpt)) {
									$page_excerpt = 'Découvrez ce jeu de piste unique.';
								}
								// Enlever toutes les balises HTML de l'extrait
								$page_excerpt = wp_strip_all_tags($page_excerpt);
								// Limiter l'extrait à 20 mots maximum pour uniformiser les hauteurs
								$page_excerpt = wp_trim_words($page_excerpt, 20, '...');
								
								// Récupérer l'image de la page
								$page_image = get_the_post_thumbnail_url($page_id, 'medium');
								if (empty($page_image)) {
									$page_image = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
								}
							?>
							<div class="col-md-3 col-sm-6 col-xs-12">
								<div class="urbanquest-game-card">
									<a href="<?php echo esc_url($page_permalink); ?>">
										<div class="urbanquest-game-card-image-wrapper">
											<img 
												src="<?php echo esc_url($page_image); ?>" 
												alt="<?php echo esc_attr($page_title); ?>" 
												class="urbanquest-game-card-image"
												loading="lazy" 
											/>
										</div>
										<div class="urbanquest-game-card-content">
											<h3 class="urbanquest-game-card-title"><?php echo esc_html($page_title); ?></h3>
											<?php if (!empty($page_excerpt)) : ?>
												<p class="urbanquest-game-card-excerpt"><?php echo esc_html($page_excerpt); ?></p>
											<?php endif; ?>
											<div class="urbanquest-game-card-footer">
												<span class="urbanquest-game-card-button">
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
					
					<!-- Liste des départements -->
					<?php if (!empty($filtered_departements)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="margin-bottom: 30px;">Découvrez nos jeux de piste dans les départements de <?php echo esc_html($region_name); ?></h2>
						<ul style="margin-bottom: 60px; list-style: none; padding: 0;">
							<?php foreach ($filtered_departements as $departement) : 
								$departement_id = $departement->ID;
								$departement_name = $departement->post_title;
								$departement_permalink = get_permalink($departement_id);
							?>
							<li style="margin-bottom: 10px;">
								<a href="<?php echo esc_url($departement_permalink); ?>" style="color: #00bbff; text-decoration: none; font-size: 16px;">
									Les jeux de piste en <?php echo esc_html($departement_name); ?>
								</a>
							</li>
							<?php endforeach; ?>
						</ul>
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
