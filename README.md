# AARISE — thème WordPress

Thème enfant d'**Astra** (Astra Pro) pour le site du studio, [www.aarise.games](https://www.aarise.games).

- Site **en anglais uniquement** (pas de Polylang).
- Devise du studio : **« The Art of Immersion »**. Ton visuel sobre et sérieux, plus que Kiwi Boing :
  le studio fera différents types de jeux, le site ne doit pas épouser le style d'un seul.

Ce dépôt **est** le dossier du thème : sur le serveur, il vit dans
`public_html/wp-content/themes/aarise/` de l'application WordPress AARISE sur Cloudways
(même serveur que Kiwi Boing, application distincte).

```
style.css            En-tête du thème enfant (Template: astra)
functions.php        Chargement des styles, traductions, catégorie de compositions
assets/css/          Styles du site
assets/img/          Images du thème (logo, décors…)
content/             Contenu des pages (balisage de blocs) envoyé via l'API REST
languages/           Fichiers de traduction .po/.mo du thème
tools/wp.ps1         Accès à l'API REST WordPress
.github/workflows/   Déploiement automatique vers Cloudways
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
