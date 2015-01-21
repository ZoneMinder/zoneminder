<?php function xhtmlHeaders( $file, $title ) {
	extract( $GLOBALS, EXTR_OVERWRITE );
?>
<!DOCTYPE html>
<html lang="en" ng-app="ZoneMinder">
<head>
	<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= validHtmlStr($title) ?></title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<base href="/">
	<link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
	<link rel="shortcut icon" href="graphics/favicon.ico"/>
	<link rel="stylesheet" href="skins/bootstrap/css/bootstrap.css" type="text/css" />
	<link rel="stylesheet" href="skins/bootstrap/css/datetimepicker.css" type="text/css" />
	<link rel="stylesheet" href="skins/bootstrap/css/skin.css" type="text/css" />
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.8/angular.min.js"></script>
	<script src="skins/bootstrap/js/dirPagination.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/Chart.min.js"></script>
	<script src="skins/bootstrap/js/app.js"></script>
	<script src="skins/bootstrap/js/controllers.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/tc-angular-chartjs.min.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/ui-bootstrap-tpls-0.12.0.min.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="skins/bootstrap/js/datetimepicker.js"></script>
</head>
<?php } ?>
