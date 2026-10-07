# AARISE — thème WordPress

Thème enfant d'**Astra** (Astra Pro) pour le site du studio, [www.aarise.games](https://www.aarise.games).

- Site **en anglais uniquement** (pas de Polylang).
- Devise du studio : **« The Art of Immersion »**. Ton visuel sobre et sérieux, plus que Kiwi Boing :
  le studio fera différents types de jeux, le site ne doit pas épouser le style d'un seul.

Ce dépôt **est** le dossier du thème : sur le serveur, il vit dans
`public_html/wp-content/themes/aarise/` de l'application WordPress AARISE sur Cloudways
(même serveur que Kiwi Boing, application distincte).

```
style.css              En-tête du thème enfant (Template: astra)
functions.php          Réglages, styles, menus, partage, redirections, noindex hors production
inc/consent.php        Bandeau cookies, Google Consent Mode v2, chargement de GTM
inc/projects.php       Type de contenu « Project » (menu Projects de l'administration)
header.php, footer.php En-tête et pied de page du site (remplacent ceux d'Astra)
page.php               Page simple : titre + contenu (pages légales…)
templates/canvas.php   Modèle « Designed page (no title) » : pages composées de sections
single-project.php     Page d'un projet : grand visuel, titre, accroche, contenu
index.php, 404.php     Secours (tout autre contenu) et page introuvable
patterns/              Compositions « AARISE » de l'éditeur (héros, sections, projets, offre d'emploi…)
assets/css/            site.css (design), editor.css (éditeur), consent.css (bandeau)
assets/js/             site.js (menu mobile, en-tête, apparitions), consent.js (bandeau)
assets/fonts/          Inter et Cormorant Garamond, hébergées avec le thème (licence OFL)
assets/img/            Logos, symbole « A », icônes, vignette de partage (og-image.jpg)
content/               Contenu initial des pages (blocs) + pages.json ; installé par tools/push-content.ps1
tools/wp.ps1           Accès à l'API REST WordPress (staging par défaut)
.github/workflows/     Déploiement automatique vers Cloudways
```

---

## 1. Mettre le dépôt sur GitHub

1. Créer un dépôt **privé** vide sur GitHub : `fogarty/aarise-site`, **sans** README, .gitignore ni licence
   (sinon son historique entre en conflit avec celui de ce dossier).
2. Depuis ce dossier :

   ```bash
   git remote add origin https://github.com/fogarty/aarise-site.git
   git push -u origin main
   ```

## 2. Configurer « Deployment via Git » sur Cloudways

1. **Cloudways > Applications >** l'app WordPress **AARISE** (pas celle de Kiwi Boing) **> Deployment Via Git**.
2. Cliquer **Generate SSH Keys**, puis **View SSH Key** et copier la clé publique.
3. Sur GitHub : **dépôt aarise-site > Settings > Deploy keys > Add deploy key**. Coller la clé,
   *lecture seule* (ne **pas** cocher « Allow write access »).
   Une clé de déploiement ne sert qu'à un dépôt : chaque application Cloudways a la sienne.
4. De retour sur Cloudways :
   - **Git Remote Address** : `git@github.com:fogarty/aarise-site.git`
   - Cliquer **Authenticate**, puis choisir la branche `main`.
   - **Deployment Path** : `wp-content/themes/aarise` (relatif à `public_html`).
5. Cliquer **Start Deployment**.
6. **Ne pas activer le thème en production** : le site actuel tourne sur Astra directement.
   Préparer le nouveau site d'abord (voir § 4).

> Le dossier `wp-content/themes/aarise` ne doit pas exister avant le premier déploiement.

## 3. Déploiement automatique

Le workflow `.github/workflows/deploy.yml` appelle l'API Cloudways pour faire le *Pull* tout seul.

- **Push sur `main` → staging.** Pendant la refonte, la production n'est jamais touchée par un push.
- **Production** : *Actions > Deploy to Cloudways > Run workflow*, cible `production`.

1. **Cloudways > API Integration > Access Tokens** : un token avec accès **Git deployment**.
2. Identifiants : dans l'URL de la page d'une application, on lit
   `.../server/<SERVER_ID>/application/<APP_ID>`.
3. Sur GitHub, **dépôt aarise-site > Settings > Secrets and variables > Actions** :
   - Secrets : `CLOUDWAYS_ACCESS_TOKEN`, `CLOUDWAYS_SERVER_ID`, `CLOUDWAYS_APP_ID` (production),
     `CLOUDWAYS_STAGING_APP_ID` (staging), et `CLOUDWAYS_STAGING_SERVER_ID` seulement si le staging
     est sur un autre serveur.
   - Variable : `CLOUDWAYS_DEPLOY_PATH` = `wp-content/themes/aarise`
4. Pousser sur `main` : l'onglet **Actions** montre le déploiement.

## 4. Staging : refaire le site sans casser l'actuel

