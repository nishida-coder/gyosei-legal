<?php
/**
 * GYOSEI LEGAL — GENSEN Child Theme
 * 暁星OB弁護士ネットワークポータル
 * Prestige × Modern brushup (sister of gensen-gyosei / GYOSEI MEDICAL, gensen-dental / GYOSEI DENTAL)
 *
 * PERSON-FIRST MODEL
 * ------------------
 * Unlike the medical / dental verticals (where the clinic is the primary entity
 * and the doctor is shown inside it), attorneys are sole practitioners: the
 * individual is the primary entity and the law firm ("事務所") is an attachment
 * that follows the person. Listings therefore lead with the lawyer's NAME (text),
 * with the affiliated firm rendered as a text subtitle ("〇〇法律事務所 パートナー").
 * No firm logo is required — the person is the focus.
 *
 * Taxonomy mapping inherited from the GENSEN parent (shared with sister sites):
 *   category   → 取扱分野 (practice area)
 *   category2  → エリア   (region)
 *   category3  → 暁星卒業年代 (graduation era)
 * Optional post meta:
 *   gl_firm    → 所属法律事務所名（肩書き併記可）  e.g. "西田法律事務所 パートナー"
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GLEGAL_CHILD_VERSION', '1.0.0');

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'gensen-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'glegal-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['gensen-parent-style'],
        GLEGAL_CHILD_VERSION
    );

    wp_enqueue_style(
        'glegal-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;700&family=Shippori+Mincho+B1:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'glegal-brushup',
        get_stylesheet_directory_uri() . '/assets/css/brushup.css',
        ['glegal-child-style'],
        GLEGAL_CHILD_VERSION
    );

    wp_enqueue_script(
        'glegal-brushup-js',
        get_stylesheet_directory_uri() . '/assets/js/brushup.js',
        [],
        GLEGAL_CHILD_VERSION,
        true
    );
}, 20);

/* =========================================================================
 * SEO / GEO enhancements
 * ========================================================================= */

define('GYOSEI_SITE_NAME', 'GYOSEI LEGAL');
define('GYOSEI_SITE_TAGLINE', '暁星卒業生OB弁護士の情報ポータル');
define('GYOSEI_SITE_DESC', '暁星学園を卒業され弁護士としてご活躍されているOBの情報ポータル。取扱分野、エリア、卒業年代から信頼できる弁護士を探せる暁星OB弁護士ネットワーク。');
define('GYOSEI_OGP_IMAGE', 'https://gyosei-legal.jp/wp-content/uploads/logo.png');
// Own-domain contact mailbox for the legal vertical. (Sister sites consolidate
// dental enquiries into the medical mailbox; legal is kept separate because the
// vertical is distinct and more sensitive.)
define('GYOSEI_CONTACT_EMAIL', 'info@gyosei-legal.jp');

/**
 * Strip empty meta description tags output by the parent theme,
 * then our own richer tags run later via wp_head hook.
 */
add_action('wp_head', function () { ob_start(); }, 0);
add_action('wp_head', function () {
    $head = ob_get_clean();
    if (is_string($head) && $head !== '') {
        $head = preg_replace(
            '/<meta\s+name=["\']description["\']\s+content=["\']\s*["\']\s*\/?>\s*/i',
            '',
            $head
        );
        echo $head;
    }
}, PHP_INT_MAX);

/**
 * Read the optional law-firm attachment for a listing.
 */
function gyosei_legal_firm($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $firm = get_post_meta($post_id, 'gl_firm', true);
    return is_string($firm) ? trim($firm) : '';
}

/**
 * Build title/description/image/url context for the current page.
 */
