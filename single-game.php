<?php
/**
 * The template for displaying all single posts and attachments.
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
	// Fonction helper pour récupérer le nom de la ville via la relation city
	$city_post = get_field('city');
	$ville_name = '';
	$ville_id = null;
	if ($city_post) {
		// Si c'est un objet WP_Post
		if (is_object($city_post) && isset($city_post->post_title)) {
			$ville_name = $city_post->post_title;
			$ville_id = $city_post->ID;
		}
		// Si c'est un ID
		elseif (is_numeric($city_post)) {
			$ville_name = get_the_title($city_post);
			$ville_id = $city_post;
		}
		// Si c'est un tableau (cas improbable mais on gère)
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
	$region_name = '';
	if ($ville_id) {
		// Récupérer le département depuis la ville (champ 'ville' sur le post type ville pointe vers departement)
		$departement_post = get_field('ville', $ville_id);
		$departement_id = null;
		
		if ($departement_post) {
			// Si c'est un objet WP_Post
			if (is_object($departement_post) && isset($departement_post->ID)) {
				$departement_id = $departement_post->ID;
			}
			// Si c'est un ID
			elseif (is_numeric($departement_post)) {
				$departement_id = $departement_post;
			}
			// Si c'est un tableau (multiple: true)
			elseif (is_array($departement_post) && !empty($departement_post)) {
				$first_departement = $departement_post[0];
				if (is_object($first_departement) && isset($first_departement->ID)) {
					$departement_id = $first_departement->ID;
				} elseif (is_numeric($first_departement)) {
					$departement_id = $first_departement;
				}
			}
		}
		
		// Récupérer la région depuis le département (champ 'region' sur le post type departement pointe vers region)
		if ($departement_id) {
			$region_post = get_field('region', $departement_id);
			
			if ($region_post) {
				// Si c'est un objet WP_Post
				if (is_object($region_post) && isset($region_post->post_title)) {
					$region_name = $region_post->post_title;
				}
				// Si c'est un ID
				elseif (is_numeric($region_post)) {
					$region_name = get_the_title($region_post);
				}
				// Si c'est un tableau (multiple: true)
				elseif (is_array($region_post) && !empty($region_post)) {
					$first_region = $region_post[0];
					if (is_object($first_region) && isset($first_region->post_title)) {
						$region_name = $first_region->post_title;
					} elseif (is_numeric($first_region)) {
						$region_name = get_the_title($first_region);
					}
				}
			}
		}
	}
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-game-content'); ?>>
			<div class="row">
				<div class="col-md-8 page-content-wrap  col-md-offset-2">

					<h2></h2>
					<div style="display: flex; flex-wrap: wrap; gap: 0;">
						<div style="flex: 0 0 50%; max-width: 50%; padding: 0 30px 0 0; box-sizing: border-box;">
							<h2>Jouez quand vous voulez à <?php echo esc_html($ville_name); ?></h2>
							<p>Urban Quest, c'est <strong>le jeu de piste nouvelle génération</strong> à <?php echo esc_html($ville_name); ?> : pas besoin de réserver un créneau, tout se joue en totale autonomie.
							Après achat, vous recevez vos <strong>instructions de jeu</strong> par e-mail. Il vous suffit de vous rendre au point de départ indiqué, de sortir <strong>votre smartphone</strong>… et de lancer l'aventure.</p>

							<p>Plus qu'un simple jeu, c'est une véritable expérience immersive qui mêle jeu de piste, exploration et esprit d'équipe.</p>

							<img src="http://urbanquest.fr/wp-content/uploads/2025/10/notation-urbanquest-1024x219.png" alt="" width="750" height="160" class="aligncenter size-large wp-image-26992" />
							<h5>En groupe ?</h5>
							<p style="margin-top: 10px;">Pour les grands groupes nous proposons des options personnalisées sur demande pour rendre le jeu encore plus inoubliable, il suffit de nous <a href="https://urbanquest.fr/contact/">contacter</a>.</p>

							<h5>Imaginés par nos game designers</h5>
							<p>Nos jeux sont conçus à la main par des game designers, en lien direct avec l'histoire et la géographie de <?php echo esc_html($ville_name); ?>... pour une expérience authentique et unique.</p>
						</div>

						<div style="flex: 0 0 50%; max-width: 50%; box-sizing: border-box;">
							<section style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 48px; padding: 24px 22px; max-width: 540px; margin: 0;">
								<img src="http://urbanquest.fr/wp-content/uploads/2025/10/Group-10.png" alt="" width="486" height="316" class="aligncenter wp-image-26994 size-full" />
								<img src="http://urbanquest.fr/wp-content/uploads/2025/10/made-in-france-1.png" alt="" width="234" height="19" class="wp-image-26996 size-full aligncenter" />
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="users"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">2 – 5 joueurs</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">à partir de 8 ans</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="clock"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">60 minutes</div>
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
										<div style="text-align: center;"><a href="<?php echo esc_url(get_field('payment_url')); ?>" target="_blank" style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 10px 25px; text-decoration: none; border-radius: 999px;" rel="noopener">Réserve ton jeu d'exploration
										</a></div></li>
								</ul>
							</section>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<!-- ===================== SECTION VALEUR / POURQUOI NOUS ===================== -->
					<h2 style="text-align: center;">Pourquoi choisir Urban Quest à <?php echo esc_html($ville_name); ?> ?</h2>
					<img src="http://urbanquest.fr/wp-content/uploads/2025/10/compo-photo-nice.png" alt="" width="561" height="101" class="center aligncenter wp-image-27010 size-full" />
					<p style="text-align: center; max-width: 860px; margin: 0 auto;">Un savant mélange <strong>jeu de piste</strong>, <strong>chasse au trésor</strong> et <strong>visite insolite</strong> : observation, logique, audace et stratégie vous feront grimper au classement, tout en (re)découvrant <?php echo esc_html($ville_name); ?> et ses lieux emblématiques.</p>

					<div style="display: flex; gap: 18px; flex-wrap: wrap; justify-content: center; margin-top: 14px;">
						<div style="flex: 1 1 260px; min-width: 240px; background: #fafafa; padding: 16px; border-radius: 12px;"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="calendar-heart"></i>
							<strong>100% libre</strong>
							Vous lancez la session quand vous voulez, où vous voulez.</div>
						<div style="flex: 1 1 260px; min-width: 240px; background: #fafafa; padding: 16px; border-radius: 12px;"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="smartphone"></i>
							<strong>Ultra simple</strong>
							Vos instructions de jeu par e-mail, votre smartphone… c'est tout.</div>
						<div style="flex: 1 1 260px; min-width: 240px; background: #fafafa; padding: 16px; border-radius: 12px;"><i style="width: 40px; height: 40px; display: inline-block;" data-lucide="swords"></i>
							<strong>Fun &amp; challenge</strong>
							Défis variés, énigmes malignes, score et classement.</div>
					</div>
					<div style="text-align: center; margin: 18px 0 6px;"><?php echo do_shortcode('[xyz-ihs snippet="replace-buy-button"]'); ?></div>
					<div></div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<div style="display: flex; flex-wrap: wrap; gap: 0;">
						<div style="flex: 0 0 33.333%; max-width: 33.333%; padding: 0 60px 0 0; box-sizing: border-box;">
							<section style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 48px; padding: 32px 22px 0px 22px; max-width: 540px; margin: 0;">
								<h5 style="text-align: left;">+ d'infos sur le jeu</h5>
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">De 7 à 77 ans</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Tant que marcher 60 min n'est pas un challenger pour vous</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Nous avons aussi des jeux conçus spécialement pour les kids</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">Env. 3,4km</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Pensez à prendre vos baskets!</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 0px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 18px; letter-spacing: 0.2px;">Typologie de jeu</div>
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Fun</div>
													<img src="http://urbanquest.fr/wp-content/uploads/2025/10/jauge-taille-1.png" alt="" width="100%" height="53" style="margin: -8px 0 -10px 0;" />
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Histoire</div>
													<img src="http://urbanquest.fr/wp-content/uploads/2025/10/jauge-taille-2.png" alt="" width="100%" height="53" style="margin: -8px 0 -10px 0;" />
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Réflexion</div>
													<img src="http://urbanquest.fr/wp-content/uploads/2025/10/jauge-taille-3.png" alt="" width="100%" height="53" style="margin: -8px 0 -10px 0;" />
													<div style="color: #6b7280; font-size: 14px; margin-top: 6px; font-weight: 500;">Culture locale</div>
													<img src="http://urbanquest.fr/wp-content/uploads/2025/10/jauge-taille-2.png" alt="" width="100%" height="53" style="margin: -8px 0 -10px 0;" />

												</div></li>
										</ul>
									</li>
								</ul>
							</section>
						</div>

						<div style="flex: 0 0 66.666%; max-width: 66.666%; box-sizing: border-box;">
							<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($ville_name); ?> devient votre terrain de jeu</h3>
							<div><img src="http://urbanquest.fr/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png" alt="" width="750" height="139" class="aligncenter size-large wp-image-26967" /></div>
							<p style="margin: 10px 0;">Avec Urban Quest, oubliez les visites classiques : chaque rue peut cacher un indice, chaque monument peut être la clé d'une énigme.
							Entre rires, stratégie et adrénaline, vous vivez une expérience intense où l'observation et l'esprit d'équipe font toute la différence.</p>
							<p style="margin: 10px 0;">Pendant <strong>60 minutes</strong>, la ville s'anime sous vos pas : explorez, déduisez, surprenez-vous… et laissez-vous porter par l'énergie du jeu.</p>
							<p style="margin: 10px 0;">En famille, entre amis ou pour un EVJF/EVG, préparez-vous à <strong>découvrir la ville autrement</strong> et à créer des souvenirs mémorables ✨</p>


							<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

							<h3 style="margin: 0 0 10px; text-align: center;">Un jeu de piste unique à <?php echo esc_html($ville_name); ?></h3>
							<p style="margin: 10px 0;">Si vous cherchez une <strong>activité insolite à <?php echo esc_html($ville_name); ?></strong>, Urban Quest est le jeu parfait : une chasse au trésor moderne, ludique et connectée qui vous entraîne à travers les rues et les lieux emblématiques de <?php echo esc_html($ville_name); ?>.
							Idéal pour ceux qui veulent <strong>découvrir <?php echo esc_html($ville_name); ?> autrement</strong> en mêlant culture, divertissement et esprit de compétition.</p>
							<p style="margin: 10px 0;">🔎 <em>Fun fact :</em> avec Urban Quest, c'est à votre tour d'apporter couleurs et énergie à <?php echo esc_html($ville_name); ?> en résolvant ses énigmes !</p>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					<p style="text-align: center;"><!-- ===================== COMMENT ÇA MARCHE (HOW-TO) ===================== --></p>

					<h2 style="text-align: center;">Comment se déroule une partie ?</h2>
					<p style="text-align: center;"><img src="http://urbanquest.fr/wp-content/uploads/2025/08/newUQderoulement-2.png" alt="" width="760" height="231" class="aligncenter size-full wp-image-26861" /></p>
					<p class="p1" style="text-align: center;">Choisis ton parcours, pars à l'aventure dans la ville et mesure-toi aux autres équipes.</p>
					<p class="p1" style="text-align: center;">Une expérience fun, rapide à lancer et 100 % autonome !</p>
					<p style="text-align: center;"><?php echo do_shortcode('[xyz-ihs snippet="replace-buy-button"]'); ?></p>


					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<!-- ===================== TABLE INFO CLÉ (SEO + Conversion) ===================== -->
					<h2>Informations clés</h2>
					<table style="width: 100%; border-collapse: collapse;">
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
								<td style="border: 1px solid #eee; padding: 10px;">60 minutes</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Équipe</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;">2 à 5 joueurs</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Matériel</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;">Votre smartphone + connexion data</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Tarif</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>39 € par équipe</strong></td>
							</tr>
						</tbody>
					</table>
					<?php echo do_shortcode('[xyz-ihs snippet="replace-buy-button"]'); ?>

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
	do_action( 'hestia_blog_related_posts' );
}
?>
<div class="footer-wrapper">
<?php get_footer(); ?>


