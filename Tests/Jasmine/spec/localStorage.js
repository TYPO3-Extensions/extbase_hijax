function using(name, values, func){
	for (var i = 0, count = values.length; i < count; i++) {
		if (Object.prototype.toString.call(values[i]) !== '[object Array]') {
			values[i] = [values[i]];
		}
		func.apply(this, values[i]);
		jasmine.currentEnv_.currentSpec.description += ' (with "' + name + '" using ' + JSON.stringify(values[i]) + ')';
	}
}

describe("LocalStorage", function() {
	using("valid values", ['123', {x: 1, y: '2'}, 456, true], function(value){
		var key = 'sample';
		var valueRead = null;

		it("test setter / getter", function() {
			runs(function() {
				$.hStorage.set(key, value);
				$.hStorage.get(key, null, function(val) {
					valueRead = val;
				});
			});

			waitsFor(function() {
				return valueRead != null;
			}, "The value read from local storage should not be null", 3000);

			runs(function() {
				if (typeof valueRead !== 'string') {
					valueReadTest = JSON.stringify(valueRead);
				} else {
					valueReadTest = valueRead;
				}
				if (typeof value !== 'string') {
					valueTest = JSON.stringify(value);
				} else {
					valueTest = value;
				}
				expect(valueReadTest).toBe(valueTest);
			});
		});
	});
});