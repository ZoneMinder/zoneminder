<?php xhtmlHeaders(__FILE__, $SLANG['Options'] ); ?>
<body>

<?php include("header.php"); ?>


<div class="container-fluid">
	<div class="container" ng-controller="ConfigController">
		<ul class="nav nav-tabs" role="tablist" id="myTab">
			<li role="presentation" ng-repeat="category in categories">
				<a href="#{{category.Config.Category}}" aria-controls="{{category.Config.Category}}" role="tab" data-toggle="tab">{{category.Config.Category}}</a>
			</li>
		</ul>
		
		<div class="tab-content">
			<div role="tabpanel" class="form-horizontal tab-pane" id="{{category.Config.Category}}" ng-repeat="category in categories" angular-html-bind="{{ category.Config.Category }}"></div>
		</div>
	</div> <!-- End ConfigController -->
</div>

<?php include("footer.php"); ?>

</body>
</html>
