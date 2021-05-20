<?php

session_start();

if (!isset($_SESSION["user"])){
  echo file_get_contents('login.html');
  echo "<!--";
}

$db_params = parse_ini_file( dirname(__FILE__).'/annotation-service/db_params.ini', false);

$servername = "localhost";
$username = $db_params['user'];
$password = $db_params['password'];
$dbname = $db_params['database'];

$conn = new mysqli($servername, $username, $password, $dbname);


if ($_GET["request"] != "load-highlight" && $_GET["request"] != "load-results" && $_GET["request"] != "get-phase" && $_GET["request"] != "get-annotator" ){
  if (isset($_GET["open_issue"])){
    $_SESSION['current_issue'] =  $_GET["open_issue"];
  }else{
    $_SESSION['current_issue'] = 0;
  }

  if (isset($_GET["phase"])){
    $_SESSION['phase'] =  $_GET["phase"];
  } else if (!isset($_SESSION['phase'])){
    $_SESSION['phase'] =  1;
  }
}


$sql = "SELECT annotator_name, finished_calibration, calibration_score, calibration_score_2 FROM Annotators WHERE id = ?";
$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user']);
$stmt->execute();
$annotator1_result = $stmt->get_result();
$annotator1_row = $annotator1_result->fetch_assoc();
$annotator1 = $annotator1_row['annotator_name'];
$finished_calibration = $annotator1_row['finished_calibration'];

if ($_SESSION['annotation_mode'] != 'claim'){
  echo 'Wrong annotator type. You are not authorized to use this tool.';
  echo '<a href="annotation-service/logout.php">Logout</a>';
  echo "<!--";
}
else if($finished_calibration < $_SESSION['phase']){
  echo 'Please finish your calibration annotations first. You can then see your statistics here after receiving feedback from a project coordinator.';
  echo '<a href="annotation-service/logout.php">Logout</a>';
  echo "<!--";
}

if($_SESSION['phase'] ==  1){
  $calibration_data_table = "CalibrationClaimAnnotationDataP1";
  $claim_id_map = array(1,2, 3, 4,5,6,7, 8, 9 ,10);
  $calibration_score =  $annotator1_row['calibration_score'];
  $references = array(
    0 => "1. Thomas Arundell was a a member of the parliament for 5 years.<br>
    2. Thomas Arundell's wife died after he was elected member of parliament. <br>
    3. Thomas Arundell's daughter died after he was elected member of parliament <br>
    <hr>
    1. Thomas Arundell left the parliament in 1640. <br>
    2. Thomas Arundell was married to Julian Cary. <br>
    3. Thomas Arundell was married to Julie Andrews. <br>
    <hr>
    1. Thomas Arundell was elected member of Parliament for West Looe before building a millhouse. <br>
    2. Thomas Arundell married his second wife after he was elected Member of Parliament. <br>
    3. Thomas Arundell married his first wife after he was elected Member of Parliament.
    ",
    1 => "1. In the 1861 boat race the Oxford boat had three Wadham College crew members.  <br>
    2. In the 1861 boat race Cambridge had non-British rowers. <br>
    3. In the 1861 boat race the Oxford boat had three Trinity College crew members.<br>
    <hr>
    1. R. U. P. Fitzgerald participated in The Boat Race 1861 as part of the Cambridge team. <br>
    2. R. U. P. Fitzgerald and his team won The Boat Race 1861.  <br>
    3. R. U. P. Fitzgerald participated in The Boat Race 1861 as part of the Oxford team. <br>
    ",
    2 => "1. The Cherry Hill Farmhouse is mentioned in literary work.  <br>
    2. The Cherry Hill Farmhouse used to be a museum, but now it is inhabited by a rich family. <br>
    3.There are no reference to the Cherry Hill Farmhouse in the literature. <br>
    <hr>
    1. The University of Virginia owned Cherry Hill Farmhouse for eleven years. <br>
    2. Cherry Hill Farmhouse was converted to a museum over a century after it was built. <br>
    3. Cherry Hill Farmhouse never belonged to the University of Virginia. <br>
    ",
    3 => "1. The Cherry Hill Farmhouse is an example of post colonial architectural style. <br>
    2. The Cherry Hill Farmhouse was added to the National Register of Historic Places after it became property of a church.  <br>
    3.The architectural style of the Cherry Hill Farmhouse was very prominent in France and England.  <br>
    <hr>
    1. The Cherry Hill Farmhouse was added to the NRHP 20 days before the VLR.     <br>
    2. The house in Falls Church has been named Cherry Hill because of the fruit orchards surrounding the house.  <br>
    3. The house at 312 Park Avenue, Falls Church has been named Cherry Hill because of the fruit orchards surrounding the house. <br>
    ",
    4 => "1. William Soone was a fellow of Trinity Hall. <br>
    2. William Soone was borne in 1540 and went to Cambridge University.  <br>
    3. William Soone was a fellow of Gonville Hall. <br>
    ",
    5 => "1. Jikki sung most songs for the comedy film Kalyanam Panniyum Brahmachari.   <br>
    2. Jikki was an Indian playback singer who sung songs for Kalyanam Panniyum Brahmachari.  <br>
    3. A. M. Rajah sung most songs for the comedy film Kalyanam Panniyum Brahmachari.    <br>
    <hr>
    1. The duration of Jolly Life Jolly Life by K. D. Santhanam is less than 4 minutes.    <br>
    2. K. D. Santhanam of British nationality wrote the lyrics of Jolly Life Jolly Life.  <br>
    3. The duration of Medhavi Pole Edhetho Pesi by K. D. Santhanam is less than 4 minutes.   <br>
    ",
    6 => "1. Jens Assur worked as a staff photographer before starting his own company.   <br>
    2. Ravens director Jens Assur worked as a staff photographer before starting his own company.  <br>
    3. Ravens director Jens Assur never worked as a staff photographer for a newspaper.    <br>
    ",
    7 => "1. Davy Arnaud joined Montreal Impact in November.   <br>
    2. Davy Arnaud joined Montreal Impact when he was 31 years old.    <br>
    3. Some players joined Montreal Impact in November.   <br>
    ",
);
}else if ($_SESSION['phase'] ==  2){
  $calibration_data_table = "CalibrationClaimAnnotationDataP2";
  $claim_id_map = array(65,66, 67, 68,69,70,71,72,73,74);
  $calibration_score =  $annotator1_row['calibration_score_2'];
}

