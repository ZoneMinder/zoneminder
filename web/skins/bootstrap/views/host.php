<?php
xhtmlHeaders( __FILE__, 'Host' );
?>

<body>

	<?php include("header.php"); ?>

	<div class="container-fluid" ng-controller="HostController">

		<div class="row">
			<div class="col-md-4">
				<h3>Disk Usage in Gigabytes</h3>
				
				<div class="col-md-6">
				<canvas tc-chartjs-polararea chart-data="ddata" chart-options="doptions" width="250px" height="250px" chart-legend="doughnutChart1"></canvas>
				</div>

				<div class="col-md-6" tc-chartjs-legend="" chart-legend="doughnutChart1"></div>
			</div> <!-- End disk usage -->

			<div class="col-md-4">
				<h3>CPU Load</h3>

				<canvas tc-chartjs-line chart-data="loadData" chart-options="doptions" width="250px" height="250px"></canvas>
			</div>
		</div>

	</div>

	<?php include("footer.php"); ?>

</body>
</html>
