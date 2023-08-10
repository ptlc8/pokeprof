# Poképrof

Poképrof est un jeu de cartes en ligne, les utilisateurs peuvent créer des cartes, jouer avec etc

La version Poképrof de l'EISTI (ex CY Tech) est actuellement à cette URL : [ambi.dev/cards](https://ambi.dev/cards). (BDD 10.5.12-MariaDB-cll-lve, PHP 7.2.34) La branche master y est auto-déployer à chaque push.

## Lancer en local

Il est possible de lancer le projet en local.
Pour cela il faut faudra PHP et mysql.
 - cloner le projet
 - créer un fichier credentials.php dans le dossier api contenant identifiants de la base de données, sous cette forme :
```php
<?php
define('POKEPROF_DB_HOSTNAME', 'localhost');
define('POKEPROF_DB_USER', 'user');
define('POKEPROF_DB_PASSWORD', 'password123');
define('POKEPROF_DB_NAME', 'pokeprof');
define('POKEPROF_WEBHOOK_CARD_CREATE', null);
define('POKEPROF_WEBHOOK_CARD_EDIT', null);
define('POKEPROF_WEBHOOK_ERROR', null);
define('POKEPROF_CONNECT_URL', 'http://localhost/connect.php?app=pokeprof&params=');
define('POKEPROF_AVATAR_URL', 'http://localhost/avatar.php?id=');
define('POKEPROF_USER_URL', 'http://localhost/api/user.php?token=');
?>
```
 - exécuter dans la base de données le script SQL [init.sql](init.sql)
 - optionnel : faire en sorte que [onceaday.php](api/onceaday.php) s'exécute une fois par jour
 - lancer le serveur php


## Administration

Le jeu n'est pas autonome, il nécessite une intervention humaine pour être jouable. Notamment pour la modération des cartes et la création des scripts.

Des explications sur la création des scripts sont disponibles dans [scripts.md](scripts.md).