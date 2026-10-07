<?php
/**
 * Title: Heading + text
 * Slug: aarise/split
 * Categories: aarise
 * Description: Section with a heading on the left and text on the right.
 * Keywords: text, about, columns
 */
?>
<!-- wp:group {"align":"full","className":"aa-section","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull aa-section"><!-- wp:columns {"className":"aa-split"} -->
<div class="wp-block-columns aa-split"><!-- wp:column {"width":"42%"} -->
<div class="wp-block-column" style="flex-basis:42%"><!-- wp:paragraph {"className":"is-style-eyebrow"} -->
<p class="is-style-eyebrow">Label</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">A heading that <em>says it all</em>.</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"className":"is-style-lead"} -->
<p class="is-style-lead">An introduction sentence, a little larger.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>More text. Keep paragraphs short: the layout gives them room to breathe.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-arrow"} -->
<div class="wp-block-button is-style-arrow"><a class="wp-block-button__link wp-element-button" href="/about/">Read more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
