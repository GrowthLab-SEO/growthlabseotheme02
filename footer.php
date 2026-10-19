  <?php
  if (!defined('ABSPATH')) {
    exit;
  }
  ?>

  <footer id="site-footer" role="contentinfo" class="site-footer">

    <?php
    global $post;
    $post_id = $post ? $post->ID : 0;

    $options = get_current_language_options();
    $options = fill_acf_group_defaults($options, 'options', 'option');
    foreach ($options as $key => $value) $$key = $value;

    $phone_number = $contact_phone ?: $main_phone_number;
    if (get_field('custom_footer')) $form_section = get_field('form_section');
    if (get_field('custom_footer')) $content_section = get_field('content_section');
    if (get_field('custom_footer')) $copyright_section = get_field('copyright_section');
    ?>

    <?php
    if (empty($form_section['hide_section'])):
      // Custom offices from the page form section replace the site offices only when show_offices is "custom"
      $offices = ($form_section['show_offices'] ?? null) === "custom" ? ($form_section['offices'] ?? null) : $options['offices'];
    ?>
      <section class="contact-form-footer">

        <?php if (!empty($offices) && empty($form_section['switch_side_map'])): ?>
          <div class="contact-form-footer__offices">
            <ul class="footer-offices-selector">
              <?php foreach ($offices as $office): ?>
                <li class="footer-offices-selector__item btn btn--secondary" data-office="<?= esc_attr($office['city']); ?>">
                  <span>
                    <?= esc_html($office['city']); ?>
                  </span>
                </li>
              <?php endforeach ?>
            </ul>

            <div class="contact-form-footer__offices-wrapper">
              <?php foreach ($offices as $office): ?>
                <div class="footer-office" data-office="<?= esc_attr($office['city']); ?>">
                  <div class="footer-office__inner">
                    <?php
                    get_template_part('template-parts/google', 'maps', array(
                      'iframe_src' => $office['google_maps_embed_code'],
                      'classes' => 'footer-office__map',
                      'name' => $office['city']
                    ));
                    ?>

                    <address class="footer-office__info">

                      <<?= $office["city_tag"] ?> class="footer-office__city">
                        <a href="<?= $office['target_page_url'] ?>" aria-label="<?= esc_attr($office['city']); ?>">
                          <?= esc_html($office['city']); ?>
                        </a>
                      </<?= $office["city_tag"] ?>>

                      <div class="footer-office__item">
                        <?php include get_stylesheet_directory() . '/assets/icons/icon-location-outlined.svg'; ?>
                        <p><?= esc_html($office['address']); ?></p>
                      </div>

                      <p class="footer-office__item">
                        <?php include get_stylesheet_directory() . '/assets/icons/icon-phone.svg'; ?>
                        <a href="tel:+1<?= get_flat_number($office['phone']) ?>" aria-label="<?= esc_attr($office['phone']); ?>"><?= esc_html($office['phone']); ?></a>
                      </p>

                      <?php if ($office['cta_button'] && isset($office['cta_button'])): ?>
                        <a href="<?= $office['cta_button']['url'] ?>" class="footer-office__cta btn btn--tertiary" target="<?= $office['cta_button']['target'] ?>" aria-label="<?= esc_attr($office['cta_button']['title']); ?>">
                          <span>
                            <?= esc_html($office['cta_button']['title']); ?>
                          </span>
                        </a>
                      <?php endif; ?>
                    </address>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php elseif (!empty($form_section['side_image'])): ?>
          <div class="contact-form-footer__side-image">
            <?php img_print_picture_tag($form_section['side_image'], is_cover: false, max_size: "cover-desktop", min_size: "cover-mobile", classes: "side-image bg-image") ?>
          </div>
        <?php endif; ?>

        <div class="contact-form-footer__wrapper">
          <div class="contact-form-footer__inner">

            <div class="contact-form">

              <?php
              print_title($form_section['contact_form_title'] ?? '', $form_section['contact_form_title_tag'] ?? null, "contact-form__title tx-center");
              ?>

              <div class="contact-form__description formatted-text tx-center">
                <?php echo wp_kses_post(wpautop($form_section['contact_form_description'] ?? '')); ?>
              </div>

              <div class="contact-form__form">
                <?php render_gravity_form($form_section['contact_form'] ?? null, display_title: false, display_description: false); ?>
              </div>

            </div>

          </div>

        </div>
      </section>
    <?php
    endif;
    ?>

    <?php
    if (empty($content_section['hide_section'])):
    ?>

      <section class="content-info-footer">
        <div class="content-info-footer__wrapper container">

          <?php if (!empty($content_section['first_column_content'])): ?>
            <div class="content-info-footer__col">
              <div class="formatted-text">
                <?= $content_section['first_column_content'] ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if (!empty($content_section['logo']) || !empty($content_section['cta_button'])): ?>

            <div class="content-info-footer__col--social">
              <?php if (!empty($content_section['logo'])) img_print_picture_tag(img: $content_section['logo'], classes: "footer-logo", max_size: "medium") ?>


              <div class="content-info-footer__social">
                <?php get_template_part('template-parts/social', 'networks', ["social_networks" => $content_section['select_social_networks'] ?? null]); ?>
              </div>

              <?php if (!empty($content_section['cta_button'])): ?>
                <a href="<?= $content_section['cta_button']['url'] ?>" class="btn btn--primary" target="<?= $content_section['cta_button']['target'] ?>" aria-label="<?= esc_attr($content_section['cta_button']['title']) ?>">
                  <span>
                    <?= esc_html($content_section['cta_button']['title']) ?>
                  </span>
                </a>
              <?php endif; ?>
            </div>

          <?php endif; ?>

          <?php if (!empty($content_section['last_column_content'])): ?>
            <div class="content-info-footer__col">
              <div class="formatted-text">
                <?= $content_section['last_column_content'] ?>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </section>

    <?php endif; ?>

    <?php
    if (empty($copyright_section['hide_section'])):
    ?>
      <section class="copyright-footer">
        <div class="copyright-footer__wrapper container">
          <p class="copyright-footer__advertisement">
            <?= !empty($copyright_section['copyright']) ? $copyright_section['copyright'] : '' ?>
          </p>

          <?php
          if (!empty($copyright_section['footer_links_menu']) && !empty(wp_get_nav_menus())) {
            wp_nav_menu(
              array(
                'menu'  => $copyright_section['footer_links_menu'],
                'container'          => 'nav',
                'container_class' => 'footer-nav',
                'menu_class'      => 'footer-nav__menu',
                'items_wrap'      => '<ul class="%2$s">%3$s</ul>',
                'link_before'          => '<span>',
                'link_after'              => '</span>'
              )
            );
          }
          ?>

          <a href="https://growthlabseo.com/" target="_blank" class="copyright-footer__logo" aria-label="Growth Lab SEO">
            <img src="<?= get_template_directory_uri() . "/assets/img/Growth-Lab-Logo-footer.webp" ?>" alt="Growth Lab SEO Logo" width="270" height="50">
          </a>

        </div>
      </section>
    <?php
    endif;
    ?>
  </footer>

  <?php wp_footer(); ?>

  </body>

  </html>