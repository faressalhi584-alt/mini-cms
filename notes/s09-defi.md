# Défi — Session 09

## Bandeau du mode lecture seule

Le composant de mise en page commun affiche le bandeau afin qu'il reste visible sur toutes les pages. Le middleware `EnsureSiteIsWritable` bloque les méthodes HTTP non sûres sur les routes d'écriture, tandis que les requêtes sûres continuent de fonctionner.

## Configuration des tests

Les tests fixent `SITE_READ_ONLY=false` dans `phpunit.xml` pour ne pas dépendre de la valeur définie dans le fichier `.env` local. Le test du mode lecture seule active la configuration explicitement avec `config(['site.read_only' => true])`.

## Suggestions de la page 404

La page dédiée aux articles introuvables présente au maximum trois articles publiés récents. Les relations auteur, catégorie et tags sont chargées à l'avance ; les suggestions sont omises lorsqu'aucun article publié n'est disponible.
