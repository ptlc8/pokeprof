<?php

include("init.php");

sendRequest("UPDATE CARDS SET official = 1 WHERE DATEDIFF(NOW(),lastEditDate) > 7 AND official > 1");

?>
