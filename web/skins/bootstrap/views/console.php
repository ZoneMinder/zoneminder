<?php xhtmlHeaders( __FILE__, $SLANG['Console'] ); ?>
<body>
	<?php include("header.php"); ?>

	<div class="container-fluid" ng-controller="ConsoleController">
		<div ng-show="fresh" class="alert alert-warning">
			<p><strong>Uh-oh!</strong>  You have no monitors!  Why not <a class="alert-link" href="/?view=monitor">add</a> one?</p>
		</div>

		<div class="row" ng-hide="fresh">
			<div class="col-md-12">
				<div class="btn-toolbar pull-right">
					<button ng-click="consoleView()" class="btn btn-default btn-lg" type="button">
						<span class="glyphicon" ng-class="gridButton"></span>
					</button>
				</div>
			</div>
		</div>

		<div class="row" ng-show="grid" ng-hide="fresh">
			<div class="col-md-12">
				<div class="flexcontainer">
					<div ng-repeat="monitor in monitors" class="monitor panel" ng-class="(monitor.alerts.zmc || monitor.alerts.zma) ? 'panel-default' : 'panel-danger'">

						<div class="panel-heading">
							<a ng-hide="(monitor.alerts.zmc || monitor.alerts.zma)" class="pull-right" href="#" tooltip="{{ monitor.alert }}">
								<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
							</a>
							<h2 class="text-left panel-title">{{ monitor.Name }}</h2>
						</div> <!-- End .panel-heading -->
						
						<div class="panel-body center-block">
							<img class="img-responsive img-rounded" ng-src="/cgi-bin/nph-zms?mode=single&monitor={{monitor.Id}}&scale=50" />
						</div> <!-- End .panel-body -->

					</div> <!-- End .monitor -->
				</div> <!-- End .flexcontainer -->
			</div>
		</div> <!-- End .row -->


		<div class="row" ng-hide="grid">
			<div class="col-md-12">

				<table class="table table-striped">
					<tr>
						<th>Id</th>
						<th>Name</th>
						<th>Function</th>
						<th>Source</th>
						<th>Enabled</th>
						<th>Zones</th>
						<th>Delete</th>
					</tr>

					<tr ng-repeat="monitor in monitors">
						<td ng-bind="monitor.Id"></td>
						<td ng-bind="monitor.Name"></td>
						<td ng-bind="monitor.Function"></td>
						<td ng-bind="monitor.Type"></td>
						<td ng-bind="monitor.Enabled"></td>
						<td ng-bind="monitor.Zones"></td>
						<td><button type="button" class="btn btn-danger btn-sm" ng-click="delete($index)">{{ monitor.deleteText }}</button></td>
					</tr>
					<tfoot>
						<tr>
							<td colspan="7"><a href="?view=monitor">Add New Monitor</a></td>
						</tr>
					</tfoot>
				</table>

			</div>
		</div>

	</div> <!-- .container-fluid -->
</body>
</html>
