<!DOCTYPE HTML>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    p {
        text-align: center;
        font-size: 60px;
        margin-top: 0px;
    }
    </style>
    
</head>

<body>
        <?php
        function AffTps($tps){
            $min=$tps/60;
            $sec=$tps%60;
            settype($min,"integer");
            if($tps%60 < 10){
                $prt="$min:0$sec";
            }
            else{
                $prt="$min:$sec";
            }
            return $prt;
        }
        
        $now=0;
        while($now>=0){
            echo AffTps($now),'<br />';
            $now--;
        }
            ?>
    <span id="zefs"></span>
    <script type="text/javascript">
        var timeleft = 100;
        var downloadTimer = setInterval(function(){
          document.getElementById("zefs").textConent = 100 - timeleft;
          timeleft -= 1;
          if(timeleft <= 0){
            clearInterval(downloadTimer);
          }
        }, 100);
    </script>
            
        
</body>

</html>