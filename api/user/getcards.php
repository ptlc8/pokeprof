<?php
include('../init.php');

$user = login();
if ($user == null)
    exit("not logged");

$cardsUser = sendRequest("SELECT cards FROM CARDSUSERS WHERE id = '", $user['id'], "'")->fetch_assoc();

if ($cardsUser === null)
    exit("Veuillez d'abord aller à la page d'accueil");

echo $cardsUser["cards"];
?>