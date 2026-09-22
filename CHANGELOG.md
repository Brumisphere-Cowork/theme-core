# Changelog — brumisphere/theme-core

Les versions de ce paquet, la plus récente en tête. Le numéro suit celui des publications
du parc Brumisphère : une publication qui ne touche pas le socle ne lui pose aucun tag,
d'où les numéros absents. Les tags sont figés (DEC-008) et ne se déplacent jamais.

*Écrit le 22/09/2026 avec la 1.0.23. Les entrées antérieures sont reconstituées à partir
des tags publiés (`v1.0.0`, `v1.0.1`, `v1.0.4`), comparés entre eux, et du journal du dépôt
de documentation.*

## 1.0.23 — non publiée

### Corrigé

- `Path::dans()` et `Path::fichiers_php()` refusent une entrée qui contient un octet nul :
  `null` pour l'une, liste vide pour l'autre. Elles laissaient `realpath()` lever une
  `ValueError`, soit une erreur fatale au lieu du refus promis. Aucun appelant actuel ne
  leur passe une telle valeur : `Templates` transmet un chemin déjà résolu par WordPress.

- `AcfJson` teste l'écriture du répertoire par `wp_is_writable()` au lieu de
  `is_writable()`. Même résultat sous Linux ; la fonction de WordPress traite aussi le cas
  de Windows.

### Modifié

- Code mis aux conventions de `phpcs.xml`, qui ne l'avaient jamais lu : un `@var`
  manquant, deux alignements de docblock, un commentaire pris pour du code. `phpcs.xml`
  admet « / » dans les noms de crochets : `brumisphere/core/amorce` et
  `brumisphere/core/gabarit` sont une interface publique, **ils ne sont pas renommés**.

### Ajouté

- Tests du confinement de `Path` (`php tests/executer.php`) : traversées, chemins
  absolus, noms réservés, sous-répertoires, liens symboliques sortants, racine voisine au
  préfixe commun, octet nul. Prescrits avant le tag `v1`, écrits après la 1.0.4.
- `phpstan.neon.dist`. Le paquet entre dans la CI du dépôt de documentation : tests,
  PHPCS et PHPStan niveau 6.
- Ce fichier.

## 1.0.4 — 16/09/2026

### Modifié

- `supports` accepte des paramètres : `'title-tag' => true` et
  `'html5' => array( … )`, en plus de la forme simple `'post-thumbnails'`. Le thème
  enfant appelait `add_theme_support( 'html5' )` sans liste, d'où un avertissement à
  chaque chargement.
- `Bootstrap::VERSION` alignée sur le tag. Elle valait encore `1.0.0`.

## 1.0.1 — 16/09/2026

### Ajouté

- `.gitattributes` : fins de ligne LF. Aucun changement de code.

## 1.0.0 — 15/09/2026

Première version : `Bootstrap`, `Assets`, `Templates`, `Shortcodes`, `AcfJson`, `Path`,
les jetons SCSS et `phpcs.xml`.
