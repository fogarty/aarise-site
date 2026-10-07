<?php
/**
 * Pied de page du site (remplace celui d'Astra).
 *
 * La partie haute est le modèle synchronisé « Site footer » (Apparence > Compositions,
 * slug site-footer) : logo, devise, contact, réseaux… modifiables dans l'éditeur. S'il n'existe
 * pas, une version de secours codée ci-dessous est affichée.
 * La barre du bas (liens légaux, cookies, copyright) reste fixe ; ses liens se modifient dans
 * Apparence > Menus, emplacement « Legal links (footer) ».
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

$aa_footer = aarise_synced_pattern( 'site-footer' );
?>
<footer class="aa-footer">
	<div class="aa-footer__main">
		<?php if ( $aa_footer ) : ?>
			<?php echo $aa_footer; // phpcs:ignore WordPress.Security.EscapeOutput -- contenu de blocs rendu par WordPress. ?>
		<?php else : ?>
			<div class="aa-footer__brand">
				<?php aarise_logo(); ?>
				<p class="aa-footer__motto">The Art of Immersion</p>
			</div>
			<div class="aa-footer__contact">
				<p class="is-style-eyebrow">Contact</p>
				<p><a href="mailto:contact@aarise.games">contact@aarise.games</a></p>
				<p>6 rue Virginie Hériot<br>17000 La Rochelle, France</p>
			</div>
		<?php endif; ?>
	</div>

	<div class="aa-footer__bar">
		<p class="aa-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> AARISE SAS</p>
		<nav class="aa-footer__legal" aria-label="Legal information">
			<?php aarise_menu( 'legal' ); ?>
			<button type="button" class="aa-link-button" data-aa-consent-open>Manage cookies</button>
		</nav>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
