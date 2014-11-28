<?php xhtmlHeaders( __FILE__, $SLANG['Console'] ); ?>
<body>
		<?php include("header.php"); ?>

    <form name="monitorForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?= $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <div id="content" class="container-fluid">
			<div class="row">
				<div class="col-md-2"><?php include("sidebar.php"); ?></div>

				<div class="col-md-10">

	<div ng-controller="ConsoleController">
		<div class="row">
			<div class="col-md-4" ng-repeat="monitor in monitors">
				<div class="panel" ng-class="(monitor.alerts.zmc || monitor.alerts.zma) ? 'panel-default' : 'panel-danger'">
					<div class="panel-heading">
						<a ng-hide="(monitor.alerts.zmc || monitor.alerts.zma)" class="pull-right" href="#" tooltip="{{ monitor.alert }}"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></a>
						<h2 class="text-left panel-title">{{ monitor.Name }} <small>({{monitor.Id}})</small></h2>
					</div>

					<div class="panel-body center-block">
						<img class="img-responsive img-rounded" ng-src="/cgi-bin/nph-zms?mode=single&monitor={{monitor.Id}}&scale=50" />
					</div>
				</div>
			</div>
		</div>

	</div>



			</div> <!-- End .col-md-10 -->


				</div> <!-- End .row -->
			</div> <!-- End #content .container-fluid -->
    </form>
		<?php include("footer.php"); ?>
</body>
</html>
