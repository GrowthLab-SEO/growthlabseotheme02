<?php
foreach (get_post_fields_with_defaults() as $key => $value) $$key = $value;
?>
<?php get_header(); ?>

<section class="single__inner">

    <div class="single__wrapper container">

        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

                <aside role="complementary" class="single__sidebar single-team__sidebar formatted-text">
                    <?php
                    if (isset($headshot) && !empty($headshot)) {
                        img_print_picture_tag(img: $headshot, max_size: "cover-mobile", classes: "single-team__picture");
                    }
                    ?>

                    <ul class="single-team__points">
                        <?php
                        if ($highlighted_points && !empty($highlighted_points)) :
                            foreach ($highlighted_points as $point) : ?>
                                <li class="single-team__point">
                                    <p class="h4"><?= wp_kses_post($point['heading'] ?? '') ?></p>
                                    <?= wp_kses_post($point['content'] ?? '') ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </aside>

                <main id="main-content" role="main" class="single__main">
                    <div class="single-team__heading">
                        <p class="single-team__role pretitle"><?= wp_kses_post($role ?? ''); ?></p>
                        <h2 class="single-team__title"><?php the_title(); ?></h2>
                    </div>
                    <div class="single-team__content formatted-text">
                        <?php the_content(); ?>
                    </div>
                </main>
        <?php endwhile;
        endif; ?>
    </div>
</section>

<?php get_footer(); ?>