<?php
/**
 * GYOSEI LEGAL — one-shot content deploy (run via: wp eval-file deploy.php)
 * Idempotent: safe to re-run. Creates CF7 form, 3 pages, and model case #1.
 * Requires Contact Form 7 active before running.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "Run via wp eval-file\n"); exit(1); }

$theme   = get_stylesheet_directory();
$content = $theme . '/content';

function gl_log($m) { echo $m . "\n"; }

/* ---------- 1) Contact Form 7 ---------- */
$cf7_id = 0;
if (!class_exists('WPCF7_ContactForm')) {
    gl_log('!! Contact Form 7 not active — skipping form/pages CF7 wiring');
} else {
    // Reuse existing form if one with our title exists
    $existing = get_posts([
        'post_type'   => 'wpcf7_contact_form',
        'title'       => 'お問い合わせフォーム',
        'post_status' => 'publish',
        'numberposts' => 1,
    ]);
    $form = !empty($existing)
        ? WPCF7_ContactForm::get_instance($existing[0]->ID)
        : WPCF7_ContactForm::get_template();
    $form->set_title('お問い合わせフォーム');
    $props = $form->get_properties();
    $props['form']   = file_get_contents($content . '/cf7-form.txt');
    $props['mail']   = json_decode(file_get_contents($content . '/cf7-mail.json'), true);
    $props['mail_2'] = json_decode(file_get_contents($content . '/cf7-mail2.json'), true);
    $form->set_properties($props);
    $cf7_id = $form->save();
    gl_log('CF7 form id = ' . $cf7_id);
}

/* ---------- 2) Pages ---------- */
function gl_upsert_page($slug, $title, $html) {
    $existing = get_page_by_path($slug);
    $data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ];
    if ($existing) {
        $data['ID'] = $existing->ID;
        wp_update_post($data);
        return $existing->ID;
    }
    return wp_insert_post($data);
}

$pages = [
    ['contact',    'お問い合わせ',          'contact.html'],
    ['join',       '掲載申込・お問い合わせ', 'join.html'],
    ['management', 'GYOSEI LEGALについて',  'management.html'],
];
foreach ($pages as $p) {
    $html = file_get_contents($content . '/' . $p[2]);
    if ($cf7_id) {
        $html = str_replace('CF7_ID', (string) $cf7_id, $html);
    }
    $pid = gl_upsert_page($p[0], $p[1], $html);
    gl_log('page /' . $p[0] . '/ = ' . $pid);
}

/* ---------- 3) Taxonomy helper ---------- */
function gl_set_terms($post_id, $taxonomy, $names) {
    if (!taxonomy_exists($taxonomy)) {
        gl_log('   (taxonomy ' . $taxonomy . ' not registered — skipped)');
        return;
    }
    $ids = [];
    foreach ($names as $name) {
        $term = term_exists($name, $taxonomy);
        if (!$term) { $term = wp_insert_term($name, $taxonomy); }
        if (!is_wp_error($term)) { $ids[] = (int) $term['term_id']; }
    }
    if ($ids) { wp_set_object_terms($post_id, $ids, $taxonomy); }
}

/* ---------- 4) Model case #1 : 折田 裕彦 ---------- */
$meta = json_decode(file_get_contents($content . '/lawyers/orita-hirohiko.json'), true);
$body = file_get_contents($content . '/lawyers/orita-hirohiko.html');

$existing = get_page_by_path($meta['post_name'], OBJECT, 'post');
$post_data = [
    'post_title'   => $meta['post_title'],
    'post_name'    => $meta['post_name'],
    'post_content' => $body,
    'post_status'  => 'publish',
    'post_type'    => 'post',
];
if ($existing) { $post_data['ID'] = $existing->ID; $post_id = wp_update_post($post_data); }
else { $post_id = wp_insert_post($post_data); }
gl_log('model post 折田 裕彦 = ' . $post_id);

// post meta
foreach (($meta['meta'] ?? []) as $k => $v) { update_post_meta($post_id, $k, $v); }

// taxonomies
foreach (($meta['taxonomy'] ?? []) as $tax => $names) { gl_set_terms($post_id, $tax, $names); }

// featured image (photo staged on server next to the json)
$photo = $content . '/lawyers/orita-hirohiko.jpg';
if (file_exists($photo) && !has_post_thumbnail($post_id)) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $copy = wp_tempnam('orita-hirohiko.jpg');
    copy($photo, $copy);
    $file_array = ['name' => 'orita-hirohiko.jpg', 'tmp_name' => $copy];
    $att_id = media_handle_sideload($file_array, $post_id, '折田 裕彦 弁護士');
    if (is_wp_error($att_id)) {
        @unlink($copy);
        gl_log('!! featured image failed: ' . $att_id->get_error_message());
    } else {
        set_post_thumbnail($post_id, $att_id);
        gl_log('featured image attachment = ' . $att_id);
    }
} else {
    gl_log('featured image: ' . (has_post_thumbnail($post_id) ? 'already set' : 'photo not found at ' . $photo));
}

gl_log('=== DEPLOY DONE ===');
