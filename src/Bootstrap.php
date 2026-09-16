<?php
/**
 * Point d'entrée du socle de thème Brumisphère.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Amorce le socle depuis le functions.php du thème enfant.
 *
 * Le thème enfant ne contient aucune logique : il décrit ce qu'il veut et appelle boot().
 *
 *     use Brumisphere\ThemeCore\Bootstrap;
 *
 *     Bootstrap::boot(
 *         array(
 *             'menus'    => array( 'principal' => 'Menu principal' ),
 *             'tailles'  => array( 'carte' => array( 640, 420, true ) ),
 *             'supports' => array( 'post-thumbnails', 'title-tag' ),
 *             // Avec paramètres : 'supports' => array( 'title-tag' => true, 'html5' => array( 'script' ) ),
 *         )
 *     );
 */
final class Bootstrap {

	/**
	 * Version du socle, utilisée pour le cache-busting des assets.
	 */
	public const VERSION = '1.0.4';

	/**
	 * Empêche une double amorce si functions.php est inclus deux fois.
	 */
	private static bool $amorce = false;

	/**
	 * Configuration résolue.
	 *
	 * @var array<string, mixed>
	 */
	private static array $config = array();

	/**
	 * Amorce le socle.
	 *
	 * @param array<string, mixed> $config Configuration propre au thème enfant.
	 */
	public static function boot( array $config = array() ): void {
		if ( self::$amorce ) {
			return;
		}

		self::$amorce = true;
		self::$config = self::fusionner_defauts( $config );

		Assets::enregistrer( self::$config );
		Templates::enregistrer();
		Shortcodes::enregistrer();
		AcfJson::enregistrer();

		add_action( 'after_setup_theme', array( self::class, 'configurer_theme' ), 20 );

		self::charger_modules();

		/**
		 * Signale que le socle est amorcé.
		 *
		 * @param array<string, mixed> $config Configuration résolue.
		 */
		do_action( 'brumisphere/core/amorce', self::$config );
	}

	/**
	 * Lit une entrée de configuration.
	 *
	 * @param string $cle    Clé recherchée.
	 * @param mixed  $defaut Valeur si la clé est absente.
	 * @return mixed
	 */
	public static function config( string $cle, mixed $defaut = null ): mixed {
		return self::$config[ $cle ] ?? $defaut;
	}

	/**
	 * Déclare menus, tailles d'image et supports du thème.
	 *
	 * Public car branché sur un hook ; ne pas appeler directement.
	 */
	public static function configurer_theme(): void {
		$menus = self::config( 'menus', array() );

		if ( is_array( $menus ) && array() !== $menus ) {
			register_nav_menus( $menus );
		}

		$tailles = self::config( 'tailles', array() );

		if ( is_array( $tailles ) ) {
			foreach ( $tailles as $nom => $dimensions ) {
				if ( ! is_string( $nom ) || ! is_array( $dimensions ) ) {
					continue;
				}

				add_image_size(
					$nom,
					(int) ( $dimensions[0] ?? 0 ),
					(int) ( $dimensions[1] ?? 0 ),
					(bool) ( $dimensions[2] ?? false )
				);
			}
		}

		$supports = self::config( 'supports', array() );

		if ( is_array( $supports ) ) {
			foreach ( $supports as $cle => $valeur ) {
				// 'title-tag', 'title-tag' => true, ou 'html5' => array( 'script', 'style' ).
				if ( is_int( $cle ) && is_string( $valeur ) ) {
					add_theme_support( $valeur );
				} elseif ( is_string( $cle ) && true === $valeur ) {
					add_theme_support( $cle );
				} elseif ( is_string( $cle ) && is_array( $valeur ) ) {
					add_theme_support( $cle, $valeur );
				}
			}
		}
	}

	/**
	 * Charge les modules PHP du thème enfant, dans l'ordre alphabétique.
	 *
	 * Le répertoire est fixe et le contenu vient du dépôt, jamais d'une entrée
	 * utilisateur. Le confinement par Path reste appliqué : un lien symbolique
	 * déposé dans le répertoire ne doit pas permettre de sortir du thème.
	 */
	private static function charger_modules(): void {
		$repertoire = get_stylesheet_directory() . '/inc';

		foreach ( Path::fichiers_php( $repertoire ) as $fichier ) {
			require_once $fichier;
		}
	}

	/**
	 * Applique les valeurs par défaut du socle.
	 *
	 * @param array<string, mixed> $config Configuration fournie.
	 * @return array<string, mixed>
	 */
	private static function fusionner_defauts( array $config ): array {
		$defauts = array(
			'menus'          => array(),
			'tailles'        => array(),
			'supports'       => array( 'post-thumbnails', 'title-tag' ),
			'style_parent'   => false,
			'scripts_footer' => true,
			'acf_json'       => true,
		);

		return array_merge( $defauts, $config );
	}
}