function gyosei_seo_context() {
    $ctx = [
        'title'       => GYOSEI_SITE_NAME . ' | ' . GYOSEI_SITE_TAGLINE,
        'description' => GYOSEI_SITE_DESC,
        'image'       => GYOSEI_OGP_IMAGE,
        'url'         => home_url('/'),
        'type'        => 'website',
    ];

    if (is_front_page() || is_home()) {
        // defaults above
    } elseif (is_singular('post')) {
        // The post title IS the attorney's name (person-first model).
        $lawyer_name = get_the_title();
        $cats = get_the_category();
        $practice = !empty($cats) ? $cats[0]->name : null;
        $area = null;
        $grad = null;
        $tax_area = get_the_terms(get_the_ID(), 'category2');
        if (!is_wp_error($tax_area) && !empty($tax_area)) { $area = $tax_area[0]->name; }
        $tax_grad = get_the_terms(get_the_ID(), 'category3');
        if (!is_wp_error($tax_grad) && !empty($tax_grad)) { $grad = $tax_grad[0]->name; }
        $firm = gyosei_legal_firm();

        $desc_parts = ['暁星学園OB弁護士「' . $lawyer_name . '」の情報。'];
        if ($firm) $desc_parts[] = '所属：' . $firm . '。';
        if ($practice) $desc_parts[] = '取扱分野：' . $practice . '。';
        if ($area) $desc_parts[] = 'エリア：' . $area . '。';
        if ($grad) $desc_parts[] = '暁星卒業年代：' . $grad . '。';
        $desc_parts[] = 'GYOSEI LEGALは暁星卒業生OB弁護士を集約する情報サイトです。';

        $ctx['title']       = $lawyer_name . ($firm ? '（' . $firm . '）' : '') . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = mb_substr(implode('', $desc_parts), 0, 160);
        $thumb = get_the_post_thumbnail_url(null, 'full');
        if ($thumb) $ctx['image'] = $thumb;
        $ctx['url']  = get_permalink();
        $ctx['type'] = 'profile';
    } elseif (is_page()) {
        $ctx['title']       = get_the_title() . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = wp_strip_all_tags(get_the_excerpt()) ?: GYOSEI_SITE_DESC;
        $ctx['description'] = mb_substr($ctx['description'], 0, 160);
        $ctx['url']         = get_permalink();
        $ctx['type']        = 'article';
    } elseif (is_category() || is_tax() || is_archive()) {
        $obj = get_queried_object();
        $name = is_object($obj) && !empty($obj->name) ? $obj->name : '一覧';
        $ctx['title']       = $name . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = $name . 'に該当する暁星OB弁護士の一覧。取扱分野、エリア、卒業年代から検索できる暁星OB弁護士ネットワーク。';
        $ctx['url']         = is_object($obj) ? get_term_link($obj) : home_url('/');
    } elseif (is_search()) {
        $q = get_search_query();
        $ctx['title']       = '「' . $q . '」の検索結果 | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = '「' . $q . '」に該当する暁星OB弁護士の検索結果。';
    }
    return $ctx;
}

/**
 * Inject OGP, Twitter Card, and a meta description.
 * Suppressed when Rank Math SEO is active to avoid duplicate tags.
 */
add_action('wp_head', function () {
    if (defined('RANK_MATH_VERSION')) return;
    $ctx = gyosei_seo_context();
    $title = esc_attr($ctx['title']);
    $desc  = esc_attr($ctx['description']);
    $image = esc_url($ctx['image']);
    $url   = esc_url($ctx['url']);
    $type  = esc_attr($ctx['type']);

    echo "\n<!-- GYOSEI LEGAL SEO -->\n";
    echo '<meta name="description" content="' . $desc . '">' . "\n";
    echo '<meta property="og:type" content="' . $type . '">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $desc . '">' . "\n";
    echo '<meta property="og:url" content="' . $url . '">' . "\n";
    echo '<meta property="og:image" content="' . $image . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(GYOSEI_SITE_NAME) . '">' . "\n";
    echo '<meta property="og:locale" content="ja_JP">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $desc . '">' . "\n";
    echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
}, 2);

/**
 * Override the document title for legibility across AI/search engines.
 * Only when Rank Math is not handling titles.
 */
add_filter('pre_get_document_title', function ($title) {
    if (defined('RANK_MATH_VERSION')) return $title;
    $ctx = gyosei_seo_context();
    return $ctx['title'] ?: $title;
}, 20);

/**
 * Build the person-first Attorney JSON-LD for a single listing.
 * Modeled as a Person (the individual lawyer) whose firm is attached via worksFor.
 */
