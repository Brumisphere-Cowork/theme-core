<?php
/**
 * Confinement de Path : traversées, liens symboliques, racines voisines.
 *
 * Tests prescrits par le document 04 avant le tag v1, écrits le 22/09/2026 alors que
 * la 1.0.4 était publiée.
 *
 * @package Brumisphere\ThemeCore
 */

declare(strict_types=1);

use Brumisphere\ThemeCore\Path;

$bac    = $GLOBALS['bru_bac'];
$racine = $bac . '/racine';

bru_cas(
	'dans() : un fichier présent est résolu en chemin réel',
	static function () use ( $racine ): void {
		bru_egal( realpath( $racine . '/gabarit.php' ), Path::dans( $racine, 'gabarit.php' ), 'fichier direct' );
	}
);

bru_cas(
	'dans() : un chemin absolu est ramené à son nom, cherché sous la racine',
	static function () use ( $racine ): void {
		bru_egal( realpath( $racine . '/gabarit.php' ), Path::dans( $racine, '/var/www/theme/gabarit.php' ), 'chemin absolu' );
	}
);

bru_cas(
	'dans() : une traversée vers un fichier extérieur renvoie null',
	static function () use ( $racine ): void {
		bru_egal( null, Path::dans( $racine, '../dehors/secret.php' ), '../dehors/secret.php' );
		bru_egal( null, Path::dans( $racine, '../../../../../../etc/passwd' ), '/etc/passwd par traversée' );
		bru_egal( null, Path::dans( $racine, '/etc/passwd' ), '/etc/passwd absolu' );
	}
);

bru_cas(
	'dans() : une traversée dont le nom existe sous la racine résout le fichier de la racine, jamais l\'autre',
	static function () use ( $bac, $racine ): void {
		file_put_contents( $bac . '/dehors/gabarit.php', "<?php\n" );
		bru_egal( realpath( $racine . '/gabarit.php' ), Path::dans( $racine, '../dehors/gabarit.php' ), 'nom homonyme' );
	}
);

bru_cas(
	'dans() : les noms vides ou réservés renvoient null',
	static function () use ( $racine ): void {
		foreach ( array( '', '.', '..', '/', 'sous/..', '../' ) as $nom ) {
			bru_egal( null, Path::dans( $racine, $nom ), var_export( $nom, true ) );
		}
	}
);

bru_cas(
	'dans() : un sous-répertoire n\'est pas atteignable, ni un répertoire comme fichier',
	static function () use ( $racine ): void {
		bru_egal( null, Path::dans( $racine, 'sous/profond.php' ), 'sous/profond.php' );
		bru_egal( null, Path::dans( $racine, 'sous' ), 'répertoire' );
	}
);

bru_cas(
	'dans() : un lien symbolique qui sort de la racine renvoie null',
	static function () use ( $racine ): void {
		bru_vrai( is_file( $racine . '/lien-dehors.php' ), 'le lien pointe bien vers un fichier existant' );
		bru_egal( null, Path::dans( $racine, 'lien-dehors.php' ), 'lien-dehors.php' );
	}
);

bru_cas(
	'dans() : un lien vers une racine voisine au préfixe commun renvoie null',
	static function () use ( $racine ): void {
		bru_egal( null, Path::dans( $racine, 'lien-voisin.php' ), 'racine-voisine partage le préfixe « racine »' );
	}
);

bru_cas(
	'dans() : une racine inexistante ou traversante ne résout rien',
	static function () use ( $bac, $racine ): void {
		bru_egal( null, Path::dans( $bac . '/absente', 'gabarit.php' ), 'racine absente' );
		bru_egal( realpath( $racine . '/gabarit.php' ), Path::dans( $racine . '/sous/..', 'gabarit.php' ), 'racine normalisée par realpath' );
	}
);

bru_cas(
	'dans() : un octet nul renvoie null au lieu de lever une erreur',
	static function () use ( $racine ): void {
		bru_egal( null, Path::dans( $racine, "gabarit.php\0.txt" ), 'octet nul dans le nom' );
		bru_egal( null, Path::dans( $racine . "\0", 'gabarit.php' ), 'octet nul dans la racine' );
	}
);

bru_cas(
	'fichiers_php() : les seuls .php directs, triés, sans lien sortant',
	static function () use ( $racine ): void {
		$attendus = array( realpath( $racine . '/a.php' ), realpath( $racine . '/b.php' ), realpath( $racine . '/gabarit.php' ) );
		bru_egal( $attendus, Path::fichiers_php( $racine ), 'a, b, gabarit — ni notes.txt, ni sous/, ni les deux liens' );
	}
);

bru_cas(
	'fichiers_php() : un répertoire absent ou un fichier renvoient une liste vide',
	static function () use ( $bac, $racine ): void {
		bru_egal( array(), Path::fichiers_php( $bac . '/absente' ), 'absent' );
		bru_egal( array(), Path::fichiers_php( $racine . '/gabarit.php' ), 'fichier' );
		bru_egal( array(), Path::fichiers_php( $racine . "\0" ), 'octet nul' );
	}
);
