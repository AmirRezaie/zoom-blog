<?php
/**
 * Mega menu.
 *
 * Renders the "mega" menu location. Top-level items with children display
 * their children in a full-width panel with a featured latest post from the
 * linked category (when the item points to a category).
 *
 * @package ZoomBlog
 */

$zb_locations = get_nav_menu_locations();
if ( empty( $zb_locations['mega'] ) ) {
	return;
}
$zb_menu  = wp_get_nav_menu_object( $zb_locations['mega'] );
$zb_items = $zb_menu ? wp_get_nav_menu_items( $zb_menu->term_id ) : array();
if ( empty( $zb_items ) ) {
	return;
}

// Build a simple two-level tree.
$zb_tree = array();
foreach ( $zb_items as $item ) {
	if ( 0 === (int) $item->menu_item_parent ) {
		$zb_tree[ $item->ID ] = array( 'item' => $item, 'children' => array() );
	}
}
foreach ( $zb_items as $item ) {
	$parent = (int) $item->menu_item_parent;
	if ( $parent && isset( $zb_tree[ $parent ] ) ) {
		$zb_tree[ $parent ]['children'][] = $item;
	}
}
?>
<ul class="zb-menu">
	<?php foreach ( $zb_tree as $node ) : $it = $node['item']; $kids = $node['children']; ?>
		<li class="<?php echo $kids ? 'zb-mega menu-item-has-children' : ''; ?>">
			<a href="<?php echo esc_url( $it->url ); ?>"><?php echo esc_html( $it->title ); ?></a>
			<?php if ( $kids ) : ?>
				<div class="zb-mega__panel">
					<div class="zb-mega__grid">
						<?php
						// Column of links.
						echo '<div class="zb-mega__col" style="grid-column: span 3">';
						echo '<h4>' . esc_html( $it->title ) . '</h4><ul>';
						foreach ( $kids as $kid ) {
							printf( '<li><a href="%s">%s</a></li>', esc_url( $kid->url ), esc_html( $kid->title ) );
						}
						echo '</ul></div>';

						// Featured latest post from the first child category (if any).
						$obj_id = (int) $it->object_id;
						if ( 'category' === $it->object && $obj_id ) {
							$feat = get_posts( array( 'numberposts' => 1, 'category' => $obj_id ) );
							if ( $feat ) {
								$fp = $feat[0];
								echo '<a class="zb-mega__feature" href="' . esc_url( get_permalink( $fp ) ) . '">';
								echo zoomblog_thumbnail( $fp, 'zoomblog-card', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '</a>';
							}
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
