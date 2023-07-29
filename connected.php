<?php

if (!isset($_REQUEST['token'])) exit('invalid request');

include('api/init.php');

// valider le token
$user = getUser($_REQUEST['token']);
if ($user == null) exit('invalid token');

session_start();
$_SESSION['pokeprof_token'] = $_REQUEST['token'];

if (isset($_REQUEST['go'])) {
    echo("<script>window.location.replace(decodeURIComponent('".$_REQUEST['go']."'))</script>");
} else if (isset($_REQUEST['closeafter'])) {
    echo("<script>window.close();</script>");
} else{
    echo("<script>window.location.replace('.');</script>");
}

?>