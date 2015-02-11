<div class="container-fluid">
	<div class="container" ng-controller="ConfigController">
		<div class="row">
			<div class="col-md-2">
				<ul class="nav nav-pills nav-stacked" role="tablist" id="myTab">
					<li role="presentation" ng-repeat="category in categories" ng-class="{ 'active': (category.Config.Category == 'system') }">
						<a href="#{{category.Config.Category}}" aria-controls="{{category.Config.Category}}" role="tab" data-toggle="tab">{{category.Config.Category}}</a>
					</li>
				</ul>
			</div>
		
			<div class="col-md-10">
				<div class="tab-content">
					<div role="tabpanel" class="form-horizontal tab-pane" ng-class="{ 'active': (category.Config.Category == 'system') }" id="{{category.Config.Category}}" ng-repeat="category in categories" angular-html-bind="{{ category.Config.Category }}"></div>
				</div>
			</div>
		</div> <!-- End .row -->
	</div> <!-- End ConfigController -->
</div>
