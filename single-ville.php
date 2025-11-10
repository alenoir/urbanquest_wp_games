<?php
/**
 * The template for displaying single city (ville) posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

get_header();
?>

<!-- Breadcrumb Navigation -->
<?php 
if (function_exists('urbanquest_display_breadcrumb')) {
	urbanquest_display_breadcrumb();
}
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
	 */
	function extract_acf_relationship_id($field_value) {
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
	 * Fonction helper pour récupérer les données d'un jeu pour l'affichage dans les listes
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
		
		return array(
			'image' => $game_image,
			'title' => $game_title,
			'excerpt' => $game_excerpt,
			'payment_url' => $payment_url
		);
	}
	
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
		$image_section_terrain_de_jeu_url = 'https://urbanquest.fr/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
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
	
	// Récupérer tous les jeux de cette ville
	// Selon acf.json : game.city → ville (relationship, return_format: object, max: 1)
	$games = array();
	if ($ville_id) {
		$all_games = get_posts(array(
			'post_type' => 'game',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'suppress_filters' => false
		));
		
		foreach ($all_games as $game) {
			$city_field = get_field('city', $game->ID);
			$city_id = extract_acf_relationship_id($city_field);
			
			if ($city_id == $ville_id) {
				$games[] = $game;
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
								<img src="<?php echo esc_url($image_section_terrain_de_jeu_url); ?>" alt="<?php echo esc_attr($ville_name); ?>" width="750" height="139" class="aligncenter size-large wp-image-26967" />
							</div>
							
							<?php echo wp_kses_post($description_section_terrain_de_jeu); ?>
							
							<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
							
							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_jeu_unique); ?></h3>
							
							<?php echo wp_kses_post($description_section_jeu_unique); ?>
							
						</div>
					</div>
					
					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					
					<!-- Liste des jeux de la ville -->
					<?php if (!empty($games)) : ?>
						<h2 style="text-align: center; margin-bottom: 40px;">Les jeux de piste à <?php echo esc_html($ville_name); ?></h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($games as $game) : 
								$game_data = get_game_display_data($game);
							?>
							<div class="col-md-4" style="margin-bottom: 30px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<a href="<?php echo esc_url(get_permalink($game->ID)); ?>" style="text-decoration: none; color: inherit; display: block;">
										<img src="<?php echo esc_url($game_data['image']); ?>" alt="<?php echo esc_attr($game_data['title']); ?>" style="width: 100%; height: 200px; object-fit: cover;" />
										<div style="padding: 20px;">
											<h3 style="margin: 0 0 10px; font-size: 20px; color: #1f2a37;"><?php echo esc_html($game_data['title']); ?></h3>
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
					<?php else : ?>
						<!-- Message SEO-friendly si aucun jeu -->
						<div style="text-align: center; padding: 60px 20px; background: #F7F9FC; border-radius: 12px; margin-bottom: 60px;">
							<h2 style="margin-bottom: 20px; color: #1f2a37;">Jeux de piste à <?php echo esc_html($ville_name); ?> - Bientôt disponible</h2>
							<p style="font-size: 18px; color: #6b7280; line-height: 1.6; max-width: 800px; margin: 0 auto 30px;">
								Urban Quest prépare actuellement des jeux de piste connectés pour découvrir <?php echo esc_html($ville_name); ?> autrement. 
								Nos game designers travaillent sur des parcours uniques qui vous permettront d'explorer les lieux emblématiques de la ville tout en résolvant des énigmes passionnantes.
							</p>
							<p style="font-size: 16px; color: #6b7280; line-height: 1.6; max-width: 800px; margin: 0 auto 30px;">
								Si vous souhaitez être informé dès que les jeux seront disponibles à <?php echo esc_html($ville_name); ?>, 
								<a href="https://urbanquest.fr/contact/" style="color: #00bbff; text-decoration: underline;">contactez-nous</a> ou 
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