$calibration_score_details = explode('[SEP]', $calibration_score)[1];
$calibration_score = explode('[SEP]', $calibration_score)[0];

// echo json_encode($references);

if($calibration_score == 0){
  $calibration_score = 'Evaluation has not started yet.';
}


$sql = "SELECT id, claim, data_source,annotator,page, claim_type, challenges FROM CalibrationClaims WHERE data_source = ? AND annotator=?";
$stmt= $conn->prepare($sql);
$stmt->bind_param("ii", $claim_id_map[$_SESSION['current_issue']],$_SESSION["user"]);#, $_SESSION["user"]);
$stmt->execute();
$result_anno1 = $stmt->get_result();
$anno1_c1 = $result_anno1->fetch_assoc();
$anno1_c2 = $result_anno1->fetch_assoc();
$anno1_c3 = $result_anno1->fetch_assoc();



$sql = "SELECT page, selected_id, is_table, multiple_pages, veracity, manipulation FROM $calibration_data_table WHERE id = ?";
$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $anno1_c1['data_source']);
$stmt->execute();
$result_claim = $stmt->get_result();
$claim_data = $result_claim->fetch_assoc();
$data_veracity = (($claim_data['veracity'] == 0) ? 'false' : 'true');
$data_multiple_pages = (($claim_data['multiple_pages'] == 0) ? 'Same page' : 'Multiple pages');


if ($_SERVER['REQUEST_METHOD'] === 'GET'){
  if ($_GET["request"] == "load-highlight"){
    echo json_encode(array($claim_data['is_table'], $claim_data['selected_id'],$_SESSION['current_issue']));
    return;
  }
  else if ($_GET['request'] == 'load-results'){
    echo json_encode($calibration_score_details);
    return;
  }
  else if ($_GET['request'] == 'get-phase'){
    echo json_encode(array($_SESSION['phase']));
    return;
  }
  else if ($_GET['request'] == 'get-annotator'){
    echo json_encode(array($_SESSION['user']));
    return;
  }
}
//   echo json_encode(array('hi'));
?>

