<?php
/**
 * Template part pour afficher les badges (tags et catégories) dans le header des pages de jeu
 *
 * @package UrbanQuest
 */

if (!is_singular('game')) {
	return;
}

$game_id = get_the_ID();

// Récupérer les tags du jeu
$game_tags = get_the_terms($game_id, 'game_tag');

// Récupérer les catégories (types de jeu) du jeu
$game_categories = get_the_terms($game_id, 'game_category');

// Afficher les tags et catégories si disponibles
if ((!empty($game_tags) && !is_wp_error($game_tags)) || (!empty($game_categories) && !is_wp_error($game_categories))) :
?>
<div class="game-header-badges-wrapper">
	<div class="game-meta-badges">
		<?php if (!empty($game_tags) && !is_wp_error($game_tags)) : ?>
			<?php foreach ($game_tags as $tag) : ?>
				<span class="game-tag-badge"><?php echo esc_html($tag->name); ?></span>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if (!empty($game_categories) && !is_wp_error($game_categories)) : ?>
			<?php foreach ($game_categories as $category) : ?>
				<?php $category_link = get_term_link($category, 'game_category'); ?>
				<?php if (!is_wp_error($category_link)) : ?>
					<a href="<?php echo esc_url($category_link); ?>" class="game-category-badge" style="text-decoration: none;">
						<?php echo esc_html($category->name); ?>
					</a>
				<?php else : ?>
					<span class="game-category-badge"><?php echo esc_html($category->name); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

