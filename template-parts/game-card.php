<?php
/**
 * Template partiel pour afficher une carte de jeu
 * 
 * @param WP_Post|int $game Le post du jeu ou son ID
 * @param array $args Arguments optionnels :
 *   - 'show_city' => bool (défaut: true) - Afficher le nom de la ville
 *   - 'layout' => string (défaut: 'card') - Layout: 'card' ou 'simple'
 *   - 'image_size' => string (défaut: 'medium') - Taille de l'image
 *   - 'excerpt_length' => int (défaut: 20) - Nombre de mots pour l'extrait
 */

// Vérifier que $game est défini
if (!isset($game) || empty($game)) {
	return;
}

if (!isset($args)) {
	$args = [];
}

$defaults = [
	'show_city' => true,
	'layout' => 'card',
	'image_size' => 'medium',
	'excerpt_length' => 20,
];

$args = wp_parse_args($args, $defaults);

// Récupérer les données du jeu
$game_id = is_object($game) ? $game->ID : $game;
$game_data = urbanquest_get_game_display_data($game_id);

// Extraire les données
$game_image = $game_data['image'];
$game_title = $game_data['title'];
$game_excerpt = $game_data['excerpt'];
$game_permalink = get_permalink($game_id);
$city_name = $game_data['city_name'] ?? '';

// Tronquer l'extrait si nécessaire
if ($args['excerpt_length'] > 0) {
	$game_excerpt = wp_trim_words($game_excerpt, $args['excerpt_length']);
}

// Layout card (par défaut)
if ($args['layout'] === 'card') :
?>
<div class="urbanquest-game-card" style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%; display: flex; flex-direction: column;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
	<a href="<?php echo esc_url($game_permalink); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
		<div style="position: relative; width: 100%; height: 200px; overflow: hidden;">
			<img 
				src="<?php echo esc_url($game_image); ?>" 
				alt="Jeu de piste connecté Urban Quest - <?php echo esc_attr($game_title); ?><?php echo $city_name ? ' à ' . esc_attr($city_name) : ''; ?>" 
				style="width: 100%; height: 100%; object-fit: cover;" 
				loading="lazy" 
			/>
		</div>
		<div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
			<h3 style="margin: 0 0 10px; font-size: 20px; color: #1f2a37; line-height: 1.3;"><?php echo esc_html($game_title); ?></h3>
			<?php if ($args['show_city'] && !empty($city_name)) : ?>
				<p style="margin: 0 0 10px; color: #6b7280; font-size: 14px; font-weight: 500;">
					<i style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;" data-lucide="map-pin"></i>
					<?php echo esc_html($city_name); ?>
				</p>
			<?php endif; ?>
			<?php if (!empty($game_excerpt)) : ?>
				<p style="margin: 0 0 15px; color: #6b7280; font-size: 14px; line-height: 1.5; flex: 1;"><?php echo esc_html($game_excerpt); ?></p>
			<?php endif; ?>
			<div style="text-align: center; margin-top: auto;">
				<span style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 8px 20px; border-radius: 999px; font-size: 14px;">
					Découvrir le jeu
				</span>
			</div>
		</div>
	</a>
</div>

<?php
// Layout simple (même style que card mais pour les grilles)
elseif ($args['layout'] === 'simple') :
?>
<div class="urbanquest-game-card" style="background: #F7F9FC; border: 1px solid #E6ECF4; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%; display: flex; flex-direction: column;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
	<a href="<?php echo esc_url($game_permalink); ?>" style="text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%;">
		<div style="position: relative; width: 100%; height: 200px; overflow: hidden;">
			<img 
				src="<?php echo esc_url($game_image); ?>" 
				alt="Jeu de piste connecté Urban Quest - <?php echo esc_attr($game_title); ?><?php echo $city_name ? ' à ' . esc_attr($city_name) : ''; ?>" 
				style="width: 100%; height: 100%; object-fit: cover;" 
				loading="lazy" 
			/>
		</div>
		<div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
			<h3 style="margin: 0 0 10px; font-size: 20px; color: #1f2a37; line-height: 1.3;"><?php echo esc_html($game_title); ?></h3>
			<?php if ($args['show_city'] && !empty($city_name)) : ?>
				<p style="margin: 0 0 10px; color: #6b7280; font-size: 14px; font-weight: 500;">
					<?php echo esc_html($city_name); ?>
				</p>
			<?php endif; ?>
			<?php if (!empty($game_excerpt)) : ?>
				<p style="margin: 0 0 15px; color: #6b7280; font-size: 14px; line-height: 1.5; flex: 1;"><?php echo esc_html($game_excerpt); ?></p>
			<?php endif; ?>
			<div style="text-align: center; margin-top: auto;">
				<span style="display: inline-block; background: #00bbff; color: white; font-weight: bold; padding: 8px 20px; border-radius: 999px; font-size: 14px;">
					Découvrir le jeu
				</span>
			</div>
		</div>
	</a>
</div>
<?php endif; ?>

