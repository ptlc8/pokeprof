<?php

// si le fichier credentials.php existe, on l'inclut
@include('credentials.php');

// obtention d'une variable de configuration
function get_config($name) {
    if (defined($name) && !empty(constant($name)))
            return constant($name);
    return getenv($name) ?? null;
}

// initialisation BDD
$mysqli = new mysqli(get_config('DB_HOSTNAME'), get_config('DB_USER'), get_config('DB_PASSWORD'), get_config('DB_NAME'));
if ($mysqli->connect_errno) {
	echo 'Erreur de connexion côté serveur, veuillez réessayer plus tard';
	exit;
}

// fonction de requête BDD
function sendRequest(...$requestFrags) {
    global $mysqli;
	$request = '';
	$var = false;
	foreach ($requestFrags as $frag) {
		$request .= ($var ? $mysqli->real_escape_string($frag) : $frag);
		$var = !$var;
	}
	if (!$result = $mysqli->query($request)) {
		echo 'Erreur de requête côté serveur, veuillez réessayer plus tard<br>'.$request;
		exit;
	}
	return $result;
}


// connexion à un compte
function login($redirect_to_login=false) {
    session_start();
    if (!isset($_SESSION['pokeprof_token']) || ($user = getUser($_SESSION['pokeprof_token'])) == null) {
        if ($redirect_to_login)
            exit(header('Location: connect.php?go='.urlencode($_SERVER['REQUEST_URI'])));
    	return null;
    } else {
        sendRequest("UPDATE CARDSUSERS SET lastConnection = NOW() WHERE id = '", $user['id'], "'");
    }

    //
    $infoslist = sendRequest("SELECT infos FROM CARDSUSERS WHERE id = '", $user['id'], "'");
    $infoslist= $infoslist -> fetch_assoc();
    $infoslist= $infoslist['infos'];
    if(isset($infoslist) &&  $infoslist!=false &&  $infoslist!=""){
        $infoslist=explode(';',$infoslist);
        foreach($infoslist as $theinfo){
            if (preg_match("#[a-zA-z0-9]*:[a-zA-z0-9]*#",$theinfo)){
                $theinfo=explode(':',$theinfo);
                $infoslist2[$theinfo[0]]=$theinfo[1];
            }
        }
        global $infoslist;
        $userinfos=$infoslist2;
    }
    return $user;
}

// récupération des informations externes de l'utilisateur connecté
function getUser($token) {
    if (!isset($token)) return null;
    $context = null;
    if (get_config('PORTAL_OVERRIDE_HOST'))
        $context = stream_context_create([ 'http' => [ 'header' => 'Host: '.get_config('PORTAL_OVERRIDE_HOST') ] ]);
    $response = file_get_contents(get_config('PORTAL_USER_URL').$token, false, $context);
    if ($response === false) return null;
    return json_decode($response, true);
}

// envoi d'un message dans un salon Discord via un webhook
function sendToDiscord($url, $content) {
    set_error_handler(function() { /* ignore errors */ });
    $context  = stream_context_create(array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query(array('content' => $content))
        )
    ));
    file_get_contents($url, false, $context);
    restore_error_handler();
}

// écriture des meta-tags de base
function echo_head_tags($pageName, $description) { ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageName ?> - PokéProf !</title>
    <link rel="icon" type="image/png" href="assets/icon.png" />
    <link rel="manifest" href="manifest.webmanifest" />
    <script src="include-service-worker.js"></script>
    <link rel="shortcut icon" href="favicon.ico" />
    <meta name="language" content="fr" />
    <meta name="sitename" content="Poképrof !" />
    <meta name="keywords" content="jeu de cartes en ligne, poképrof, jeu stratégique EISTI, batailles cartes tour par tour, héros déjantés EISTI, profs, élèves, terrains, effets, jeu de cartes humoristique, deck-building en ligne, combat stratégique multijoueur, effets drôles, combats épiques, jeu de stratégie, jeu multijoueur fun, cartes et mana, univers EISTI, fun et stratégie, jeu avec professeurs, jeu étudiant, humour et stratégie" />
    <meta name="description" content="<?= $description ?>" />
    <meta name="robots" content="index, follow" />
    <meta name="copyright" content="© <?= date('Y') ?> Ambi - Tous droits réservés" />
    <meta name="author" content="Ambi" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= $pageName ?> | PokéProf !" />
    <meta property="og:description" content="<?= $description ?>" />
    <meta property="og:image" content="assets/screenshot.jpg" />
<?php }

?>
