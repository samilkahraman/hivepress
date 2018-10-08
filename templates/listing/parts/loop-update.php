<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $listing_query->have_posts() ) :
	?>
	<table>
		<?php
		while ( $listing_query->have_posts() ) :
			$listing_query->the_post();
			?>
			<tr>
				<td>
					<?php
					if ( get_post_status() === 'pending' ) :
						the_title();
					else :
						?>
						<a href="<?php echo esc_url( hivepress()->template->get_url( 'listing__edit', [ get_the_ID() ] ) ); ?>"><i class="hp-icon fas fa-edit"></i><?php the_title(); ?></a>
					<?php endif; ?>
				</td>
				<td>
					<?php
					if ( get_post_status() === 'pending' ) :
						esc_html_e( 'Pending Review', 'hivepress' );
					elseif ( get_post_status() === 'draft' ) :
						esc_html_e( 'Changes Requested', 'hivepress' );
					endif;
					?>
				</td>
				<td>
					<time datetime="<?php the_time( 'Y-m-d' ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</td>
				<td>
					<?php if ( get_post_status() === 'publish' ) : ?>
						<a href="<?php the_permalink(); ?>" target="_blank"><i class="hp-icon fas fa-external-link-alt"></i><?php esc_html_e( 'View', 'hivepress' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
		<?php endwhile; ?>
	</table>
<?php else : ?>
	<div class="hp-no-results">
		<p><?php esc_html_e( 'No listings yet.', 'hivepress' ); ?></p>
	</div>
<?php endif; ?>
<button type="button" class="hp-js-link" data-url="<?php echo esc_url( hivepress()->template->get_url( 'listing__submission' ) ); ?>"><?php esc_html_e( 'Submit Listing', 'hivepress' ); ?></button>
