<?php

if (!isset($_REQUEST['token'])) exit('invalid request');

include('api/init.php');

// valider le token
$user = getUser($_REQUEST['token']);
if ($user == null) exit('invalid token');

session_start();
$_SESSION['pokeprof_token'] = $_REQUEST['token'];

if (isset($_REQUEST['go'])) {
    header('Location: '.urldecode($_REQUEST['go']));
} else if (isset($_REQUEST['closeafter'])) {
    echo("<script>window.close();</script>");
} else{
    header('Location: .');
}

?>