<?php
/**
 * Standalone public template for legal pages.
 *
 * @package UniversalLegalPages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$legal_page         = get_queried_object();
$legal_page_title   = '';
$legal_page_content = '';

if ( $legal_page instanceof WP_Post ) {
	$legal_page_title = get_the_title( $legal_page );

	if ( post_password_required( $legal_page ) ) {
		$legal_page_content = wp_kses(
			get_the_password_form( $legal_page ),
			[
				'div' => [
					'class' => true,
					'role'  => true,
				],
				'form' => [
					'action' => true,
					'class'  => true,
					'method' => true,
				],
				'p' => [
					'id' => true,
				],
				'label' => [
					'for' => true,
				],
				'input' => [
					'aria-describedby' => true,
					'autocomplete' => true,
					'class'        => true,
					'id'           => true,
					'name'         => true,
					'required'     => true,
					'size'         => true,
					'spellcheck'   => true,
					'type'         => true,
					'value'        => true,
				],
			],
			[ 'http', 'https' ]
		);
	} else {
		$legal_page_content = apply_filters( 'the_content', $legal_page->post_content );
		$legal_page_content = wp_kses_post( $legal_page_content );
	}
}

if ( trim( $legal_page_title ) === '' ) {
	$legal_page_title = __( 'Legal document', 'universal-legal-pages' );
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'universal-legal-page' ); ?>>
	<?php
	if ( function_exists( 'wp_body_open' ) ) {
		wp_body_open();
	}
	?>
	<main class="universal-legal-page__main">
		<article class="universal-legal-page__document">
			<h1 class="universal-legal-page__title"><?php echo esc_html( $legal_page_title ); ?></h1>
			<div class="universal-legal-page__content">
				<?php echo $legal_page_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized through the branch-specific allowlist above. ?>
			</div>
		</article>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
