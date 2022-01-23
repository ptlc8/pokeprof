<?php

include("public_html/init.php");

sendRequest("UPDATE CARDS SET official = 1 WHERE DATEDIFF(NOW(),lastEditDate) > 7 AND official > 1");

?>