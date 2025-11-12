<?php
/**
 * Template pour les jeux (post type: game)
 * Optimisé pour les LLM avec fonctions helper réutilisables
 */

// SEO : Préparer les données avant get_header()
if (have_posts()) {
	the_post();
	$game_id = get_the_ID();
	
	// Récupérer les données de localisation
	$location_data = urbanquest_get_game_location_data($game_id);
	extract($location_data);
	
	// Titre SEO
	$game_title_seo = urbanquest_get_field_with_default('titre_seo', '');
	if (empty($game_title_seo)) {
		$game_title_seo = !empty($ville_name) 
			? 'Jeu de piste Urban Quest à ' . esc_html($ville_name) . ' - ' . get_the_title()
			: get_the_title() . ' - UrbanQuest';
	}
	
	// Meta description SEO
	$game_meta_description = urbanquest_get_field_with_default('description_principale', '');
	if (empty($game_meta_description)) {
		$game_meta_description = get_the_excerpt();
	}
	if (empty($game_meta_description)) {
		$game_meta_description = 'Découvrez le jeu de piste Urban Quest à ' . esc_html($ville_name) . '. Une expérience immersive qui mêle jeu de piste, exploration et esprit d\'équipe.';
	}
	$game_meta_description = wp_strip_all_tags($game_meta_description);
	if (strlen($game_meta_description) > 160) {
		$game_meta_description = substr($game_meta_description, 0, 157) . '...';
	}
	
	// Image pour Open Graph
	$default_image = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	$game_image_url = get_the_post_thumbnail_url($game_id, 'large') 
		?: urbanquest_get_image_url(get_field('image_principale'), 'large', $default_image);
	
	// Fonction helper pour formater la durée
	function urbanquest_format_duree($minutes) {
		$minutes = intval($minutes);
		if ($minutes < 60) {
			return $minutes . ' min';
		} else {
			$heures = floor($minutes / 60);
			$minutes_restantes = $minutes % 60;
			if ($minutes_restantes == 0) {
				return $heures . ' h';
			} else {
				return $heures . ' h ' . $minutes_restantes . ' min';
			}
		}
	}
	
	// Prix et durée pour Schema.org
	$prix = urbanquest_get_field_with_default('prix', '39€');
	$prix_numeric = preg_replace('/[^0-9]/', '', $prix);
	$duree_minutes = intval(urbanquest_get_field_with_default('duree_minutes', 60));
	$duree = urbanquest_format_duree($duree_minutes);
	
	// Modifier le title
	add_filter('document_title_parts', function($title) use ($game_title_seo) {
		$title['title'] = $game_title_seo;
		return $title;
	}, 10);
	
	// Meta tags Open Graph et Twitter Cards
	add_action('wp_head', function() use ($game_title_seo, $game_meta_description, $game_image_url) {
		echo '<meta property="og:title" content="' . esc_attr($game_title_seo) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr($game_meta_description) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url($game_image_url) . '" />' . "\n";
		echo '<meta property="og:type" content="product" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
		echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
		echo '<meta property="og:locale" content="fr_FR" />' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($game_title_seo) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr($game_meta_description) . '" />' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url($game_image_url) . '" />' . "\n";
	}, 1);
	
	// Schéma JSON-LD Product
	add_action('wp_head', function() use ($prix_numeric, $duree, $ville_name, $region_name, $game_image_url, $game_meta_description) {
		$payment_url = urbanquest_get_field_with_default('payment_url', get_permalink());
		$rating_value = floatval(urbanquest_get_field_with_default('rating_value', 4.8));
		$review_count = intval(urbanquest_get_field_with_default('review_count', 254));
		
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => get_the_title(),
			'description' => $game_meta_description,
			'image' => $game_image_url,
			'offers' => [
				'@type' => 'Offer',
				'price' => $prix_numeric,
				'priceCurrency' => 'EUR',
				'availability' => 'https://schema.org/InStock',
				'url' => $payment_url,
				'priceValidUntil' => date('Y-m-d', strtotime('+1 year'))
			],
			'brand' => [
				'@type' => 'Organization',
				'name' => 'Urban Quest',
				'url' => get_site_url()
			]
		];
		
		if ($rating_value > 0 && $review_count > 0) {
			$schema['aggregateRating'] = [
				'@type' => 'AggregateRating',
				'ratingValue' => $rating_value,
				'reviewCount' => $review_count,
				'bestRating' => 5,
				'worstRating' => 1
			];
		}
		
		if (!empty($ville_name)) {
			$schema['locationCreated'] = ['@type' => 'City', 'name' => $ville_name];
		}
		
		if (!empty($region_name)) {
			$schema['areaServed'] = ['@type' => 'State', 'name' => $region_name];
		}
		
		echo '<script type="application/ld+json">' . "\n";
		echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		echo "\n" . '</script>' . "\n";
	}, 99);
	
	rewind_posts();
}

