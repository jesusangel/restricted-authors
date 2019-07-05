<?php

$terms                      = get_terms( 'category', [ 'hide_empty' => false ] );
$default_user_category      = get_user_meta( $user->ID, '_restricted_authors_default_category', true );
$restricted_user_categories = is_array( get_user_meta( $user->ID, '_restricted_authors_restricted_category', true ) ) ? get_user_meta( $user->ID, '_restricted_authors_restricted_category', true ) : [];

wp_nonce_field( 'restricted_authors', 'restricted_authors_profile' );
?>

<h3><?php esc_html_e( 'Restricted Categories', 'restricted-authors' ); ?></h3>

<table class="form-table">
	<tr>
		<th>
			<label for="restricted_categories[]"><?php esc_html_e( 'Select Restricted Categories', 'restricted-authors' ); ?>:</label><br />
			<span class="description"><?php esc_html_e( 'Categories the author is restricted to posting.', 'restricted-authors' ); ?></span>
		</th>
		<td>
			<select name="restricted_categories[]" id="restricted-categories" class="restricted-authors-select" multiple>
				<?php
				foreach ( $terms as $term ) :

					$selected = in_array( strval( $term->term_id ), $restricted_user_categories, true ) ? " selected='selected'" : '';
				?>

					<option value="<?php echo esc_attr( intval( $term->term_id ) ); ?>" <?php echo esc_attr( $selected ); ?> ><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>

	</tr>
</table>

<table class="form-table">
	<tr>
		<th>
			<label for="restricted_default"><?php esc_html_e( 'Select Default Category', 'restricted-authors' ); ?>:</label><br />
			<span class="description"><?php esc_html_e( 'Author Default Category.', 'restricted-authors' ); ?></span>
		</th>
		<td>
			<select name="restricted_default" id="" class="restricted-authors-select">
				<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( intval( $term->term_id ), intval( $default_user_category ) ); ?> ><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
</table>
<script>
	const choices = new Choices('.restricted-authors-select', { removeItemButton: true });
</script>