function gyosei_legal_person_jsonld() {
    $cats = get_the_category();
    $practice = !empty($cats) ? $cats[0]->name : null;
    $area = null;
    $tax_area = get_the_terms(get_the_ID(), 'category2');
    if (!is_wp_error($tax_area) && !empty($tax_area)) { $area = $tax_area[0]->name; }
    $thumb = get_the_post_thumbnail_url(null, 'full');
    $firm = gyosei_legal_firm();

    $person = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        '@id'         => get_permalink() . '#lawyer',
        'name'        => get_the_title(),
        'url'         => get_permalink(),
        'jobTitle'    => '弁護士',
        'description' => '暁星学園OB弁護士' . ($practice ? '（取扱分野：' . $practice . '）' : '') . '。GYOSEI LEGAL掲載。',
        'alumniOf'    => [
            '@type' => 'EducationalOrganization',
            'name'  => '暁星高等学校',
        ],
        'memberOf'    => [
            '@type' => 'Organization',
            'name'  => GYOSEI_SITE_NAME,
            'url'   => home_url('/'),
        ],
    ];
    if ($thumb) $person['image'] = $thumb;
    if ($practice) $person['knowsAbout'] = $practice;
    if ($firm) {
        // The law firm is a LegalService that the attorney works for.
        $person['worksFor'] = [
            '@type' => 'Attorney',
            'name'  => $firm,
        ];
    }
    if ($area) {
        $person['workLocation'] = [
            '@type' => 'Place',
            'name'  => $area,
        ];
    }
    return $person;
}

/**
 * Inject JSON-LD structured data for GEO (LLM/Generative Engine) discovery.
 *
 * Rank Math already outputs Organization + WebSite + BreadcrumbList, so when
 * it is active we only add our unique person-first Attorney schema. When
 * Rank Math is absent, emit the full set.
 */
add_action('wp_head', function () {
    $json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $rankmath_active = defined('RANK_MATH_VERSION');

    if ($rankmath_active) {
        if (is_singular('post')) {
            $person = gyosei_legal_person_jsonld();
            echo "\n<!-- GYOSEI LEGAL Attorney JSON-LD -->\n";
            echo '<script type="application/ld+json">' . wp_json_encode($person, $json_flags) . '</script>' . "\n";
        }
        return;
    }

    // Organization (site-wide)
    $organization = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Organization',
        '@id'           => home_url('/#organization'),
        'name'          => GYOSEI_SITE_NAME,
        'alternateName' => '暁星OB弁護士ネットワーク',
        'url'           => home_url('/'),
        'logo'          => [
            '@type'  => 'ImageObject',
            'url'    => GYOSEI_OGP_IMAGE,
            'width'  => 800,
            'height' => 200,
        ],
        'description'   => GYOSEI_SITE_DESC,
        'email'         => GYOSEI_CONTACT_EMAIL,
        'sameAs'        => [
            'https://gyosei-medical.com/',
            'https://gyosei-dental.com/',
        ],
    ];

    // WebSite + SearchAction
    $website = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => home_url('/#website'),
        'name'            => GYOSEI_SITE_NAME,
        'alternateName'   => '暁星OB弁護士の情報サイト',
        'url'             => home_url('/'),
        'description'     => GYOSEI_SITE_DESC,
        'inLanguage'      => 'ja',
        'publisher'       => ['@id' => home_url('/#organization')],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}'),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    echo "\n<!-- GYOSEI LEGAL JSON-LD -->\n";
    echo '<script type="application/ld+json">' . wp_json_encode($organization, $json_flags) . '</script>' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode($website, $json_flags) . '</script>' . "\n";

    // Person (Attorney) per individual lawyer page
    if (is_singular('post')) {
        $person = gyosei_legal_person_jsonld();
        $person['isPartOf'] = ['@id' => home_url('/#website')];
        echo '<script type="application/ld+json">' . wp_json_encode($person, $json_flags) . '</script>' . "\n";
    }

    // BreadcrumbList everywhere except the front page
    if (!is_front_page()) {
        $items = [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'ホーム',
                'item'     => home_url('/'),
            ],
        ];
        $pos = 2;
        if (is_singular('post')) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        } elseif (is_category() || is_tax()) {
            $obj = get_queried_object();
            if ($obj) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $obj->name,
                    'item'     => get_term_link($obj),
                ];
            }
        } elseif (is_page()) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        }

        $breadcrumb = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb, $json_flags) . '</script>' . "\n";
    }
}, 3);

