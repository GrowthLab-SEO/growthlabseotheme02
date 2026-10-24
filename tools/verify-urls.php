<?php
/**
 * Prints, as JSON, the URLs of the local site that tools/verify.js checks. Command line only.
 * Usage: php tools/verify-urls.php
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
// The theme lives in <site>/wp-content/themes/<theme>/tools
require dirname(__DIR__, 4) . '/wp-load.php';

$urls = ['home' => home_url('/')];

foreach (get_pages(['post_status' => 'publish', 'number' => 25, 'sort_column' => 'menu_order']) as $page) {
    if ((int) $page->ID === (int) get_option('page_on_front')) continue;
    $urls['page:' . $page->post_name] = get_permalink($page);
}

$posts_page = (int) get_option('page_for_posts');
if ($posts_page) $urls['blog'] = get_permalink($posts_page);

$post = get_posts(['numberposts' => 1, 'post_status' => 'publish']);
if ($post) $urls['post'] = get_permalink($post[0]);

foreach (get_post_types(['public' => true, '_builtin' => false]) as $type) {
    $item = get_posts(['post_type' => $type, 'numberposts' => 1, 'post_status' => 'publish']);
    if ($item) $urls["single:$type"] = get_permalink($item[0]);
}

$category = get_categories(['number' => 1, 'hide_empty' => true]);
if ($category) $urls['archive'] = get_category_link($category[0]);

$urls['search'] = home_url('/?s=law');
$urls['404'] = home_url('/gl-verify-missing-page/');

echo json_encode(['home' => home_url('/'), 'template' => get_template(), 'urls' => $urls], JSON_UNESCAPED_SLASHES);
