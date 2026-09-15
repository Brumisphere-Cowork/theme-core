<?php
/**
 * Redirection des gabarits vers un sous-répertoire du thème enfant.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Permet de ranger les gabarits dans inc/Templates plutôt qu'à la racine du thème.
 *
 * WordPress cherche ses gabarits à la racine du thème. Un thème qui déclare plusieurs
 * types de contenu y accumule donc des dizaines de fichiers mêlés aux fichiers de
 * configuration.
 *
 * L'approche habituelle consiste à écrire un bloc conditionnel par type de contenu,
 * qui doit être complété à chaque ajout. Celle-ci n'a aucune maintenance : la hiérarchie
 * native de WordPress détermine le gabarit, et on se contente de regarder si le thème
 * en propose une version rangée sous inc/Templates.
 *
 * Conséquence à connaître : les noms de fichiers restent ceux de la hiérarchie
 * WordPress. Un gabarit pour le type "services" doit s'appeler single-services.php,
 * pas services.php.
 */
final class Templates {

	/**
	 * Sous-répertoire du thème enfant contenant les gabarits.
	 */
	private const REPERTOIRE = '/inc/Templates';

	/**
	 * Branche le module.
	 */
	public static function enregistrer(): void {
		add_filter( 'template_include', array( self::class, 'rediriger' ), 99 );
	}

	/**
	 * Substitue le gabarit si le thème en propose une version rangée.
	 *
	 * Public car branché sur un filtre ; ne pas appeler directement.
	 *
	 * @param string $gabarit Chemin absolu résolu par WordPress.
	 * @return string Chemin absolu à utiliser.
	 */
	public static function rediriger( string $gabarit ): string {
		if ( '' === $gabarit ) {
			return $gabarit;
		}

		$racine = get_stylesheet_directory() . self::REPERTOIRE;

		// Path::dans applique basename() puis vérifie que le fichier résolu reste
		// sous la racine attendue, y compris après résolution d'un lien symbolique.
		$candidat = Path::dans( $racine, $gabarit );

		if ( null === $candidat ) {
			return $gabarit;
		}

		/**
		 * Filtre le gabarit retenu par le socle.
		 *
		 * @param string $candidat Gabarit rangé trouvé.
		 * @param string $gabarit  Gabarit initialement résolu par WordPress.
		 */
		return (string) apply_filters( 'brumisphere/core/gabarit', $candidat, $gabarit );
	}
}
