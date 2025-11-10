<?php
/**
 * The template for displaying single region posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

get_header();
?>

<!-- Breadcrumb Navigation -->
<?php 
// Afficher le breadcrumb juste après le header
if (function_exists('urbanquest_display_breadcrumb')) {
	urbanquest_display_breadcrumb();
} else {
	// Debug: afficher un message si la fonction n'existe pas
	if (current_user_can('administrator')) {
		echo '<!-- ERREUR: La fonction urbanquest_display_breadcrumb n\'existe pas -->';
	}
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
	
	$region_id = get_the_ID();
	$region_name = get_the_title() ?: 'Nouvelle-Aquitaine';
	
	// Valeurs par défaut pour les champs ACF
	$region_description = get_field('description_region');
	if (empty($region_description)) {
		$region_description = get_the_content();
	}
	if (empty($region_description)) {
		$region_description = "C'est à Bordeaux qu'est né le premier jeu de piste connecté Urban Quest. Notre région " . esc_html($region_name) . " est parsemée de joyaux et nous avons hâte de vous les faire découvrir ! De La Rochelle à Pau en passant par Périgueux, Urban Quest vous tend les bras.";
	}
	
	$region_image = get_field('image_region');
	$region_image_url = '';
	if ($region_image) {
		if (is_array($region_image)) {
			$region_image_url = isset($region_image['url']) ? $region_image['url'] : '';
		} elseif (is_string($region_image)) {
			$region_image_url = $region_image;
		} elseif (is_numeric($region_image)) {
			$region_image_url = wp_get_attachment_image_url($region_image, 'full');
		}
	}
	if (empty($region_image_url)) {
		$region_image_url = 'https://urbanquest.fr/wp-content/uploads/2020/07/NA.png';
	}
	
	$temoignage_texte = get_field('temoignage_texte');
	if (empty($temoignage_texte)) {
		$temoignage_texte = "Ce jeu de piste est tellement fun à faire ! On a passé un bon moment entre amis, c'était cool ! Je recommande vraiment.";
	}
	
	$temoignage_auteur = get_field('temoignage_auteur');
	if (empty($temoignage_auteur)) {
		$temoignage_auteur = 'Manon G.';
	}
	
	$temoignage_avatar = get_field('temoignage_avatar');
	$temoignage_avatar_url = '';
	if ($temoignage_avatar) {
		if (is_array($temoignage_avatar)) {
			$temoignage_avatar_url = isset($temoignage_avatar['url']) ? $temoignage_avatar['url'] : '';
		} elseif (is_string($temoignage_avatar)) {
			$temoignage_avatar_url = $temoignage_avatar;
		} elseif (is_numeric($temoignage_avatar)) {
			$temoignage_avatar_url = wp_get_attachment_image_url($temoignage_avatar, 'full');
		}
	}
	if (empty($temoignage_avatar_url)) {
		$temoignage_avatar_url = 'https://urbanquest.fr/wp-content/uploads/2019/08/avatar-2.jpg';
	}
	
	// Récupérer tous les jeux de cette région via la chaîne de relations
	// Région → Départements → Villes → Jeux
	$games = array();
	
	try {
		// 1. Récupérer tous les départements liés à cette région
		$departements = get_posts(array(
			'post_type' => 'departement',
			'posts_per_page' => -1,
			'suppress_filters' => false
		));
		
		// Filtrer les départements qui ont cette région
		// Selon acf.json : departement.region → region (relationship, return_format: object, max: 1)
		$filtered_departements = array();
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
		// En cas d'erreur, utiliser des jeux de fallback
		$games = array();
	}
	
	// Si aucun jeu trouvé, créer des jeux de fallback pour l'affichage
	if (empty($games)) {
		// Créer des objets factices pour l'affichage avec images
		$fallback_games = array(
			(object) array(
				'ID' => 0,
				'post_title' => 'Bordeaux',
				'post_excerpt' => 'Explorez le centre ville historique de Bordeaux aux grès de 4 places emblématiques de la belle endormie.',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Bayonne',
				'post_excerpt' => 'A cheval sur la Nive, vous allez parcourir le petit et le grand Bayonne et découvrir ses curiosités. Ready ? Go !',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bayonneSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Biarritz',
				'post_excerpt' => 'Entre plage et belles bâtisses vous allez profiter à fond du climat basque. Idéal pour les EVJF et EVG.',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-biarritzSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Mont-de-Marsan',
				'post_excerpt' => 'Découvrez la cité au fil de l\'eau et vous comprendrez pourquoi on l\'appelle la Villes aux Trois Rivières.',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-montdemarsanSMALL-1.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Limoges',
				'post_excerpt' => 'Découvrez le phare du limousin, vous verrez il n\'y a pas que la porcelaine à découvrir !',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-navarrenxSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Lacanau',
				'post_excerpt' => 'Avant d\'aller faire bronzette, prenez le temps de découvrir le centre ville canaulais. Promis vous allez adorer !',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2020/07/uq-lacanau-small-1.png'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'La Rochelle',
				'post_excerpt' => 'Rendez-vous sous la Porte Océane pour découvrir La Rochelle sous un nouveau jour.',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2020/07/uq-larochelle-small.png'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Pau',
				'post_excerpt' => 'Telle la section paloise, la ville aux Mille Palmiers vous attend de pied ferme ! Saurez-vous relever le défi ?',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-pauSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'Périgueux',
				'post_excerpt' => 'La Dordogne vous ouvre ses portes. Au gré des vestiges romains et médievaux, explorez l\'histoire !',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-perigueuxSMALL.jpg'
			),
			(object) array(
				'ID' => 0,
				'post_title' => 'St Emilion',
				'post_excerpt' => 'Une aventure gustative dont vous vous souviendrez. La cité du vin va vous surprendre.',
				'fallback_image' => 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-stemilionSMALL.jpg'
			)
		);
		$games = $fallback_games;
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-region-content'); ?>>
			<div class="row">
				<div class="col-md-10 page-content-wrap col-md-offset-1">

					<!-- Section Introduction -->
					<div class="row" style="margin-bottom: 60px;">
						<div class="col-md-8">
							<h2>Le Berceau d'Urban Quest</h2>
							
							<!-- Breadcrumb Navigation -->
							<?php 
							if (function_exists('urbanquest_display_breadcrumb_simple')) {
								urbanquest_display_breadcrumb_simple();
							}
							?>
							
							<div class="region-description">
								<?php echo wpautop($region_description); ?>
							</div>
							
							<div style="text-align: center; margin: 40px 0;">
								<div style="border-top: 1px solid #e6e6e6; padding-top: 15px;">
									<span style="font-weight: 700;">Découvrez tous nos jeux de piste en <?php echo esc_html($region_name); ?></span>
								</div>
							</div>
							
							<!-- Première ligne de jeux (2 jeux) -->
							<?php if (!empty($games)) : ?>
								<div class="row" style="margin-top: 30px;">
									<?php 
									$first_two_games = array_slice($games, 0, 2);
									foreach ($first_two_games as $game) : 
										// Gérer les jeux réels et les jeux de fallback
										if (isset($game->ID) && $game->ID > 0) {
											$game_data = get_game_display_data($game);
											$game_image = $game_data['image'];
											$game_title = $game_data['title'];
											$game_excerpt = $game_data['excerpt'];
											$payment_url = $game_data['payment_url'];
										} else {
											// Jeu de fallback
											$game_image = isset($game->fallback_image) ? $game->fallback_image : 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
											$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
											$payment_url = '#';
											$game_title = isset($game->post_title) ? $game->post_title : 'Jeu de piste';
										}
									?>
									<div class="col-md-6" style="margin-bottom: 30px;">
										<div style="text-align: center;">
											<img src="<?php echo esc_url($game_image); ?>" alt="<?php echo esc_attr($game_title); ?>" style="width: 100%; max-width: 562px; height: auto; border-radius: 8px; margin-bottom: 15px;" />
											<h3 style="margin: 10px 0;"><?php echo esc_html($game_title); ?></h3>
											<p style="margin-bottom: 20px;"><?php echo esc_html($game_excerpt); ?></p>
											<?php 
											$button_text = (empty($payment_url) || $payment_url === '#') ? 'Bientôt' : 'Réserver';
											$button_href = (empty($payment_url) || $payment_url === '#') ? '#' : $payment_url;
											?>
											<a href="<?php echo esc_url($button_href); ?>" <?php echo ($button_href !== '#') ? 'target="_blank" rel="noopener"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text); ?>
											</a>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
						
						<div class="col-md-4">
							<img src="<?php echo esc_url($region_image_url); ?>" alt="<?php echo esc_attr($region_name); ?>" style="width: 100%; height: auto; border-radius: 22px;" />
						</div>
					</div>
					
					<!-- Section Témoignage -->
					<div class="row" style="background: #f8f9fa; padding: 40px; border-radius: 8px; margin: 60px 0;">
						<div class="col-md-6">
							<img src="<?php echo esc_url($temoignage_avatar_url); ?>" alt="<?php echo esc_attr($temoignage_auteur); ?>" style="width: 100px; height: 100px; border-radius: 50%; margin-bottom: 20px;" />
							<p style="color: #474747; font-size: 26px; margin-bottom: 20px;"><?php echo esc_html($temoignage_texte); ?></p>
							<p style="text-align: left;">
								<img src="https://urbanquest.fr/wp-content/uploads/2020/07/tripadvisor_logo-1024x192.png" alt="TripAdvisor" width="100" height="19" style="margin-right: 10px;" />
								<?php echo esc_html($temoignage_auteur); ?>
							</p>
						</div>
					</div>
					
					<!-- Grille de jeux (4 colonnes) -->
					<?php if (!empty($games) && count($games) > 2) : ?>
						<div class="row" style="margin-top: 40px;">
							<?php 
							$remaining_games = array_slice($games, 2);
							foreach ($remaining_games as $game) : 
								// Gérer les jeux réels et les jeux de fallback
								if (isset($game->ID) && $game->ID > 0) {
									$game_data = get_game_display_data($game);
									$game_image = $game_data['image'];
									$game_title = $game_data['title'];
									$game_excerpt = $game_data['excerpt'];
									$payment_url = $game_data['payment_url'];
								} else {
									// Jeu de fallback
									$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-biarritzSMALL.jpg';
									$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
									$payment_url = '#';
									$game_title = isset($game->post_title) ? $game->post_title : 'Jeu de piste';
								}
							?>
							<div class="col-md-3" style="margin-bottom: 30px;">
								<div style="text-align: center;">
									<img src="<?php echo esc_url($game_image); ?>" alt="<?php echo esc_attr($game_title); ?>" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;" />
									<h3 style="font-size: 20px; margin: 10px 0;"><?php echo esc_html($game_title); ?></h3>
									<p style="font-size: 14px; margin-bottom: 20px;"><?php echo esc_html($game_excerpt); ?></p>
									<?php 
									$button_text = (empty($payment_url) || $payment_url === '#') ? 'Bientôt' : 'Réserver';
									$button_href = (empty($payment_url) || $payment_url === '#') ? '#' : $payment_url;
									?>
									<a href="<?php echo esc_url($button_href); ?>" <?php echo ($button_href !== '#') ? 'target="_blank" rel="noopener"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text); ?>
									</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<!-- Section Comment ça marche -->
					<div style="text-align: center; margin: 60px 0;">
						<img src="https://urbanquest.fr/wp-content/uploads/2019/08/deroulement-partie-urbanquest-1-1024x232.jpg" alt="Comment se déroule une partie" style="max-width: 100%; height: auto;" />
					</div>
					
					<!-- Deuxième grille de jeux si nécessaire -->
					<?php if (!empty($games) && count($games) > 6) : ?>
						<div class="row" style="margin-top: 40px;">
							<?php 
							$more_games = array_slice($games, 6);
							foreach ($more_games as $game) : 
								// Gérer les jeux réels et les jeux de fallback
								if (isset($game->ID) && $game->ID > 0) {
									$game_data = get_game_display_data($game);
									$game_image = $game_data['image'];
									$game_title = $game_data['title'];
									$game_excerpt = $game_data['excerpt'];
									$payment_url = $game_data['payment_url'];
								} else {
									// Jeu de fallback
									$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-biarritzSMALL.jpg';
									$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
									$payment_url = '#';
									$game_title = isset($game->post_title) ? $game->post_title : 'Jeu de piste';
								}
							?>
							<div class="col-md-3" style="margin-bottom: 30px;">
								<div style="text-align: center;">
									<img src="<?php echo esc_url($game_image); ?>" alt="<?php echo esc_attr($game_title); ?>" style="width: 100%; height: auto; border-radius: 8px; margin-bottom: 15px;" />
									<h3 style="font-size: 20px; margin: 10px 0;"><?php echo esc_html($game_title); ?></h3>
									<p style="font-size: 14px; margin-bottom: 20px;"><?php echo esc_html($game_excerpt); ?></p>
									<?php 
									$button_text = (empty($payment_url) || $payment_url === '#') ? 'Bientôt' : 'Réserver';
									$button_href = (empty($payment_url) || $payment_url === '#') ? '#' : $payment_url;
									?>
									<a href="<?php echo esc_url($button_href); ?>" <?php echo ($button_href !== '#') ? 'target="_blank" rel="noopener"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;"><?php echo esc_html($button_text); ?>
									</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
					<!-- ===================== JEUX QUI PEUVENT VOUS INTÉRESSER ===================== -->
					<?php 
					// Créer une sélection de jeux pour le bloc "Jeux qui peuvent vous intéresser"
					// Prendre les jeux déjà récupérés et en sélectionner quelques-uns (max 6)
					$related_games_region = array();
					if (!empty($games)) {
						// Mélanger les jeux pour avoir une sélection variée
						$shuffled_games = $games;
						shuffle($shuffled_games);
						$related_games_region = array_slice($shuffled_games, 0, 6);
					}
					?>
					<?php if (!empty($related_games_region)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="text-align: center; margin-bottom: 40px;">Jeux qui peuvent vous intéresser</h2>
						<div class="row" style="margin-bottom: 60px;">
							<?php foreach ($related_games_region as $related_game) : 
								// Gérer les jeux réels et les jeux de fallback
								if (isset($related_game->ID) && $related_game->ID > 0) {
									$game_data = get_game_display_data($related_game);
									$game_image = $game_data['image'];
									$game_title = $game_data['title'];
									$game_excerpt = $game_data['excerpt'];
									$payment_url = $game_data['payment_url'];
									$related_city_name = $game_data['city_name'];
								} else {
									// Jeu de fallback
									$game_image = isset($related_game->fallback_image) ? $related_game->fallback_image : 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
									$game_excerpt = isset($related_game->post_excerpt) ? $related_game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
									$payment_url = '#';
									$game_title = isset($related_game->post_title) ? $related_game->post_title : 'Jeu de piste';
									$related_city_name = '';
								}
							?>
							<div class="col-md-4" style="margin-bottom: 30px;">
								<div style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
									<a href="<?php echo esc_url(isset($related_game->ID) && $related_game->ID > 0 ? get_permalink($related_game->ID) : $payment_url); ?>" style="text-decoration: none; color: inherit; display: block;">
										<img src="<?php echo esc_url($game_image); ?>" alt="<?php echo esc_attr($game_title); ?>" style="width: 100%; height: 200px; object-fit: cover;" />
										<div style="padding: 20px;">
											<h3 style="margin: 0 0 10px; font-size: 20px; color: #1f2a37;"><?php echo esc_html($game_title); ?></h3>
											<?php if ($related_city_name) : ?>
												<p style="margin: 0 0 10px; color: #6b7280; font-size: 14px; font-weight: 500;">
													<i style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;" data-lucide="map-pin"></i>
													<?php echo esc_html($related_city_name); ?>
												</p>
											<?php endif; ?>
											<p style="margin: 0 0 15px; color: #6b7280; font-size: 14px; line-height: 1.5;"><?php echo esc_html(wp_trim_words($game_excerpt, 20)); ?></p>
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
					
					<!-- Carrousel d'images (optionnel) -->
					<?php 
					$carousel_images = get_field('carousel_images');
					if ($carousel_images && is_array($carousel_images) && !empty($carousel_images)) : 
					?>
					<div style="margin: 60px 0;">
						<div class="swiper-container" style="padding: 20px 0;">
							<div class="swiper-wrapper">
								<?php foreach ($carousel_images as $image) : 
									$img_url = is_array($image) ? $image['url'] : (is_string($image) ? $image : wp_get_attachment_image_url($image, 'medium'));
								?>
								<div class="swiper-slide">
									<img src="<?php echo esc_url($img_url); ?>" alt="" style="width: 100%; height: auto; border-radius: 8px;" />
								</div>
								<?php endforeach; ?>
							</div>
						</div>
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
