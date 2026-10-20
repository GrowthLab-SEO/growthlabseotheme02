<?php
if (!defined('ABSPATH')) {
    exit;
}

$options = get_current_language_options();
$selected_social_networks = $args['social_networks'] ?? null;

// Option field => icon file and network name
$social_networks = array(
    'facebook_url'   => array('icon' => 'icon-facebook.svg', 'name' => 'Facebook'),
    'youtube_url'    => array('icon' => 'icon-youtube.svg', 'name' => 'YouTube'),
    'tiktok_url'     => array('icon' => 'icon-tiktok.svg', 'name' => 'TikTok'),
    'twitterx_url'   => array('icon' => 'icon-twitter-x.svg', 'name' => 'X (Twitter)'),
    'instagram_url'  => array('icon' => 'icon-instagram.svg', 'name' => 'Instagram'),
    'linkedin_url'   => array('icon' => 'icon-linkedin.svg', 'name' => 'LinkedIn'),
);

if ($selected_social_networks) {
    $social_networks = array_filter($social_networks, function ($key) use ($selected_social_networks) {
        return in_array($key, (array) $selected_social_networks);
    }, ARRAY_FILTER_USE_KEY);
}
?>

<ul class="social-networks">
    <?php foreach ($social_networks as $field_key => $network) : ?>
        <?php
        $url = $options[$field_key] ?? '';
        /* translators: %s: social network name, e.g. Facebook */
        $label = sprintf(__('Visit our %s page', 'growthlabseotheme02'), $network['name']);
        ?>
        <?php if (!empty($url)) : ?>
            <li>
                <a href="<?= esc_url($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= esc_attr($label) ?>">
                    <?= get_theme_svg('assets/icons/' . $network['icon']) ?>
                </a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
</ul>
