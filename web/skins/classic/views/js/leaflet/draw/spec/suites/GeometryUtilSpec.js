describe("L.GeometryUtil", function () {
	it("geodesicArea", function () {
		expect(L.GeometryUtil.geodesicArea([
			{ lat: 0,  lng: 0 },
			{ lat: 0,  lng: 10 },
			{ lat: 10, lng: 10 },
			{ lat: 10, lng: 0 },
			{ lat: 0,  lng: 0 }
		])).to.eql(1232921098571.292);
	});

	describe("readableDistance", function () {
		describe("metric", function () {
			it("returns meters or kilometers", function() {
				expect(L.GeometryUtil.readableDistance(1000, true)).to.eql('1000 m');
				expect(L.GeometryUtil.readableDistance(1500, true)).to.eql('1.50 km');
			});

			it("is used when 'metric' is specified", function() {
				expect(L.GeometryUtil.readableDistance(1500, 'metric')).to.eql('1.50 km');
			});

			it("is used even when other flags are set", function() {
				expect(L.GeometryUtil.readableDistance(1500, true, true, true)).to.eql('1.50 km');
			});

			it("switches from meters to kilometers on more than 1000 meters", function() {
				expect(L.GeometryUtil.readableDistance(999, true)).to.eql('999 m');
				expect(L.GeometryUtil.readableDistance(1000, true)).to.eql('1000 m');
				expect(L.GeometryUtil.readableDistance(1001, true)).to.eql('1.00 km');
				expect(L.GeometryUtil.readableDistance(1002, true)).to.eql('1.00 km');
			});

			it("uses the precision specified", function () {
				var precision = {
					km: 0,
					m: 2
				};

				expect(L.GeometryUtil.readableDistance(1000, true, false, false, precision)).to.eql('1000.00 m');
				expect(L.GeometryUtil.readableDistance(100.123, true, false, false, precision)).to.eql('100.12 m');
				expect(L.GeometryUtil.readableDistance(100.456, true, false, false, precision)).to.eql('100.46 m');
				expect(L.GeometryUtil.readableDistance(1001, true, false, false, precision)).to.eql('1 km');
				expect(L.GeometryUtil.readableDistance(1500, true, false, false, precision)).to.eql('2 km');
				expect(L.GeometryUtil.readableDistance(2000, true, false, false, precision)).to.eql('2 km');
			});
		});

		describe("imperial", function () {
			it("returns yards or miles", function() {
				expect(L.GeometryUtil.readableDistance(1609.3488537961)).to.eql('1760 yd');
				expect(L.GeometryUtil.readableDistance(1610.3488537961)).to.eql('1.00 miles');
			});

			it("is used when 'yards' is specified", function() {
				expect(L.GeometryUtil.readableDistance(1610.3488537961, 'yards')).to.eql('1.00 miles');
			});

			it("switches from yards to miles on more than 1760 yards", function() {
				expect(L.GeometryUtil.readableDistance(1608.3488537961)).to.eql('1759 yd');
				expect(L.GeometryUtil.readableDistance(1609.3488537961)).to.eql('1760 yd');
				expect(L.GeometryUtil.readableDistance(1610.3488537961)).to.eql('1.00 miles');
				expect(L.GeometryUtil.readableDistance(1611.3488537961)).to.eql('1.00 miles');
			});

			it("uses the precision specified", function () {
				var precision = {
					mi: 0,
					yd: 2
				};

				expect(L.GeometryUtil.readableDistance(1609.3488537961, false, false, false, precision)).to.eql('1760.00 yd');
				expect(L.GeometryUtil.readableDistance(1609.2488537961, false, false, false, precision)).to.eql('1759.89 yd');
				expect(L.GeometryUtil.readableDistance(1610.3488537961, false, false, false, precision)).to.eql('1 miles');
				expect(L.GeometryUtil.readableDistance(2415.3488537961, false, false, false, precision)).to.eql('2 miles');
				expect(L.GeometryUtil.readableDistance(3218.3488537961, false, false, false, precision)).to.eql('2 miles');
			});
		});

		describe("imperial feet", function () {
			it("always returns feet", function() {
				expect(L.GeometryUtil.readableDistance(1609.3488537961, false, true, false)).to.eql('5280 ft');
				expect(L.GeometryUtil.readableDistance(1610.3488537961, false, true, false)).to.eql('5283 ft');
			});

			it("is used when 'feet' is specified", function() {
				expect(L.GeometryUtil.readableDistance(1610.3488537961, 'feet')).to.eql('5283 ft');
			});

			it("uses the precision specified", function () {
				var precision = {
					ft: 2
				};

				expect(L.GeometryUtil.readableDistance(1609.3488537961, false, true, false, precision)).to.eql('5280.00 ft');
				expect(L.GeometryUtil.readableDistance(1609.4488537961, false, true, false, precision)).to.eql('5280.33 ft');
			});
		});

		describe("nautical", function () {
			it("always returns nautical miles", function() {
				expect(L.GeometryUtil.readableDistance(1609.3488537961, false, false, true)).to.eql('0.87 nm');
				expect(L.GeometryUtil.readableDistance(1610.3488537961, false, false, true)).to.eql('0.87 nm');
			});

			it("is used when 'nauticalMile' is specified", function() {
				expect(L.GeometryUtil.readableDistance(1610.3488537961, 'nauticalMile')).to.eql('0.87 nm');
			});

			it("uses the precision specified", function () {
				var precision = {
					nm: 3
				};

				expect(L.GeometryUtil.readableDistance(1609.3488537961, false, false, true, precision)).to.eql('0.869 nm');
				expect(L.GeometryUtil.readableDistance(1610.3488537961, false, false, true, precision)).to.eql('0.870 nm');
			});
		});
	});

	describe("formatted number", function () {
		it("accepts a thousands seperator", function () {
			L.drawLocal.format = {
				numeric: {
					delimiters: {
						thousands: '#',
					}
				}
			};
			expect(L.GeometryUtil.formattedNumber(100)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000)).to.eql('1#000');
			expect(L.GeometryUtil.formattedNumber(1000000)).to.eql('1#000#000');
			expect(L.GeometryUtil.formattedNumber(100, 0)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000, 0)).to.eql('1#000');
			expect(L.GeometryUtil.formattedNumber(1000000, 0)).to.eql('1#000#000');
			expect(L.GeometryUtil.formattedNumber(100, 2)).to.eql('100.00');
			expect(L.GeometryUtil.formattedNumber(1000, 2)).to.eql('1#000.00');
			expect(L.GeometryUtil.formattedNumber(1000000, 2)).to.eql('1#000#000.00');
		});

		it("accepts a decimal seperator", function () {
			L.drawLocal.format = {
				numeric: {
					delimiters: {
						decimal: '$'
					}
				}
			};
			expect(L.GeometryUtil.formattedNumber(100)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000)).to.eql('1000');
			expect(L.GeometryUtil.formattedNumber(1000000)).to.eql('1000000');
			expect(L.GeometryUtil.formattedNumber(100, 0)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000, 0)).to.eql('1000');
			expect(L.GeometryUtil.formattedNumber(1000000, 0)).to.eql('1000000');
			expect(L.GeometryUtil.formattedNumber(100, 2)).to.eql('100$00');
			expect(L.GeometryUtil.formattedNumber(1000, 2)).to.eql('1000$00');
			expect(L.GeometryUtil.formattedNumber(1000000, 2)).to.eql('1000000$00');
		});

		it("accepts a thousands and a decimal seperator", function () {
			L.drawLocal.format = {
				numeric: {
					delimiters: {
						thousands: '#',
						decimal: '$'
					}
				}
			};
			expect(L.GeometryUtil.formattedNumber(100)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000)).to.eql('1#000');
			expect(L.GeometryUtil.formattedNumber(1000000)).to.eql('1#000#000');
			expect(L.GeometryUtil.formattedNumber(100, 0)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000, 0)).to.eql('1#000');
			expect(L.GeometryUtil.formattedNumber(1000000, 0)).to.eql('1#000#000');
			expect(L.GeometryUtil.formattedNumber(100, 2)).to.eql('100$00');
			expect(L.GeometryUtil.formattedNumber(1000, 2)).to.eql('1#000$00');
			expect(L.GeometryUtil.formattedNumber(1000000, 2)).to.eql('1#000#000$00');
		});

		it("defaults to no thousands and decimal dot", function () {
			delete L.drawLocal.format;
			expect(L.GeometryUtil.formattedNumber(100)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000)).to.eql('1000');
			expect(L.GeometryUtil.formattedNumber(1000000)).to.eql('1000000');
			expect(L.GeometryUtil.formattedNumber(100, 0)).to.eql('100');
			expect(L.GeometryUtil.formattedNumber(1000, 0)).to.eql('1000');
			expect(L.GeometryUtil.formattedNumber(1000000, 0)).to.eql('1000000');
			expect(L.GeometryUtil.formattedNumber(100, 2)).to.eql('100.00');
			expect(L.GeometryUtil.formattedNumber(1000, 2)).to.eql('1000.00');
			expect(L.GeometryUtil.formattedNumber(1000000, 2)).to.eql('1000000.00');
		});

		it("is used for readableDistance and readableArea", function () {
			L.drawLocal.format = {
				numeric: {
					delimiters: {
						thousands: '.',
						decimal: ','
					}
				}
			};
			expect(L.GeometryUtil.readableDistance(1000, true)).to.eql('1.000 m');
			expect(L.GeometryUtil.readableArea(50000, true)).to.eql('5,00 ha');
		});
	});
});
