<!DOCTYPE html>
<html lang="en">
<head>
	<?php require_once('header.html'); ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load("current", {packages:["corechart"]});
      google.charts.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Student', 'Number'],
            <?php
                $idlist = file_get_contents('idlist.txt');
                $count_32 = 0;
                $count_33 = 0;
                for($i=14001;$i<14140;$i++){
                    if(count(explode($i,$idlist)) != 1){
                        $count_32++;
                    }
                }
                for($i=15001;$i<15140;$i++){
                    if(count(explode($i,$idlist)) != 1){
                        $count_33++;
                    }
                }
                echo "['32기 - 참여', " . $count_32 . "], \r\n";
                echo "['32기 - 미참여', " . (127 - $count_32) . "], \r\n";
                echo "['33기 - 참여', " . $count_33 . "], \r\n";
                echo "['33기 - 미참여', " . (125 - $count_33) . "] \r\n";
            ?>
        ]);

        var options = {
          title: '기수별 참여도',
          is3D: true,
        };

        var chart = new google.visualization.PieChart(document.getElementById('piechart_3d'));
        chart.draw(data, options);
      }
    </script>
</head>
<body>
    <?php require_once('navbar.php'); ?>
    <!-- Page Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <h1> 조사 참여 통계 </h1>
                <h4> (실시간 갱신됨) </h4>
	   <div id="piechart_3d" style="width: 800px; height: 600px; display: block; margin: 0 auto; display: inline-block"></div>
            </div>
        </div>
        <!-- /.row -->
    </div>
	<?php require_once('footer.php'); ?>
</body>
</html>
