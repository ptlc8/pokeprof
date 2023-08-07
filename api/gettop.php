<?php
include('init.php');

$result = sendRequest("SELECT trophies, name, id FROM CARDSUSERS ORDER BY trophies DESC, id DESC LIMIT 10");
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>