<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>

<main id="main-content">

    <section class="error-404" style="padding-block: 200px;">

        <div class="container">

            <p class="h1 tx-center"><?= esc_html__('404:', 'growthlabseotheme02') ?></p>
            <h1 class="h2 tx-center"><?= esc_html__('Page Not Found', 'growthlabseotheme02') ?></h1>

        </div>

    </section>

</main>

<?php get_footer() ?>
