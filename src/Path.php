<?php
/**
 * Résolution de chemins confinée à un répertoire racine.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

namespace Brumisphere\ThemeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Toute construction de chemin à partir d'une valeur non maîtrisée passe par ici.
 *
 * Le socle charge des fichiers dynamiquement : gabarits, modules, shortcodes. Sans
 * confinement, une valeur contenant une traversée de répertoire permettrait de charger
 * un fichier arbitraire du serveur. Les deux garde-fous sont cumulatifs : on neutralise
 * d'abord le nom, puis on vérifie que le résultat réel reste sous la racine attendue.
 */
final class Path {

	/**
	 * Résout un fichier à l'intérieur d'un répertoire, ou renvoie null.
	 *
	 * @param string $racine  Répertoire de confinement, chemin absolu.
	 * @param string $fichier Nom de fichier, éventuellement non maîtrisé.
	 * @return string|null Chemin absolu réel, ou null si le fichier est hors périmètre.
	 */
	public static function dans( string $racine, string $fichier ): ?string {
		$racine_reelle = realpath( $racine );

		if ( false === $racine_reelle ) {
			return null;
		}

		// basename() neutralise toute composante de chemin : "../../wp-config.php"
		// devient "wp-config.php", qui ne sera pas trouvé dans le répertoire ciblé.
		$nom = basename( $fichier );

		if ( '' === $nom || '.' === $nom || '..' === $nom ) {
			return null;
		}

		$candidat = realpath( $racine_reelle . DIRECTORY_SEPARATOR . $nom );

		if ( false === $candidat || ! is_file( $candidat ) ) {
			return null;
		}

		// Seconde barrière : un lien symbolique placé dans le répertoire pourrait
		// pointer ailleurs. realpath() l'a résolu, on vérifie donc la destination.
		if ( ! self::est_sous( $candidat, $racine_reelle ) ) {
			return null;
		}

		return $candidat;
	}

	/**
	 * Liste les fichiers PHP d'un répertoire, triés, sans récursion.
	 *
	 * @param string $repertoire Chemin absolu.
	 * @return string[] Chemins absolus réels.
	 */
	public static function fichiers_php( string $repertoire ): array {
		$racine = realpath( $repertoire );

		if ( false === $racine || ! is_dir( $racine ) ) {
			return array();
		}

		$trouves = glob( $racine . DIRECTORY_SEPARATOR . '*.php' );

		if ( false === $trouves ) {
			return array();
		}

		$fichiers = array();

		foreach ( $trouves as $fichier ) {
			$reel = realpath( $fichier );

			if ( false !== $reel && is_file( $reel ) && self::est_sous( $reel, $racine ) ) {
				$fichiers[] = $reel;
			}
		}

		sort( $fichiers );

		return $fichiers;
	}

	/**
	 * Indique si un chemin réel se trouve sous une racine réelle.
	 *
	 * @param string $chemin Chemin absolu déjà résolu.
	 * @param string $racine Racine absolue déjà résolue.
	 */
	private static function est_sous( string $chemin, string $racine ): bool {
		$prefixe = rtrim( $racine, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

		return str_starts_with( $chemin, $prefixe );
	}
}
