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
	
	// Titre SEO par défaut si vide
	if (empty($pays_name_raw)) {
		$pays_name = 'France';
		$pays_title_seo = 'Jeux de piste Urban Quest en France - Découvrez nos aventures';
	} else {
		$pays_name = $pays_name_raw;
		$pays_title_seo = 'Jeux de piste Urban Quest en ' . esc_html($pays_name) . ' - Découvrez nos aventures';
	}
	
	// Valeurs par défaut pour les champs ACF avec textes SEO optimisés
	$pays_description = get_field('description_pays');
	if (empty($pays_description)) {
		$pays_description = get_the_content();
	}
	if (empty($pays_description)) {
		$pays_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest en " . esc_html($pays_name) . ". Explorez nos différentes régions et villes pour vivre une expérience unique et immersive. Parcourez les rues, résolvez des énigmes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Meta description SEO (limité à 160 caractères)
	$pays_meta_description = wp_strip_all_tags($pays_description);
	if (strlen($pays_meta_description) > 160) {
		$pays_meta_description = substr($pays_meta_description, 0, 157) . '...';
	}
	
	// Ajouter les balises meta SEO dans le head
	add_action('wp_head', function() use ($pays_title_seo, $pays_meta_description) {
		echo '<meta name="description" content="' . esc_attr($pays_meta_description) . '" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr($pays_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($pays_meta_description) . '" />' . "\n";
		echo '<title>' . esc_html($pays_title_seo) . '</title>' . "\n";
	}, 1);
	
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
			$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
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
		$pays_description = "Découvrez les meilleurs jeux de piste connectés Urban Quest en " . esc_html($pays_name) . ". Explorez nos différentes régions et villes pour vivre une expérience unique et immersive. Parcourez les rues, résolvez des énigmes et découvrez le patrimoine local en vous amusant. Urban Quest vous propose des aventures adaptées à tous les âges, à faire en famille ou entre amis.";
	}
	
	// Récupérer tous les jeux de ce pays via la chaîne de relations
	// Pays → Régions → Départements → Villes → Jeux
	$games = array();
	$filtered_regions = array();
	
	try {
		// 1. Récupérer toutes les régions liées à ce pays
		// Selon acf.json : region.countries → country (relationship, return_format: object, max: 1)
		$regions = get_posts(array(
			'post_type' => 'region',
			'posts_per_page' => -1,
			'suppress_filters' => false
		));
		
		// Filtrer les régions qui ont ce pays
		if (!empty($regions)) {
			foreach ($regions as $region) {
				$country_field = get_field('countries', $region->ID);
				$country_id = extract_acf_relationship_id($country_field);
				
				if ($country_id == $pays_id) {
					$filtered_regions[] = $region;
				}
			}
		}
		
		// 2. Pour chaque région, récupérer les départements
		$departement_ids = array();
		foreach ($filtered_regions as $region) {
			$region_id = $region->ID;
			
			// Récupérer tous les départements et vérifier s'ils sont liés à cette région
			$all_departements = get_posts(array(
				'post_type' => 'departement',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			foreach ($all_departements as $departement) {
				$region_field = get_field('region', $departement->ID);
				$reg_id = extract_acf_relationship_id($region_field);
				
				if ($reg_id == $region_id) {
					if (!in_array($departement->ID, $departement_ids)) {
						$departement_ids[] = $departement->ID;
					}
				}
			}
		}
		
		// 3. Pour chaque département, récupérer les villes
		$villes_ids = array();
		foreach ($departement_ids as $departement_id) {
			$all_villes = get_posts(array(
				'post_type' => 'ville',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			foreach ($all_villes as $ville) {
				$ville_departement_field = get_field('ville', $ville->ID);
				$dep_id = extract_acf_relationship_id($ville_departement_field);
				
				if ($dep_id == $departement_id) {
					if (!in_array($ville->ID, $villes_ids)) {
						$villes_ids[] = $ville->ID;
					}
				}
			}
		}
		
		// 4. Pour chaque ville, récupérer les jeux
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
		// En cas d'erreur, utiliser un tableau vide
		$games = array();
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
						<h2 style="margin-bottom: 40px;">Les jeux de piste en <?php echo esc_html($pays_name); ?></h2>
						<div class="row">
							<?php 
							foreach ($games as $game) : 
								$game_id = is_object($game) ? $game->ID : $game;
								$game_data = get_game_display_data($game);
								$game_image = $game_data['image'];
								$game_title = $game_data['title'];
								$game_excerpt = $game_data['excerpt'];
								$game_permalink = get_permalink($game_id);
							?>
							<div class="col-md-4" style="margin-bottom: 30px;">
								<div style="text-align: center;">
									<img src="<?php echo esc_url($game_image); ?>" alt="<?php echo esc_attr($game_title); ?>" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;" />
									<h3 style="margin: 10px 0;"><?php echo esc_html($game_title); ?></h3>
									<p style="margin-bottom: 20px;"><?php echo esc_html($game_excerpt); ?></p>
									<a href="<?php echo esc_url($game_permalink); ?>" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;">Découvrir le jeu</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
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
										Voir les jeux de piste de <?php echo esc_html($region_name); ?>
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