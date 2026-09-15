# brumisphere/theme-core

Socle technique partagé des thèmes enfants Brumisphère.

**Il porte le mécanisme, jamais le contenu d'un site.**

---

## Ce qu'il fait

| Module | Rôle |
|---|---|
| `Bootstrap` | Point d'entrée, configuration, chargement des modules du thème |
| `Assets` | Styles et scripts, versionnés par date de modification, chargés conditionnellement |
| `Templates` | Permet de ranger les gabarits dans `inc/Templates` au lieu de la racine |
| `Shortcodes` | Chargement automatique des shortcodes, avec rendu en tampon de sortie |
| `AcfJson` | Fait vivre les groupes de champs ACF dans le dépôt plutôt qu'en base |
| `Path` | Résolution de chemins confinée, mutualisée par les modules |

## Ce qu'il ne fait pas

- **Aucun type de contenu, aucune taxonomie.** Ce sont des structures de contenu :
  elles vont dans un mu-plugin propre au site. Déclarées dans un thème, elles
  disparaissent si le thème est désactivé et toutes les URL du site tombent en 404.
- **Aucun durcissement de sécurité.** Il vit dans le mu-plugin `brumisphere-hardening`,
  actif quel que soit le thème.
- **Aucune valeur de marque.** Le fichier `_tokens.scss` définit la structure des
  jetons, le thème client en fournit les valeurs.

---

## Installation

Le socle est une bibliothèque PHP ordinaire, installée à la racine du site.

```bash
composer require brumisphere/theme-core
```

Un mu-plugin charge l'autoloader avant tout le reste :

```php
<?php
// wp-content/mu-plugins/00-autoload.php

// En WordPress classique (DEC-001), le composer.json vit à la racine du site,
// c'est-à-dire dans ABSPATH. vendor/ en est donc le voisin direct.
$autoload = ABSPATH . 'vendor/autoload.php';

if ( is_file( $autoload ) ) {
    require_once $autoload;
}
```

> **Le répertoire `vendor/` doit être inaccessible depuis le navigateur.**
> Sur un WordPress classique il se trouve sous la racine web. Ajouter au `.htaccess` :
>
> ```apache
> <IfModule mod_rewrite.c>
>   RewriteRule ^(vendor|composer\.(json|lock))(/|$) - [F,L]
> </IfModule>
> ```
>
> Vérifier ensuite que `https://<site>/composer.json` renvoie bien 403.
> Ce contrôle fait partie de la recette de sécurité.

---

## Usage dans un thème enfant

Le `functions.php` ne contient aucune logique. Il décrit, il n'implémente pas.

```php
<?php
declare(strict_types=1);

use Brumisphere\ThemeCore\Bootstrap;

Bootstrap::boot(
    array(
        'style_parent' => true,   // Flatsome : charger la feuille du parent
        'menus'        => array(
            'principal'    => 'Menu principal',
            'pied-de-page' => 'Pied de page',
        ),
        'tailles'      => array(
            'carte'    => array( 640, 420, true ),
            'banniere' => array( 1600, 600, true ),
        ),
        'supports'     => array( 'post-thumbnails', 'title-tag', 'html5' ),
        'scripts'      => array(
            'carrousel' => array(
                'fichier'   => 'js/carrousel.js',
                'condition' => static fn (): bool => is_front_page(),
            ),
        ),
    )
);
```

### Arborescence attendue du thème enfant

```
flatsome-child/
├── functions.php          appel à Bootstrap::boot()
├── style.css              en-tête + feuille compilée
├── acf-json/              groupes de champs versionnés
├── inc/
│   ├── Templates/         gabarits rangés (single-services.php, archive-faq.php…)
│   ├── Shortcodes/        un fichier par shortcode
│   └── *.php              modules chargés automatiquement
├── scss/
│   ├── _variables.scss    valeurs de marque du client
│   └── style.scss
└── js/
```

### Consommer les jetons SCSS

Le socle fournit la structure des jetons, le thème client en fournit les valeurs. La
liaison se fait par configuration de module Sass, pas par `@import` — cette règle est
dépréciée et disparaît dans Dart Sass 3.0.

```scss
// scss/style.scss du thème client
@use 'variables' as marque;
@use 'tokens' with (
  $marque-primaire: marque.$primaire,
  $marque-secondaire: marque.$secondaire,
  $police-titres: marque.$police-titres
);
@use 'base';
```

Le répertoire `scss/` du socle est passé au compilateur par `--load-path`, ce qui évite
un chemin relatif illisible dans chaque partiel :

```bash
sass --load-path=../../../vendor/brumisphere/theme-core/scss \
     --load-path=scss --no-source-map --style=compressed \
     scss/style.scss css/style.css
```

**L'ordre compte.** Un partiel chargé avant la configuration verrait les valeurs de
repli du socle. Elles sont volontairement fades pour qu'un oubli se voie, mais mieux
vaut ne pas livrer le site pour s'en apercevoir.

### Écrire un shortcode

Un fichier par shortcode dans `inc/Shortcodes/`, nommé d'après lui.

```php
<?php
// inc/Shortcodes/encart.php
declare(strict_types=1);

use Brumisphere\ThemeCore\Shortcodes;

Shortcodes::rendre(
    'encart',
    static function ( array $attributs, ?string $contenu ): void {
        ?>
        <aside class="encart">
            <h2 class="encart__titre"><?php echo esc_html( $attributs['titre'] ); ?></h2>
            <div class="encart__corps"><?php echo wp_kses_post( (string) $contenu ); ?></div>
        </aside>
        <?php
    },
    array( 'titre' => '' )
);
```

La fonction de rendu **écrit** dans la sortie, elle ne retourne rien. C'est ce qui permet
d'alterner PHP et HTML sans concaténer de chaînes, où l'échappement finit toujours par
être oublié quelque part.

### Ranger les gabarits

`Templates` redirige vers `inc/Templates` en conservant les noms de la hiérarchie
WordPress. Un gabarit pour le type de contenu `services` s'appelle `single-services.php`,
pas `services.php`.

Il n'y a rien à déclarer : ajouter le fichier suffit.

---

## Sécurité

Le socle charge des fichiers dynamiquement. Deux garde-fous cumulatifs s'appliquent à
chaque résolution de chemin, dans `Path` :

1. `basename()` neutralise toute composante de chemin. Une valeur du type
   `../../wp-config.php` devient `wp-config.php`, qui ne sera pas trouvé dans le
   répertoire ciblé.
2. Le chemin réel obtenu par `realpath()` est comparé à la racine attendue. Un lien
   symbolique déposé dans le répertoire et pointant ailleurs est donc rejeté.

Les tests correspondants doivent être écrits avant publication du tag `v1`.

---

## Versionnement

Versionnement sémantique. Les thèmes clients contraignent en `^1.0`, jamais sur une
branche de développement.

| Changement | Incrément |
|---|---|
| Correction sans effet visuel | Correctif |
| Nouveau module ou nouvelle option de configuration | Mineur |
| Modification du balisage produit par un composant | **Majeur** |
| Renommage d'un jeton SCSS ou d'une propriété CSS exposée | **Majeur** |
| Changement de signature d'une méthode publique | **Majeur** |

Toute modification de balisage est une rupture, même minime : les thèmes clients ont pu
écrire des sélecteurs CSS dessus. Une version majeure impose une relecture projet par
projet, ce qui est exactement le comportement souhaité.

Le `CHANGELOG.md` est obligatoire.

---

## Qualité

```bash
composer lint      # PHPCS, conventions WordPress
composer analyse   # PHPStan niveau 6
```

Les deux doivent passer avant toute publication de tag.