<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

  <script src="js/extensions/jquery.js"></script>
  <script src="js/extensions/jquery.md5.js"></script>
  <script src="js/extensions/jquery_ui.js"></script>
  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/vast-engineering/jquery-popup-overlay@2/jquery.popupoverlay.min.js"></script>

  <script type="text/javascript">
  $.ajaxSetup({async:false});

  $(function () {
    $('[data-toggle="popover"]').popover()
  })
  $(window).on('load', function() {

    $.get('calibration_evaluation_claims_user.php', {request:'get-phase'},function(data,status,xhr){
      var curr_phase =  localStorage.getItem('curr_phase');
      var server_phase = data[0];
      if(curr_phase != null){
        if (server_phase != curr_phase){
          localStorage.setItem('first_load', 'true');
        }
      }
      localStorage.setItem('curr_phase', server_phase);
    },'json');

    var first_load = localStorage.getItem('first_load');

    $.get('calibration_evaluation_claims_user.php', {request:'load-results'},function(data,status,xhr){
        if (first_load == null || first_load == 'true'){
        localStorage.setItem('first_load', 'false');
        if (data !='' && data !=0 && data !=null){
          localStorage.setItem('score_dict', data);
        }else{
          localStorage.setItem('score_dict', JSON.stringify({}));
        }
      }
        var score_list = JSON.parse(localStorage.getItem('score_dict'));
        if (Object.keys(score_list).length === 0){
          var score = 0;
          var finished_claims = 0;
        }else{
          var results = calculate_score(score_list);
          var finished_claims = results[0];
          var score = results[1];
        }
        $('#current-num').text("Finished claims: " + finished_claims.toString() + "/30");
        $('#current-score').text("Score: " + score.toString());
        var current_task = $('#current-task').text();
        if(current_task + '1' in score_list && score_list[current_task + '1'] == 1){
          $('#c1').addClass('border-primary border-3');
        }else if (current_task + '1' in score_list && score_list[current_task + '1'] == 0){
          $('#w1').addClass('border-primary border-3');
        }
        if(current_task + '2' in score_list && score_list[current_task + '2'] == 1){
          $('#c2').addClass('border-primary border-3');
        }else if (current_task + '2' in score_list && score_list[current_task + '2'] == 0){
          $('#w2').addClass('border-primary border-3');
        }
        if(current_task + '3' in score_list && score_list[current_task + '3'] == 1){
          $('#c3').addClass('border-primary border-3');
        }else if (current_task + '3' in score_list && score_list[current_task + '3'] == 0){
          $('#w3').addClass('border-primary border-3');
        }

        for(var i = 0; i < 10; i++){
          var button_it = 'Annotation ' + i.toString();
          var current_task_ele = button_it.replace(' ', '-');
          if (button_it + '1' in score_list && button_it + '2' in score_list && button_it + '3' in score_list){
            $('#' + current_task_ele).addClass('a-button');
          }else{
            $('#' + current_task_ele).removeClass('a-button');
          }
        }
      },'json');

    $.get('calibration_evaluation_claims_user.php', {request:'load-highlight'},function(data,status,xhr){
      var is_table = data[0];
      var selected_id = data[1];
      var iframeDoc = $("#my-wikipedia")[0].contentWindow.document;
      if(is_table == 1){
        $(iframeDoc).find("p:contains('" + selected_id.replaceAll('.', '\\.').replaceAll("''", "'").replaceAll('(', '\\(').replaceAll(')', '\\)').replaceAll("'", "\\'").replaceAll('&', '\\&').replaceAll('!', '\\!').replaceAll('?', '\\?') + "')").next().css('border-style','solid').css('border-width', 'thick').css('border-color', 'coral');
        var offset = $(iframeDoc).find("p:contains('" + selected_id.replaceAll('.', '\\.').replaceAll("''", "'").replaceAll('(', '\\(').replaceAll(')', '\\)').replaceAll("'", "\\'").replaceAll('&', '\\&').replaceAll('!', '\\!').replaceAll('?', '\\?') + "')").next().offset();
      }else{
        var sentences = selected_id.replaceAll("''", "'").split(" [SEP] ");
        var offset = $(iframeDoc).find("p:contains('" + sentences[0].replaceAll('.', '\\.').replaceAll('(', '\\(').replaceAll(')', '\\)').replaceAll("'", "\\'").replaceAll('&', '\\&').replaceAll('!', '\\!').replaceAll('?', '\\?') + "')").offset();
        for(var i = 0; i < sentences.length; i++){
          $(iframeDoc).find("p").filter(function() {
            if($(this).find('span').text() ==  sentences[i]){
              $(this).css( "background-color", 'coral');
            }
          });
        }
      }
      if (offset != null){
        offset.left -= 20;
        $("#my-wikipedia").contents().scrollTop(offset.top);
      }
    },'json');

    function getSum(total, num) {
      return total + Math.round(num);
    }


    function calculate_score(score_list){
      finished_claims = Object.keys(score_list).length;
      score = (Object.values(score_list).reduce(getSum,0))/finished_claims;
      return [finished_claims, score];
    }

  });
  </script>

  <style type="text/css">
  .container-fluid {
    width: auto !important;
    margin-right: 10% !important;
    margin-left: 10% !important;
  }

  .border-3 {
    border-width:3px !important;
  }

  .list-in {
    background-color: chartreuse;

  }

  .a-button{
    background: 	#D3D3D3;
  }
  .list-out {
    background-color:#ff6666 ;
  }
  </style>
