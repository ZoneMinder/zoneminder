<?php xhtmlHeaders(__FILE__, $SLANG['SystemLog'] ); ?>
<body>

	<?php include("header.php"); ?>

  <div class="container-fluid" ng-controller="LogController">
		<div class="row">


		<div class="col-md-2">

      <div id="filters">
				<div class="form-group">
        	<label class="sr-only" for="filter[Component]">Component</label>
					<select class="form-control" id="filter[Component]" onchange="filterLog(this)"><option value="">Component</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Pid]">PID</label>
					<select class="form-control" id="filter[Pid]" onchange="filterLog(this)"><option value="">PID</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Level]">Level</label>
					<select class="form-control" id="filter[Level]" onchange="filterLog(this)"><option value="">Level</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[File]">File</label>
					<select class="form-control" id="filter[File]" onchange="filterLog(this)"><option value="">File</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Line]">Line</label>
					<select class="form-control" id="filter[Line]" onchange="filterLog(this)"><option value="">Line</option></select>
				</div>

      </div>
		</div>

    <div class="col-md-10">
				<dir-pagination-controls on-page-change="pageChanged(newPageNumber)"></dir-pagination-controls>

			<table class="table table-striped table-condensed">
				<tr>
					<th><?= $SLANG['DateTime'] ?></th>
					<th><?= $SLANG['Component'] ?></th>
					<th><?= $SLANG['Pid'] ?></th>
					<th><?= $SLANG['Level'] ?></th>
					<th><?= $SLANG['Message'] ?></th>
					<th><?= $SLANG['File'] ?></th>
					<th><?= $SLANG['Line'] ?></th>
				</tr>
				<tr dir-paginate="log in logs| itemsPerPage: logsPerPage" total-items="totalLogs">
					<td>{{ log.Log.TimeKey }}</td>
					<td>{{ log.Log.Component }}</td>
					<td>{{ log.Log.Pid }}</td>
					<td>{{ log.Log.Level }}</td>
					<td>{{ log.Log.Message }}</td>
					<td>{{ log.Log.File }}</td>
					<td>{{ log.Log.Line }}</td>
				</tr>
			</table>

    </div>
  </div>
</div>
</body>
</html>
