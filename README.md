# Mini-CMS

Projet fil rouge de l'Atelier Framework Côté Serveur, construit avec Laravel 13, Blade, Tailwind CSS et SQLite.

## Installation locale

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
if (-not (Test-Path database\database.sqlite)) { New-Item database\database.sqlite -ItemType File }
php artisan migrate
npm install
composer run dev
```

Les migrations créent les tables, dont `posts`. Les articles de démonstration sont des données locales et ne sont pas incluses dans le dépôt.

## État actuel (tag lab-03)

- Articles enregistrés dans SQLite et lus avec le modèle Eloquent `Post`.
- La table `posts` est créée par une migration ; après un clone, `php artisan migrate` la recrée vide.
- Liste paginée de quatre articles, détail par liaison modèle-route et archive par année.
- Formulaire de création avec validation côté serveur.
- Les trois derniers articles publiés s'affichent sur l'accueil.
- Tests Pest exécutables avec `php artisan test`.

## Routes disponibles

| Méthode | URI | Nom | Action |
|---|---|---|---|
| GET | `/` | `home` | `PageController@home` |
| GET | `/a-propos` | `about` | `PageController@about` |
| GET | `/heure` | `time` | `PageController@time` |
| GET | `/posts` | `posts.index` | `PostController@index` |
| GET | `/posts/archive/{year}` | `posts.archive` | `PostController@archive` |
| GET | `/posts/create` | `posts.create` | `PostController@create` |
| POST | `/posts` | `posts.store` | `PostController@store` |
| GET | `/posts/{post}` | `posts.show` | `PostController@show` |

## Vérifications

```powershell
php artisan migrate:status
php artisan route:list --except-vendor
php artisan test
npm run build
```

## Captures

![Liste paginée des articles](screenshots/s05-articles.png)

![Formulaire avec erreurs de validation](screenshots/s05-formulaire.png)
