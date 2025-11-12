<?php
/**
 * The template for displaying single departement posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

// Préparer les données SEO avant get_header()
if (have_posts()) {
	the_post();
	$departement_id = get_the_ID();
	$departement_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide - Optimisé avec "jeu de piste"
	if (empty($departement_name_raw)) {
		$departement_name = 'Gironde';
		$departement_title_seo = 'Jeu de piste dans le ' . esc_html($departement_name) . ' | Urban Quest - Aventure connectée';
	} else {
		$departement_name = $departement_name_raw;
		$departement_title_seo = 'Jeu de piste dans le ' . esc_html($departement_name) . ' | Urban Quest - Aventure connectée';
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$departement_description = get_field('description_departement');
	if (empty($departement_description)) {
		$departement_description = get_the_content();
	}
	if (empty($departement_description)) {
		$departement_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest dans le département " . esc_html($departement_name) . ". Ce jeu de piste innovant vous permet d'explorer nos différentes villes pour vivre une expérience unique et immersive. Parcourez les rues historiques, résolvez des énigmes passionnantes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Meta description SEO (limité à 160 caractères)
	$departement_meta_description = wp_strip_all_tags($departement_description);
	if (strlen($departement_meta_description) > 160) {
		$departement_meta_description = substr($departement_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph (1200x630px recommandé)
	$departement_image_url = get_the_post_thumbnail_url($departement_id, 'large');
	if (empty($departement_image_url)) {
		$departement_image_url = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	}
	
	// Modifier le title via le filtre WordPress (meilleure pratique)
	add_filter('document_title_parts', function($title) use ($departement_title_seo) {
		$title['title'] = $departement_title_seo;
		return $title;
	}, 10);
	
	// Ajouter les balises meta SEO dans le head
	add_action('wp_head', function() use ($departement_title_seo, $departement_meta_description, $departement_image_url) {
		// Meta description
		echo '<meta name="description" content="' . esc_attr($departement_meta_description) . '" />' . "\n";
		
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr($departement_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($departement_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($departement_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		
		// Twitter Cards
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($departement_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($departement_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($departement_image_url) . '" />' . "\n";
		
		// Canonical
		echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
		
		// Robots
		echo '<meta name="robots" content="index, follow" />' . "\n";
	}, 1);
	
	// Ajouter le schéma JSON-LD CollectionPage/ItemList
	add_action('wp_head', function() use ($departement_name, $departement_meta_description, $departement_image_url, $departement_id) {
		// On récupère les jeux pour le schéma ItemList
		$games = array();
		$filtered_villes = array();
		
		try {
			$villes = get_posts(array(
				'post_type' => 'ville',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			if (!empty($villes)) {
				foreach ($villes as $ville) {
					$ville_departement_field = get_field('ville', $ville->ID);
					$dep_id = is_object($ville_departement_field) && isset($ville_departement_field->ID) ? $ville_departement_field->ID : (is_numeric($ville_departement_field) ? $ville_departement_field : null);
					
					if ($dep_id == $departement_id) {
						$filtered_villes[] = $ville;
					}
				}
			}
			
			// Récupérer les jeux (simplifié pour le schéma)
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => 20, // Limiter pour le schéma
				'suppress_filters' => false
			));
			
			$villes_ids = array();
			foreach ($filtered_villes as $ville) {
				$villes_ids[] = $ville->ID;
			}
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = is_object($city_field) && isset($city_field->ID) ? $city_field->ID : (is_numeric($city_field) ? $city_field : null);
				
				if ($city_id && in_array($city_id, $villes_ids)) {
					$games[] = $game;
				}
			}
		} catch (Exception $e) {
			$games = array();
		}
		
		// Schéma CollectionPage avec ItemList
		$schema = array(
			'@context' => 'https://schema.org',
			'@type' => 'CollectionPage',
			'name' => 'Jeu de piste Urban Quest dans le ' . esc_html($departement_name) . ' - Aventure connectée',
			'description' => $departement_meta_description,
			'url' => get_permalink(),
			'image' => $departement_image_url,
			'about' => array(
				'@type' => 'AdministrativeArea',
				'name' => $departement_name
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
						'image' => $game_image ?: $departement_image_url
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
	$departement_id = get_the_ID();
	$departement_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide
	if (empty($departement_name_raw)) {
		$departement_name = 'Gironde';
	} else {
		$departement_name = $departement_name_raw;
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$departement_description = get_field('description_departement');
	if (empty($departement_description)) {
		$departement_description = get_the_content();
	}
	if (empty($departement_description)) {
		$departement_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest dans le département " . esc_html($departement_name) . ". Ce jeu de piste innovant vous permet d'explorer nos différentes villes pour vivre une expérience unique et immersive. Parcourez les rues historiques, résolvez des énigmes passionnantes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Récupérer tous les jeux de ce département (OPTIMISÉ avec meta_query)
	$games = urbanquest_get_games_by_departement($departement_id);
	
	// Récupérer les villes du département pour l'affichage
	$filtered_villes = array();
	if ($departement_id) {
		// Utiliser la relation bidirectionnelle si disponible
		$villes_ids = get_field('villes', $departement_id);
		if ($villes_ids) {
			if (!is_array($villes_ids)) {
				$villes_ids = [$villes_ids];
			}
			$filtered_villes = get_posts([
				'post_type' => 'ville',
				'post__in' => array_map('intval', $villes_ids),
				'posts_per_page' => -1,
				'post_status' => 'publish'
			]);
		}
		// Fallback avec meta_query
		if (empty($filtered_villes)) {
			$villes_ids = get_posts([
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
			if (!empty($villes_ids)) {
				$filtered_villes = get_posts([
					'post_type' => 'ville',
					'post__in' => $villes_ids,
					'posts_per_page' => -1
				]);
			}
		}
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-departement-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
<!-- Breadcrumb Navigation -->
<?php 
					if (function_exists('urbanquest_display_breadcrumb_simple')) {
						urbanquest_display_breadcrumb_simple();
					}
					?>

					<!-- Titre -->
					<h1 style="margin-bottom: 30px;"><?php echo esc_html($departement_name); ?></h1>
					
					<!-- Description -->
					<div class="departement-description" style="margin-bottom: 60px;">
						<?php echo wpautop($departement_description); ?>
					</div>
					
					<!-- Liste des jeux -->
					<?php if (!empty($games)) : ?>
						<h2 style="margin-bottom: 40px;">Jeu de piste dans le département <?php echo esc_html($departement_name); ?> - Nos aventures</h2>
						<?php urbanquest_display_games_grid($games, ['columns' => 4, 'layout' => 'simple', 'show_city' => true]); ?>
					<?php endif; ?>
					
					<!-- Liste des villes -->
					<?php if (!empty($filtered_villes)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="margin-bottom: 30px;">Les villes du département <?php echo esc_html($departement_name); ?></h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($filtered_villes as $ville) : 
								$ville_id = $ville->ID;
								$ville_name = $ville->post_title;
								$ville_permalink = get_permalink($ville_id);
							?>
							<div class="col-md-4" style="margin-bottom: 20px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; padding: 20px; text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<h3 style="margin: 0 0 15px; font-size: 22px; color: #1f2a37;"><?php echo esc_html($ville_name); ?></h3>
									<a href="<?php echo esc_url($ville_permalink); ?>" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px; font-size: 14px;">
										Voir le jeu de piste à <?php echo esc_html($ville_name); ?>
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