get_header();
do_action('hestia_before_single_post_wrapper');
?>
<style>

	/* Styles pour la sidebar de jeu */
	.game-card-image {
		width: 100%;
		height: 250px;
		background-size: cover;
		background-position: center;
		border-radius: 22px ;
		overflow: visible;
		position: relative;
		margin: 0;
	}
	
	.game-price-badge-wrapper {
		position: absolute;
		bottom: -30px;
		left: 50%;
		transform: translateX(-50%);
		z-index: 10;
	}
	
	.game-price-badge {
		background: #F5EA4D;
		padding: 10px 20px;
		border-radius: 999px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
		display: inline-flex;
		align-items: center;
		gap: 10px;
		white-space: nowrap;
		min-width: 120px;
		justify-content: center;
	}

	.game-main-content {
		font-size: 16px;
	}
	
	/* Taille de police pour le contenu texte uniquement (pas les titres) */
	.single-game-content p,
	.single-game-content div,
	.single-game-content span,
	.single-game-content li,
	.single-game-content td,
	.single-game-content a,
	.single-game-content strong,
	.single-game-content em {
		font-size: 16px;
	}
	
	/* Exception pour le breadcrumb - garder 14px */
	.urbanquest-breadcrumb-simple,
	.urbanquest-breadcrumb-simple * {
		font-size: 14px !important;
	}
	
	/* Layout responsive pour les pages de jeu */
	@media (max-width: 991px) {
		.game-sidebar-card,
		.game-info-sidebar {
			position: static !important;
			margin-top: 30px;
		}
	}
</style>

<div class="<?php echo hestia_layout(); ?>">
	<div class="blog-post blog-post-wrapper">
		<div class="container">

