# Session 10 - Robustesse

TagAdminTest contient 8 tests verts : oui
/posts/categorie/inconnue affiche Catégorie introuvable avec le statut 404 : oui
php artisan test est vert : oui

1. Pourquoi le message de messages() l'emporte-t-il sur celui de lang/fr/validation.php ?

Laravel cherche d'abord un message défini pour cette règle et ce champ dans la Form Request. Le fichier de langue fournit le message général lorsque la Form Request ne définit pas de message plus précis.

2. Pourquoi phpunit.xml fixe-t-il APP_LOCALE ?

Les tests comparent des messages en français. Fixer la locale dans phpunit.xml leur donne le même résultat sur chaque poste et chaque clone, indépendamment du fichier .env local.

3. Pourquoi CategoryController n'a-t-il besoin d'aucun try/catch ?

Les Form Requests refusent les données invalides avant l'appel du contrôleur, la liaison de modèle produit une réponse 404 pour une catégorie absente, et le middleware renvoie 503 en lecture seule. Le gestionnaire d'exceptions transforme ces erreurs en pages adaptées ; un try/catch ne ferait que masquer ces réponses.
