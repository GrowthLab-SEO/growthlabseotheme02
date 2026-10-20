<?php
if (!defined('ABSPATH')) {
    exit;
}

// ── Categories ────────────────────────────────────────────────────────────────
$categories = get_categories(array(
    'hide_empty' => true,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

// ── Date range (first and last published post) ────────────────────────────────
$oldest_post = get_posts(array(
    'numberposts' => 1,
    'order'       => 'ASC',
    'orderby'     => 'date',
    'post_status' => 'publish',
));
$newest_post = get_posts(array(
    'numberposts' => 1,
    'order'       => 'DESC',
    'orderby'     => 'date',
    'post_status' => 'publish',
));

$year_start = !empty($oldest_post) ? (int) get_the_date('Y', $oldest_post[0]) : (int) date('Y');
$year_end   = !empty($newest_post) ? (int) get_the_date('Y', $newest_post[0]) : (int) date('Y');

// posts-filters.js reads the English month name from the option value, so only the visible label is translated
$months = array(
    'January'   => __('January', 'growthlabseotheme02'),
    'February'  => __('February', 'growthlabseotheme02'),
    'March'     => __('March', 'growthlabseotheme02'),
    'April'     => __('April', 'growthlabseotheme02'),
    'May'       => __('May', 'growthlabseotheme02'),
    'June'      => __('June', 'growthlabseotheme02'),
    'July'      => __('July', 'growthlabseotheme02'),
    'August'    => __('August', 'growthlabseotheme02'),
    'September' => __('September', 'growthlabseotheme02'),
    'October'   => __('October', 'growthlabseotheme02'),
    'November'  => __('November', 'growthlabseotheme02'),
    'December'  => __('December', 'growthlabseotheme02'),
);

$extra_classes = isset($args['classes']) ? esc_attr($args['classes']) : '';
?>

<section class="posts-filters <?= $extra_classes ?>" id="postsFilters">

    <form role="search" class="posts-filters__form" id="postsFiltersForm" novalidate>

        <!-- Search -->
        <div class="posts-filters__group posts-filters__group--search">
            <label class="screen-reader-text" for="pf-search"><?= esc_html__('Search posts', 'growthlabseotheme02') ?></label>
            <div class="posts-filters__input-wrap">
                <div class="posts-filters__icon">
                    <svg aria-hidden="true" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
                <input
                    class="posts-filters__input"
                    type="text"
                    id="pf-search"
                    name="search"
                    placeholder="<?= esc_attr__('Title, keywords, content…', 'growthlabseotheme02') ?>"
                    autocomplete="off" />
            </div>
        </div>

        <!-- Category -->
        <?php if (!empty($categories)): ?>
            <div class="posts-filters__group">
                <label class="screen-reader-text" for="pf-category"><?= esc_html__('Category', 'growthlabseotheme02') ?></label>
                <div class="posts-filters__select-wrap">
                    <select class="posts-filters__select" id="pf-category" name="category">
                        <option value=""><?= esc_html__('All categories', 'growthlabseotheme02') ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= esc_attr($cat->term_id) ?>">
                                <?= esc_html($cat->name) ?> (<?= (int) $cat->count ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                    <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
            </div>
        <?php endif ?>

        <!-- Month -->
        <div class="posts-filters__group">
            <label class="screen-reader-text" for="pf-month"><?= esc_html__('Month', 'growthlabseotheme02') ?></label>
            <div class="posts-filters__select-wrap">
                <select class="posts-filters__select" id="pf-month" name="month">
                    <option value=""><?= esc_html__('All months', 'growthlabseotheme02') ?></option>
                    <?php foreach ($months as $value => $name): ?>
                        <option value="<?= esc_attr($value) ?>">
                            <?= esc_html($name) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>

        <!-- Year -->
        <div class="posts-filters__group">
            <label class="screen-reader-text" for="pf-year"><?= esc_html__('Year', 'growthlabseotheme02') ?></label>
            <div class="posts-filters__select-wrap">
                <select class="posts-filters__select" id="pf-year" name="year">
                    <option value=""><?= esc_html__('All years', 'growthlabseotheme02') ?></option>
                    <?php for ($y = $year_end; $y >= $year_start; $y--): ?>
                        <option value="<?= (int) $y ?>"><?= (int) $y ?></option>
                    <?php endfor ?>
                </select>
                <svg class="posts-filters__chevron" aria-hidden="true" width="12" height="12"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </div>

        <!-- Actions -->
        <div class="posts-filters__actions">
            <button type="submit" class="posts-filters__btn posts-filters__btn--apply">
                <?= esc_html__('Apply filters', 'growthlabseotheme02') ?>
            </button>
            <button type="button" class="posts-filters__btn posts-filters__btn--reset" id="pfReset">
                <?= esc_html__('Clear', 'growthlabseotheme02') ?>
            </button>
        </div>

    </form>

    <!-- Active pills -->
    <div class="posts-filters__pills" id="pfPills" aria-live="polite"></div>

</section>