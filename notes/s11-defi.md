# Session 11 - Défi

/mes-articles ne montre que mes articles, brouillons compris : oui
Un visiteur qui ouvre /mes-articles arrive sur /connexion : oui
Le cookie remember_web_ apparaît quand la case est cochée : oui
Les deux nouveaux tests passent et php artisan test est vert : oui

Pourquoi partir de $request->user()->posts() plutôt que de Post::where('user_id', $request->user()->id) ?

La relation part de l'utilisateur authentifié et limite automatiquement la requête à ses articles. Elle ne dépend donc ni d'une valeur fournie dans la requête ni d'un identifiant d'utilisateur modifiable.

Pourquoi la case remember ne doit-elle pas être dans le tableau passé à Auth::attempt() ?

Auth::attempt() traite les clés du tableau comme des critères de recherche sur l'utilisateur, à l'exception du mot de passe. La case remember n'est pas une colonne de users ; elle est passée séparément comme second argument.
