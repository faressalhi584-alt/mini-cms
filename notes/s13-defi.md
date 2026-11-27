# Session 13 - Défi

- `/api/posts/3` affiche les noms des tags : oui.
- `/api/posts` ne contient aucun `body`, tandis que `/api/posts/3` en contient un : oui.
- Aucune `LazyLoadingViolationException` : oui.

1. Le corps complet n'est pas envoyé dans la liste pour réduire les données transférées et accélérer le chargement. Le client ne le demande que lorsqu'une personne ouvre l'article.

2. Un 404 pour un brouillon ne révélerait pas qu'un article existe à cet identifiant. Le 403 actuel suit la règle de `PostPolicy` et évite de diverger entre le site et l'API.
