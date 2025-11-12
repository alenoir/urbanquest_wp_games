<?php
/**
 * The template for displaying single country (pays) posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

// Préparer les données SEO avant get_header()
if (have_posts()) {
	the_post();
	$pays_id = get_the_ID();
	$pays_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide - Optimisé avec "jeu de piste"
	if (empty($pays_name_raw)) {
		$pays_name = 'France';
		$pays_title_seo = 'Jeu de piste en ' . esc_html($pays_name) . ' | Urban Quest - Aventure connectée';
	} else {
		$pays_name = $pays_name_raw;
		$pays_title_seo = 'Jeu de piste en ' . esc_html($pays_name) . ' | Urban Quest - Aventure connectée';
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$pays_description = get_field('description_pays');
	if (empty($pays_description)) {
		$pays_description = get_the_content();
	}
	if (empty($pays_description)) {
		$pays_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest en " . esc_html($pays_name) . ". Ce jeu de piste innovant vous permet d'explorer nos différentes régions et villes pour vivre une expérience unique et immersive. Parcourez les rues, résolvez des énigmes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Meta description SEO (limité à 160 caractères)
	$pays_meta_description = wp_strip_all_tags($pays_description);
	if (strlen($pays_meta_description) > 160) {
		$pays_meta_description = substr($pays_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph (1200x630px recommandé)
	$pays_image_url = get_the_post_thumbnail_url($pays_id, 'large');
	if (empty($pays_image_url)) {
		$pays_image_url = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	}
	
	// Modifier le title via le filtre WordPress (meilleure pratique)
	add_filter('document_title_parts', function($title) use ($pays_title_seo) {
		$title['title'] = $pays_title_seo;
		return $title;
	}, 10);
	
	// Ajouter les balises meta SEO dans le head
	add_action('wp_head', function() use ($pays_title_seo, $pays_meta_description, $pays_image_url) {
		// Meta description
		echo '<meta name="description" content="' . esc_attr($pays_meta_description) . '" />' . "\n";
		
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr($pays_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($pays_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($pays_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		
		// Twitter Cards
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($pays_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($pays_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($pays_image_url) . '" />' . "\n";
		
		// Canonical
		echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
		
		// Robots
		echo '<meta name="robots" content="index, follow" />' . "\n";
	}, 1);
	
	// Ajouter le schéma JSON-LD CollectionPage/ItemList
	add_action('wp_head', function() use ($pays_name, $pays_meta_description, $pays_image_url, $pays_id) {
		// On récupère les jeux pour le schéma ItemList
		$games = array();
		$filtered_regions = array();
		
		try {
			$regions = get_posts(array(
				'post_type' => 'region',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			if (!empty($regions)) {
				foreach ($regions as $region) {
					$country_field = get_field('countries', $region->ID);
					$country_id = is_object($country_field) && isset($country_field->ID) ? $country_field->ID : (is_numeric($country_field) ? $country_field : null);
					
					if ($country_id == $pays_id) {
						$filtered_regions[] = $region;
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
						$region_field = get_field('region', $departement_id);
						$region_id = is_object($region_field) && isset($region_field->ID) ? $region_field->ID : (is_numeric($region_field) ? $region_field : null);
						
						if ($region_id) {
							foreach ($filtered_regions as $region) {
								if ($region->ID == $region_id) {
									$games[] = $game;
									break;
								}
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
			'name' => 'Jeu de piste Urban Quest en ' . esc_html($pays_name) . ' - Aventure connectée',
			'description' => $pays_meta_description,
			'url' => get_permalink(),
			'image' => $pays_image_url,
			'about' => array(
				'@type' => 'Country',
				'name' => $pays_name
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
						'image' => $game_image ?: $pays_image_url
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
	$pays_id = get_the_ID();
	$pays_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide
	if (empty($pays_name_raw)) {
		$pays_name = 'France';
	} else {
		$pays_name = $pays_name_raw;
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$pays_description = get_field('description_pays');
	if (empty($pays_description)) {
		$pays_description = get_the_content();
	}
	if (empty($pays_description)) {
		$pays_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest en " . esc_html($pays_name) . ". Ce jeu de piste innovant vous permet d'explorer nos différentes régions et villes pour vivre une expérience unique et immersive. Parcourez les rues, résolvez des énigmes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Récupérer tous les jeux de ce pays (OPTIMISÉ avec meta_query)
	$games = urbanquest_get_games_by_country($pays_id);
	
	// Récupérer les régions du pays pour l'affichage
	$filtered_regions = array();
	if ($pays_id) {
		// Utiliser la relation bidirectionnelle si disponible
		$regions_ids = get_field('regions', $pays_id);
		if ($regions_ids) {
			if (!is_array($regions_ids)) {
				$regions_ids = [$regions_ids];
			}
			$filtered_regions = get_posts([
				'post_type' => 'region',
				'post__in' => array_map('intval', $regions_ids),
				'posts_per_page' => -1,
				'post_status' => 'publish'
			]);
		}
		// Fallback avec meta_query
		if (empty($filtered_regions)) {
			$regions_ids = get_posts([
				'post_type' => 'region',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'meta_query' => [
					[
						'key' => 'countries',
						'value' => '"' . $pays_id . '"',
						'compare' => 'LIKE'
					]
				]
			]);
			if (!empty($regions_ids)) {
				$filtered_regions = get_posts([
					'post_type' => 'region',
					'post__in' => $regions_ids,
					'posts_per_page' => -1
				]);
			}
		}
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-pays-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
<!-- Breadcrumb Navigation -->
<?php 
					if (function_exists('urbanquest_display_breadcrumb_simple')) {
						urbanquest_display_breadcrumb_simple();
					}
					?>

					<!-- Titre -->
					<h1 style="margin-bottom: 30px;"><?php echo esc_html($pays_name); ?></h1>
					
					<!-- Description -->
					<div class="pays-description" style="margin-bottom: 60px;">
						<?php echo wpautop($pays_description); ?>
					</div>
					
					<!-- Liste des jeux -->
					<?php if (!empty($games)) : ?>
						<h2 style="margin-bottom: 40px;">Jeu de piste en <?php echo esc_html($pays_name); ?> - Nos aventures</h2>
						<?php urbanquest_display_games_grid($games, ['columns' => 3, 'layout' => 'simple', 'show_city' => true]); ?>
					<?php endif; ?>
					
					<!-- Liste des régions -->
					<?php if (!empty($filtered_regions)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="margin-bottom: 30px;">Les régions de <?php echo esc_html($pays_name); ?></h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($filtered_regions as $region) : 
								$region_id = $region->ID;
								$region_name = $region->post_title;
								$region_permalink = get_permalink($region_id);
							?>
							<div class="col-md-4" style="margin-bottom: 20px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; padding: 20px; text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<h3 style="margin: 0 0 15px; font-size: 22px; color: #1f2a37;"><?php echo esc_html($region_name); ?></h3>
									<a href="<?php echo esc_url($region_permalink); ?>" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px; font-size: 14px;">
										Voir le jeu de piste de <?php echo esc_html($region_name); ?>
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