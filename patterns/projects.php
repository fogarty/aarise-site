<?php
/**
 * Title: Projects grid
 * Slug: aarise/projects
 * Categories: aarise
 * Description: Lists all projects (menu Projects in the dashboard): image, name, tagline. New projects appear automatically.
 * Keywords: projects, games, query, grid
 */
?>
<!-- wp:group {"align":"full","className":"aa-section aa-section--ink","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull aa-section aa-section--ink"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow">Projects</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"aa-measure"} -->
<h2 class="wp-block-heading aa-measure">What we are <em>making</em>.</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:query {"queryId":1,"query":{"perPage":12,"pages":0,"offset":0,"postType":"project","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"aa-projects"} -->
<div class="wp-block-query aa-projects"><!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->

<!-- wp:post-excerpt {"moreText":"Discover","excerptLength":40} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>New worlds are on their way.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->
