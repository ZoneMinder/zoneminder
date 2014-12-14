<div role="tabpanel" class="tab-pane" id="buffers">
	<div class="form-group">
		<label for="ImageBufferCount"><?= $SLANG['ImageBufferSize'] ?></label>
		<input id="ImageBufferCount" type="number" class="form-control" name="newMonitor[ImageBufferCount]" value="100" />
	</div>
	<div class="form-group">
		<label for="WarmupCount"><?= $SLANG['WarmupFrames'] ?></label>
		<input id="WarmupCount" type="number" class="form-control" name="newMonitor[WarmupCount]" value="25" />
	</div>
	<div class="form-group">
		<label for="PreEventCount"><?= $SLANG['PreEventImageBuffer'] ?></label>
		<input id="PreEventCount" type="number" class="form-control" name="newMonitor[PreEventCount]" value="50" />
	</div>
	<div class="form-group">
		<label for="PostEventCount"><?= $SLANG['PostEventImageBuffer'] ?></label>
		<input id="PostEventCount" type="number" class="form-control" name="newMonitor[PostEventCount]" value="50" />
	</div>
	<div class="form-group">
		<label for="StreamReplayBuffer"><?= $SLANG['StreamReplayBuffer'] ?></label>
		<input id="StreamReplayBuffer" type="number" class="form-control" name="newMonitor[StreamReplayBuffer]" value="1000" />
	</div>
	<div class="form-group">
		<label for="AlarmFrameCount"><?= $SLANG['AlarmFrameCount'] ?></label>
		<input id="AlarmFrameCount" type="number" class="form-control" name="newMonitor[AlarmFrameCount]" value="1" />
	</div>
</div>