Le site actuel (Astra + Spectra, pages Home, About, Projects, Early Access) reste en ligne
pendant la refonte ; tout se construit sur le staging.

1. **Cloudways > l'application AARISE > Staging Management > Create Staging** (même serveur).
   C'est une copie complète (fichiers + base) : le thème `aarise` et les comptes y sont déjà.
2. Sur l'application de **staging**, refaire le § 2 : **Deployment via Git**, nouvelle clé SSH à
   ajouter comme **deuxième** deploy key du dépôt (un dépôt peut en avoir plusieurs), branche `main`,
   chemin `wp-content/themes/aarise`.
3. Ajouter le secret `CLOUDWAYS_STAGING_APP_ID` (§ 3).
4. Dans `.env`, renseigner `WP_STAGING_URL` (adresse du staging). Les identifiants de production
   y fonctionnent, la base ayant été copiée.
5. Dans le WordPress du staging : **Apparence > Thèmes > activer « AARISE »**.

**Mise en ligne** : *Staging Management > Push to Live* (fichiers et base). Faire une sauvegarde
de la production juste avant ; après la bascule, lancer un déploiement `production` pour que la
copie Git de la production soit de nouveau alignée sur `main`.

## 5. Gérer les pages via l'API REST

`tools/wp.ps1` lit, crée et modifie pages et contenus via l'API REST WordPress. Il vise le
**staging** par défaut ; ajouter `-Site live` pour la production.

1. WordPress (AARISE) > **Utilisateurs > Profil > Mots de passe d'application** : créer un mot
   de passe nommé « Claude Code ».
2. Copier `.env.example` en `.env` et remplir les valeurs. `.env` n'est jamais commité :
   ne jamais le créer ni le modifier sur GitHub.
3. Exemple : `./tools/wp.ps1 GET 'pages?_fields=id,slug,title'`

## Développement

Modifier les fichiers, commit, push sur `main` → déployé. Les changements de CSS sont mis en cache
par Breeze/Varnish : incrémenter `AARISE_VERSION` dans `functions.php` (et `Version` dans
`style.css`) — le cache est alors vidé automatiquement au premier chargement.

## Modifier le site (guide pour l'équipe)

Tout le contenu se modifie dans WordPress, sans code. Le thème garantit le style : il n'y a pas
de couleurs ou de polices à choisir, seulement celles du site.

- **Pages** (*Pages*) : Home, About, Projects, Jobs, Press kit, Contact utilisent le modèle
  « Designed page (no title) » (panneau de droite > Modèle) et sont faites de sections. Pour ajouter
  une section : bouton **+** > **Compositions** > catégorie **AARISE** (Hero, Page intro,
  Heading + text, Three pillars, Projects grid, Call to action, Contact cards, Job offer).
  Les pages légales utilisent le modèle par défaut (le titre est affiché automatiquement).
- **Styles de blocs** (panneau de droite > Styles) : *Eyebrow* (petit titre doré), *Lead* (texte
  d'introduction), *Display* (très grand titre), *Panel* (encadré), *Facts* (liste « libellé —
  valeur », libellé en gras), *Text + arrow* (bouton discret). Un mot en *italique* dans un titre
  passe en doré.
- **Projets** (*Projects > Add project*) : titre, **Image mise en avant** (grand visuel, 16:9
  conseillé), **Extrait** (accroche, affichée en haut de la page et sur les cartes), puis le contenu.
  Le projet apparaît automatiquement sur l'accueil et la page Projects (le plus récent en premier).
- **Offres d'emploi** : sur la page Jobs, insérer la composition **Job offer** (une par poste) à
  la place de l'encadré « No open positions right now ».
- **Menus** (*Apparence > Menus*) : « Main menu » (en-tête) et « Legal links » (pied de page).
- **Pied de page** (*Apparence > Compositions > Site footer*) : logo, devise, contact, adresse.
  On peut y ajouter un bloc « Icônes de réseaux sociaux ».
- **Partage et référencement** : l'**Extrait** d'une page sert de description ; son **Image mise en
  avant**, d'image de partage (sinon la vignette du studio).

## Cookies, GTM, GA4

Même système que Kiwi Boing (`inc/consent.php`) : rien n'est chargé avant le consentement
(mode « basic »). Conteneur GTM : `GTM-N36SX5P4` (`AARISE_GTM_ID`), flux GA4 : `G-M2L60F8YNM` (cookie `_ga_M2L60F8YNM`,
listé dans la page Cookie policy). Dans GTM, configurer la balise Google (GA4) comme dans le
conteneur Kiwi Boing. Le choix du visiteur est aussi poussé dans le dataLayer (événement
`aa_consent_update`). Sur le staging, GTM ne se charge qu.en mode Aperçu (adresse avec `?gtm_debug=`), pour ne pas
mélanger ses visites aux statistiques.
