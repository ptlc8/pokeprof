<?php
include("../init.php");

// connexion à un compte
$user = login();
if ($user == null)
    exit("not logged");

// récupération de l'historique
$history = sendRequest("SELECT CARDSMATCHESHISTORY.*, CARDSUSERS.name as opponentName FROM CARDSMATCHESHISTORY JOIN CARDSUSERS ON (CARDSUSERS.id=opponentId1 AND opponentId2='",$user['id'],"' OR CARDSUSERS.id=opponentId2 AND opponentId1='",$user['id'],"') WHERE CARDSUSERS.id != '", $user['id'], "' ORDER BY date DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
foreach($history as $i=>$match) {
    $history[$i]['deck1'] = json_decode($history[$i]['deck1']);
    $history[$i]['deck2'] = json_decode($history[$i]['deck2']);
    $history[$i]['opponentName1'] = $history[$i]['opponentId1']==$user['id'] ? $user['name'] : $history[$i]['opponentName'];
    $history[$i]['opponentName2'] = $history[$i]['opponentId2']==$user['id'] ? $user['name'] : $history[$i]['opponentName'];
    $history[$i]['win'] = $history[$i]['opponentId'.(1+$history[$i]['winner'])] == $user['id'];
}
echo json_encode($history);
?>