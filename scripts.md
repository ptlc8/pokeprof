# Scripts des cartes Poképrof

Les cartes après avoir été créées par un utilisateur doivent être modérées, complétées et configurées par un administrateur.

Chaque carte possède deux scripts, un par compétence. Cette compétence est décrite par un script (et une description humainement intelligible pour l'utilisateur).


## Comment modifier une carte ?

Pour mettre un utilisateur administrateur, il faut changer le champ `admin` de l'utilisateur à `true` dans la base de données.

Ainsi l'utilisateur en question aura accès à la page d'administration des cartes : `manage.php`.


## Structure d'un script

Un script est composé de 3 parties :
- un déclencheur
- des fonctions
- une condition

Sous cette forme :
```css
déclencheur{fonction1 fonction2 fonction3}[condition]
```

Une fonction est composé de 3 parties aussi :
- le type d'action
- des arguments
- une condition

Sous cette forme :
```css
fonction(param1,param2,param3)[condition]
```

### Exemples

```css
onaction{attack(target,20) sleep(target,1)}
```
Quand l'action de la carte est utilisée (`onaction`), inflige (`attack`) 20 dégâts à la cible et l'endort (`sleep`) pendant 1 tour.

```css
onturn{addstrength(all[hastype_etudiant|hastype_asso],5)}
```
Quand un tour passe (`onturn`), ajoute 5 points de force (`addstrength`) à tous les combattants de type etudiant ou asso (`all[hastype_etudiant|hastype_asso]`).

```css
onaction{attack(target,5*count_all[id=204])}
```
Quand l'action de la carte est utilisée (`onaction`), inflige (`attack`) 5 fois le nombre de cartes d'identifiant 204 en jeu (`count_all[id=204]`) à la cible (`target`).


### Déclencheurs

Liste des déclencheurs possibles, leur description et contraintes :
- `onplaycard`{function[]: que faire quand la carte est jouée ?}[boolean: la carte doit-elle être jouée ?] : Quand la carte est jouée (combattants, effets et lieux)
- `onaction`{function[]: que faire lors de l'action ?}[boolean: l'action doit-elle être effectuée ?] :  Quand le script est sélectionné comme attaque (combattants uniquement)
- `onturn`{function[]: que faire lors de l'intertour ?}[boolean: le script doit-il être exécuté ?] : Quand un tour passe (combattants et lieux)
- `onsummon`{function[]: que faire lorsqu'un combattant est posé ?}[boolean: le script doit-il être exécuté ?] : Quand une autre carte combattant est posée (lieux uniquement)
- `ondie`{function[]: que faire lorsque le combattant est défaussé ?}[boolean: le script doit-il être exécuté ?] : Quand la carte combattant est défaussée (combattants uniquement)


### Fonctions

Liste des fonctions et leurs paramètres (il est toujours possible d'ajouter une condition) :
- `attackif` : inflige aux *cibles* *dégâts* dégâts, multipliés par *multiplicateur* si *condition_multiplicatrice* est vraie. 4 arguments :
    - *cibles*, de type combattants
    - *dégâts*, de type nombre
    - *condition_multiplicatrice*, de type condition
    - *multiplicateur*, de type nombre
- `attack` : inflige *dégâts* dégâts aux *cibles*. 3 arguments :
    - *cibles*, de type combattants
    - *dégâts*, de type nombre
    - *ignorer_la_regle_des_defenseurs*, de type booléen, **facultatif**, si c'est vrai l'attaque peut être faite sur le joueur directement
- `heal` : soigne de *soin* pv les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *soin*, de type nombre
- `sleep` : endort les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *nombre_de_tours*, de type nombre
- `wakeup` : réveille les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *nombre_de_tours*, de type nombre
- `seedraw` : montre à *joueur* ses *nombre_de_cartes* prochaines cartes à piocher, 2 arguments :
    - *joueur*, de type joueur
    - *nombre_de_cartes*, de type nombre
- `seedrawhim` : montre à *joueur* les *nombre_de_cartes* prochaines cartes que l'adversaire va piochées, 2 arguments :
    - *joueur*, de type joueur
    - *nombre_de_cartes*, de type nombre
- `kick` : met les *cibles* à la défausse, 1 argument :
    - *cibles*, de type combattants
- `paralyse` : paralyse les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *nombre_de_tours*, de type nombre
- `affraid` : effraye les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *nombre_de_tours*, de type nombre
- `courage` : déseffraye les *cibles*, 1 argument :
    - *cibles*, de type combattants
- `electrify` : électrifie les *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *nombre_de_tours*, de type nombre
- `diselectrify` : désélectrifie les *cibles*, 1 argument :
    - *cibles*, de type combattants
- `setvar` : définie la valeur *valeur* de la variable *nom_de_variable* propre à la carte, 2 arguments :
    - *nom_de_variable*, de type texte (uniquement des lettres minuscules)
    - *valeur*, de type nombre
- `leaveplace` : défausse le lieu actuel, 0 argument
- `delmana` : supprime *mana* mana au *joueur*, 2 arguments :
    - *joueur*, de type joueur
    - *mana*, de type nombre
- `givemana` : donne *mana* mana au joueur, 2 arguments :
    - *joueur*, de type joueur
    - *mana*, de type nombre
- `draw` : *joueur* pioche une carte, 1 argument :
    - *joueur*, de type joueur
- `drop` : *joueur* défausse une carte aléatoire, 1 argument :
    - *joueur*, de type joueur
    - random, le mot random, rien d'autre (car il était prévu de pouvoir laisser l'utilisateur choisir)
- `addshield` : ajoute *bouclier* défense aux *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *bouclier*, de type nombre
- `removeshield` : retire *bouclier* défense des *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *bouclier*, de type nombre
- `addstrength` : ajoute *force* force aux *cibles*, 2 arguments :
    - *cibles*, de type combattants
    - *force*, de type nombre
- `summon` : créer une *quantité* de cartes d'id *id_de_carte* sur le terrain allié avec *pv* pv, *degats* dégâts et de *type* type, 5 arguments :
    - *quantité*, de type nombre
    - *id_de_carte*, de type nombre
    - *type*, de type texte
    - *pv*, de type nombre
    - *degats*, de type nombre
- `convert` : déplace les *cibles* dans votre camp, 1 argument :
    - *cibles*, de type combattants
- `disengage` : désengage les *cibles*, 1 argument :
    - *cibles*, de type combattants
- `invoc` : invoque une carte d'id *id_de_carte* sur le terrain d'un *joueur*, 2 arguments :
    - *joueur*, de type joueur
    - *id_de_carte*, de type nombre
- `rescue` : ramène une carte de la défausse dans votre main, 1 argument :
    - *emplacement*, `random` ou `last`
- `givecard` : donne une carte d'id *id_de_carte* à un *joueur*, 2 arguments :
    - *joueur*, de type joueur
    - *id_de_carte*, de type nombre
- `retreat` : retire les *cibles* du terrain, 1 argument :
    - *cibles*, de type combattants
- `makeprovoking` : rend les *cibles* provocantes, 1 argument :
    - *cibles*, de type combattants


### Sélecteurs de combattants

Liste des sélecteurs de profs : (possibilité de rajouter une condition `selecteur[condition]`)
- `all` : tous les combattants
- `allofhim` : tous les combattants de l'adversaire
- `allofyou` : tous les combattants du joueur
- `targetofhim` : demande un combattant cible de l'adversaire (uniquement avec onaction et onplaycard)
- `target` : comme targetofhim (déprécié)
- `target2ofhim` : demande 2 combattants cibles de l'adversaire (uniquement avec onaction et onplaycard)
- `targetofyou` : demande un combattant cible allié (uniquement avec onaction et onplaycard)
- `target2ofyou` : demande 2 combattants cibles alliés (uniquement avec onaction et onplaycard)
- `it` : la carte elle-même
- `you` : le joueur ou celui qui commence son tour
- `him` : l'adversaire ou celui qui finit son tour
- `summoned` : la carte qui vient d'être posée (uniquement avec le déclencheur onsummon)
- `randomofhim` : un combattant aléatoire de l'adversaire
- `randomofyou` : un combattant aléatoire du joueur


### Joueurs

Liste des sélecteur de joueur :
- `you` : le joueur ou celui qui commence son tour
- `him` : l'adversaire ou celui qui finit son tour


### Conditions

Liste des conditions :
- *condition*`|`*condition* : vrai si l'une des deux conditions est vraie
- *condition*`&`*condition* : vrai si les deux conditions sont vraies
- *valeur*`=`*valeur* : vrai si les deux valeurs sont égales (non strict)
- *condition*`!=`*condition* : vrai si les deux valeurs sont inégales (non strict)
- *valeur*`!`*valeur* : équivalent à !=
- *valeur1*`<`*valeur2* : vrai si valeur1 est inférieur à valeur2 (strict)
- *valeur1*`>`*valeur2* : vrai si valeur1 est supérieur à valeur2 (strict)
- `targetsleep` : vrai si targetofhim est endormi (déprécié, à remplacer par targetofhim_)
- `isplace_`*idcarte* : vrai si la carte lieu à pour id idcarte (équivalent à place=idcarte)
- `in_`*selecteurdecombattants*`_`*idcarte* : vrai si la sélection de combattants contient un combattant ayant l'id idcarte
- `hastype_`*typedecombattant* : vrai si le combattant possède le type typedecombattant
- `hasnottype_`*typedecombattant* : vrai si le combattant ne possède pas le type typedecombattant 


### Valeurs

Liste des valeurs :
- valeur`+`valeur : somme des valeurs
- valeur`-`valeur : différence des deux valeurs
- valeur`*`valeur : produit des deux valeurs
- valeur1`%`valeur2 : modulo de valeur1 par valeur2
- ~~`type` : type de la carte (student,math,asso...)~~ (déprécié)
- `place` : id de la carte lieu
- `getvar_`nomdevariable : valeur de la variable custom nomdevariable
- `random_`min`_`max : entier aléatoire entre min et max inclus
- `count_`sélecteur : nombre de combattants dans la sélection
- `t` : depuis combien de tour la carte est sur le terrain 
- `hp` : nombre de pv de la carte
- `hpmax` : nombre de pv max de la carte
- `cost` :  coût en mana de la carte
- `slp` : combien de tour la carte est endormie
- `prl` : combien de tour la carte est paralysée
- `efr` : combien de tour la carte est effrayée
- `elc` : combien de tour la carte est électrifiée
- `shield` : bouclier de la carte
- `strength` : force de la carte


### Types de combattants

Les types de combattants sont ceux dans la base de données, dans la tables `FIGHTERSCARDSTYPES`. Il est possible d'en rajouter dans la base de données directement.