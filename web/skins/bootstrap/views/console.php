<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

xhtmlHeaders( __FILE__, $SLANG['Console'] );
?>
<body>
		<?php include("header.php"); ?>

    <form name="monitorForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?= $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <div id="content" class="container-fluid">
			<div class="row">
				<div class="col-md-2"><?php include("sidebar.php"); ?></div>

				<div class="col-md-10">

	<div ng-controller="ConsoleController">
		<div class="row">
			<div class="col-md-4" ng-repeat="monitor in monitors">
				<div class="panel" ng-class="(monitor.alerts.zmc || monitor.alerts.zma) ? 'panel-default' : 'panel-danger'">
					<div class="panel-heading">
						<a ng-hide="(monitor.alerts.zmc || monitor.alerts.zma)" class="pull-right" href="#" tooltip="{{ monitor.alert }}"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></a>
						<h2 class="text-left panel-title">{{ monitor.Name }} <small>({{monitor.Id}})</small></h2>
					</div>

					<div class="panel-body center-block">
						<img class="img-responsive img-rounded" ng-src="/cgi-bin/nph-zms?mode=single&monitor={{monitor.Id}}&scale=50" />
					</div>
				</div>
			</div>
		</div>

	</div>



			</div> <!-- End .col-md-10 -->


				</div> <!-- End .row -->
			</div> <!-- End #content .container-fluid -->
    </form>
		<?php include("footer.php"); ?>
</body>
</html>
