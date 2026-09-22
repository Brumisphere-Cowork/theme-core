<?php
/**
 * Socle de la suite de tests : constante ABSPATH, autoload du socle, bac à sable.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

spl_autoload_register(
	static function ( string $classe ): void {
		$prefixe = 'Brumisphere\\ThemeCore\\';

		if ( ! str_starts_with( $classe, $prefixe ) ) {
			return;
		}

		$fichier = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', substr( $classe, strlen( $prefixe ) ) ) . '.php';

		if ( is_file( $fichier ) ) {
			require_once $fichier;
		}
	}
);

/**
 * Bac à sable de la session : un répertoire temporaire, supprimé à la sortie.
 *
 * Arborescence posée par bru_reinitialiser() :
 *
 *     <bac>/racine/gabarit.php
 *     <bac>/racine/b.php, a.php, notes.txt
 *     <bac>/racine/sous/profond.php
 *     <bac>/racine/lien-dehors.php  -> <bac>/dehors/secret.php
 *     <bac>/racine/lien-voisin.php  -> <bac>/racine-voisine/voisin.php
 *     <bac>/dehors/secret.php
 *     <bac>/racine-voisine/voisin.php   (préfixe commun avec « racine »)
 *
 * @var string
 */
$GLOBALS['bru_bac'] = sys_get_temp_dir() . '/brumisphere-theme-core-' . getmypid();

/**
 * Supprime une arborescence sans suivre les liens symboliques.
 *
 * @param string $chemin Répertoire à supprimer.
 */
function bru_supprimer( string $chemin ): void {
	if ( is_link( $chemin ) || is_file( $chemin ) ) {
		unlink( $chemin );
		return;
	}

	if ( ! is_dir( $chemin ) ) {
		return;
	}

	foreach ( array_diff( (array) scandir( $chemin ), array( '.', '..' ) ) as $entree ) {
		bru_supprimer( $chemin . '/' . $entree );
	}

	rmdir( $chemin );
}

/**
 * Repose le bac à sable dans son état initial avant chaque cas.
 */
function bru_reinitialiser(): void {
	$bac = $GLOBALS['bru_bac'];
	bru_supprimer( $bac );

	foreach ( array( 'racine/sous', 'dehors', 'racine-voisine' ) as $rep ) {
		mkdir( $bac . '/' . $rep, 0700, true );
	}

	foreach ( array( 'racine/gabarit.php', 'racine/b.php', 'racine/a.php', 'racine/notes.txt', 'racine/sous/profond.php', 'dehors/secret.php', 'racine-voisine/voisin.php' ) as $f ) {
		file_put_contents( $bac . '/' . $f, "<?php\n" );
	}

	symlink( $bac . '/dehors/secret.php', $bac . '/racine/lien-dehors.php' );
	symlink( $bac . '/racine-voisine/voisin.php', $bac . '/racine/lien-voisin.php' );
}

register_shutdown_function(
	static function (): void {
		bru_supprimer( $GLOBALS['bru_bac'] );
	}
);