/**
 * Append practice-scope tag chips (対応法務の範囲) to single attorney pages.
 * Driven by WP post tags so each listing carries its own #企業法務 #離婚 etc.
 */
add_filter('the_content', function ($content) {
    if (is_singular('post') && in_the_loop() && is_main_query()) {
        $tags = get_the_tags();
        if ($tags && !is_wp_error($tags)) {
            $chips = '';
            foreach ($tags as $t) {
                $chips .= '<a class="gl-tag" href="' . esc_url(get_tag_link($t->term_id)) . '">#' . esc_html($t->name) . '</a>';
            }
            $content .= '<div class="gl-tags"><span class="gl-tags-label">対応法務の範囲</span>'
                      . '<div class="gl-tags-list">' . $chips . '</div></div>';
        }
    }
    return $content;
}, 20);

/**
 * Hint AI crawlers explicitly via robots meta (complement robots.txt).
 */
add_filter('wp_robots', function ($robots) {
    $robots['max-image-preview'] = 'large';
    $robots['max-snippet']       = -1;
    $robots['max-video-preview'] = -1;
    return $robots;
});

/* =========================================================================
 * Output buffer rewrites — parent-theme-level fixes applied to every page.
 *
 * The GENSEN (TCD050) parent renders the same markup across all sister sites,
 * so these structural fixes carry over. Content-specific legacy fixes that the
 * medical / dental sites needed (broken cross-domain images, legacy clinic WEB
 * blocks) are intentionally omitted — GYOSEI LEGAL is authored fresh.
 * ========================================================================= */

add_action('template_redirect', 'gyosei_force_https_buffer', 1);
function gyosei_force_https_buffer() {
    if (is_admin()) return;
    ob_start('gyosei_legal_rewrite');
}

