<?php
/**
 * Chargement automatique des shortcodes du thème enfant.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Charge chaque fichier de inc/Shortcodes au moment où WordPress est prêt.
 *
 * Le chargement est différé jusqu'à "init" : un shortcode déclaré trop tôt s'enregistre
 * avant que les types de contenu et les taxonomies ne soient connus, ce qui produit des
 * comportements difficiles à diagnostiquer.
 *
 * Un fichier par shortcode, nommé d'après lui. La convention suffit : il n'y a aucune
 * liste à tenir à jour ailleurs.
 */
final class Shortcodes {

	/**
	 * Sous-répertoire du thème enfant contenant les shortcodes.
	 */
	private const REPERTOIRE = '/inc/Shortcodes';

	/**
	 * Branche le module.
	 */
	public static function enregistrer(): void {
		add_action( 'init', array( self::class, 'charger' ), 5 );
	}

	/**
	 * Charge les fichiers de shortcodes.
	 *
	 * Public car branché sur un hook ; ne pas appeler directement.
	 */
	public static function charger(): void {
		$repertoire = get_stylesheet_directory() . self::REPERTOIRE;

		foreach ( Path::fichiers_php( $repertoire ) as $fichier ) {
			require_once $fichier;
		}
	}

	/**
	 * Enregistre un shortcode dont la sortie est mise en tampon.
	 *
	 * Sert de raccourci aux fichiers de inc/Shortcodes :
	 *
	 *     Shortcodes::rendre(
	 *         'encart',
	 *         static function ( array $attributs ): void {
	 *             ?><div class="encart"><?php echo esc_html( $attributs['titre'] ); ?></div><?php
	 *         },
	 *         array( 'titre' => '' )
	 *     );
	 *
	 * La fonction de rendu écrit dans la sortie standard, elle ne retourne rien. C'est ce
	 * qui permet d'alterner PHP et HTML sans concaténer des chaînes, source classique
	 * d'échappement oublié.
	 *
	 * @param string                         $nom      Nom du shortcode, sans crochets.
	 * @param callable                       $rendu    Reçoit les attributs résolus et le contenu.
	 * @param array<string, string|int|bool> $defauts  Attributs acceptés et leurs valeurs par défaut.
	 */
	public static function rendre( string $nom, callable $rendu, array $defauts = array() ): void {
		add_shortcode(
			$nom,
			static function ( $attributs, $contenu = null ) use ( $rendu, $defauts, $nom ): string {
				$resolus = shortcode_atts( $defauts, is_array( $attributs ) ? $attributs : array(), $nom );

				ob_start();
				$rendu( $resolus, $contenu );
				$sortie = ob_get_clean();

				return false === $sortie ? '' : $sortie;
			}
		);
	}
}
