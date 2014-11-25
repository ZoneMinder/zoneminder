<?php
xhtmlHeaders( __FILE__, 'Host' );
?>

<body>

	<?php include("header.php"); ?>

	<div class="container-fluid" ng-controller="HostController">

		<div class="row">
			<div class="col-md-4">
				<h3>CPU Load</h3>

				<canvas tc-chartjs-line chart-data="loadData" chart-options="doptions" width="250px" height="250px"></canvas>
			</div>
		</div>

	</div>

	<?php include("footer.php"); ?>

</body>
</html>
