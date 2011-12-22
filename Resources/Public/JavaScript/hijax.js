; (function($) {
	var elements = [], startedTimer = false, timerInterval = 10000, currentTimerTime = 0, uniqueIDCounter = 0;
	
	/*
	 * Private methods 
	 */

	_addElements = function(newElements) {
			// add unique element ID if it already doesn't have it
		$.each(newElements, function(i, element) {
			var id = $(element).attr('id');
			if (!id) {
				$(element).attr('id', 'hijax-'+(uniqueIDCounter++));
			}
		});

			// filter out duplicates and join the new elements to the existing array
		$.each(newElements, function(i, newElement) {
			var newId = $(newElement).attr('id');
			var notDuplicate = true;
			$.each(elements, function(i, element) {
				var id = $(element).attr('id');
				var loader = $(element).find('> .'+EXTBASE_HIJAX.loadingClass);
				var content = $(element).find('> .'+EXTBASE_HIJAX.contentClass);
				$(element).css('height', content.outerHeight());
				if (id==newId) {
					notDuplicate = false;
					return false;
				}
			});
			if (notDuplicate) {
				elements.push(newElement);
			}
		});
	};
	
	_timer = function () {
		currentTimerTime += timerInterval/1000;
		processElements = [];
		$.each(elements, function(i, element) {
			var elementTiming = parseInt($(element).attr('data-hijax-timing'), 10);
			if (currentTimerTime % elementTiming == 0) {
				processElements.push(element);
			}
		});
		_process(processElements);
	};
	
	_process = function (elements) {
		if (elements && elements.length > 0) {
			var requests = [];
			$.each(elements, function(i, element) {
				var el = {
					id: $(element).attr('id'),
					extension: $(element).attr('data-hijax-extension'),
					plugin: $(element).attr('data-hijax-plugin'),
					controller: $(element).attr('data-hijax-controller'),
					action: $(element).attr('data-hijax-action'),
					arguments: $(element).attr('data-hijax-arguments'),
					settingsHash: $(element).attr('data-hijax-settings')
				};
				var loader = $(element).find('> .'+EXTBASE_HIJAX.loadingClass);
				if (!loader.data('targetOpacity')) {
					loader.data('targetOpacity', loader.css('opacity'));
					loader.css('opacity', 0);
				}
				loader.show();
				loader.stop().animate({
					opacity: loader.data('targetOpacity')
				}, 500, function() {
						// Animation complete.
				});
				requests.push(el);
			});
			
			var ajaxRequest = $.ajax({
				url: EXTBASE_HIJAX.url,
				type: "POST",
				//crossDomain: true,
				data: {r: requests},
				dataType: "jsonp",
				success: function(data) { 
					$.each(data, function(i, r) {
						var element = $('#'+r['id']);
						if (element) {
							var loader = element.find('> .'+EXTBASE_HIJAX.loadingClass);
							var content = element.find('> .'+EXTBASE_HIJAX.contentClass);
							
							element.removeClass(EXTBASE_HIJAX.fallbackClass);

							content.html(r['response']).css('height', content.height());
							
							if (!loader.data('targetOpacity')) {
								loader.data('targetOpacity', loader.css('opacity'));
							}
							loader.stop().animate({
								opacity: 0
							}, 500, function() {
									// Animation complete.
								loader.hide();
							});
							
							element.stop().animate({
								height: content.outerHeight()
							}, 500, function() {
									// Animation complete.
							});
						}
					});
				}
			});			
		}
	};
	
	/*
	 * Public methods 
	 */

	$.fn.extbaseHijax = function(options) {
		if (!$(this).length) {
			return this;
		}
		
		_addElements(this);
		
		$(this)
			.data('extbaseHijax', $.extend({}, options, ($.metadata ? $(this).metadata() : {})));

		return this;
	};

	$.extbaseHijax = function(options) {
	};
	
	$.extbaseHijax.start = function() {
		if (!startedTimer) {
			startedTimer = true;
			window.setInterval(_timer, timerInterval);
			_process(elements);
		}
	};

})(jQuery);

jQuery(document).ready(function(){
//	jQuery('[data-hijax-extension]:nth-child(even)').extbaseHijax();
//	jQuery('[data-hijax-extension]:nth-child(odd)').extbaseHijax();
	jQuery('.hijax-element').extbaseHijax();
	jQuery.extbaseHijax.start();
});
