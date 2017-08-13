<?php
  session_start();
  require "../db/connect.php";
  
  if (!authCheck($_SESSION['user'], $_SESSION['pass']) || !isset($_SESSION['isHR'])) {
    header('Location: ../');
    exit();
  }

  if (is_null($_GET['for']) || empty($_GET['for'])) {
    header('Location: ../hr');
  }

  $emp = securityPipe($_GET['for']);

  // Get the ReviewCount
  // Update: INSERT INTO `loreal_hr_feedback`.`review_cycle` (`date`, `review_count`) VALUES (CURRENT_DATE(), NULL);
  $reviewCountSQL = 'SELECT max(review_count) as rc FROM review_cycle';
  $reviewCount = $conn->query($reviewCountSQL)->fetch_assoc()['rc'];

  // $reviewCount = 2;

  $withoutSelf = 'SELECT avg(competency1) as c1, avg(competency2) as c2, avg(competency3) as c3, 
                  avg(competency4) as c4, avg(competency5) as c5, avg(competency_agg) as cavg
                  FROM emp_feedback 
                  WHERE designation = \'' . $emp . '\' and reviewer <> \'' . $emp . '\' and review_count = ' . $reviewCount;

  $selfScores = 'SELECT sum(competency1) as c1, sum(competency2) as c2, sum(competency3) as c3, 
                  sum(competency4) as c4, sum(competency5) as c5, sum(competency_agg) as cavg
                  FROM emp_feedback 
                  WHERE designation = \'' . $emp . '\' and reviewer = \'' . $emp . '\' and review_count = ' . $reviewCount;

  $scoresByOthers = $conn->query($withoutSelf);
  $scoresBySelf = $conn->query($selfScores);

  $finalScores = array();
  $finalSelfScores = array();

  if ($scoresByOthers->num_rows > 0) {
    $score = $scoresByOthers->fetch_assoc();
    array_push($finalScores, $score['c1']);
    array_push($finalScores, $score['c2']);
    array_push($finalScores, $score['c3']);
    array_push($finalScores, $score['c4']);
    array_push($finalScores, $score['c5']);
    array_push($finalScores, $score['cavg']);
  }

  if ($scoresBySelf->num_rows > 0) {
    $score = $scoresBySelf->fetch_assoc();
    array_push($finalSelfScores, $score['c1']);
    array_push($finalSelfScores, $score['c2']);
    array_push($finalSelfScores, $score['c3']);
    array_push($finalSelfScores, $score['c4']);
    array_push($finalSelfScores, $score['c5']);
    array_push($finalSelfScores, $score['cavg']);
  }

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loreal: Feedback form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link rel="stylesheet" type="text/css" href="../css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <script src="../canvasjs.min.js"></script>
      <script type="text/javascript">
  window.onload = function () {
    var chart = new CanvasJS.Chart("chartContainer",
    {
      title:{
        text: "Feedback Statistics"
      },
      animationEnabled: true,
      legend: {
        cursor:"pointer",
        itemclick : function(e) {
          if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
              e.dataSeries.visible = false;
          }
          else {
              e.dataSeries.visible = true;
          }
          chart.render();
        }
      },
      axisY: {
        title: "Rating"
      },
      toolTip: {
        shared: true,  
        content: function(e){
          var str = '';
          var total = 0 ;
          var str3;
          var str2 ;
          for (var i = 0; i < e.entries.length; i++){
            var  str1 = "<span style= 'color:"+e.entries[i].dataSeries.color + "'> " + e.entries[i].dataSeries.name + "</span>: <strong>"+  e.entries[i].dataPoint.y + "</strong> <br/>" ; 
            total = e.entries[i].dataPoint.y + total;
            str = str.concat(str1);
          }
          str2 = "<span style = 'color:DodgerBlue; '><strong>"+e.entries[0].dataPoint.label + "</strong></span><br/>";
       
          return (str2.concat(str));
        }

      },
      data: [
      {        
        type: "bar",
        showInLegend: true,
        name: "Highest",
        color: "#A0EC37",
        dataPoints: [
        { y: 5, label: "Competency1"},
        { y: 4, label: "Competency2"},
        { y: 5, label: "Competency3"},        
        { y: 5, label: "Competency4"},        
        { y: 4, label: "Competency5"}


        ]
      },
      {        
        type: "bar",
        showInLegend: true,
        name: "Average",
        color: "#37B3EC",          
        dataPoints: [
        { y: 3.5, label: "Competency1"},
        { y: 4, label: "Competency2"},
        { y: 4.5, label: "Competency3"},        
        { y: 3, label: "Competency4"},        
        { y: 2, label: "Competency5"}


        ]
      },
      {        
        type: "bar",
        showInLegend: true,
        name: "Lowest",
        color: "#EC5637",
        dataPoints: [
        { y: 2, label: "Competency1"},
        { y: 3, label: "Competency2"},
        { y: 2.5, label: "Competency3"},        
        { y: 1.5, label: "Competency4"},        
        { y: 1, label: "Competency5"}

        ]
      }

      ]
    });

chart.render();
}
</script>
</head>
<body>
  <header>
    <h1 style="color:white;font-weight:bold;margin-bottom: 0px;padding-bottom: 5px;">L'ORÉAL: <?php echo $emp; ?></h1><h4 style="color:white;font-weight:bold;margin-top: 0px;margin-bottom: 20px;">India</h4>
    <a href="../hr/choose_function.php"><button class="btn btn-sm">Home</button></a>
    <a href="../hr/view_feedback.php"><button class="btn btn-sm">Back</button></a>
  </header>
  
  <div class="row jumbotron">
    <div class="col-lg-12">
      <div class="container">
        <table class="table">
        <thead class="thead-inverse">
          <tr>
            <th>#</th>
            <th>Competency</th>
            <th>Self</th>
            <th>Team</th>
            <th>Others</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row">1</th>
            <td>Competency 1</td>
            <td>4.5</td>
            <td>3</td>
            <td>4</td>
          </tr>
<!--           <?php
            for ($i=0; $i < 5; $i++) { 
              echo '<tr>';
              echo  '<th scope="row">' . $i . '</th>';
              echo  '<td>Competency ' . $i . '</td>';
              echo  '<td>' . round($finalSelfScores[$i], 2) . '</td>';
              echo  '<td>' . round($finalScores[$i], 2) . '</td>';
              echo'</tr>';
            }
          ?> -->
        </tbody>
      </table>
      </div> <!-- /container -->
    </div>

        <div class="col-lg-12">
      <div class="container">
        <table class="table">
        <thead class="thead-inverse">
          <tr>
            <th>Aggregate</th>
            <th>Value</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Self</td>
            <td><?php echo round($finalSelfScores[5], 2); ?></td>
          </tr>
          <tr>
            <td>Team</td>
            <td><?php echo round($finalScores[5]); ?></td>
          </tr>
          <tr>
            <td>Others</td>
            <td><?php echo round($finalScores[5]); ?></td>
          </tr>  
        </tbody>
      </table>
        <div id="chartContainer" style="height: 300px; width: 100%;">
  </div>
      </div> <!-- /container -->
    </div>

  </div>  
</body>
</html>

