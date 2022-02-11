<?php session_start(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Connexion | <?=$_SERVER['HTTP_HOST']?></title>
		<link rel="stylesheet" href="style.css" />
		<link rel="stylesheet" href="connect.css" />
	    <meta name="viewport" content="width=device-width, initial-scale=1" />
	</head>
	<body>
		<form method="post" action="" class="form">
			<h1 class="title">Connexion</h1>
			<span style="color:green;">(Les identifiants de l'ex agnd.fr, de cy-hub.fr et de ambi.dev sont les mêmes)</span>
			<input name="username" type="text" placeholder="Nom d'utilisateur" <?php echo(isset($_REQUEST['username']) ? 'value="'.$_REQUEST['username'].'" ' : '') ?> autocomplete="name" autofocus />
			<input name="password" type="password" placeholder="Mot de passe" autocomplete="current-password" />
			<input type="submit" value="Se connecter" />
			
			<?php
			include("api/init.php");
			if (!(isset($_REQUEST['username'], $_POST['password']))) {}
			else {
				$hashed_password = hash('sha512', $_POST['password']);
				if (sendRequest("SELECT * FROM USERS WHERE `name` = '", $_REQUEST['username'], "' and `password` = '", $hashed_password, "'")->num_rows === 0) {
					echo("<p style='color: red;'>Nom d'utlisateur ou mot de passe invalide. 😱</p>");
				} else {
					$_SESSION['username'] = $_REQUEST['username'];
					$_SESSION['password'] = $hashed_password;
					if (isset($_REQUEST['go'])) {
						echo("<script>window.location.replace(decodeURIComponent('".$_REQUEST['go']."'))</script>");
					} else if (isset($_REQUEST['closeafter'])) {
					    echo("<script>window.close();</script>");
					} else{
						echo("<script>window.location.replace('.');</script>");
					}
				}
			}
			?>
			
			<p><a href="/register.php<?php echo isset($_REQUEST['go']) ? '?go='.urlencode($_REQUEST['go']) : (isset($_REQUEST['closeafter']) ? '?closeafter' : ''); ?>">Je me connecte pour la première fois</a><br /><a href="/forgotten-password.php">J'ai oublié mon mot de passe</a></p>
		</form>
	</body>
</html>

