<?php
/**
 * The template for displaying single city (ville) posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

// Préparer les données SEO avant get_header()
if (have_posts()) {
	the_post();
	$ville_id = get_the_ID();
	$ville_name_raw = get_the_title();
	
	// Titre SEO par défaut si vide - Optimisé avec "jeu de piste"
	if (empty($ville_name_raw)) {
		$ville_name = 'Bordeaux';
		$ville_title_seo = 'Jeu de piste à ' . esc_html($ville_name) . ' | Urban Quest - Aventure connectée';
	} else {
		$ville_name = $ville_name_raw;
		$ville_title_seo = 'Jeu de piste à ' . esc_html($ville_name) . ' | Urban Quest - Aventure connectée';
	}
	
	// Description SEO
	$ville_description = get_the_content();
	if (empty($ville_description)) {
		$description_section_terrain_de_jeu = get_field('description_section_terrain_de_jeu');
		if (!empty($description_section_terrain_de_jeu)) {
			$ville_description = wp_strip_all_tags($description_section_terrain_de_jeu);
		} else {
			$ville_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest à " . esc_html($ville_name) . ". Ce jeu de piste innovant vous permet d'explorer la ville autrement en résolvant des énigmes passionnantes. Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
		}
	}
	
	// Meta description SEO (limité à 160 caractères)
	$ville_meta_description = wp_strip_all_tags($ville_description);
	if (strlen($ville_meta_description) > 160) {
		$ville_meta_description = substr($ville_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph (1200x630px recommandé)
	$ville_image_url = get_the_post_thumbnail_url($ville_id, 'large');
	if (empty($ville_image_url)) {
		$image_section_terrain_de_jeu = get_field('image_section_terrain_de_jeu');
		if ($image_section_terrain_de_jeu) {
			if (is_array($image_section_terrain_de_jeu) && isset($image_section_terrain_de_jeu['url'])) {
				$ville_image_url = $image_section_terrain_de_jeu['url'];
			} elseif (is_string($image_section_terrain_de_jeu)) {
				$ville_image_url = $image_section_terrain_de_jeu;
			} elseif (is_numeric($image_section_terrain_de_jeu)) {
				$ville_image_url = wp_get_attachment_image_url($image_section_terrain_de_jeu, 'large');
			}
		}
	}
	if (empty($ville_image_url)) {
		$ville_image_url = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
	}
	
	// Modifier le title via le filtre WordPress (meilleure pratique)
	add_filter('document_title_parts', function($title) use ($ville_title_seo) {
		$title['title'] = $ville_title_seo;
		return $title;
	}, 10);
	
	// Ajouter les balises meta SEO dans le head
	add_action('wp_head', function() use ($ville_title_seo, $ville_meta_description, $ville_image_url) {
		// Meta description
		echo '<meta name="description" content="' . esc_attr($ville_meta_description) . '" />' . "\n";
		
		// Open Graph
		echo '<meta property="og:title" content="' . esc_attr($ville_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($ville_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($ville_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		
		// Twitter Cards
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($ville_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($ville_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($ville_image_url) . '" />' . "\n";
		
		// Canonical
		echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
		
		// Robots
		echo '<meta name="robots" content="index, follow" />' . "\n";
	}, 1);
	
	// Ajouter le schéma JSON-LD CollectionPage/ItemList
	add_action('wp_head', function() use ($ville_name, $ville_meta_description, $ville_image_url, $ville_id) {
		// Récupérer les jeux pour le schéma ItemList
		$games = array();
		
		try {
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => 20, // Limiter pour le schéma
				'post_status' => 'publish',
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$city_id = is_object($city_field) && isset($city_field->ID) ? $city_field->ID : (is_numeric($city_field) ? $city_field : null);
				
				if ($city_id == $ville_id) {
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
			'name' => 'Jeu de piste Urban Quest à ' . esc_html($ville_name) . ' - Aventure connectée',
			'description' => $ville_meta_description,
			'url' => get_permalink(),
			'image' => $ville_image_url,
			'about' => array(
				'@type' => 'City',
				'name' => $ville_name
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
						'image' => $game_image ?: $ville_image_url
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
	
	$ville_id = get_the_ID();
	$ville_name = get_the_title();
	
	// Récupérer les champs ACF avec valeurs par défaut
	$titre_section_terrain_de_jeu = get_field('titre_section_terrain_de_jeu');
	if (empty($titre_section_terrain_de_jeu)) {
		$titre_section_terrain_de_jeu = '[ville] devient votre terrain de jeu';
	}
	// Remplacer [ville] par le nom de la ville
	$titre_section_terrain_de_jeu = str_replace('[ville]', $ville_name, $titre_section_terrain_de_jeu);
	
	$image_section_terrain_de_jeu = get_field('image_section_terrain_de_jeu');
	$image_section_terrain_de_jeu_url = '';
	if ($image_section_terrain_de_jeu) {
		if (is_array($image_section_terrain_de_jeu) && isset($image_section_terrain_de_jeu['url'])) {
			$image_section_terrain_de_jeu_url = $image_section_terrain_de_jeu['url'];
		} elseif (is_string($image_section_terrain_de_jeu)) {
			$image_section_terrain_de_jeu_url = $image_section_terrain_de_jeu;
		} elseif (is_numeric($image_section_terrain_de_jeu)) {
			$image_section_terrain_de_jeu_url = wp_get_attachment_image_url($image_section_terrain_de_jeu, 'large');
		}
	}
	// Image par défaut si vide
	if (empty($image_section_terrain_de_jeu_url)) {
		$image_section_terrain_de_jeu_url = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
	}
	
	$description_section_terrain_de_jeu = get_field('description_section_terrain_de_jeu');
	if (empty($description_section_terrain_de_jeu)) {
		$description_section_terrain_de_jeu = '<p style="margin: 10px 0;">Avec Urban Quest, oubliez les visites classiques : chaque rue peut cacher un indice, chaque monument peut être la clé d\'une énigme. Entre rires, stratégie et adrénaline, vous vivez une expérience intense où l\'observation et l\'esprit d\'équipe font toute la différence.</p><p style="margin: 10px 0;">Pendant 60 minutes, la ville s\'anime sous vos pas : explorez, déduisez, surprenez-vous… et laissez-vous porter par l\'énergie du jeu.</p><p style="margin: 10px 0;">En famille, entre amis ou pour un EVJF/EVG, préparez-vous à découvrir la ville autrement et à créer des souvenirs mémorables ✨</p>';
	}
	// Remplacer [ville] par le nom de la ville
	$description_section_terrain_de_jeu = str_replace('[ville]', $ville_name, $description_section_terrain_de_jeu);
	
	$titre_section_jeu_unique = get_field('titre_section_jeu_unique');
	if (empty($titre_section_jeu_unique)) {
		$titre_section_jeu_unique = 'Un jeu de piste unique à [ville]';
	}
	// Remplacer [ville] par le nom de la ville
	$titre_section_jeu_unique = str_replace('[ville]', $ville_name, $titre_section_jeu_unique);
	
	$description_section_jeu_unique = get_field('description_section_jeu_unique');
	if (empty($description_section_jeu_unique)) {
		$description_section_jeu_unique = '<p style="margin: 10px 0;">Si vous cherchez une activité insolite à [ville], Urban Quest est le jeu parfait : une chasse au trésor moderne, ludique et connectée qui vous entraîne à travers les rues et les lieux emblématiques de [ville]. Idéal pour ceux qui veulent découvrir autrement [ville] en mêlant culture, divertissement et esprit de compétition.</p><p style="margin: 10px 0;">🔎 <em>Fun fact :</em> avec Urban Quest, c\'est à votre tour d\'apporter couleurs et énergie à [ville] en résolvant ses énigmes !</p>';
	}
	// Remplacer [ville] par le nom de la ville
	$description_section_jeu_unique = str_replace('[ville]', $ville_name, $description_section_jeu_unique);
	
	// Récupérer tous les jeux de cette ville (OPTIMISÉ avec meta_query)
	$games = array();
	if ($ville_id) {
		// Utiliser la fonction optimisée avec meta_query
		$games = urbanquest_get_games_by_city($ville_id);
		
		// Fallback : utiliser la relation bidirectionnelle si disponible
		if (empty($games) && function_exists('get_field')) {
			$games_ids = get_field('games', $ville_id);
			if ($games_ids) {
				if (!is_array($games_ids)) {
					$games_ids = [$games_ids];
				}
				$games = get_posts([
					'post_type' => 'game',
					'post__in' => array_map('intval', $games_ids),
					'posts_per_page' => -1,
					'post_status' => 'publish'
				]);
			}
		}
	}
?>

		<article id="post-<?php the_ID(); ?>" <?php post_class('single-ville-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
                <?php 
						if (function_exists('urbanquest_display_breadcrumb_simple')) {
							urbanquest_display_breadcrumb_simple();
						}
						?>
					<!-- Titre -->
					<h1 style="margin-bottom: 30px;"><?php echo esc_html($ville_name); ?></h1>
					
					<!-- Contenu principal -->
					<div style="display: flex; flex-wrap: wrap; gap: 0;">
						<div style="">
							
							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_terrain_de_jeu); ?></h3>
							
							<div>
								<img src="<?php echo esc_url($image_section_terrain_de_jeu_url); ?>" alt="<?php echo esc_attr($ville_name); ?> - Terrain de jeu Urban Quest" width="750" height="139" class="aligncenter size-large wp-image-26967" loading="lazy" />
							</div>
							
							<?php echo wp_kses_post($description_section_terrain_de_jeu); ?>
							
							<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
							
							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_jeu_unique); ?></h3>
							
							<?php 
							// Optimiser la description avec "jeu de piste" si pas déjà présent
							$description_optimized = $description_section_jeu_unique;
							if (stripos($description_optimized, 'jeu de piste') === false && stripos($description_optimized, 'jeux de piste') === false) {
								$description_optimized = str_replace('[ville]', $ville_name, $description_optimized);
								$description_optimized = '<p style="margin: 10px 0;">Ce jeu de piste connecté vous permet de découvrir ' . esc_html($ville_name) . ' de manière ludique et interactive.</p>' . $description_optimized;
							}
							echo wp_kses_post($description_optimized); 
							?>
							
						</div>
					</div>
					
					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					
					<!-- Liste des jeux de la ville -->
					<?php if (!empty($games)) : ?>
						<h2 style="text-align: center; margin-bottom: 40px;">Jeu de piste à <?php echo esc_html($ville_name); ?> - Nos aventures</h2>
						<?php urbanquest_display_games_grid($games, ['columns' => 3, 'show_city' => false]); ?>
					<?php else : ?>
						<!-- Message SEO-friendly si aucun jeu -->
						<div style="text-align: center; padding: 60px 20px; background: #F7F9FC; border-radius: 12px; margin-bottom: 60px;">
							<h2 style="margin-bottom: 20px; color: #1f2a37;">Jeu de piste à <?php echo esc_html($ville_name); ?> - Bientôt disponible</h2>
							<p style="font-size: 18px; color: #6b7280; line-height: 1.6; max-width: 800px; margin: 0 auto 30px;">
								Urban Quest prépare actuellement un jeu de piste connecté pour découvrir <?php echo esc_html($ville_name); ?> autrement. 
								Ce jeu de piste innovant vous permettra d'explorer les lieux emblématiques de la ville tout en résolvant des énigmes passionnantes. Nos game designers travaillent sur des parcours uniques pour vous offrir une expérience de jeu de piste mémorable.
							</p>
							<p style="font-size: 16px; color: #6b7280; line-height: 1.6; max-width: 800px; margin: 0 auto 30px;">
								Si vous souhaitez être informé dès que ce jeu de piste sera disponible à <?php echo esc_html($ville_name); ?>, 
								<a href="<?php echo esc_url(get_site_url() . '/contact/'); ?>" style="color: #00bbff; text-decoration: underline;">contactez-nous</a> ou 
								suivez-nous sur nos réseaux sociaux pour rester informé de nos nouveautés.
							</p>
							<p style="font-size: 16px; color: #6b7280; line-height: 1.6; max-width: 800px; margin: 0 auto;">
								En attendant, découvrez nos autres jeux de piste disponibles dans d'autres villes de France !
							</p>
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

