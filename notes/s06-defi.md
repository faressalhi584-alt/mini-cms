# Session 06 - Défi : publier et dépublier

La route posts.publication apparaît dans route:list avec le verbe PATCH : oui
Le bouton affiche « Publier » sur un brouillon et « Dépublier » sur un article publié : oui
published_at est rempli à la publication et vidé à la dépublication : oui
Le nouveau test passe et php artisan test est vert : oui

Pourquoi PATCH et pas PUT ni GET pour ce bouton ?

PATCH convient parce que l'action ne modifie qu'une partie de la ressource : son état de publication. PUT remplacerait l'ensemble des champs du formulaire, tandis que GET ne doit jamais modifier des données.
