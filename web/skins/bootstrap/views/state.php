<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" ng-controller="StateController">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<p class="modal-title">State</p>
			</div>


			<form name="contentForm" ng-submit="changestate('fubar')" novalidate>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="runState">Change State</label>
								<select class="form-control" name="runState" id="runState">
									<option ng-if="isRunning" value="stop" selected="selected">Stop</option>
									<option ng-if="isRunning" value="restart">Restart</option>
									<option ng-if="!isRunning" value="start" selected="selected">Start</option>
									<option ng-repeat="state in states" ng-value="state.State.Name">{{state.State.Name}}</option>
								</select>
							</div> <!-- end .form-group -->
						</div> <!-- end .col-md-6 -->

						<div class="col-md-6">
							<div class="form-group">
								<label for="newState"> <?= $SLANG['NewState'] ?></label>
								<input class="form-control" type="text" id="newState" name="newState" value="" size="16" />
							</div> <!-- end .form-group -->
						</div> <!-- end .col-md-6 -->
					</div> <!-- end .row -->
				</div> <!-- End .modal-body -->

				<div class="modal-footer">
					<button ng-if="!isRunning" type="button" class="btn btn-success" ng-click="changeState('start')">Start</button>
					<button ng-if="isRunning" type="button" class="btn btn-success" ng-click="changeState('restart')">Restart</button>
					<button ng-if="isRunning" type="button" class="btn btn-danger" ng-click="changeState('stop')">Stop</button>
				</div> <!-- End .modal-footer -->

			</form>
		</div> <!-- End .modal-content -->
	</div> <!-- End .modal-dialog -->
</div> <!-- End .modal -->
