<?php

include('credentials.php');

// initialisation BDD
$mysqli = new mysqli(DB_HOSTNAME, DB_USER, DB_PASSWORD, DB_NAME);
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
    if (defined('PORTAL_OVERRIDE_HOST') && !empty(PORTAL_OVERRIDE_HOST))
        $context = stream_context_create([ 'http' => [ 'header' => 'Host: '.PORTAL_OVERRIDE_HOST ] ]);
    $response = file_get_contents(PORTAL_USER_URL.$token, false, $context);
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

?>
