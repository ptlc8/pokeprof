<?php
include('api/init.php');
$user = login(false);
?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<?php echo_head_tags("Galerie de cartes", "Parcourez la Galerie de Cartes et découvrez les héros déjantés de l’EISTI ! Profs, élèves, effets et terrains : analysez leurs stats, préparez votre deck, et trouvez les cartes parfaites pour écraser vos adversaires. Stratégie et fun garantis !"); ?>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="gallery.css" />
		<script src="cards.js"></script>
		<script><!--tmp?-->
			window.onload = () => {
				let hash = window.location.hash;
				if (hash!='') {
					let card = hash.substring(1);
					printCardAbout(card);
				}
			}
		</script>
	</head>
	<body class="no-x-scroll">
	    <div class="hollow"></div>
		<span class="title">Galerie</span>
		<?php if ($user == null) { ?>
			<a id="login" class="button" href="connect.php?go=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Se connecter</a>
		<?php } else { ?>
			<span id="logged">Vous êtes connecté en tant que <?= htmlspecialchars($user['name']) ?></span>
			<a href="disconnect.php?back" id="log-out">Se déconnecter</a>
		<?php } ?>
		<!--Modif de Léo-->
		<a href="." id="home-button" class="button">Retourner au menu principal</a>
		<div id="cards">
			<div class="card-container" style="margin: 0.5% 0.2%;">
				<a href="create.php"><canvas id="add-card" class="aff"></canvas></a>
				<script>drawAddCard(document.getElementById('add-card'), "<?php echo 'rgb('.random_int(0,255).','.random_int(0,255).','.random_int(0,255).')'?>", "Créer une carte")</script>
			</div>
			<?php
			// affichage des cartes
			$result = sendRequest("SELECT CARDS.*, CARDSUSERS.name AS author FROM CARDS JOIN CARDSUSERS ON CARDSUSERS.id = CARDS.authorId".(isset($_REQUEST['all'])?"":" WHERE official > '0'")." ORDER BY ".(isset($_REQUEST['all'])?"":"CASE WHEN official >= 2 THEN 1 ELSE 2 END, rarity DESC, ")."id DESC");
			while ($card = $result->fetch_assoc()) {
			    if (isset($_REQUEST['prestige']) && $card['prestigeable']!=1) continue;
			    $cardIdForClient = $card['id'].(isset($_REQUEST['prestige'])?'p':'').(isset($_REQUEST['shiny'])?'s':'').(isset($_REQUEST['holo'])?'h':'');
				echo('<div class="card-container'.($card['official']==2?' new':'').($card['official']==3?' buff':'').($card['official']==4?' nerf':'').'"><div class="card'.($card['rarity']==4?' shining':'').'" id="card'.$card['id'].'" onclick="printCardAbout(\''.$cardIdForClient.'\')"></div>');
				echo('<script>setCardElementById(document.getElementById("card'.$card['id'].'"), "'.$cardIdForClient.'")</script>');
				echo('<span class="author">'.$card['id'].' - créée par '.htmlspecialchars($card['author']).' '.($card['official']>0?'⭐':($card['official']==0?'🧩':($card['official']==-2?'🧲':($card['official']==-3?'❌':'⌛')))) .'</span></div>');
			}
			?>
		</div>
	</body>
</html>
