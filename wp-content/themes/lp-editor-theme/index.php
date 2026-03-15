<?php
/**
 * The main template file
 *
 * @package LP_Editor_Theme
 */

get_header();
?>

<main class="site-main">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('コンテンツが見つかりません。', 'lp-editor-theme'); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
