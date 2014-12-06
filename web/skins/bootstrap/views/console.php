<?php xhtmlHeaders( __FILE__, $SLANG['Console'] ); ?>
<body>
	<?php include("header.php"); ?>

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-2"><?php include("sidebar.php"); ?></div>

			<div class="col-md-10">
				<div ng-controller="ConsoleController" class="flexcontainer">
					<div ng-repeat="monitor in monitors" class="monitor panel" ng-class="(monitor.alerts.zmc || monitor.alerts.zma) ? 'panel-default' : 'panel-danger'">

						<div class="panel-heading">
							<a ng-hide="(monitor.alerts.zmc || monitor.alerts.zma)" class="pull-right" href="#" tooltip="{{ monitor.alert }}">
								<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
							</a>
							<h2 class="text-left panel-title">{{ monitor.Name }} <small>({{monitor.Id}})</small></h2>
						</div> <!-- End .panel-heading -->
						
						<div class="panel-body center-block">
							<img class="img-responsive img-rounded" ng-src="/cgi-bin/nph-zms?mode=single&monitor={{monitor.Id}}&scale=50" />
						</div> <!-- End .panel-body -->

					</div> <!-- End .monitor -->
				</div> <!-- End ConsoleController -->
			</div> <!-- End .col-md-10 -->

		</div> <!-- End .row -->
	</div> <!-- .container-fluid -->
	<?php include("footer.php"); ?>
</body>
</html>
