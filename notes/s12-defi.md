# Session 12 - Défi

1. `role` n'est pas dans `#[Fillable]` parce qu'un rôle est une donnée sensible et ne doit pas être modifiable avec des valeurs envoyées librement dans une requête. Le contrôleur autorisé valide `author` ou `admin`, affecte cette valeur à l'utilisateur cible, puis appelle `save()`.

2. Le callback de `Gate::before` renvoie `null` pour un auteur afin que Laravel continue la vérification avec le Gate ou la Policy correspondant à la capacité. Renvoyer `false` arrêterait la vérification et refuserait même les actions qu'un auteur est autorisé à faire sur ses propres articles. Pour `changeRole`, le callback renvoie également `null` pour que la Policy puisse interdire à un administrateur de modifier son propre rôle.
