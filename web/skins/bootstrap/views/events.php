<?php xhtmlHeaders(__FILE__, $SLANG['Events'] ); ?>

<body>

	<?php include("header.php"); ?>

	<div class="container-fluid">
		<div class="row">
			<div class="col-md-2">
				<?php if ( true || canEdit( 'Events' ) ) { ?>
				<div class="btn-group-vertical">
					<input class="btn btn-default" type="button" name="archiveBtn" value="<?= $SLANG['Archive'] ?>" onclick="archiveEvents( this, 'markEids' )" disabled="disabled"/>
					<input class="btn btn-default" type="button" name="unarchiveBtn" value="<?= $SLANG['Unarchive'] ?>" onclick="unarchiveEvents( this, 'markEids' );" disabled="disabled"/>
					<input class="btn btn-default" type="button" name="editBtn" value="<?= $SLANG['Edit'] ?>" onclick="editEvents( this, 'markEids' )" disabled="disabled"/>
					<input class="btn btn-default" type="button" name="exportBtn" value="<?= $SLANG['Export'] ?>" onclick="exportEvents( this, 'markEids' )" disabled="disabled"/>
					<input class="btn btn-default" type="button" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" onclick="deleteEvents( this, 'markEids' );" disabled="disabled"/>
				</div>
				<?php } ?>
			</div> <!-- End sidebar .col-md-2 -->

			<div class="col-md-10" ng-controller="EventsController">
				<div class="clearfix">
					<div class="event" dir-paginate="event in events | itemsPerPage: eventsPerPage" total-items="totalEvents">
						<img ng-src="/events/{{ event.thumbData.Path }}" class="img-thumbnail" alt="..." />
						<div class="over">
							<div class="info">
								<span>Monitor {{event.Event.MonitorId}}</span>
								<span>{{ event.Event.StartTime | DateDiff:event.Event.EndTime:'pretty' }}</span>
								<span>Event {{event.Event.Id}}</span>
							</div> <!-- End .info -->
						</div> <!-- End .over -->
					</div> <!-- End .event -->
				</div> <!-- End .clearfix -->
				<dir-pagination-controls on-page-change="pageChanged(newPageNumber)"></dir-pagination-controls>
			</div> <!-- End main .col-md-10 -->
		</div>
	</div>
	<?php include("footer.php"); ?>
</body>
</html>
