<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 */

get_header(); ?>

<main class="site-main">
    <div class="container">
        <?php if (have_posts()) : ?>
            <div class="posts-container">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
                        <header class="post-header">
                            <h2 class="post-title">
                                <a href="<?php the_permalink(); ?>" rel="bookmark">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            <div class="post-meta">
                                <span class="posted-on">
                                    Posted on <?php echo get_the_date(); ?>
                                </span>
                                <span class="byline">
                                    by <?php the_author(); ?>
                                </span>
                            </div>
                        </header>
                        
                        <div class="post-content">
                            <?php if (is_home() || is_archive()) : ?>
                                <?php the_excerpt(); ?>
                                <a href="<?php the_permalink(); ?>" class="read-more">
                                    Read More →
                                </a>
                            <?php else : ?>
                                <?php the_content(); ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            
            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('← Previous', 'custom-theme'),
                'next_text' => __('Next →', 'custom-theme'),
            ));
            ?>
            
        <?php else : ?>
            <div class="no-posts">
                <h2>Nothing Found</h2>
                <p>It seems we can't find what you're looking for. Perhaps searching can help.</p>
                <?php get_search_form(); ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>