</head>
<body>
  <div class="container-fluid p-3">
    <div class="row">
      <div class="col-9">
        <a href="annotation-service/logout.php">Logout</a>
        <h3 class='text-center pb-5'>Claim Annotation Calibration Evaluation</h3>
        <h5 class="" id="current-num"> Start evaluation to see scores.</h5>
        <h5 class="" id="current-score">Start evaluation to see scores.</h5>
        <hr style="height:5px;background-color: #333;">
        <h5 class="" id="current-task">Annotation <?php  echo $_SESSION['current_issue']; ?></h5>
        <?php if (isset($references) && array_key_exists($_SESSION['current_issue'], $references)) echo '<button type="button" class="btn btn-warning mb-5" data-container="body" data-toggle="popover" data-html="true" data-placement="top" data-content="' . $references[$_SESSION["current_issue"]] . '">Reference claims </button>'?>
        <div class="mx-auto embed-responsive embed-responsive-21by9" style="width:80vh;">
          <iframe id="my-wikipedia" class="embed-responsive-item" src="http://mediawiki.feverous.co.uk/index.php/<?php echo $claim_data['page'] ?>"></iframe>
        </div>
        <p class='text-center pt-5' id='claim-1'>Claim using Highlight <?php  echo "(" .  $data_veracity . "): " . "<b>" . $anno1_c1['claim'] . '</b>'?></p>
        <p class='text-center'>Challenge: <?php echo $anno1_c1['challenges']?></p>
        <div class="text-center">
          <button name="a1"  class="btn btn-success center" id='c1'> Accept.</button>
          <button name="a2" class="btn btn-danger center" id='w1'> Reject. </button>
        </div>
        <p class='text-center pt-5' id='claim-2'>Claim beyond Highlight: <?php  echo "(" .  $data_multiple_pages . "): " . "<b>" . $anno1_c2['claim'] . '</b>'?> </p>
        <p class='text-center'>Challenge: <?php echo $anno1_c2['challenges']?></p>
        <div class="text-center">
          <button name="a1" class="btn btn-success center" id='c2'> Accept.</button>
          <button name="a2"  class="btn btn-danger center" id='w2'> Reject. </button>
        </div>
        <p class='text-center pt-5' id='claim-3'>Manipulation: <?php  echo "(" .  $claim_data['manipulation'] . "): " . "<b>" . $anno1_c3['claim'] . '</b>'?> </p>
        <p class='text-center'>Challenge: <?php echo $anno1_c3['challenges']?></p>
        <div class="text-center">
          <button name="a1" class="btn btn-success center" id='c3'> Accept.</button>
          <button name="a2" class="btn btn-danger center" id='w3'> Reject. </button>
        </div>
      </div>
      <div class="col-2 bg-success bg-light border text-center">
        <h5 class="list-group-item-heading">Calibration Claims</h5>
        <?php
        // foreach ($inbox as &) {
        for($i=0; $i<10; $i++) {
          if ($i == $_SESSION['current_issue']){
            echo "<a href='?open_issue=$i'
            type='button' id='Annotation-$i' class='btn btn-outline-secondary btn-sm mb-1 w-90 overflow-hidden active' data-toggle='tooltip' data-placement='right' data-html='true'
            title='$i'> Claim $i
            </a>";
          }else{
            echo "<a href='?open_issue=$i'
            type='button' id='Annotation-$i' class='btn btn-outline-secondary btn-sm mb-1 w-90 overflow-hidden' data-toggle='tooltip' data-placement='right' data-html='true'
            title='$i'> Claim $i
            </a>";
          }
        }//da war $sub_email[abbr]
        ?>
        <h5 class="list-group-item-heading">Calibration Phase</h5>
        <?php
        // foreach ($inbox as &) {
        for($i=1; $i<3; $i++) {
          if ($i == $_SESSION['phase']){
            echo "<a href='?phase=$i'
            type='button' class='btn btn-outline-secondary btn-sm mb-1 w-90 overflow-hidden active' data-toggle='tooltip' data-placement='right' data-html='true'
            title='$i'> Phase $i
            </a>";
          }else{
            echo "<a href='?phase=$i'
            type='button' class='btn btn-outline-secondary btn-sm mb-1 w-90 overflow-hidden' data-toggle='tooltip' data-placement='right' data-html='true'
            title='$i'> Phase $i
            </a>";
          }
        }//da war $sub_email[abbr]
        ?>
      </div>
    </div>
  </div>

</body>
</html>
