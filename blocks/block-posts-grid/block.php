<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
    $grid_type = get_valid_field_choice('grid_type', $grid_type ?? '');

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    if (is_front_page()) {
        $paged = (get_query_var('page')) ? get_query_var('page') : 1;
    }

    $args = array(
        'post_type' => $grid_type,
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'order' => 'DESC',
        'paged' => $paged,
    );

    if ($grid_type === "testimonial") {
        $args['posts_per_page'] = -1;
    }

    if ($grid_type === "case-result") {
        $args['posts_per_page'] = -1;
        $args['meta_key'] = 'numerical_amount';
        $args['orderby'] = 'meta_value_num';
    }

    switch ($grid_type) {
        case 'case-result':
            $selected_posts = $select_results_posts ?? [];
            break;

        case 'team':
            $selected_posts = $select_team_members_posts ?? [];
            break;

        case 'post':
            $selected_posts = $select_blog_posts ?? [];
            break;

        case 'testimonial':
            $selected_posts = $select_testimonials_posts ?? [];
            break;

        default:
            $selected_posts = [];
            break;
    }

    // "Show all" only applies to post type grids; custom grids always use their own selection or items
    if ($grid_type === "custom") {
        $show_all = false;
    }

    $shows_selection = isset($show_all) && !$show_all;

    if ($shows_selection) {
        $args['post__in'] = !empty($selected_posts) ? array_map('intval', (array) $selected_posts) : [];
        $args['posts_per_page'] = -1;
        $args['orderby'] = 'post__in';
        // Explicit selections keep their order and must not require a value for the amount meta key
        unset($args['meta_key']);
    }

    if ($grid_type === "custom" && !empty($select_or_create_items)) {
        $args['post_type'] = "any";
        $args['post__in'] = !empty($select_posts) ? array_map('intval', (array) $select_posts) : [];
        $args['orderby'] = 'post__in';
    }

    // Custom grid built from the repeater instead of selected posts
    $uses_custom_items = $grid_type === "custom" && empty($select_or_create_items);

    // Without selected posts or custom items the grid is not rendered; editors see a notice instead
    if ($uses_custom_items ? empty($custom_grid) : ($shows_selection && empty($args['post__in']))) {
        print_block_notice(__('Posts Grid: no posts selected or created. This notice is only visible to editors.', 'growthlabseotheme02'));
        return;
    }

    // Each paginated grid gets its own page parameter, so paginating one grid doesn't paginate the others
    $page_arg = '';
    if (!$uses_custom_items && (int) $args['posts_per_page'] > 0) {
        $page_arg = get_posts_grid_page_arg();
        if ($page_arg !== '') {
            $paged = max(1, absint($_GET[$page_arg] ?? 1));
            $args['paged'] = $paged;
        }
    }

    $query = $uses_custom_items ? null : new WP_Query($args);
?>


    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block posts-grid<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <?php
        if (isset($background_image) && $background_image) img_print_picture_tag(img: $background_image, is_cover: true, classes: "posts-grid__bg bg-image gradient-overlay");
        ?>

        <div class="posts-grid__wrapper container">

            <?php
            if (isset($pretitle) && $pretitle) print_title($pretitle, $pretitle_tag, "posts-grid__pretitle pretitle");
            if (isset($title) && $title) print_title($title, $title_tag, "posts-grid__title tx-center");
            ?>

            <?php if ($text_content): ?>
                <div class="posts-grid__content formatted-text tx-center">
                    <?= $text_content ?>
                </div>
            <?php endif ?>

            <div class="posts-grid__feed">

                <div class="posts-grid__grid  <?= esc_attr($grid_type) ?>">

                    <?php
                    if ($uses_custom_items) {
                        foreach ($custom_grid as $item) {
                            get_template_part('template-parts/default', 'card', array(
                                "title_tag" => $items_title_tag ?? "h3",
                                "classes" => "posts-grid__card " . $grid_type,
                                "picture" => $item['picture'] ?? '',
                                "title" => $item['title'] ?? '',
                                "link_url" => $item['link']['url'] ?? '',
                                "link_target" => $item['link']['target'] ?? '_self',
                                "content" => $item['content'] ?? '',
                            ));
                        }
                    } elseif ($query && $query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();

                            $post_fields = get_fields(get_the_ID()) ?: [];

                            switch ($grid_type) {
                                case 'case-result':
                                    get_template_part('template-parts/result', 'card', array(
                                        "title_tag" => $items_title_tag ?? "h3",
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "numerical_amount" => ($post_fields['numerical_amount'] ?? ''),
                                        "case_title" => ($post_fields['case_title'] ?? ''),
                                        "case_description" => ($post_fields['case_description'] ?? ''),
                                    ));
                                    break;

                                case 'team':
                                    get_template_part('template-parts/team', 'card-grid', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => ($post_fields['headshot'] ?? ''),
                                        "name" => get_the_title(),
                                        "role" => ($post_fields['role'] ?? ''),
                                        "link_url" => get_the_permalink(),
                                        "link_target" => '_self',
                                    ));
                                    break;

                                case 'post':
                                    get_template_part('template-parts/post', 'card', array(
                                        "title_tag" => $items_title_tag ?? "h3",
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => get_the_post_thumbnail_url(),
                                        "meta" => get_the_date(),
                                        "title" => get_the_title(),
                                        "excerpt" => get_the_excerpt(),
                                        "link_url" => get_the_permalink(),
                                        "link_target" => '_self',
                                    ));
                                    break;

                                case 'testimonial':
                                    get_template_part('template-parts/testimonial', 'card', array(
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => ($post_fields['author_picture'] ?? ''),
                                        "author" => ($post_fields['author_name'] ?? ''),
                                        "role" => ($post_fields['author_role'] ?? ''),
                                        "content" => ($post_fields['testimonial_content'] ?? ''),
                                        "source" => ($post_fields['testimonial_source'] ?? ''),
                                        "link_url" => ($post_fields['testimonial_source_link'] ?? ''),
                                    ));
                                    break;

                                default:
                                    get_template_part('template-parts/default', 'card', array(
                                        "title_tag" => $items_title_tag ?? "h3",
                                        "classes" => "posts-grid__card grid " . $grid_type,
                                        "picture" => get_the_post_thumbnail_url(),
                                        "title" => get_the_title(),
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

                <?php
                if ($query && $query->max_num_pages > 1) {
                    get_template_part('template-parts/posts', 'pagination', array(
                        'paged' => $paged,
                        'query' => $query,
                        'page_arg' => $page_arg,
                        'classes' => 'case-results__pagination'
                    ));
                }
                ?>

            </div>

        </div>
    </section>

<?php
    wp_reset_postdata();
endif;
