# AARISE — thème WordPress

Thème enfant d'**Astra** (Astra Pro) pour le site du studio, [www.aarise.games](https://www.aarise.games).

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

1. Créer un dépôt **privé** vide sur GitHub : `fogarty/aarise-site`.
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
6. **Ne pas activer le thème tout de suite** : le site actuel tourne sur Astra directement.
   Préparer le nouveau site d'abord (voir § 4).

> Le dossier `wp-content/themes/aarise` ne doit pas exister avant le premier déploiement.

## 3. Déploiement automatique à chaque push

Le workflow `.github/workflows/deploy.yml` appelle l'API Cloudways pour faire le *Pull* tout seul.

1. **Cloudways > API Integration > Access Tokens** : le token créé pour Kiwi Boing peut servir
   s'il couvre tout le serveur ; sinon en créer un (accès **Git deployment**).
2. Identifiants : dans l'URL de la page de l'application AARISE, on lit
   `.../server/<SERVER_ID>/application/<APP_ID>`. Le `SERVER_ID` est le même que pour Kiwi Boing,
   l'`APP_ID` est différent.
3. Sur GitHub, **dépôt aarise-site > Settings > Secrets and variables > Actions** :
   - Secrets : `CLOUDWAYS_ACCESS_TOKEN`, `CLOUDWAYS_SERVER_ID`, `CLOUDWAYS_APP_ID`
   - Variable : `CLOUDWAYS_DEPLOY_PATH` = `wp-content/themes/aarise`
4. Pousser sur `main` : l'onglet **Actions** montre le déploiement.

## 4. Refaire le site sans casser l'actuel

Le site actuel (Astra + Spectra, pages Home, About, Projects, Early Access) reste en ligne
pendant la refonte. Deux options :

- **Staging Cloudways** (recommandé) : *Application > Staging Management* crée une copie ;
  on y active le thème AARISE et on construit les pages, puis *Push to Live*.
  Prévoir alors une deuxième configuration Git (deploy path identique) sur l'app de staging.
- **Directement en production** : construire les nouvelles pages en brouillon, puis activer
  le thème et basculer la page d'accueil (*Réglages > Lecture*) le jour J.

## 5. Gérer les pages via l'API REST

`tools/wp.ps1` lit, crée et modifie pages et contenus via l'API REST WordPress.

1. WordPress (AARISE) > **Utilisateurs > Profil > Mots de passe d'application** : créer un mot
   de passe nommé « Claude Code ».
2. Copier `.env.example` en `.env` et remplir `WP_USER` et `WP_APP_PASSWORD`. `.env` n'est jamais commité.
3. Exemple : `./tools/wp.ps1 GET 'pages?_fields=id,slug,title'`

## Développement

Modifier les fichiers, commit, push sur `main` → déployé. Les changements de CSS sont mis en cache
par Breeze/Varnish : incrémenter `AARISE_VERSION` dans `functions.php` (et `Version` dans
`style.css`) — le cache est alors vidé automatiquement au premier chargement.
