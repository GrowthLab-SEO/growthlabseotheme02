<?php
if (!defined('ABSPATH')) {
    exit;
}

if (get_field('toggle_block')):
    foreach (get_block_fields($block) as $key => $value) $$key = $value;
?>

    <section<?= get_block_id_attribute($block_id ?? '') ?>
        class="block featured-video<?= !empty($block['className']) ? ' ' . esc_attr($block['className']) : '' ?>"
        <?= block_style_attribute($block); ?>
        <?= get_block_extract_attribute($extract_block_from_content ?? false, $place ?? '') ?>>

        <div class="featured-video__wrapper">
            <?php
            // A YouTube URL that isn't a single video (channel, playlist, search...) has no ID and can't be played
            $is_youtube_video = !empty($is_youtube_video);
            $video_id = $is_youtube_video && !empty($youtube_url) ? get_yt_code($youtube_url) : false;
            $has_video = $is_youtube_video ? (bool) $video_id : !empty($video);

            if (isset($cover_image) && $cover_image) {
                img_print_picture_tag(img: $cover_image, is_cover: true, classes: "featured-video__cover", context: 'block/featured-video/cover');
            } elseif ($video_id) {
                $thumb = get_youtube_thumbnail($video_id, 'maxresdefault');
                echo "<img data-src='" . esc_url($thumb) . "' alt='' class='featured-video__cover lazy-image'>";
            }

            if ($is_youtube_video && !empty($youtube_url) && !$video_id) {
                print_block_notice(__('Featured Video: the YouTube URL is not a single video (channel, playlist or page URLs are not supported). This notice is only visible to editors.', 'growthlabseotheme02'));
            }
            ?>

            <?php
            if ($has_video) :
            ?>
                <button
                    type="button"
                    class="featured-video__btn"
                    <?php
                    if ($is_youtube_video) {
                        echo "data-videourl='" . esc_url($youtube_url) . "'";
                    } else {
                        echo "data-videoid='" . esc_url($video) . "'";
                    }
                    ?>
                    data-mode="<?= $enable_lightbox ? 'lightbox' : 'inline' ?>"
                    data-target=".featured-video__wrapper">
                    <span><?= esc_html__('Play', 'growthlabseotheme02') ?></span>
                </button>
            <?php
            endif;
            ?>
            <hr>
        </div>

    </section>

<?php
endif;
?>