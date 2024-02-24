# API Poképrof

## Sommaire

- [Authentification](#authentification)
- [Cartes](#cartes)
- [Types de combattants](#types-de-combattants)
- [Matchs](#matchs)
- [Utilisateurs](#utilisateurs)
- [Règles](#règles)

### Routes

| Méthode | Chemin | Description |
| --- | --- | --- |
| 🟢 POST | [/auth](#-post-auth) | Authentifie un utilisateur |
| 🔵 GET | [/cards](#-get-cards) | Retourne une liste de cartes |
| 🟢 POST | [/cards](#-post-cards) | Crée une carte |
| 🔵 GET | [/cards/:id](#-get-cardsid) | Retourne une carte |
| 🟠 PATCH | [/cards/:id](#-patch-cardsid) | Modifie une carte |
| 🟢 POST | [/cards/loot](#-post-cardsloot) | Ouvre un booster |
| 🔵 GET | [/fighterstypes](#-get-fighterstypes) | Retourne la liste des types de combattants |
| 🟢 POST | [/matchs](#-post-matchs) | Recherche un match |
| 🟢 POST | [/matchs/:id/attack](#-post-matchsidattack) | Attaque dans un match |
| 🟢 POST | [/matchs/:id/endturn](#-post-matchsidendturn) | Termine le tour dans un match |
| 🟢 POST | [/matchs/:id/giveup](#-post-matchsidgiveup) | Abandonne un match |
| 🟢 POST | [/matchs/:id/playcard](#-post-matchsidplaycard) | Joue une carte dans un match |
| 🔵 GET | [/matchs/:id](#-get-matchsid) | Spectate un match |
| 🔵 GET | [/users/:id](#-get-usersid) | Retourne un utilisateur |
| 🔵 GET | [/users/top](#-get-userstop) | Retourne le top 10 des utilisateurs |
| 🔵 GET | [/users/self](#-get-userself) | Retourne l'utilisateur courant |
| 🔵 GET | [/users/self/cards](#-get-userselfcards) | Retourne les cartes de l'utilisateur courant |
| 🔴 DELETE | [/users/self/cards/:id](#-delete-userselfcardsid) | Vend une carte de l'utilisateur courant |
| 🔵 GET | [/users/self/matches](#-get-userselfmatches) | Retourne les matches de l'utilisateur courant |
| 🔵 GET | [/users/self/shop](#-get-userselfshop) | Retourne les cartes de la boutique de l'utilisateur courant |
| 🟢 POST | [/users/self/shop](#-post-userselfshop) | Achète une carte de la boutique de l'utilisateur courant |
| 🟢 POST | [/users/self/infos](#-post-userselfinfos) | Modifie les méta-informations de l'utilisateur courant |
| 🔵 GET | [/users/self/decks](#-get-userselfdecks) | Retourne les decks de l'utilisateur courant |
| 🔵 GET | [/users/self/decks/:slot](#-get-userselfdecksslot) | Retourne un deck de l'utilisateur courant |
| 🔵 GET | [/users/self/decks/selected](#-get-userselfdecksselected) | Retourne le deck sélectionné de l'utilisateur courant |
| 🟡 PUT | [/users/self/decks/selected](#-put-userselfdecksselected) | Change le deck sélectionné de l'utilisateur courant |
| 🟢 POST | [/users/self/decks](#-post-userselfdecks) | Ajoute un slot de deck pour l'utilisateur courant |
| 🟡 PUT | [/users/self/decks/:slot/:index](#-put-userselfdecksslotindex) | Change une carte d'un deck de l'utilisateur courant |
| 🔵 GET | [/rules](#-get-rules) | Retourne les règles du jeu |

## Authentification

### 🟢 `POST /auth`

Authentifie un utilisateur.

#### Requête

```json
{
  "token": "..."
}
```

#### Réponse

```json
{
    "id": 1,
    "name": "Ash Ketchum"
}
```


## Cartes

### 🔵 `GET /cards`

Retourne une liste de cartes.

#### Réponse

```json
[
  {
    "id": 1,
    "name": "Abo",
    "hp": 60
  },
  {
    "id": 2,
    "name": "Pierre",
    "hp": 80
  }
]
```

### 🟢 `POST /cards`

Crée une carte.

#### Requête

```json
{
  "name": "Abo",
  "hp": 60
}
```

#### Réponse

```json
{
  "id": 1,
  "name": "Abo",
  "hp": 60
}
```

### 🔵 `GET /cards/:id`

Retourne une carte.

#### Réponse

```json
{
  "id": 1,
  "name": "Abo",
  "hp": 60
}
```

### 🟠 `PATCH /cards/:id`

Modifie une carte.

#### Requête

```json
{
  "hp": 100
}
```

#### Réponse

```json
{
  "id": 1,
  "name": "Abo",
  "hp": 100
}
```

### 🟢 `POST /cards/loot`

Ouvre un booster.

#### Réponse

```json
[
  {
    "type": "money",
    "money": "5"
  },
  {
    "type": "card",
    "id": 2
  }
]
```


## Types de combattants

### 🔵 `GET /fighterstypes`

Retourne la liste des types de combattants.

#### Réponse

```json
{
  "math": {
    "name": "Maths"
  },
  "fire": {
    "name": "Feu"
  }
}
```


## Matchs

### 🟢 `POST /matchs`

Recherche un match.

#### Réponse

```json
{
  ...
}
```

### 🟢 `POST /matchs/:id/:action`

Joue dans un match.

#### Requête

```json
{
  ...
}
```

Actions possibles : `get`, `update`, `playcard`, `atttack`, `endturn`, `giveup`.

#### Réponse

```json
{
  ...
}
```

### 🔵 `GET /matchs/:id?action=:action`

Spectate un match.

Actions possibles : `get`, `update`.

#### Réponse

```json
{
  ...
}
```


## Utilisateurs

### 🔵 `GET /users/:id`

Retourne un utilisateur.

#### Réponse

```json
{
  "id": 1,
  "name": "Ash Ketchum"
}
```

### 🔵 `GET /users/top`

Retourne le top 10 des utilisateurs.

#### Réponse

```json
[
  {
    "id": 1,
    "name": "Ash Ketchum"
  },
  {
    "id": 2,
    "name": "Misty"
  },
  ...
]
```

### 🔵 `GET /users/self`

Retourne l'utilisateur courant.

#### Réponse

```json
{
  "id": 1,
  "name": "Ash Ketchum"
}
```

### 🔵 `GET /users/self/cards`

Retourne les cartes de l'utilisateur courant.

#### Réponse

```json
{
  "1": 7,
  "2": 3,
  "1h": 1
}
```

### 🔴 `DELETE /users/self/cards/:id`

Vend une carte de l'utilisateur courant.

### 🔵 `GET /users/self/matches`

Retourne les matches de l'utilisateur courant.

### 🔵 `GET /users/self/shop`

Retourne les cartes de la boutique de l'utilisateur courant.

### 🟢 `POST /users/self/shop`

Achète une carte de la boutique de l'utilisateur courant.

### 🟢 `POST /users/self/infos`

Modifie les méta-informations de l'utilisateur courant.

### 🔵 `GET /users/self/decks`

Retourne les decks de l'utilisateur courant.

### 🔵 `GET /users/self/decks/:slot`

Retourne un deck de l'utilisateur courant.

### 🔵 `GET /users/self/decks/selected`

Retourne le deck sélectionné de l'utilisateur courant.

### 🟡 `PUT /users/self/decks/selected`

Change le deck sélectionné de l'utilisateur courant.

### 🟢 `POST /users/self/decks`

Ajoute un slot de deck pour l'utilisateur courant.

### 🟡 `PUT /users/self/decks/:slot/:index`

Change une carte d'un deck de l'utilisateur courant.


## Règles

### 🔵 `GET /rules`

Retourne les règles du jeu.

#### Réponse

```json
{
  ...
}
```
