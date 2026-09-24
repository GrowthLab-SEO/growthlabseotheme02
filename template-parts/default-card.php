<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="default-card <?php echo $args["classes"];
                            if (isset($args['content']) && $args['content'] && $args['content'] !== '') echo " content" ?>">

    <?php
    if ($args['link_url'] && $args['link_url'] !== '') {
        echo "<a href=" . $args['link_url'] . " class='default-card__wrapper' target=" . $args['link_target'] . " aria-label='" . esc_attr($args['title']) . "'>";
    } else {
        echo "<div class='default-card__wrapper'>";
    }
    if (isset($args['picture']) && $args['picture'] && $args['picture'] !== '') {
        img_print_picture_tag(img: $args["picture"], max_size: "featured-small", min_size: "featured-small", classes: "default-card__pic");
    } else {
        include get_stylesheet_directory() . '/assets/icons/icon-file-image.svg';
    }
    ?>

    <div class="default-card__inner tx-center">

        <?php print_title($args["title"], $args["title_tag"], "default-card__title"); ?>

    </div>

    <?php
    if ($args['link_url'] && $args['link_url'] !== '') {
        echo "</a>";
    } else {
        echo "</div>";
    }
    ?>

    <?php if (isset($args['content']) && $args['content'] && $args['content'] !== ''): ?>
        <div class="default-card__con">
            <div class="default-card__con-wrapper">
                <?php print_title($args["title"], "p", "default-card__con-title"); ?>

                <div class="default-card__con-content">
                    <?= $args['content'] ?>
                </div>

                <?php
                if ($args['link_url'] && $args['link_url'] !== '') {
                    echo "<a href=" . $args['link_url'] . " class='default-card__con-link' target=" . $args['link_target'] . " aria-label='" . esc_attr($args['title']) . "'>";
                    echo "<span class='default-card__con-link-text'>LEARN MORE</span>";
                    include get_stylesheet_directory() . '/assets/icons/icon-arrow-right.svg';
                    echo "</a>";
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>