<link rel="icon" type="image/png" href="assets/back.png" />
<body style="position:relative;background-color:rebeccapurple;text-align:left;">
    <script src="cards.js?<?php echo time() ?>"></script>
    <link rel="stylesheet" href="style.css?<?php echo time() ?>" />
    <canvas id="card" style="width:50%;"></canvas>
    <div class="card" id="newgencard" style="position:absolute;top:0;left:50%;width:50%;font-size:2vw;"></div>
    <script>
        <?php
        if (isset($_REQUEST['q'])) echo "q = ".$_REQUEST['q'].";";
        ?>
        var cvs = document.getElementById('card');
        <?php
        echo isset($_REQUEST['card']) ? "drawCardById(cvs, '".$_REQUEST['card']."');" : "drawCardBack(cvs);";
        ?>
        setCardElementById(document.getElementById("newgencard"), "<?php echo $_REQUEST['card']; ?>");
    </script>
</body>