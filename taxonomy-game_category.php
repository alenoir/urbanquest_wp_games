<?php
/**
 * The template for displaying game category archive pages.
 *
 * @package Hestia
 * @since Hestia 1.0
 */

// Récupérer le terme de la taxonomie
$term = get_queried_object();
$category_name = $term->name;
$category_description = $term->description;
$category_id = $term->term_id;

// Titre SEO
$category_title_seo = 'Jeux de piste ' . esc_html($category_name) . ' | Urban Quest - Aventure connectée';

// Description SEO
if (empty($category_description)) {
	$category_description = "Découvrez tous nos jeux de piste connectés de type " . esc_html($category_name) . ". Urban Quest vous propose des aventures de jeu de piste adaptées à tous les âges, à faire en famille ou entre amis.";
}

// Meta description SEO (limité à 160 caractères)
$category_meta_description = wp_strip_all_tags($category_description);
if (strlen($category_meta_description) > 160) {
	$category_meta_description = substr($category_meta_description, 0, 157) . '...';
}

// Image pour Open Graph
$category_image_url = get_site_url() . '/wp-content/uploads/2025/09/ville-photos-uq-1024x190.png';

// Modifier le title via le filtre WordPress
add_filter('document_title_parts', function($title) use ($category_title_seo) {
	$title['title'] = $category_title_seo;
	return $title;
}, 10);

// Ajouter les balises meta SEO dans le head
add_action('wp_head', function() use ($category_title_seo, $category_meta_description, $category_image_url) {
	// Meta description
	echo '<meta name="description" content="' . esc_attr($category_meta_description) . '" />' . "\n";
	
	// Open Graph
	echo '<meta property="og:title" content="' . esc_attr($category_title_seo) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr($category_meta_description) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url($category_image_url) . '" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url(get_term_link($category_id, 'game_category')) . '" />' . "\n";
	echo '<meta property="og:site_name" content="Urban Quest" />' . "\n";
	echo '<meta property="og:locale" content="fr_FR" />' . "\n";
	
	// Twitter Cards
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr($category_title_seo) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr($category_meta_description) . '" />' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url($category_image_url) . '" />' . "\n";
	
	// Canonical
	echo '<link rel="canonical" href="' . esc_url(get_term_link($category_id, 'game_category')) . '" />' . "\n";
	
	// Robots
	echo '<meta name="robots" content="index, follow" />' . "\n";
}, 1);

get_header(); ?>

<div class="<?php echo esc_attr( hestia_layout() ); ?>">
	<div class="blog-post blog-post-wrapper">
		<div class="container">
			<article id="post-<?php echo esc_attr($category_id); ?>" class="section section-text">
				<div class="row">
					<div class="col-md-12">
						
						<!-- En-tête de la catégorie -->
						<header class="entry-header">
							<h1 class="entry-title">Jeux de piste <?php echo esc_html($category_name); ?></h1>
							<?php if (!empty($category_description)) : ?>
								<div class="entry-content">
									<p><?php echo wp_kses_post($category_description); ?></p>
								</div>
							<?php endif; ?>
						</header>
						
						<hr class="urbanquest-section-divider" />
						
						<!-- Liste des jeux de la catégorie -->
						<?php
						// Récupérer tous les jeux de cette catégorie
						$games_query = new WP_Query(array(
							'post_type' => 'game',
							'posts_per_page' => -1,
							'tax_query' => array(
								array(
									'taxonomy' => 'game_category',
									'field' => 'term_id',
									'terms' => $category_id,
								),
							),
							'orderby' => 'title',
							'order' => 'ASC',
						));
						
						$games = $games_query->posts;
						
						if (!empty($games)) : ?>
							<h2 class="urbanquest-section-title">Nos jeux de piste <?php echo esc_html($category_name); ?></h2>
							<?php urbanquest_display_games_grid($games, ['columns' => 4, 'show_city' => true]); ?>
						<?php else : ?>
							<!-- Message si aucun jeu -->
							<div class="urbanquest-empty-state">
								<h2>Aucun jeu disponible dans cette catégorie</h2>
								<p>
									Il n'y a actuellement aucun jeu de piste disponible dans la catégorie "<?php echo esc_html($category_name); ?>".
								</p>
								<p>
									<a href="<?php echo esc_url(home_url('/')); ?>">Retour à l'accueil</a> pour découvrir nos autres jeux de piste.
								</p>
							</div>
						<?php endif; ?>
						
						<?php wp_reset_postdata(); ?>
						
					</div>
				</div>
			</article>
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