function gyosei_legal_rewrite($html) {
    if (!is_string($html) || $html === '') return $html;

    // 1) Force HTTPS on own + sister asset URLs (mixed-content safety) — ONLY when
    //    the current request is itself HTTPS. Before the free SSL cert is issued the
    //    site is served over plain HTTP; upgrading asset URLs to https:// then points
    //    every stylesheet/script at a non-existent certificate and the page renders
    //    completely unstyled. Gate on is_ssl() so http stays http until SSL is live.
    if (is_ssl()) {
        $patterns = [
            'http://gyosei-legal.jp/',
            'http://www.gyosei-legal.jp/',
            'http://gyosei-medical.com/',
            'http://www.gyosei-medical.com/',
            'http://gyosei-dental.com/',
            'http://www.gyosei-dental.com/',
        ];
        $replace = [
            'https://gyosei-legal.jp/',
            'https://www.gyosei-legal.jp/',
            'https://gyosei-medical.com/',
            'https://www.gyosei-medical.com/',
            'https://gyosei-dental.com/',
            'https://www.gyosei-dental.com/',
        ];
        $html = str_replace($patterns, $replace, $html);
    }

    // 1b) Neutralize the medical-site logo hardcoded in the parent header.php
    //     (a cross-site <img> to gyosei-medical.com) with a clean text wordmark
    //     placeholder until the real GYOSEI LEGAL logo is supplied.
    $html = preg_replace(
        '#<div id="header_logo">.*?</a></div>#us',
        '<div id="header_logo"><a href="' . esc_url(home_url('/')) . '" class="gl-wordmark">GYOSEI LEGAL</a></div>',
        $html
    );

    // 2) Strip `js-ellipsis` from listing titles inside #post_list so the parent
    //    theme's textOverflowEllipsis() doesn't truncate the name after a <br>.
    $html = preg_replace(
        '#(<p class="title)\s+js-ellipsis(" style="margin-left: 10px;"><strong>)#u',
        '$1$2',
        $html
    );

    // 3) Rewrite the listing's <p class="title"> (which contains the attorney name,
    //    optionally with a "(XX年卒)" grad-year line after a <br>) into a clean
    //    block OUTSIDE `.title` so the parent's 2-line clamp can't clip it.
    //    Person-first: the NAME is the primary element.
    //      <strong>西田 志門<br>(99年卒)</strong>
    //      <strong>福田 隆慧（03卒）</strong>
    $html = preg_replace_callback(
        '#<p class="title" style="margin-left: 10px;"><strong>(.*?)</strong></p>#u',
        function ($m) {
            $raw = $m[1];
            $tmp = preg_replace('#<br\s*/?\s*>#i', '|', $raw);
            $name = $tmp;
            $grad = '';
            if (strpos($tmp, '|') !== false) {
                $parts = explode('|', $tmp, 2);
                $name = trim($parts[0]);
                $grad = trim($parts[1]);
            } elseif (preg_match('#^(.+?)[（(]([^）)]*?卒[^）)]*?)[）)]\s*$#u', $tmp, $mm)) {
                $name = trim($mm[1]);
                $grad = '(' . trim($mm[2]) . ')';
            }
            $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $grad_esc = htmlspecialchars($grad, ENT_QUOTES, 'UTF-8');
            $out  = '<div class="gd-dr-meta gl-lawyer-meta">';
            $out .= '<span class="gd-dr-name gl-lawyer-name">' . $name_esc . '</span>';
            if ($grad !== '') {
                $out .= '<span class="gd-dr-grad gl-lawyer-grad">' . $grad_esc . '</span>';
            }
            $out .= '</div>';
            return $out;
        },
        $html
    );

    // 4) Hero catchphrase: parent theme hardcodes a near-invisible color and a
    //    broken inline opacity. Rebuild with a clean class for full restyling.
    //    Person-first wording: 暁星OB × 弁護士.
    $html = preg_replace_callback(
        '#<p class="catchphrase rich_font"[^>]*>.*?</p>#us',
        function ($m) {
            return '<p class="catchphrase rich_font gd-hero-catch">'
                . '<span class="gd-hero-catch-box">'
                . '<span class="gd-hero-catch-line1">暁星OB</span>'
                . '<span class="gd-hero-catch-x">×</span>'
                . '<span class="gd-hero-catch-line2">弁護士 情報ポータル</span>'
                . '</span></p>';
        },
        $html
    );

    // 5) Rebuild footer SNS icons with inline SVG (parent icon-font fails on this install).
    $sns_svg_fb = '<svg class="gd-sns-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8.2h2.76l.41-3.2H13.5V7.55c0-.92.26-1.55 1.58-1.55h1.69V3.14C16.48 3.1 15.48 3 14.33 3 11.9 3 10.24 4.48 10.24 7.2v2.4H7.5v3.2h2.74V21h3.26z"/></svg>';
    $sns_svg_ig = '<svg class="gd-sns-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M12 2.2c3.2 0 3.58 0 4.85.07 1.17.05 1.8.25 2.23.42.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.37 1.06.42 2.23.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.05 1.17-.25 1.8-.42 2.23a3.7 3.7 0 0 1-.9 1.38 3.7 3.7 0 0 1-1.38.9c-.42.16-1.06.37-2.23.42-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-1.17-.05-1.8-.25-2.23-.42a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.37-1.06-.42-2.23C2.2 15.58 2.2 15.2 2.2 12s0-3.58.07-4.85c.05-1.17.25-1.8.42-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.37 2.23-.42C8.42 2.2 8.8 2.2 12 2.2z"/></svg>';
    $sns_html = '<ul id="footer_social_link" class="gd-sns-list">'
        . '<li class="gd-sns-item gd-sns-facebook"><a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook">' . $sns_svg_fb . '</a></li>'
        . '<li class="gd-sns-item gd-sns-instagram"><a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram">' . $sns_svg_ig . '</a></li>'
        . '</ul>';
    $html = preg_replace(
        '#<ul id="footer_social_link">.*?</ul>#us',
        $sns_html,
        $html
    );

    // 6) Replace deprecated Google Maps `pb=` embed iframes with the legacy
    //    parameterless `?q=lat,lng&output=embed` format (the pb= URLs now 404
    //    with X-Frame-Options:SAMEORIGIN). Generic — fires only if a map exists.
    $html = preg_replace_callback(
        '#<iframe([^>]*?)\s+src="https://www\.google\.com/maps/embed\?pb=([^"]+)"([^>]*)>(\s*</iframe>)#u',
        function ($m) {
            $before = $m[1];
            $pb     = $m[2];
            $after  = $m[3];
            $end    = $m[4];
            $lat = $lon = null;
            if (preg_match('#!2d(-?\d+(?:\.\d+)?)#', $pb, $mlon)) $lon = $mlon[1];
            if (preg_match('#!3d(-?\d+(?:\.\d+)?)#', $pb, $mlat)) $lat = $mlat[1];
            if ($lat === null || $lon === null) return $m[0];
            $new_src = 'https://www.google.com/maps?q=' . $lat . ',' . $lon . '&z=16&output=embed';
            return '<iframe' . $before . ' src="' . $new_src . '"' . $after . '>' . $end;
        },
        $html
    );

    // 7) Homepage bottom banner strip: grid the clearfix container, rebuild each
    //    sister-site banner card, and inject a CTA card linking to /join/.
    //    Banner image filenames are placeholders until the sister banners are
    //    uploaded — until then the per-banner rebuild simply no-ops.
    if (strpos($html, '<!-- END #main_col -->') !== false &&
        strpos($html, 'cb_content-wysiwyg') !== false &&
        !strpos($html, 'gm-home-cta-btn')) {

        $html = preg_replace(
            '#(<div id="cb_1"[^>]*cb_content-wysiwyg[^>]*>\s*<div class="inner">\s*<div class=")(\s*clearfix)(")#u',
            '$1$2 gm-home-banners$3',
            $html
        );

        $html = preg_replace(
            '#<div class=""(\s+style="padding-bottom:\s*30px[^"]*")?>#u',
            '<div class="gm-home-banner-item"$1>',
            $html
        );

        // Sister-site banners (filenames are placeholders — set when banners land).
        $banner_labels = [
            'GYOSEI_MEDICAL' => ['title' => 'GYOSEI MEDICAL', 'sub' => '暁星OB医師ポータル'],
            'GYOSEI_DENTAL'  => ['title' => 'GYOSEI DENTAL',  'sub' => '暁星OB歯科医師ポータル'],
            'LIBUN'          => ['title' => 'LIBUN',          'sub' => 'Reputation / webPR'],
        ];

        foreach ($banner_labels as $match_str => $label) {
            $safe = preg_quote($match_str, '#');
            $pattern =
                '#<div class="gm-home-banner-item"[^>]*>' .
                '\s*<center[^>]*>\s*<a\s+href="([^"]+)"[^>]*>\s*' .
                '<img[^>]+src="([^"]*' . $safe . '[^"]*)"[^>]*>\s*' .
                '</a>\s*</center>' .
                '(?:(?!<div class="gm-home-).)*?' .
                '</div>#us';

            $title = $label['title'];

            $html = preg_replace_callback(
                $pattern,
                function ($m) use ($title) {
                    $href = $m[1];
                    $src  = $m[2];
                    $is_external = (strpos($href, 'gyosei-legal.jp') === false);
                    $target_attr = $is_external ? ' target="_blank" rel="noopener"' : '';
                    return '<div class="gm-home-banner-item">' .
                        '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"' . $target_attr . '>' .
                        '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . htmlspecialchars($title, ENT_QUOTES) . '">' .
                        '</a>' .
                        '</div>';
                },
                $html
            );
        }

        $cta_card =
            '<div class="gm-home-banner-item gm-home-cta-card">' .
            '<a href="/join/" class="gm-home-cta-btn">' .
            '<span class="gm-home-cta-label">掲載をご希望の弁護士の方はこちら</span>' .
            '<span class="gm-home-cta-arrow">&rsaquo;</span>' .
            '</a></div>';
        $html = preg_replace(
            '#(</div>)(\s*</div>\s*</div>\s*</div>\s*</div>\s*<!-- END \#main_col -->)#u',
            '$1' . $cta_card . '$2',
            $html,
            1
        );
    }

    return $html;
}
