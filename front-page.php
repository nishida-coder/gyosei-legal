<?php
/**
 * GYOSEI LEGAL — custom front page (child theme override of the TCD parent).
 * Self-contained: hero (background image + overlay box) + attorney directory.
 * header.php opens <div id="main_contents">; footer.php closes it — so output
 * sections directly inside, do NOT open #main_col.
 */
if (!defined('ABSPATH')) { exit; }
get_header();
$hero_img = get_stylesheet_directory_uri() . '/assets/img/hero.jpg';
?>

<section class="gl-hero" style="--gl-hero-img:url('<?php echo esc_url($hero_img); ?>');">
  <div class="gl-hero-inner">
    <div class="gl-hero-box">
      <p class="gl-hero-eyebrow">GYOSEI LEGAL — 暁星OB弁護士ネットワーク</p>
      <h1 class="gl-hero-title">暁星からつながる、<br>法務ネットワーク</h1>
      <p class="gl-hero-desc">暁星学園OBの弁護士による法務情報を発信するポータルサイト。信頼できる同窓弁護士の情報を集客し、クライアントと弁護士、さらに家族や関係者が安心してつながる法務情報の場を目指します。</p>
    </div>
  </div>
</section>

<section class="gl-home-list">
  <div class="gl-home-list-inner">
    <h2 class="gl-section-title"><span class="gl-section-en">Attorneys</span>掲載弁護士</h2>
    <div class="gl-grid">
      <?php
      $gl_q = new WP_Query([
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'posts_per_page'   => 24,
        'orderby'          => 'date',
        'order'            => 'DESC',
        'ignore_sticky_posts' => 1,
      ]);
      if ($gl_q->have_posts()) :
        while ($gl_q->have_posts()) : $gl_q->the_post();
          $gl_firm = trim((string) get_post_meta(get_the_ID(), 'gl_firm', true));
          $gl_grad = '';
          $gl_gt = get_the_terms(get_the_ID(), 'category3');
          if ($gl_gt && !is_wp_error($gl_gt)) { $gl_grad = $gl_gt[0]->name; }
          $gl_tags = get_the_tags();
      ?>
      <a class="gl-card" href="<?php the_permalink(); ?>">
        <div class="gl-card-photo">
          <?php if (has_post_thumbnail()) : the_post_thumbnail('medium'); else : ?>
            <span class="gl-initial"><?php echo esc_html(mb_substr(get_the_title(), 0, 1)); ?></span>
          <?php endif; ?>
        </div>
        <h3 class="gl-card-name"><?php the_title(); ?></h3>
        <?php if ($gl_firm) : ?><p class="gl-card-firm"><?php echo esc_html($gl_firm); ?></p><?php endif; ?>
        <?php if ($gl_grad) : ?><p class="gl-card-grad">暁星<?php echo esc_html($gl_grad); ?></p><?php endif; ?>
        <?php if ($gl_tags && !is_wp_error($gl_tags)) : ?>
        <div class="gl-card-tags">
          <?php $n = 0; foreach ($gl_tags as $t) { if ($n++ >= 5) break; echo '<span class="gl-tag">#' . esc_html($t->name) . '</span>'; } ?>
        </div>
        <?php endif; ?>
      </a>
      <?php
        endwhile;
      else :
      ?>
        <p class="gl-empty">掲載準備中です。</p>
      <?php endif; wp_reset_postdata(); ?>
    </div>
    <div class="gl-home-cta">
      <a href="/join/" class="gl-cta-btn">掲載をご希望の弁護士の方はこちら <span aria-hidden="true">&rsaquo;</span></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
