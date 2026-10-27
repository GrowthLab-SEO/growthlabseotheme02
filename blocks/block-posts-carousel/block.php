<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;

    switch ($carousel_type) {
        case 'case-result':
            $posts = $select_results_posts;
            break;

        case 'team':
            $posts = $select_team_members_posts;
            break;

        case 'post':
            $posts = $select_blog_posts;
            break;

        case 'testimonial':
            $posts = $select_testimonials_posts;
            break;

        default:
            $carousel_type = isset($select_or_create_items) && $select_or_create_items ? "any" : "";
            $posts = isset($select_or_create_items) && $select_or_create_items ? ($select_posts ?? []) : [];
            break;
    }

    // Selected post IDs (empty when nothing was selected) and custom items (only in custom mode without selection)
    $posts = !empty($posts) ? array_map('intval', (array) $posts) : [];
    $custom_items = $carousel_type === "" && !empty($custom_carousel) ? $custom_carousel : [];

    // Without selected posts or custom items the carousel is not rendered; editors see a notice instead
    if (empty($posts) && empty($custom_items)) {
        print_block_notice(__('Posts Carousel: no posts selected or created. This notice is only visible to editors.', 'growthlabseotheme02'));
        return;
    }

    // Only query when posts were selected, so an empty selection never falls back to every post
    $query = null;
    if (!empty($posts)) {
        $query = new WP_Query(array(
            'post_type' => $carousel_type,
            'posts_per_page' => count($posts),
            'post__in' => $posts,
            'post_status' => 'publish',
            'orderby' => 'post__in',
        ));
    }
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="
        block 
        posts-carousel
        <?php if ($carousel_type === "team") echo " bg-gradient"; ?>
        <?= esc_attr($carousel_type) ?><?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>
        "
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "posts-carousel__bg bg-image gradient-overlay", context: 'block/posts-carousel/background');
        ?>

        <div class="posts-carousel__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "posts-carousel__pretitle pretitle tx-center");
            if (isset($title) && $title) print_title($title, $title_tag, "posts-carousel__title tx-center");
            ?>

            <?php if (!empty($text_content)): ?>
                <div class="posts-carousel__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <div class="posts-carousel__carousel" data-type="<?= esc_attr($carousel_type) ?>">

                <div class="splide">
                    <div class="splide__track">
                        <div class="splide__list">

                            <?php
                            if (!empty($custom_items)) {
                                foreach ($custom_items as $item) {
                                    get_template_part('template-parts/default', 'card', array(
                                        "classes" => "splide__slide posts-carousel__card",
                                        "picture" => $item['picture'] ?? '',
                                        "title" => $item['title'] ?? '',
                                        "title_tag" => ($item['title_tag'] ?? '') ?: ($items_title_tag ?? 'h3'),
                                        "link_url" => $item['link']['url'] ?? '',
                                        "link_target" => $item['link']['target'] ?? '_self',
                                        "content" => $item['content'] ?? '',
                                    ));
                                }
                            } elseif ($query && $query->have_posts()) {
                                while ($query->have_posts()) {
                                    $query->the_post();

                                    $post_fields = get_fields(get_the_ID()) ?: [];

                                    switch ($carousel_type) {
                                        case 'case-result':
                                            get_template_part('template-parts/result', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "numerical_amount" => ($post_fields['numerical_amount'] ?? ''),
                                                "case_title" => ($post_fields['case_title'] ?? ''),
                                                "title_tag" => $items_title_tag ?? 'h3',
                                            ));
                                            break;

                                        case 'team':
                                            get_template_part('template-parts/team', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => ($post_fields['headshot'] ?? ''),
                                                "title" => get_the_title(),
                                                "title_tag" => $items_title_tag ?? 'h3',
                                                "first_name" => ($post_fields['first_name'] ?? ''),
                                                "role" => ($post_fields['role'] ?? ''),
                                                "content" => get_the_excerpt(),
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_self',
                                            ));
                                            break;

                                        case 'post':
                                            get_template_part('template-parts/post', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => get_the_post_thumbnail_url(),
                                                "meta" => get_the_date(),
                                                "title" => get_the_title(),
                                                "title_tag" => $items_title_tag ?? 'h3',
                                                "excerpt" => get_the_excerpt(),
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_self',
                                            ));
                                            break;

                                        case 'testimonial':
                                            get_template_part('template-parts/testimonial', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "author" => ($post_fields['author_name'] ?? ''),
                                                "role" => ($post_fields['author_role'] ?? ''),
                                                "content" => ($post_fields['testimonial_content'] ?? ''),
                                            ));
                                            break;

                                        default:
                                            get_template_part('template-parts/default', 'card', array(
                                                "classes" => "splide__slide posts-carousel__card",
                                                "picture" => get_the_post_thumbnail_url(),
                                                "title" => get_the_title(),
                                                "title_tag" => $items_title_tag ?? 'h3',
                                                "link_url" => get_the_permalink(),
                                                "link_target" => '_self',
                                                "content" => get_the_excerpt(),
                                            ));
                                            break;
                                    }
                                }
                            }
                            ?>

                        </div>
                    </div>

                    <?php
                    get_template_part('template-parts/splide', 'navigation', array(
                        'nav_link' => $cta_link,
                        'classes' => 'posts-carousel__arrows'
                    ));
                    ?>

                </div>
            </div>

        </div>
    </section>

<?php
    wp_reset_postdata();
endif;
?>