<?php if (have_posts()) : while (have_posts()) : the_post(); 
	// Récupérer les données de localisation
	$location_data = urbanquest_get_game_location_data(get_the_ID());
	extract($location_data);
	
	// Récupérer les champs avec valeurs par défaut
	$prix_original = get_field('prix_original');
	$afficher_prix_original = !empty($prix_original);
	$prix = urbanquest_get_field_with_default('prix', '39€');
	$titre_offre = urbanquest_get_field_with_default('titre_offre', 'Offre du moment !');
	
	// Informations du jeu - champs numériques
	$nombre_joueurs_min = intval(urbanquest_get_field_with_default('nombre_joueurs_min', 2));
	$nombre_joueurs_max = intval(urbanquest_get_field_with_default('nombre_joueurs_max', 5));
	if ($nombre_joueurs_min == $nombre_joueurs_max) {
		$nombre_joueurs = $nombre_joueurs_min . ' joueur' . ($nombre_joueurs_min > 1 ? 's' : '');
	} else {
		$nombre_joueurs = $nombre_joueurs_min . ' – ' . $nombre_joueurs_max . ' joueurs';
	}
	
	$age_min = intval(urbanquest_get_field_with_default('age_min', 7));
	$age_max = intval(urbanquest_get_field_with_default('age_max', 77));
	$age_minimum = 'à partir de ' . $age_min . ' ans';
	$tranche_age = 'De ' . $age_min . ' à ' . $age_max . ' ans';
	
	$duree_minutes = intval(urbanquest_get_field_with_default('duree_minutes', 60));
	$duree = urbanquest_format_duree($duree_minutes);
	
	$distance_km = floatval(urbanquest_get_field_with_default('distance_km', 3.4));
	$distance = 'Env. ' . number_format($distance_km, 1, ',', '') . ' km';
	
	// Image principale
	$default_image = get_site_url() . '/wp-content/uploads/2018/08/cropped-cropped-fondurbanquest.jpg';
	$image_principale_url = urbanquest_get_image_url(get_field('image_principale'), 'full', $default_image);
	
	// Overlay image principale
	$default_overlay = get_stylesheet_directory_uri() . '/images/offre-urbanquest-ville-overlay.png';
	$overlay_image_url = urbanquest_get_image_url(get_field('overlay_image_principale'), 'full', $default_overlay);
	
	// Titre principal
	$titre_principal = urbanquest_get_field_with_default('titre_principal', 'Jouez quand vous voulez à ' . $ville_name);
	$titre_principal = str_replace('[ville]', $ville_name, $titre_principal);
	
	// Description principale
	$description_principale = get_field('description_principale');
	
	// Section infos
	$titre_section_infos = "+ d'infos sur le jeu";
	
	// Jauges
	$jauge_fun = max(1, min(100, intval(urbanquest_get_field_with_default('jauge_fun', 33))));
	$jauge_histoire = max(1, min(100, intval(urbanquest_get_field_with_default('jauge_histoire', 66))));
	$jauge_reflexion = max(1, min(100, intval(urbanquest_get_field_with_default('jauge_reflexion', 75))));
	$jauge_culture_locale = max(1, min(100, intval(urbanquest_get_field_with_default('jauge_culture_locale', 66))));
	
	// Jeux pertinents
	$related_games = urbanquest_get_related_games(get_the_ID(), $ville_id, $departement_id, $region_id, 6);
	
	// Données de la ville pour les sections
	$titre_section_terrain_de_jeu = '';
	$image_section_terrain_de_jeu_url = '';
	$description_section_terrain_de_jeu = '';
	$titre_section_jeu_unique = '';
	$description_section_jeu_unique = '';
	
	if ($ville_id) {
		$titre_section_terrain_de_jeu = str_replace('[ville]', $ville_name, 
			urbanquest_get_field_with_default('titre_section_terrain_de_jeu', '[ville] devient votre terrain de jeu', $ville_id));
		
		$default_terrain_image = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';
		$image_section_terrain_de_jeu_url = urbanquest_get_image_url(
			get_field('image_section_terrain_de_jeu', $ville_id), 'large', $default_terrain_image);
		
		$description_section_terrain_de_jeu = str_replace('[ville]', $ville_name,
			urbanquest_get_field_with_default('description_section_terrain_de_jeu', 
				'<p style="margin: 10px 0;">Avec Urban Quest, oubliez les visites classiques : chaque rue peut cacher un indice, chaque monument peut être la clé d\'une énigme. Entre rires, stratégie et adrénaline, vous vivez une expérience intense où l\'observation et l\'esprit d\'équipe font toute la différence.</p><p style="margin: 10px 0;">Pendant 60 minutes, la ville s\'anime sous vos pas : explorez, déduisez, surprenez-vous… et laissez-vous porter par l\'énergie du jeu.</p><p style="margin: 10px 0;">En famille, entre amis ou pour un EVJF/EVG, préparez-vous à découvrir la ville autrement et à créer des souvenirs mémorables ✨</p>',
				$ville_id));
		
		$titre_section_jeu_unique = str_replace('[ville]', $ville_name,
			urbanquest_get_field_with_default('titre_section_jeu_unique', 'Un jeu de piste unique à [ville]', $ville_id));
		
		$description_section_jeu_unique = str_replace('[ville]', $ville_name,
			urbanquest_get_field_with_default('description_section_jeu_unique',
				'<p style="margin: 10px 0;">Si vous cherchez une activité insolite à [ville], Urban Quest est le jeu parfait : une chasse au trésor moderne, ludique et connectée qui vous entraîne à travers les rues et les lieux emblématiques de [ville]. Idéal pour ceux qui veulent découvrir autrement [ville] en mêlant culture, divertissement et esprit de compétition.</p><p style="margin: 10px 0;">🔎 <em>Fun fact :</em> avec Urban Quest, c\'est à votre tour d\'apporter couleurs et énergie à [ville] en résolvant ses énigmes !</p>',
				$ville_id));
	}
	
	// Helper pour le bouton de paiement
	$payment_url_button = urbanquest_get_field_with_default('payment_url', '#');
	$button_disabled = (empty($payment_url_button) || $payment_url_button === '#');
	$button_text_button = $button_disabled ? 'Bientôt' : 'Réserve ton jeu d\'exploration';
	$button_href_button = $button_disabled ? '#' : $payment_url_button;
	$button_style_disabled = $button_disabled ? 'opacity: 0.6; cursor: not-allowed; pointer-events: none;' : '';
	
	// Section "Pourquoi choisir" - Champs ACF avec valeurs par défaut
	$pourquoi_choisir_titre = urbanquest_get_field_with_default('pourquoi_choisir_titre', 'Pourquoi choisir Urban Quest à [ville] ?');
	$pourquoi_choisir_titre = str_replace('[ville]', $ville_name, $pourquoi_choisir_titre);
	
	// Galerie d'images (OPTIMISÉ : utilise le repeater ACF PRO)
	$pourquoi_choisir_images = array();
	$images_repeater = get_field('pourquoi_choisir_images');
	if ($images_repeater && is_array($images_repeater)) {
		foreach ($images_repeater as $image_item) {
			if (!empty($image_item['image'])) {
				$img_url = urbanquest_get_image_url($image_item['image'], 'medium', '');
				if ($img_url) {
					$pourquoi_choisir_images[] = $img_url;
				}
			}
		}
	}
	// Fallback pour compatibilité avec anciens champs (migration progressive)
	if (empty($pourquoi_choisir_images)) {
		for ($i = 1; $i <= 5; $i++) {
			$image_field = get_field('pourquoi_choisir_image_' . $i);
			if (!empty($image_field)) {
				$img_url = urbanquest_get_image_url($image_field, 'medium', '');
				if ($img_url) {
					$pourquoi_choisir_images[] = $img_url;
				}
			}
		}
	}
	
	$pourquoi_choisir_texte = urbanquest_get_field_with_default('pourquoi_choisir_texte', 'Un savant mélange jeu de piste, chasse au trésor et visite insolite : observation, logique, audace et stratégie vous feront grimper au classement, tout en (re)découvrant [ville] et ses lieux emblématiques.');
	$pourquoi_choisir_texte = str_replace('[ville]', $ville_name, $pourquoi_choisir_texte);
	
	// Fonctionnalités (OPTIMISÉ : utilise le repeater ACF PRO)
	$default_features = array(
		array(
			'icone' => 'calendar-heart',
			'titre' => '100% libre',
			'description' => 'Vous lancez la session quand vous voulez, où vous voulez.'
		),
		array(
			'icone' => 'smartphone',
			'titre' => 'Ultra simple',
			'description' => 'Vos instructions de jeu par e-mail, votre smartphone… c\'est tout.'
		),
		array(
			'icone' => 'swords',
			'titre' => 'Fun & challenge',
			'description' => 'Défis variés, énigmes malignes, score et classement.'
		)
	);
	
	$pourquoi_choisir_features = array();
	$features_repeater = get_field('pourquoi_choisir_features');
	if ($features_repeater && is_array($features_repeater)) {
		foreach ($features_repeater as $feature) {
			$pourquoi_choisir_features[] = array(
				'icone' => !empty($feature['icone']) ? $feature['icone'] : '',
				'titre' => !empty($feature['titre']) ? $feature['titre'] : '',
				'description' => !empty($feature['description']) ? $feature['description'] : ''
			);
		}
	}
	// Fallback pour compatibilité avec anciens champs (migration progressive)
	if (empty($pourquoi_choisir_features)) {
		for ($i = 1; $i <= 3; $i++) {
			$icone = urbanquest_get_field_with_default('pourquoi_choisir_feature_' . $i . '_icone', $default_features[$i-1]['icone']);
			$titre = urbanquest_get_field_with_default('pourquoi_choisir_feature_' . $i . '_titre', $default_features[$i-1]['titre']);
			$description = urbanquest_get_field_with_default('pourquoi_choisir_feature_' . $i . '_description', $default_features[$i-1]['description']);
			
			$pourquoi_choisir_features[] = array(
				'icone' => $icone,
				'titre' => $titre,
				'description' => $description
			);
		}
	}
	// Appliquer les valeurs par défaut si vides
	foreach ($pourquoi_choisir_features as $index => &$feature) {
		if (empty($feature['icone']) && isset($default_features[$index])) {
			$feature['icone'] = $default_features[$index]['icone'];
		}
		if (empty($feature['titre']) && isset($default_features[$index])) {
			$feature['titre'] = $default_features[$index]['titre'];
		}
		if (empty($feature['description']) && isset($default_features[$index])) {
			$feature['description'] = $default_features[$index]['description'];
		}
	}
	
	// Le bouton utilise directement le bouton de paiement existant
