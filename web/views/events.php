	<div class="container-fluid" ng-controller="EventsController">
		<div class="row">

			<div class="col-md-2 sidebar">
				<div class="container-fluid">
					<?php include("events_search.html"); ?>
				</div>
			</div>

			<div class="col-md-10 col-md-offset-2">
				<div class="clearfix events">
					<div class="event" dir-paginate="event in events | itemsPerPage: eventsPerPage" total-items="totalEvents" current-page="page" ng-click="displayEvent($index)">
						<img ng-src="/events/{{ event.thumbData.Path }}" class="img-thumbnail" alt="..."/>
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
		</div> <!-- End .row -->
	</div> <!-- End .container-fluid -->
