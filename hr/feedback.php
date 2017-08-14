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
  
  // Get emp level
  $level = 2;
  $levelSQL = "SELECT level FROM emp_info WHERE designation = '{$emp}'";
  $result = $conn->query($levelSQL);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $level = $row['level'];
  }
  echo $level;

  $selfSQL = "SELECT competency, avg(section1) as s1, avg(section2) as s2, avg(section3) as s3, 
              avg(section4) as s4, avg(competency_agg) as cagg, min(min) as smin, max(max) as smax 
              FROM feedback 
              WHERE review_count = {$reviewCount}
              AND designation = '{$emp}'
              AND reviewer = '{$emp}'
              GROUP BY competency";

  $teamSQL = "SELECT competency, avg(section1) as s1, avg(section2) as s2, avg(section3) as s3, 
              avg(section4) as s4, avg(competency_agg) as cagg, min(min) as smin, max(max) as smax 
              FROM feedback 
              WHERE review_count = {$reviewCount}
              AND designation = '{$emp}'
              AND reviewer in (SELECT designation FROM emp_info WHERE manager = '{$emp}')
              GROUP BY competency";

  $managerSQL = "SELECT competency, avg(section1) as s1, avg(section2) as s2, avg(section3) as s3, 
              avg(section4) as s4, avg(competency_agg) as cagg, min(min) as smin, max(max) as smax 
              FROM feedback 
              WHERE review_count = {$reviewCount}
              AND designation = '{$emp}'
              AND reviewer in (SELECT manager FROM emp_info WHERE designation = '{$emp}')
              GROUP BY competency";

  if ($level == 1) {
    $peerSQL = "SELECT competency, avg(section1) as s1, avg(section2) as s2, avg(section3) as s3, 
              avg(section4) as s4, avg(competency_agg) as cagg, min(min) as smin, max(max) as smax 
              FROM feedback 
              WHERE review_count = {$reviewCount}
              AND designation = '{$emp}'
              AND reviewer in (SELECT designation FROM emp_info WHERE level = $level AND designation <> '{$emp}')
              GROUP BY competency";    
    $peerScoreDAO = $conn->query($peerSQL);
    $peerScore = array();
    if ($peerScoreDAO->num_rows > 0) {
      while ($row = $peerScoreDAO->fetch_assoc()) {
        array_push($peerScore, $row);
      }
    }

  }


  $selfScoresDAO = $conn->query($selfSQL);
  $teamScoresDAO = $conn->query($teamSQL);
  $managerScoreDAO = $conn->query($managerSQL);

  $selfScore = array();
  $teamScore = array();
  $managerScore = array();

  if ($selfScoresDAO->num_rows > 0) {
    while ($row = $selfScoresDAO->fetch_assoc()) {
      array_push($selfScore, $row);
    }
  }

  if ($teamScoresDAO->num_rows > 0) {
    while ($row = $teamScoresDAO->fetch_assoc()) {
      array_push($teamScore, $row);
    }
  }

  if ($managerScoreDAO->num_rows > 0) {
    while ($row = $managerScoreDAO->fetch_assoc()) {
      array_push($managerScore, $row);
    }
  }


  if (false) {
    $withoutSelf = 'SELECT avg(competency1) as c1, avg(competency2) as c2, avg(competency3) as c3, 
                    avg(competency4) as c4, avg(competency5) as c5, avg(competency_agg) as cavg
                    FROM emp_feedback 
                    WHERE designation = \'' . $emp . '\' and reviewer <> \'' . $emp . '\' and review_count = ' . $reviewCount;

                    
    $team = 'SELECT avg(competency1) as c1, avg(competency2) as c2, avg(competency3) as c3, 
                    avg(competency4) as c4, avg(competency5) as c5, avg(competency_agg) as cavg
                    FROM emp_feedback 
                    WHERE designation = \'' . $emp . '\' and reviewer <> \'' . $emp . '\' and review_count = ' . $reviewCount . '
                    and reviewer in (SELECT designation FROM emp_info WHERE manager = \''. $emp . '\')';

                    
    $manager = 'SELECT avg(competency1) as c1, avg(competency2) as c2, avg(competency3) as c3, 
                    avg(competency4) as c4, avg(competency5) as c5, avg(competency_agg) as cavg
                    FROM emp_feedback 
                    WHERE designation = \'' . $emp . '\' and reviewer in (SELECT manager FROM emp_info WHERE designation = \'' . $emp . '\' ) and review_count = ' . $reviewCount;

    // if level == 1, add peers too in the $manager sql


    $selfScores = 'SELECT sum(competency1) as c1, sum(competency2) as c2, sum(competency3) as c3, 
                    sum(competency4) as c4, sum(competency5) as c5, sum(competency_agg) as cavg
                    FROM emp_feedback 
                    WHERE designation = \'' . $emp . '\' and reviewer = \'' . $emp . '\' and review_count = ' . $reviewCount;

    $scoresByOthers = $conn->query($withoutSelf);
    $scoresBySelf = $conn->query($selfScores);

    $scoresByTeam = $conn->query($team);
    $scoresByManager = $conn->query($manager);
    echo $manager;

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
    }}

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
    <link rel="stylesheet" type="text/css" href="../css/feedback_style.css">
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
        { y: 5, label: "PEOPLE DEVELOPER"},
        { y: 4, label: "ENTREPRENEUR"},
        { y: 5, label: "STRATEGIST"},        
        { y: 5, label: "INTEGRATOR"},        
        { y: 4, label: "INNOVATOR"}


        ]
      },
      {        
        type: "bar",
        showInLegend: true,
        name: "Average",
        color: "#37B3EC",          
        dataPoints: [
        { y: 3.5, label: "PEOPLE DEVELOPER"},
        { y: 4, label: "ENTREPRENEUR"},
        { y: 4.5, label: "STRATEGIST"},        
        { y: 3, label: "INTEGRATOR"},        
        { y: 2, label: "INNOVATOR"}


        ]
      },
      {        
        type: "bar",
        showInLegend: true,
        name: "Lowest",
        color: "#EC5637",
        dataPoints: [
        { y: 2, label: "PEOPLE DEVELOPER"},
        { y: 3, label: "ENTREPRENEUR"},
        { y: 2.5, label: "STRATEGIST"},        
        { y: 1.5, label: "INTEGRATOR"},        
        { y: 1, label: "INNOVATOR"}

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
    <h1>L'ORÉAL: <?php echo $emp; ?></h1>
    <h4>India</h4>
    <a href="../hr/choose_function.php"><button class="btn btn-sm">Home</button></a>
    <a href="../hr/view_feedback.php"><button class="btn btn-sm">Back</button></a>
  </header>
  
  <div class="row jumbotron" style="background-color: white;">
    <div class="col-lg-12">
      <div class="container">
        <table class="table table-responsive table-hover">
          <thead>
                <tr><th></th><th><h4>Competency</h4></th><th><h4>Self</h4></th><th><h4>Team</h4></th><th><h4>Others</h4></th></tr>
            </thead>
            <tbody>

            <?php
              $competencyKeys = array('PEOPLE DEVELOPER',
               'ENTREPRENEUR', 
               'STRATEGIST', 
               'INTEGRATOR', 
               'INNOVATOR'
               );

              $competencyText = array('PEOPLE DEVELOPER' => ['Treats all individuals in a respectful and consistent manner', 'Leverages diversity',' Stimulates learning',' Empowers and develops individuals to contribute their best'],
               'ENTREPRENEUR' => ['Takes accountability with courage', 'Builds and manages a customer centric organization', 'Gives space for initiatives and enables teams to take risks', 'Delivers with integrity both sustainable and short term results'], 
               'STRATEGIST' => ['Builds an inspiring and shared vision', 'Creates strategic scenarios for growth', 'Leads transformation by aligning organization and human capabilities', 'Demonstrates sound judgment in decision making'], 
               'INTEGRATOR' => ['Fosters a climate of trust and constructive confrontation', 'Develops collective performance of the team', 'Enhances transversal cooperation', 'Mobilizes stakeholders through active networking'], 
               'INNOVATOR' => ['Puts the consumer as the central focus', 'Challenges the status quo and strives for excellence', 'Innovates beyond the product', 'Seizes what is just starting and opens new ventures']
               );

            ?>

                <?php
                for ($c=1; $c <= 5 ; $c++) { 
                  echo '<tr class="clickable" data-toggle="collapse" id="row' . $c . '" data-target=".row' . $c . '">
                    <td><i class="glyphicon glyphicon-plus"></i></td>
                    <td>' . $competencyKeys[$c-1] . '</td>
                    <td>' . $selfScore[$c-1]['cagg'] . '</td>
                    <td>' . $teamScore[$c-1]['cagg'] . '</td>
                    <td>' . $managerScore[$c-1]['cagg'] . '</td>
                    </tr>';

                  for ($section=1; $section <= 4 ; $section++) { 
                    echo '<tr class="collapse row' . $c . '">
                      <td><i class="glyphicon glyphicon-minus"></i></td>
                      <td>' . $competencyText[$competencyKeys[$c-1]][$section-1] . '</td>
                      <td>' . $selfScore[$c-1]['s' . $section] . '</td>
                      <td>' . $teamScore[$c-1]['s' . $section] . '</td>  
                      <td>' . $managerScore[$c-1]['s' . $section] . '</td>
                      </tr>';
                  }

                }

                ?>
            </tbody>
        </table>
      </div> <!-- /container -->
    </div>

        <div class="col-lg-12">
      <div class="container">
        <table class="table">
        <thead class="thead-inverse">
          <tr>
            <th><h4 style="font-weight: bold;">Aggregate</h4></th>
            <th><h4 style="font-weight: bold;">Value</h4></th>
          </tr>
        </thead>
        <tbody>
          <tr class="aggregate">
            <td>Self</td>
            <td><?php echo round($finalSelfScores[5], 2); ?></td>
          </tr>
          <tr class="aggregate">
            <td>Team</td>
            <td><?php echo round($finalScores[5]); ?></td>
          </tr>
          <tr class="aggregate">
            <td>Others</td>
            <td><?php echo round($finalScores[5]); ?></td>
          </tr>  
        </tbody>
      </table>
        <div id="chartContainer" style="height: 300px; width: 100%;"></div>
      </div> <!-- /container -->
    </div>

  </div>  
  <script src="../js//jquery.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
</body>
</html>