?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('single-game-content'); ?>>
			<div class="row">
				<div class="col-xs-12">
					<?php if (function_exists('urbanquest_display_breadcrumb_simple')) {
						urbanquest_display_breadcrumb_simple();
					} ?>
					
					<div class="row game-main-layout" style="margin-bottom: 60px;">
						<div class="col-md-6 game-main-content">
							<h2 style="line-height: 1.2;"><?php echo esc_html($titre_principal); ?></h2>
							
							<?php if (!empty($description_principale)) : ?>
								<div class="description-principale"><?php echo wp_kses_post($description_principale); ?></div>
							<?php endif; ?>

							<img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/10/notation-urbanquest-1024x219.png'); ?>" alt="Notation et avis Urban Quest - Jeu de piste à <?php echo esc_attr($ville_name); ?>" width="750" height="160" class="aligncenter size-large wp-image-26992" loading="lazy" />
							<h3>En groupe ?</h3>
							<p style="margin-top: 10px;">Pour les grands groupes nous proposons des options personnalisées sur demande pour rendre le jeu encore plus inoubliable, il suffit de nous <a href="<?php echo esc_url(get_site_url() . '/contact/'); ?>" rel="nofollow">contacter</a>.</p>

							<h3>Imaginés par nos game designers</h3>
							<p>Nos jeux sont conçus à la main par des game designers, en lien direct avec l'histoire et la géographie de <?php echo esc_html($ville_name); ?>... pour une expérience authentique et unique.</p>
						</div>

						<div class="col-md-6 game-sidebar-card" style="position: sticky; top: 20px;">
							<section class="game-card-section" style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 44px; padding: 20px; overflow: visible;">
								<div class="game-card-image" style="background-image: url('<?php echo esc_url($image_principale_url); ?>');">
									<?php if ($overlay_image_url) : ?>
										<img src="<?php echo esc_url($overlay_image_url); ?>" alt="Overlay Urban Quest" style="position: absolute; top: 0; left: 0; width: 100%;  pointer-events: none; z-index: 1;" />
									<?php endif; ?>
									<div class="game-price-badge-wrapper">
										<div class="game-price-badge">
											<?php if ($afficher_prix_original) : ?>
												<span style="color: #1f2a37; font-size: 18px; text-decoration: line-through; font-weight: 500; opacity: 0.7; margin-right: 4px;"><?php echo esc_html($prix_original); ?></span>
											<?php endif; ?>
											<span style="color: #1f2a37; font-size: 32px; font-weight: bold; line-height: 1;"><?php echo esc_html($prix); ?></span>
										</div>
									</div>
								</div>
								
								<div style="text-align: center; margin-top: 40px; margin-bottom: 18px;">
									<span style="color: #1f2a37; font-size: 16px; font-weight: bold;"><?php echo esc_html($titre_offre); ?></span>
								</div>
								
								<img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/10/made-in-france-1.png'); ?>" alt="Made in France - Jeu de piste Urban Quest fabriqué en France" width="234" height="19" class="wp-image-26996 size-full aligncenter" loading="lazy" />
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="users"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;"><?php echo esc_html($nombre_joueurs); ?></div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;"><?php echo esc_html($age_minimum); ?></div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="clock"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;"><?php echo esc_html($duree); ?></div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">pour enchaîner les défis</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="smartphone"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;">Votre smartphone</div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">suffit pour jouer</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; width: 90%; margin: 0 auto;"><i style="width: 28px; height: 28px; display: inline-block;" data-lucide="trophy"></i>
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;">Classement général</div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">Comparez votre score aux autres joueurs</div>
												</div></li>
										</ul>
									</li>
									<li style="list-style: none; display: center; align-items: flex-start; gap: 10px; width: 100%; margin: 0 auto; padding-top: 16px; padding-bottom: 24px;">
										<div style="text-align: center;"><a href="<?php echo esc_url($button_href_button); ?>" <?php echo (!$button_disabled) ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; font-size: 18px; padding: 10px 25px; text-decoration: none; border-radius: 999px; <?php echo esc_attr($button_style_disabled); ?>"><?php echo esc_html($button_text_button); ?></a></div></li>
								</ul>
							</section>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<h2 style="text-align: center;"><?php echo esc_html($pourquoi_choisir_titre); ?></h2>
					<?php if (!empty($pourquoi_choisir_images)) : ?>
						<div style="display: flex; justify-content: center; align-items: center; gap: 12px; flex-wrap: wrap; margin: 30px auto; max-width: 600px;">
							<?php foreach ($pourquoi_choisir_images as $img_url) : ?>
								<img src="<?php echo esc_url($img_url); ?>" alt="Composition photo Urban Quest - Jeu de piste à <?php echo esc_attr($ville_name); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 12px; flex-shrink: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" loading="lazy" />
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<p style="text-align: center; max-width: 860px; margin: 0 auto;"><?php echo esc_html($pourquoi_choisir_texte); ?></p>

					<div class="game-features-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; width: 100%; margin-top: 30px;">
						<?php foreach ($pourquoi_choisir_features as $feature) : 
							$feature_icone = !empty($feature['icone']) ? $feature['icone'] : 'circle';
							$feature_titre = !empty($feature['titre']) ? $feature['titre'] : '';
							$feature_description = !empty($feature['description']) ? $feature['description'] : '';
						?>
						<div class="game-feature-card" style="display: flex; align-items: flex-start; gap: 15px; background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; padding: 20px;">
							<div style="flex-shrink: 0;">
								<i style="width: 40px; height: 40px; display: block;" data-lucide="<?php echo esc_attr($feature_icone); ?>"></i>
							</div>
							<div style="flex: 1;">
								<?php if (!empty($feature_titre)) : ?>
									<strong style="display: block; color: #1f2a37; font-size: 16px; font-weight: bold; margin-bottom: 4px;"><?php echo esc_html($feature_titre); ?></strong>
								<?php endif; ?>
								<?php if (!empty($feature_description)) : ?>
									<div style="color: #6b7280; font-size: 14px; line-height: 1.5;"><?php echo esc_html($feature_description); ?></div>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<div style="text-align: center; margin: 30px 0 6px;">
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo (!$button_disabled) ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; font-size: 16px; padding: 10px 25px; text-decoration: none; border-radius: 999px; <?php echo esc_attr($button_style_disabled); ?>"><?php echo esc_html($button_text_button); ?></a>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<div class="row game-info-section" style="margin-bottom: 60px;">
						<div class="col-md-4 game-info-sidebar" style="position: sticky; top: 20px;">
							<section class="game-card-section" style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 44px; padding: 20px;">
								<h3 style="text-align: left;"><?php echo esc_html($titre_section_infos); ?></h3>
								<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
									<li style="list-style-type: none;">
										<ul style="list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: 1fr; gap: 18px;">
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto; padding-top: 16px;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;"><?php echo esc_html($tranche_age); ?></div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">Tant que marcher <?php echo esc_html($duree_minutes); ?> min n'est pas un challenger pour vous</div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">Nous avons aussi des jeux conçus spécialement pour les kids</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 1px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px;"><?php echo esc_html($distance); ?></div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 6px; font-weight: 500;">Pensez à prendre vos baskets!</div>
												</div></li>
											<li style="display: flex; align-items: flex-start; gap: 10px; padding-bottom: 18px; border-bottom: 0px solid #E6ECF4; width: 90%; margin: 0 auto;">
												<div style="line-height: 1.25; width: 100%;">
													<div style="color: #1f2a37; font-weight: bold; font-size: 16px; letter-spacing: 0.2px; margin-bottom: 12px;">Typologie de jeu</div>
													<div style="color: #6b7280; font-size: 16px; margin-top: 12px; font-weight: 500;">Fun</div>
													<?php echo urbanquest_render_jauge($jauge_fun); ?>
													<div style="color: #6b7280; font-size: 16px; margin-top: 12px; font-weight: 500;">Histoire</div>
													<?php echo urbanquest_render_jauge($jauge_histoire); ?>
													<div style="color: #6b7280; font-size: 16px; margin-top: 12px; font-weight: 500;">Réflexion</div>
													<?php echo urbanquest_render_jauge($jauge_reflexion); ?>
													<div style="color: #6b7280; font-size: 16px; margin-top: 12px; font-weight: 500;">Culture locale</div>
													<?php echo urbanquest_render_jauge($jauge_culture_locale); ?>
												</div></li>
										</ul>
									</li>
								</ul>
							</section>
						</div>

						<div class="col-md-8 game-info-content">
							<?php if ($ville_id) : ?>
								<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_terrain_de_jeu); ?></h3>
								<div><img src="<?php echo esc_url($image_section_terrain_de_jeu_url); ?>" alt="<?php echo esc_attr($ville_name); ?> - Terrain de jeu Urban Quest" width="750" height="139" class="aligncenter size-large wp-image-26967" style="border-radius: 12px;" loading="lazy" /></div>
								<?php echo wp_kses_post($description_section_terrain_de_jeu); ?>

								<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

								<h3 style="margin: 0 0 10px; text-align: center;"><?php echo esc_html($titre_section_jeu_unique); ?></h3>
								<?php echo wp_kses_post($description_section_jeu_unique); ?>
							<?php endif; ?>
						</div>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<h2 style="text-align: center;">Comment se déroule une partie ?</h2>
					<p style="text-align: center;"><img src="<?php echo esc_url(get_site_url() . '/wp-content/uploads/2025/08/newUQderoulement-2.png'); ?>" alt="Déroulement d'une partie Urban Quest - Comment jouer au jeu de piste" width="760" height="231" class="aligncenter size-full wp-image-26861" loading="lazy" /></p>
					<p class="p1" style="text-align: center; font-size: 16px;">Choisis ton parcours, pars à l'aventure dans la ville et mesure-toi aux autres équipes.</p>
					<p class="p1" style="text-align: center; font-size: 16px;">Une expérience fun, rapide à lancer et 100 % autonome !</p>
					<p style="text-align: center;">
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo (!$button_disabled) ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; font-size: 16px; padding: 10px 25px; text-decoration: none; border-radius: 999px; <?php echo esc_attr($button_style_disabled); ?>"><?php echo esc_html($button_text_button); ?></a>
					</p>

					<?php if (!empty($related_games)) : ?>
						<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
						<h2 style="text-align: center; margin-bottom: 40px;">Jeux qui peuvent vous intéresser</h2>
						<?php urbanquest_display_games_grid($related_games, ['columns' => 4, 'show_city' => true]); ?>
					<?php endif; ?>
					
					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					
					<h2>Informations clés</h2>
					<div class="info-table-wrapper">
						<table class="info-table">
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
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html($duree); ?></td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Équipe</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><?php echo esc_html($nombre_joueurs); ?></td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Matériel</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;">Votre smartphone + connexion data</td>
							</tr>
							<tr>
								<td style="border: 1px solid #eee; padding: 10px;"><strong>Tarif</strong></td>
								<td style="border: 1px solid #eee; padding: 10px;"><strong><?php echo esc_html($prix); ?> par équipe</strong></td>
							</tr>
						</tbody>
					</table>
					</div>
					<div style="text-align: center; padding-top: 30px;">
						<a href="<?php echo esc_url($button_href_button); ?>" <?php echo (!$button_disabled) ? 'target="_blank" rel="noopener sponsored"' : ''; ?> style="display: inline-block; background: #00bbff; color: white; font-weight: bold; font-size: 16px; padding: 10px 25px; text-decoration: none; border-radius: 999px; <?php echo esc_attr($button_style_disabled); ?>"><?php echo esc_html($button_text_button); ?></a>
					</div>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />
					
					<h2>FAQ – Jeu de piste <?php echo esc_html($ville_name); ?></h2>
					<?php echo do_shortcode('[xyz-ihs snippet="FAQ"]'); ?>

					<hr style="margin: 60px 0; border: none; border-top: 1px solid #ddd;" />

					<p style="font-size: 16px; color: #666; margin-top: 8px;">Besoin d'aide ? <a href="mailto:contact@urbanquest.fr">contact@urbanquest.fr</a> · <a href="tel:+33540248279">05 40 24 82 79</a></p>

				</div>
			</div>
		</article>
<?php endwhile; endif; ?>

		</div>
	</div>
</div>

<?php
if (!is_singular('elementor_library') && !is_singular('game')) {
	do_action('hestia_blog_related_posts');
}
?>
<div class="footer-wrapper">
<?php get_footer(); ?>
