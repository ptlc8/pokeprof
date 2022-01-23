SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `pokeprof` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pokeprof`;

--
-- Structure des tables
--

CREATE TABLE `BOOSTERS` (
  `id` int(11) NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int(11) NOT NULL DEFAULT 7,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CARDS` (
  `id` int(11) NOT NULL COMMENT 'id de la carte',
  `authorId` int(11) NOT NULL COMMENT 'id de l''auteur',
  `name` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'nom de la carte',
  `infos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'infos complementaires en JSON (couleur, vie, attaques)',
  `type` enum('prof','effect','place') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'prof',
  `script1` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'script de l''attaque 1',
  `script2` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'script de l''attaque 2 ',
  `official` tinyint(1) NOT NULL DEFAULT 0,
  `boosterId` int(11) DEFAULT NULL COMMENT 'booster dans lequel on peut trouver la carte',
  `rarity` tinyint(4) NOT NULL DEFAULT 0,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'date de création de la carte',
  `uses` int(11) NOT NULL DEFAULT 0 COMMENT 'nombre de match de la carte',
  `wins` int(11) NOT NULL DEFAULT 0 COMMENT 'nombre de victoire de la carte',
  `prestigeable` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'possibilité de l''obtenir en version prestige',
  `lastEditDate` date NOT NULL DEFAULT '0000-00-00' COMMENT 'date du dernier patch de la carte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Toutes les cartes créées';

CREATE TABLE `CARDSMATCHESHISTORY` (
  `id` int(11) NOT NULL,
  `opponentId1` int(11) NOT NULL,
  `opponentId2` int(11) NOT NULL,
  `winner` tinyint(1) NOT NULL,
  `deck1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`deck1`)),
  `deck2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`deck2`)),
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trophies1` int(11) NOT NULL DEFAULT 0,
  `trophies2` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CARDSUSERS` (
  `id` int(11) NOT NULL COMMENT 'id de l''utilisateur',
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `cards` longtext COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ('{}') COMMENT 'toutes ses cartes',
  `deck` longtext COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ('[[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]]') COMMENT 'son deck',
  `choosenDeck` tinyint(4) NOT NULL DEFAULT 0,
  `trophies` int(11) NOT NULL DEFAULT 0,
  `lastSearchDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'dernière recherche d''adversaire',
  `lastFreeCard` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'dernière carte gratuite',
  `money` int(11) NOT NULL DEFAULT 10 COMMENT 'argent du joueur',
  `rewardLevel` int(11) NOT NULL DEFAULT 70 COMMENT 'niveau de récompenses',
  `infos` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ('[]') COMMENT 'Tags de profil en json',
  `lastConnection` datetime DEFAULT NULL COMMENT 'date de la dernière connexion du joueur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `FIGHTERSCARDSTYPES` (
  `idNum` int(11) NOT NULL,
  `id` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `MATCHES` (
  `id` int(11) NOT NULL COMMENT 'id du match',
  `opponent1` int(11) NOT NULL COMMENT 'id de l''aversaire 1',
  `opponent2` int(11) NOT NULL COMMENT 'id de l''aversaire 2',
  `infos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ('{}') COMMENT 'decks, mains, profs des deux adversaires',
  `end` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `USERS` (
  `id` int(11) NOT NULL COMMENT 'id de l''utilisateur',
  `email` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'E-mail de l''utilisateur',
  `name` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'nom de l''utilisateur',
  `password` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT 'mot de passe de l''utilisateur',
  `firstName` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'Prénom',
  `lastName` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'Nom de famille'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `BOOSTERS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `CARDS`
  ADD PRIMARY KEY (`id`),
  ADD KEY `authorId` (`authorId`),
  ADD KEY `boosterId` (`boosterId`);

ALTER TABLE `CARDSMATCHESHISTORY`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opponentId1` (`opponentId1`),
  ADD KEY `opponentId2` (`opponentId2`);

ALTER TABLE `CARDSUSERS`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `FIGHTERSCARDSTYPES`
  ADD PRIMARY KEY (`idNum`),
  ADD KEY `id` (`id`);

ALTER TABLE `MATCHES`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opponent1` (`opponent1`),
  ADD KEY `opponent2` (`opponent2`);

ALTER TABLE `USERS`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

ALTER TABLE `BOOSTERS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `CARDS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id de la carte';

ALTER TABLE `CARDSMATCHESHISTORY`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `FIGHTERSCARDSTYPES`
  MODIFY `idNum` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `MATCHES`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id du match';

ALTER TABLE `USERS`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id de l''utilisateur';

--
-- Contraintes pour les tables déchargées
--

ALTER TABLE `CARDS`
  ADD CONSTRAINT `cards_link_` FOREIGN KEY (`authorId`) REFERENCES `USERS` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `CARDSMATCHESHISTORY`
  ADD CONSTRAINT `opponentId1` FOREIGN KEY (`opponentId1`) REFERENCES `CARDSUSERS` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `opponentId2` FOREIGN KEY (`opponentId2`) REFERENCES `CARDSUSERS` (`id`) ON UPDATE CASCADE;

ALTER TABLE `CARDSUSERS`
  ADD CONSTRAINT `cardsusers_link_user` FOREIGN KEY (`id`) REFERENCES `USERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `MATCHES`
  ADD CONSTRAINT `matches_link_o1` FOREIGN KEY (`opponent1`) REFERENCES `CARDSUSERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `matches_link_o2` FOREIGN KEY (`opponent2`) REFERENCES `CARDSUSERS` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
