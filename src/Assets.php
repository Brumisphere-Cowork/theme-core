<?php
/**
 * Déclaration des styles et scripts du thème enfant.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Charge les assets compilés du thème enfant.
 *
 * Deux règles structurent ce module.
 *
 * La version d'un asset est dérivée de sa date de modification, jamais de la version de
 * WordPress ni d'une constante figée. Une valeur figée impose de penser à l'incrémenter,
 * ce que personne ne fait, et le navigateur sert alors un fichier périmé.
 *
 * Un script déclaré dans la configuration n'est chargé que sur les pages qui le
 * demandent. Charger l'ensemble des scripts partout est le défaut le plus courant et le
 * plus coûteux en performance.
 */
final class Assets {

	/**
	 * Configuration du thème enfant.
	 *
	 * @var array<string, mixed>
	 */
	private static array $config = array();

	/**
	 * Branche le module.
	 *
	 * @param array<string, mixed> $config Configuration résolue.
	 */
	public static function enregistrer( array $config ): void {
		self::$config = $config;

		add_action( 'wp_enqueue_scripts', array( self::class, 'charger' ), 20 );
	}

	/**
	 * Déclare les assets du thème.
	 *
	 * Public car branché sur un hook ; ne pas appeler directement.
	 */
	public static function charger(): void {
		$uri  = get_stylesheet_directory_uri();
		$base = get_stylesheet_directory();

		if ( true === self::$config['style_parent'] ) {
			$style_parent = get_template_directory() . '/style.css';

			if ( is_file( $style_parent ) ) {
				wp_enqueue_style(
					'brumisphere-parent',
					get_template_directory_uri() . '/style.css',
					array(),
					self::version( $style_parent )
				);
			}
		}

		$feuille = $base . '/style.css';

		if ( is_file( $feuille ) ) {
			wp_enqueue_style(
				'brumisphere-theme',
				$uri . '/style.css',
				true === self::$config['style_parent'] ? array( 'brumisphere-parent' ) : array(),
				self::version( $feuille )
			);
		}

		self::charger_scripts( $base, $uri );
	}

	/**
	 * Déclare les scripts listés dans la configuration.
	 *
	 * Chaque entrée accepte une condition, évaluée au moment du chargement :
	 *
	 *     'scripts' => array(
	 *         'carrousel' => array(
	 *             'fichier'    => 'js/carrousel.js',
	 *             'dependances'=> array(),
	 *             'condition'  => static fn (): bool => is_front_page(),
	 *         ),
	 *     ),
	 *
	 * @param string $base Répertoire absolu du thème enfant.
	 * @param string $uri  URI du thème enfant.
	 */
	private static function charger_scripts( string $base, string $uri ): void {
		$scripts = self::$config['scripts'] ?? array();

		if ( ! is_array( $scripts ) ) {
			return;
		}

		foreach ( $scripts as $poignee => $definition ) {
			if ( ! is_string( $poignee ) || ! is_array( $definition ) ) {
				continue;
			}

			$condition = $definition['condition'] ?? null;

			if ( is_callable( $condition ) && ! (bool) $condition() ) {
				continue;
			}

			$relatif = (string) ( $definition['fichier'] ?? '' );

			if ( '' === $relatif ) {
				continue;
			}

			$absolu = $base . '/' . ltrim( $relatif, '/' );

			if ( ! is_file( $absolu ) ) {
				continue;
			}

			wp_enqueue_script(
				'brumisphere-' . $poignee,
				$uri . '/' . ltrim( $relatif, '/' ),
				(array) ( $definition['dependances'] ?? array() ),
				self::version( $absolu ),
				(bool) ( self::$config['scripts_footer'] ?? true )
			);
		}
	}

	/**
	 * Dérive une version d'asset de sa date de modification.
	 *
	 * @param string $fichier Chemin absolu.
	 */
	private static function version( string $fichier ): string {
		$horodatage = filemtime( $fichier );

		return false === $horodatage ? Bootstrap::VERSION : (string) $horodatage;
	}
}
