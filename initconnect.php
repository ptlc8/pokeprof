<?php

session_start();

// connexion à un compte
if (!isset($_SESSION['username'], $_SESSION['password']) || ($userRequest = sendRequest("SELECT id, name FROM USERS WHERE `name` = '", $_SESSION['username'], "' and `password` = '", $_SESSION['password'], "'"))->num_rows === 0) {
	header('Location: connect?go='.urlencode($_SERVER[REQUEST_URI]));
	exit();
} else {
	$user = $userRequest->fetch_assoc();
	sendRequest("UPDATE CARDSUSERS SET lastConnection = NOW() WHERE id = '", $user['id'], "'");
	echo('<span id="logged">Vous êtes connecté en tant que '.htmlspecialchars($user['name']).'</span>');
	echo('<a href="disconnect.php?back" id="log-out">Se déconnecter</a>');
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
        $userinfos=$infoslist2;
    }
	
?>
