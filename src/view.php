<?php
include('api/init.php');
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <?php echo_head_tags("Visualiser une carte", ""); ?>
        <script src="cards.js"></script>
        <link rel="stylesheet" href="style.css" />
    </head>
    <body style="position:relative;background-color:rebeccapurple;text-align:left;">
        <canvas id="card" style="width:50%;"></canvas>
        <div class="card" id="newgencard" style="position:absolute;top:0;left:50%;width:50%;font-size:2vw;"></div>
        <script>
            <?php
            if (isset($_REQUEST['q'])) echo "q = ".$_REQUEST['q'].";";
            ?>
            var cvs = document.getElementById('card');
            <?= isset($_REQUEST['card']) ? "drawCardById(cvs, '".$_REQUEST['card']."');" : "drawCardBack(cvs);"; ?>
            setCardElementById(document.getElementById("newgencard"), "<?= $_REQUEST['card']; ?>");
        </script>
    </body>
</html>