# Session 08 - Défi

La route `posts.tag` utilise `{tag:slug}` : oui.

La page d'un tag n'affiche ni brouillon ni article d'un autre tag : oui.

Les tags des cartes sont des liens : oui.

L'accueil n'affiche que les tags qui ont au moins un article publié : oui.

Les trois tests du défi passent et `php artisan test` est vert : oui.

Pourquoi filtrer les tags vides après `get()` plutôt que d'afficher `(0)` ?

Les tags sans article publié ne sont pas pertinents dans cette liste de découverte ; les retirer évite d'afficher des liens qui ne mèneraient à aucun article.

Combien de requêtes ajoute la liste des tags populaires, et pourquoi pas une par tag ?

Elle ajoute une seule requête pour charger les tags et leur compteur. `withCount` calcule le nombre d'articles dans la requête SQL, ce qui évite une requête supplémentaire par tag.
