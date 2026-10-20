<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$post_id = (int) get_option('page_for_posts');
// Blog listings follow the sidebar setting of the posts page
$hide_sidebar = $post_id && get_field("hide_sidebar", $post_id);
?>

<section class="blog__inner">

    <div class="blog__wrapper container">

        <main role="main" class="blog__main">

            <?php if (!have_posts()): ?>
                <p class="blog__no-results"><?= esc_html(__('No results found. Try a different search.', 'growthlabseotheme02')) ?></p>
            <?php endif; ?>

            <div class="blog__loop">

                <?php while (have_posts()) {

                    the_post();

                    $card_class = !$hide_sidebar ? "blog__card--horizontal post-card--horizontal" : "";

                    get_template_part('template-parts/post', 'card', array(
                        "classes" => "blog__card " . $card_class,
                        "picture" => get_the_post_thumbnail_url(),
                        "meta" => get_the_date(),
                        "title" => get_the_title(),
                        "title_tag" => get_current_language_options()["posts_default_title_tag"] ?? "p",
                        "excerpt" => get_the_excerpt(),
                        "link_url" => get_the_permalink(),
                        "link_target" => '_self',
                    ));
                }
                ?>

            </div>

            <?php get_template_part('template-parts/posts', 'pagination', array(
                'classes'    => 'blog__pagination',
                'paged'      => max(1, get_query_var('paged', 1)),
                'query'      => $wp_query,
            )); ?>
        </main>

        <?php
        if (!$hide_sidebar) {
            $args = array('ID' => $post_id, 'classes' => 'blog__sidebar');
            get_sidebar('blog', $args);
        }
        ?>

    </div>

</section>

<?php get_footer() ?>