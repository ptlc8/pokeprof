<?php

include('credentials.php');

// initialisation BDD
$mysqli = new mysqli(POKEPROF_DB_HOSTNAME, POKEPROF_DB_USER, POKEPROF_DB_PASSWORD, POKEPROF_DB_NAME);
if ($mysqli->connect_errno) {
	echo 'Erreur de connexion côté serveur, veuillez réessayer plus tard';
	exit;
}
	
// fonction de requête BDD
function sendRequest(...$requestFrags) {
	$request = '';
	$var = false;
	foreach ($requestFrags as $frag) {
		$request .= ($var ? str_replace(array('\\', '\''), array('\\\\', '\\\''), $frag) : $frag);
		$var = !$var;
	}
	global $mysqli;
	if (!$result = $mysqli->query($request)) {
		echo 'Erreur de requête côté serveur, veuillez réessayer plus tard<br>'.$request;
		exit;
	}
	return $result;
}


// connexion à un compte
function login($echoDetails=false, $force=false) {
    session_start();
    if (!isset($_SESSION['username'], $_SESSION['password']) || ($userRequest = sendRequest("SELECT * FROM USERS WHERE `name` = '", $_SESSION['username'], "' and `password` = '", $_SESSION['password'], "'"))->num_rows === 0) {
        if ($echoDetails) {
        	if ($force) header('Location: connect.php?go='.urlencode($_SERVER['REQUEST_URI']));
        	else echo('<span id="login" class="button" onclick="window.location.href = (\'connect.php?go=\'+encodeURIComponent(window.location.pathname))">Se connecter</span>');
        } else if ($force)
            exit("not logged");
    	return null;
    } else {
    	$user = $userRequest->fetch_assoc();
    	sendRequest("UPDATE CARDSUSERS SET lastConnection = NOW() WHERE id = '", $user['id'], "'");
    	if ($echoDetails) {
    	    echo('<span id="logged">Vous êtes connecté en tant que '.htmlspecialchars($user['name']).'</span>');
    	    echo('<a href="/disconnect.php?back" id="log-out">Se déconnecter</a>');
    	}
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

?>
