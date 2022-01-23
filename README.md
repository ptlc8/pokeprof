# Poképrof

Poképrof est un jeu de cartes en ligne, les utilisateurs peuvent créer des cartes, jouer avec etc

La version Poképrof de l'EISTI (ex CY Tech) est actuellement à cette URL : [https://ambi.dev/cards]. (BDD 10.5.12-MariaDB-cll-lve, PHP 7.2.34) La branche master y est auto-déployer à chaque push.

## Lancer en local

Il est possible de lancer le projet en local.
Pour cela il faut faudra PHP et mysql.
 - cloner le projet
 - créer un fichier credentials.php contenant identifiants de la base de données, sous cette forme :
```php
<?php
define('POKEPROF_DB_HOSTNAME', 'hostname');
define('POKEPROF_DB_USER', 'user');
define('POKEPROF_DB_PASSWORD', 'password123');
define('POKEPROF_DB_NAME', 'pokeprof');
?>
```
 - exécuter dans la base de données le script SQL [init.sql]
 - optionnel : faire en sorte que [onceaday.php] s'exécute une fois par jour
 - lancer le serveur php
