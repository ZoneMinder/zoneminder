		<div ng-show="sourceType == 'Remote'">
			<div class="form-group">
				<label for="newMonitor[Protocol]"><?= $SLANG['RemoteProtocol'] ?></label>
				<select ng-model="Protocol" id="newMonitor[Protocol]" onchange="updateMethods( this )">
					<option value="http">HTTP</option>
					<option value="rtsp">RTSP</option>
				</select>
			</div>
			<div class="form-group">
				<label for="newMonitor[Method]"><?= $SLANG['RemoteMethod'] ?></label>
<?php if ( empty($newMonitor['Protocol']) || $newMonitor['Protocol'] == "http" ) { ?>
				<select ng-model="Method" id="newMonitor[Method]">
					<option value="simple">Simple</option>
					<option value="regexp">Regexp</option>
				</select>
<?php } else { ?>
				<select ng-model="Method" id="newMonitor[Method]">
					<option value="rtpUni">RTP/Unicast</option>
					<option value="rtpMulti">RTP/Multicast</option>
					<option value="rtpRtsp">RTP/RTSP</option>
					<option value="rtpRtspHttp">RTP/RTSP/HTTP</option>
				</select>
<?php } ?>
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostName'] ?></label>
				<input type="text" class="form-control" ng-model="Host" placeholder="HostName or ip.add.re.ss" />
			</div>
			<div class="form-group">
				<label for=""><?= $SLANG['RemoteHostPort'] ?></label>
				<input type="number" class="form-control" ng-model="Port" placeholder="Usually 80 for http, 554 for rtsp" />
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostPath'] ?></label>
				<input type="text" class="form-control" ng-model="Path" placeholder="/path/to/stream.mpg" />
			</div>
	</div> <!-- End Remote -->
