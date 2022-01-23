<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Galerie de cartes</title>
		<link rel="stylesheet" href="style.css?<?php echo time() ?>" />
		<link rel="stylesheet" href="gallery.css?<?php echo time() ?>" />
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
		<link rel="icon" type="image/png" href="assets/back.png" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body class="no-x-scroll">
	    <div class="hollow"></div>
		<span class="title">Galerie</span>
		<?php
		include('init.php');
		$user = login(true, false);
		?>
		<a href="index.php"><div style="position:absolute; top:2em; left:1em">
			<span class="button">Retourner au menu principal</span>
		</div></a> <!--Modif de Léo-->
		<div id="cards">
			<div class="card-container" style="margin: 0.5% 0.2%;">
				<a href="create.php"><canvas id="add-card" class="aff"></canvas></a>
				<script>drawAddCard(document.getElementById('add-card'), "<?php echo 'rgb('.random_int(0,255).','.random_int(0,255).','.random_int(0,255).')'?>", "Créer une carte")</script>
			</div>
			<?php
			// affichage des cartes
			$result = sendRequest("SELECT CARDS.*, USERS.name AS author FROM CARDS JOIN USERS ON USERS.id = CARDS.authorId".(isset($_REQUEST['all'])?"":" WHERE official > '0'")." ORDER BY ".(isset($_REQUEST['all'])?"":"CASE WHEN official >= 2 THEN 1 ELSE 2 END, rarity DESC, ")."id DESC");
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