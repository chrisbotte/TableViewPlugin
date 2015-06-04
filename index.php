
<?php
require_once '../../redcap_connect.php';
require_once 'misc/RestCallRequest.php';
require_once 'fcns/phpfcns.php';
require_once 'class/class.php';

if(isset($_GET['pid'])){
	$pid = $_GET['pid'];
	REDCap::allowProjects($pid); 
	// user must be authorized to open current pid - allowProjects will sanitize and check pid

}else
	REDCap::allowProjects(-1); 
	// will cause application to exit and display error msg

try{
	$tv = new tv_iu_pim3($_POST, $pid);
	$tbl =  $tv->getTableHTML();
	$html = $tv->getSearchHTML();
	$title = $tv->getUIHdr();
	$summary = $tv->getSummaryHTML();
}
catch (Exception $e){
	echo $e->getMessage();	
	exit();
}

?>

<html>

	<head>
		<title>Dashboard</title>
			<link rel="stylesheet" type="text/css" href="div.css"></link>
			<script type="text/javascript" language="javascript" src="https://datatables.net/release-datatables/media/js/jquery.js"></script>
			<script type="text/javascript" language="javascript" src="https://datatables.net/release-datatables/media/js/jquery.dataTables.js"></script>
			<script type="text/javascript" language="javascript" src="https://datatables.net/release-datatables/extensions/ColVis/js/dataTables.colVis.js"></script>
			<link rel="stylesheet" type="text/css" href="https://datatables.net/release-datatables/media/css/jquery.dataTables.css">
			<link rel="stylesheet" type="text/css" href="https://datatables.net/release-datatables/extensions/ColVis/css/dataTables.colVis.css">
			<link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
			<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
			<script type="text/javascript" src="js\jq.js"></script>
	</head>

	<body>
		<div style="width:95%">
			<div id="top"><span class="title"><? echo $title; ?></span></div>
			<div id="left"><? echo $html; ?><hr><? echo $summary; ?></div>
			<div id="middle"><? echo $tbl; ?></div>
			<div id="dialog" style="display:none" title="Detail"></div>
		</div>
	</body>

</html>
