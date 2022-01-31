<?php
include('../../init.php');
$fighterstypes = [];
foreach(sendRequest("SELECT id,name FROM FIGHTERSCARDSTYPES")->fetch_all(MYSQLI_ASSOC) as $row)
    $fighterstypes[$row['id']] = array('name'=>$row['name']);
echo json_encode($fighterstypes);
?>