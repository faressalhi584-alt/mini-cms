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

## État actuel (tag lab-04)

- Modèles `Category` et `Tag` reliés à `Post` : un article a une catégorie et un auteur (1:N) et plusieurs tags (N:N, table pivot `post_tag`).
- Fabriques `PostFactory` (état `published()`), `CategoryFactory`, `TagFactory` et seeders pour un jeu de données réaliste en français (`APP_FAKER_LOCALE=fr_FR`).
- Chargement anticipé avec `with()` ; `Model::preventLazyLoading()` signale tout problème N+1 hors production.
- Tests Pest : `PostPagesTest`, `PostWriteTest` et `PostRelationsTest`.

## Données de démonstration

Après un clone, reconstruire la base avec `php artisan migrate:fresh --seed`. Attention : cette commande vide la base avant de la remplir.

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
| GET | `/posts/{post}/edit` | `posts.edit` | `PostController@edit` |
| PUT | `/posts/{post}` | `posts.update` | `PostController@update` |
| DELETE | `/posts/{post}` | `posts.destroy` | `PostController@destroy` |
| PATCH | `/posts/{post}/publication` | `posts.publication` | `PostController@togglePublication` |

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

### Session 06

![Formulaire de modification prérempli](screenshots/s06-modification.png)

![Confirmation de suppression](screenshots/s06-suppression.png)

### Session 07

![Liste des articles avec leur catégorie, leur auteur et leurs tags](screenshots/s07-liste-relations.png)

![Page d'un article avec ses tags et les articles de la même catégorie](screenshots/s07-detail-tags.png)
