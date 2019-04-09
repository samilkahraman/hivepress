<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<h4 class="hp-listing-category__name"><a href="<?php echo esc_url( hivepress()->router->get_url( 'listing/submit_category', [ 'listing_category_id' => $category->get_id() ] ) ); ?>"><?php echo esc_html( $category->get_name() ); ?></a></h4>