<?php
/**
 * The template for displaying single region posts.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

get_header();

do_action( 'hestia_before_single_post_wrapper' );
?>

<div class="<?php echo hestia_layout(); ?>">
	<div class="blog-post blog-post-wrapper">
		<div class="container">

<?php if (have_posts()) : while (have_posts()) : the_post(); 
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
		$filtered_departements = array();
		if (!empty($departements)) {
			foreach ($departements as $departement) {
				$region_field = get_field('region', $departement->ID);
				$has_region = false;
				
				if ($region_field) {
					if (is_array($region_field)) {
						foreach ($region_field as $reg) {
							$reg_id = is_object($reg) ? $reg->ID : (is_numeric($reg) ? $reg : null);
							if ($reg_id == $region_id) {
								$has_region = true;
								break;
							}
						}
					} elseif (is_object($region_field)) {
						if ($region_field->ID == $region_id) {
							$has_region = true;
						}
					} elseif (is_numeric($region_field) && $region_field == $region_id) {
						$has_region = true;
					}
				}
				
				if ($has_region) {
					$filtered_departements[] = $departement;
				}
			}
		}
		
		// 2. Pour chaque département, récupérer les villes qui ont ce département dans leur champ 'ville'
		// Le champ 'ville' est sur le post type 'ville' et pointe vers 'departement'
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
			$has_departement = false;
			
			if ($ville_departement_field) {
				if (is_array($ville_departement_field)) {
					foreach ($ville_departement_field as $dep) {
						$dep_id = is_object($dep) ? $dep->ID : (is_numeric($dep) ? $dep : null);
						if ($dep_id && in_array($dep_id, $departement_ids)) {
							$has_departement = true;
							break;
						}
					}
				} elseif (is_object($ville_departement_field)) {
					if (in_array($ville_departement_field->ID, $departement_ids)) {
						$has_departement = true;
					}
				} elseif (is_numeric($ville_departement_field) && in_array($ville_departement_field, $departement_ids)) {
					$has_departement = true;
				}
			}
			
			if ($has_departement) {
				$villes_ids[] = $ville->ID;
			}
		}
		
		// 3. Pour chaque ville, récupérer les jeux
		$villes_ids = array_unique($villes_ids);
		foreach ($villes_ids as $ville_id) {
			$all_games = get_posts(array(
				'post_type' => 'game',
				'posts_per_page' => -1,
				'suppress_filters' => false
			));
			
			foreach ($all_games as $game) {
				$city_field = get_field('city', $game->ID);
				$has_city = false;
				
				if ($city_field) {
					if (is_array($city_field)) {
						foreach ($city_field as $city) {
							$city_id = is_object($city) ? $city->ID : (is_numeric($city) ? $city : null);
							if ($city_id == $ville_id) {
								$has_city = true;
								break;
							}
						}
					} elseif (is_object($city_field)) {
						if ($city_field->ID == $ville_id) {
							$has_city = true;
						}
					} elseif (is_numeric($city_field) && $city_field == $ville_id) {
						$has_city = true;
					}
				}
				
				if ($has_city && !in_array($game, $games, true)) {
					$games[] = $game;
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
											$game_image = get_the_post_thumbnail_url($game->ID, 'full');
											if (empty($game_image)) {
												$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-bordeauxSMALL.jpg';
											}
											$game_excerpt = get_the_excerpt($game->ID);
											if (empty($game_excerpt)) {
												$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
											}
											$payment_url = get_field('payment_url', $game->ID);
											if (empty($payment_url)) {
												$payment_url = get_permalink($game->ID);
											}
											$game_title = $game->post_title;
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
											<a href="<?php echo esc_url($payment_url); ?>" class="btn btn-primary" style="display: inline-block; padding: 10px 25px; background: #e91e63; color: white; text-decoration: none; border-radius: 3px;">
												<i class="far fa-calendar-alt" style="margin-right: 5px;"></i> Réserver
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
									$game_image = get_the_post_thumbnail_url($game->ID, 'full');
									if (empty($game_image)) {
										$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-biarritzSMALL.jpg';
									}
									$game_excerpt = get_the_excerpt($game->ID);
									if (empty($game_excerpt)) {
										$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
									}
									$payment_url = get_field('payment_url', $game->ID);
									if (empty($payment_url)) {
										$payment_url = get_permalink($game->ID);
									}
									$game_title = $game->post_title;
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
									<a href="<?php echo esc_url($payment_url); ?>" class="btn btn-primary" style="display: inline-block; padding: 8px 20px; background: #e91e63; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">
										<i class="far fa-calendar-alt" style="margin-right: 5px;"></i> Réserver
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
									$game_image = get_the_post_thumbnail_url($game->ID, 'full');
									if (empty($game_image)) {
										$game_image = 'https://urbanquest.fr/wp-content/uploads/2019/06/urbanquest-biarritzSMALL.jpg';
									}
									$game_excerpt = get_the_excerpt($game->ID);
									if (empty($game_excerpt)) {
										$game_excerpt = isset($game->post_excerpt) ? $game->post_excerpt : 'Découvrez ce jeu de piste unique dans cette ville.';
									}
									$payment_url = get_field('payment_url', $game->ID);
									if (empty($payment_url)) {
										$payment_url = get_permalink($game->ID);
									}
									$game_title = $game->post_title;
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
									<a href="<?php echo esc_url($payment_url); ?>" class="btn btn-primary" style="display: inline-block; padding: 8px 20px; background: #e91e63; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">
										<i class="far fa-calendar-alt" style="margin-right: 5px;"></i> Réserver
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
