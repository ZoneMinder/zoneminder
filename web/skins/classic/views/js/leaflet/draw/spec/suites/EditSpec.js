describe("L.Edit", function () {
	var map;

	beforeEach(function () {
		map = new L.Map(document.createElement('div')).setView([0, 0], 15);
	});

	describe("L.Edit.Marker", function () {
		var marker;

		beforeEach(function () {
			marker = new L.Marker(new L.LatLng(1, 2)).addTo(map);
			marker.editing.enable();
		});

		it("Has the leaflet-edit-marker-selected class applied when enabled.", function () {
			var editingClass = 'leaflet-edit-marker-selected';

			expect(marker.editing.enabled()).to.equal(true);
			expect(L.DomUtil.hasClass(marker._icon, editingClass)).to.equal(true);
		});

		it("Lacks the leaflet-edit-marker-selected class when disabled.", function () {
			var editingClass = 'leaflet-edit-marker-selected';

			marker.editing.disable();

			expect(marker.editing.enabled()).to.equal(false);
			expect(L.DomUtil.hasClass(marker._icon, editingClass)).to.equal(false);
		});
	});

  describe("L.Edit.CircleMarker", function () {
		var circleMarker;

		beforeEach(function () {
			circleMarker = new L.CircleMarker(new L.LatLng(1, 2)).addTo(map);
			circleMarker.editing.enable();
		});

		it("Is activated correctly when editing.enable() is called.", function () {});

		it("Moves the circlemarker to the correct latlng", function () {
			var newLatLng = new L.LatLng(3, 5);

			circleMarker.editing._move(newLatLng);
			expect(circleMarker.getLatLng()).to.eql(newLatLng);
		});
	});

	describe("L.Edit.Circle", function () {
		var circle;

		beforeEach(function () {
			circle = new L.Circle(new L.LatLng(1, 2), 5).addTo(map);
			circle.editing.enable();
		});

		it("Is activated correctly when editing.enable() is called.", function () {});

		it("Moves the circle to the correct latlng", function () {
			var newLatLng = new L.LatLng(3, 5);

			circle.editing._move(newLatLng);
			expect(circle.getLatLng()).to.eql(newLatLng);
		});
	});

	describe("L.Edit.Poly", function () {
		var edit,
			drawnItems,
			poly;

		beforeEach(function () {
			drawnItems = new L.FeatureGroup().addTo(map);
			edit = new L.EditToolbar.Edit(map, {
				featureGroup: drawnItems,
				poly: {
					allowIntersection : false
				},
				selectedPathOptions: L.EditToolbar.prototype.options.edit.selectedPathOptions
			});
			poly = new L.Polyline(L.latLng(41, -87), L.latLng(42, -88));
		});

		it("Should change the style of the polyline during editing mode.", function () {
			var originalOptions = L.extend({}, poly.options);

			drawnItems.addLayer(poly);
			edit.enable();

			expect(poly.editing.enabled()).to.equal(true);
			expect(poly.options).not.to.eql(originalOptions);
		});

		it("Should revert to original styles when editing is toggled.", function () {
			var originalOptions = L.extend({maintainColor: false, poly : {allowIntersection: false} }, poly.options);

			drawnItems.addLayer(poly);
			edit.enable();
			edit.disable();

			expect(poly.options).to.eql(originalOptions);
		});

		it("Should set allowIntersection to be false when setting is set", function () {

			drawnItems.addLayer(poly);
			edit.enable();

			expect(poly.editing.enabled()).to.equal(true);
			expect(poly.options.poly.allowIntersection).to.equal(false);

		});

	});

	describe("L.EditToolbar.Delete", function () {
		var drawnItems,marker,circle,poly,deleteToollbar;

		beforeEach(function () {
			drawnItems = new L.FeatureGroup().addTo(map);
			deleteToollbar = new L.EditToolbar.Delete(map, {
				featureGroup: drawnItems
			});
			marker = new L.Marker(new L.LatLng(1, 2));
			circle = new L.Circle(new L.LatLng(1, 2), 5);
			poly = new L.Polyline(L.latLng(41, -87), L.latLng(42, -88));
			drawnItems.addLayer(marker).addLayer(circle).addLayer(poly);
		});

		it("The drawlayer should has 3 features on it.", function () {
			expect(drawnItems.getLayers().length).to.eql(3);
		});

		it("After clearing the drawlayer it should have no features.", function () {
			deleteToollbar.enable();
			deleteToollbar.removeAllLayers();
			deleteToollbar.disable();
			expect(drawnItems.getLayers().length).to.eql(0);
		});

		it("The map should fire the events for clearing.", function () {
			var events = [];
			map.on(L.Draw.Event.DELETESTART,function (event) {
					events.push(event.type);
	    })
	    map.on(L.Draw.Event.DELETED,function (event) {
	        events.push(event.type);
	    })
			map.on(L.Draw.Event.DELETESTOP,function (event) {
	        events.push(event.type);
	    })
			deleteToollbar.enable();
			deleteToollbar.removeAllLayers();
			deleteToollbar.disable();
			expect(events[0]).to.eql(L.Draw.Event.DELETESTART);
			expect(events[1]).to.eql(L.Draw.Event.DELETED);
			expect(events[2]).to.eql(L.Draw.Event.DELETESTOP);
		});

	});
});
