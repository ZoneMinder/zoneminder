		<div ng-show="monitor.Type == 'Remote'">
			<div class="form-group">
				<label for="newMonitor[Protocol]"><?= $SLANG['RemoteProtocol'] ?></label>
				<select class="form-control" ng-model="monitor.Protocol" id="newMonitor[Protocol]" ng-required="monitor.Type == 'Remote'">
					<option value="http">HTTP</option>
					<option value="rtsp">RTSP</option>
				</select>
			</div>
			<div class="form-group">
				<label for="newMonitor[Method]"><?= $SLANG['RemoteMethod'] ?></label>
				<select class="form-control" ng-model="monitor.Method" id="newMonitor[Method]" ng-required="monitor.Type == 'Remote'" ng-show="monitor.Protocol == 'http'">
					<option value="simple">Simple</option>
					<option value="regexp">Regexp</option>
				</select>
				<select class="form-control" ng-model="monitor.Method" id="newMonitor[Method]" ng-required="monitor.Type == 'Remote'" ng-show="monitor.Protocol == 'rtsp'">
					<option value="rtpUni">RTP/Unicast</option>
					<option value="rtpMulti">RTP/Multicast</option>
					<option value="rtpRtsp">RTP/RTSP</option>
					<option value="rtpRtspHttp">RTP/RTSP/HTTP</option>
				</select>
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostName'] ?></label>
				<input type="text" class="form-control" ng-model="monitor.Host" placeholder="HostName or ip.add.re.ss" ng-required="monitor.Type == 'Remote'" />
			</div>
			<div class="form-group">
				<label for=""><?= $SLANG['RemoteHostPort'] ?></label>
				<input type="text" class="form-control" ng-model="monitor.Port" placeholder="Usually 80 for http, 554 for rtsp" ng-required="monitor.Type == 'Remote'" />
			</div>
			<div class="form-group">
				<label><?= $SLANG['RemoteHostPath'] ?></label>
				<input type="text" class="form-control" ng-model="monitor.Path" placeholder="/path/to/stream.mpg" ng-required="monitor.Type == 'Remote'" />
			</div>
	</div> <!-- End Remote -->
