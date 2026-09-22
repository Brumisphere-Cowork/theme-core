<?php
/**
 * Versionnement des groupes de champs ACF.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Fait vivre les groupes de champs ACF dans le dépôt plutôt qu'en base de données.
 *
 * Sans cela, un groupe de champs créé en local doit être recréé à la main en
 * préproduction puis en production, ou transporté par un export manuel que personne ne
 * pense à refaire. C'est la principale source d'écart entre environnements sur un projet
 * WordPress.
 *
 * Avec ce module, un groupe modifié produit un fichier JSON dans le thème. Le fichier
 * part dans la pull request, se relit comme du code, et se déploie avec le reste.
 *
 * Le module reste silencieux si ACF n'est pas installé.
 */
final class AcfJson {

	/**
	 * Sous-répertoire du thème enfant recevant les fichiers JSON.
	 */
	private const REPERTOIRE = '/acf-json';

	/**
	 * Branche le module.
	 */
	public static function enregistrer(): void {
		if ( true !== Bootstrap::config( 'acf_json', true ) ) {
			return;
		}

		add_filter( 'acf/settings/save_json', array( self::class, 'chemin_ecriture' ) );
		add_filter( 'acf/settings/load_json', array( self::class, 'chemins_lecture' ) );
	}

	/**
	 * Indique à ACF où écrire les groupes de champs.
	 *
	 * Public car branché sur un filtre ; ne pas appeler directement.
	 *
	 * @param string $chemin Chemin par défaut fourni par ACF.
	 * @return string
	 */
	public static function chemin_ecriture( string $chemin ): string {
		$cible = get_stylesheet_directory() . self::REPERTOIRE;

		// ACF n'écrit pas si le répertoire est absent, et ne signale rien. La création
		// silencieuse évite une perte de travail difficile à comprendre.
		if ( ! is_dir( $cible ) && ! wp_mkdir_p( $cible ) ) {
			return $chemin;
		}

		return wp_is_writable( $cible ) ? $cible : $chemin;
	}

	/**
	 * Ajoute le répertoire du thème aux sources de lecture d'ACF.
	 *
	 * Public car branché sur un filtre ; ne pas appeler directement.
	 *
	 * @param array<int, string> $chemins Chemins déjà déclarés.
	 * @return array<int, string>
	 */
	public static function chemins_lecture( array $chemins ): array {
		$cible = get_stylesheet_directory() . self::REPERTOIRE;

		if ( is_dir( $cible ) && ! in_array( $cible, $chemins, true ) ) {
			$chemins[] = $cible;
		}

		return $chemins;
	}
}
