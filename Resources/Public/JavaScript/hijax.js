/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

; (function($) {
	var elements = [], startedTimer = false, timerInterval = 10000, currentTimerTime = 0, uniqueIDCounter = 0, ajaxCallback = false;
	
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
		var addedElements = [];
		
			// filter out duplicates and join the new elements to the existing array
		$.each(newElements, function(i, newElement) {
			var newId = $(newElement).attr('id');
			var notDuplicate = true;
			$.each(elements, function(i, element) {
				var id = $(element).attr('id');
				if (id==newId) {
					notDuplicate = false;
					return false;
				}
			});
			if (notDuplicate) {
				//var content = $(newElement).find('> .'+EXTBASE_HIJAX.contentClass);
				//$(newElement).css('height', content.outerHeight());
				elements.push(newElement);
				addedElements.push(newElement);
			}
		});
		
		return addedElements;
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
				var el = $(element);
				switch (el.attr('data-hijax-element-type')) {
					case 'conditional':
						try {
							var val = eval(el.attr('data-hijax-condition'));
							var thenTarget = el.find('> .hijax-content');
							var elseTarget = el.find('> .hijax-content-else');
							
							if (!val) {
								elseTarget.css('display', 'block').css('visibility', 'visible');
								var targetHeight = elseTarget.outerHeight();
								var startingHeight = thenTarget.outerHeight();
								thenTarget.css('display', 'none'); // TODO: remove the element?
								
								if (!ajaxCallback) {
									elseTarget.stop().css('height', startingHeight).animate({
										height: targetHeight
									}, 100, function() {
											// Animation complete.
										$(this).css('height', 'auto');
									});
								}
								
							} else {
								thenTarget.css('display', 'block').css('visibility', 'visible');
								elseTarget.css('display', 'none'); // TODO: remove the element?
							}
						} catch (err) {
							el.css('display', 'none'); // TODO: remove the element?
						}
						
						break;
					case 'form':
						el.bind('submit', function(e) {
							e.preventDefault(); // <-- important
							var requests = [];
							var el = {
									id: $(this).attr('id'),
									extension: $(this).attr('data-hijax-extension'),
									plugin: $(this).attr('data-hijax-plugin'),
									controller: $(this).attr('data-hijax-controller'),
									action: $(this).attr('data-hijax-action'),
									arguments: $(this).attr('data-hijax-arguments'),
									settingsHash: $(this).attr('data-hijax-settings')
								};
							
							$(element).showHijaxLoader();
							
							requests.push(el);
							var fields = $(this).formToArray();
							var pluginNameSpace = $(this).attr('data-hijax-namespace');
							$.each(fields, function(i, f) {
								fields[i]['name'] = fields[i]['name'].replace(pluginNameSpace, 'r[0][arguments]');
							});
							$data = $.param({r: requests})+'&'+$.param(fields);
							
							var ajaxRequest = $.ajax({
								url: EXTBASE_HIJAX.url,
								type: "POST",
								//crossDomain: true,
								data: $data,
								dataType: "jsonp",
								success: function(data) { 
									$.each(data, function(i, r) {
										var element = $('#'+r['id']);
										if (element) {
											element.loadHijaxData(r['response']);
										}
									});
								}
							});	
						});
						break;
					case 'ajax':
							// ajax request
						var el = {
							id: $(element).attr('id'),
							extension: $(element).attr('data-hijax-extension'),
							plugin: $(element).attr('data-hijax-plugin'),
							controller: $(element).attr('data-hijax-controller'),
							action: $(element).attr('data-hijax-action'),
							arguments: $(element).attr('data-hijax-arguments'),
							settingsHash: $(element).attr('data-hijax-settings')
						};
						$(element).showHijaxLoader();
						
						requests.push(el);
						break;
					default: 
						break;
				}
			});
			
			if (requests.length>0) {
				$data = {r: requests};
				
				var ajaxRequest = $.ajax({
					url: EXTBASE_HIJAX.url,
					type: "POST",
					//crossDomain: true,
					data: $data,
					dataType: "jsonp",
					success: function(data) { 
						$.each(data, function(i, r) {
							var element = $('#'+r['id']);
							if (element) {
								element.loadHijaxData(r['response']);
							}
						});
					}
				});	
			}
		}
	};
	
	/*
	 * Public methods 
	 */

	$.fn.loadHijaxData = function(response) {
		ajaxCallback = true;
		
		var element = $(this);
		var loader = element.find('> .'+EXTBASE_HIJAX.loadingClass);
		var content = element.find('> .'+EXTBASE_HIJAX.contentClass);
		
		if (element.attr('data-hijax-result-target')) {
			content = eval(element.attr('data-hijax-result-target'));

			if (content) {
				response = '<div class="hijax-element"><div class="'+EXTBASE_HIJAX.contentClass+'">'+response+'</div><div class="'+EXTBASE_HIJAX.loadingClass+'"></div></div>';
				
				var startingHeight = content.height();
				element = content.outer(response).css('height', startingHeight);
				content = element.find('> .'+EXTBASE_HIJAX.contentClass);
				loader = element.find('> .'+EXTBASE_HIJAX.loadingClass).show();
				
				if (!loader.data('targetOpacity')) {
					loader.data('targetOpacity', loader.css('opacity'));
				}
				//debugger;
				loader.stop().animate({
					opacity: 0
				}, 500, function() {
						// Animation complete.
					loader.hide();
				});
				var newElements = element.find('.hijax-element');
				if (jQuery(element[0]).hasClass('hijax-element')) {
					newElements.push(jQuery(element[0]));
				}
				newElements.extbaseHijax(true);
				
				element.stop().animate({
					height: content.outerHeight()
				}, 500, function() {
						// Animation complete.
				});
			}
		} else {
			element.removeClass(EXTBASE_HIJAX.fallbackClass);

			content.html(response).css('height', content.height());

			if (!loader.data('targetOpacity')) {
				loader.data('targetOpacity', loader.css('opacity'));
			}
			loader.stop().animate({
				opacity: 0
			}, 500, function() {
					// Animation complete.
				loader.hide();
			});
			var newElements = element.find('.hijax-element');
			if (element.hasClass('hijax-element')) {
				newElements.push(element);
			}
			newElements.extbaseHijax(true);

			element.stop().animate({
				height: content.outerHeight()
			}, 500, function() {
					// Animation complete.
			});
		}
		
		ajaxCallback = false;
		
		return this;
	};	
	
	$.fn.showHijaxLoader = function() {
		var content = $(this).find('> .'+EXTBASE_HIJAX.contentClass);
		$(this).css('height', content.outerHeight());

		var loader = $(this).find('> .'+EXTBASE_HIJAX.loadingClass);
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
		
		return this;
	};		
	
	$.fn.outer = function(val){
		if (val) {
			content = $(val);
			content.insertBefore(this);
			$(this).remove();
			return content;
		} else {
			return $("<div>").append($(this).clone()).html(); 
		}
	};
	
	$.fn.extbaseHijax = function(process) {
		if (!$(this).length) {
			return this;
		}
		
		var addedElements = _addElements(this);
		
		if (process) {
			_process(addedElements);
		}
		
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

	/**
	 * formToArray() gathers form element data into an array of objects that can
	 * be passed to any of the following ajax functions: $.get, $.post, or load.
	 * Each object in the array has both a 'name' and 'value' property.  An example of
	 * an array for a simple login form might be:
	 *
	 * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
	 *
	 * It is this array that is passed to pre-submit callback functions provided to the
	 * ajaxSubmit() and ajaxForm() methods.
	 */
	$.fn.formToArray = function(semantic) {
		var a = [];
		if (this.length === 0) {
			return a;
		}

		var form = this[0];
		var els = semantic ? form.getElementsByTagName('*') : form.elements;
		if (!els) {
			return a;
		}

		var i,j,n,v,el,max,jmax;
		for(i=0, max=els.length; i < max; i++) {
			el = els[i];
			n = el.name;
			if (!n) {
				continue;
			}

			if (semantic && form.clk && el.type == "image") {
				// handle image inputs on the fly when semantic == true
				if(!el.disabled && form.clk == el) {
					a.push({name: n, value: $(el).val(), type: el.type });
					a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
				}
				continue;
			}

			v = $.fieldValue(el, true);
			if (v && v.constructor == Array) {
				for(j=0, jmax=v.length; j < jmax; j++) {
					a.push({name: n, value: v[j]});
				}
			}
			else if (v !== null && typeof v != 'undefined') {
				a.push({name: n, value: v, type: el.type});
			}
		}

		if (!semantic && form.clk) {
			// input type=='image' are not found in elements array! handle it here
			var $input = $(form.clk), input = $input[0];
			n = input.name;
			if (n && !input.disabled && input.type == 'image') {
				a.push({name: n, value: $input.val()});
				a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
			}
		}
		return a;
	};

	/**
	 * Serializes form data into a 'submittable' string. This method will return a string
	 * in the format: name1=value1&amp;name2=value2
	 */
	$.fn.formSerialize = function(semantic) {
		//hand off to jQuery.param for proper encoding
		return $.param(this.formToArray(semantic));
	};

	/**
	 * Serializes all field elements in the jQuery object into a query string.
	 * This method will return a string in the format: name1=value1&amp;name2=value2
	 */
	$.fn.fieldSerialize = function(successful) {
		var a = [];
		this.each(function() {
			var n = this.name;
			if (!n) {
				return;
			}
			var v = $.fieldValue(this, successful);
			if (v && v.constructor == Array) {
				for (var i=0,max=v.length; i < max; i++) {
					a.push({name: n, value: v[i]});
				}
			}
			else if (v !== null && typeof v != 'undefined') {
				a.push({name: this.name, value: v});
			}
		});
		//hand off to jQuery.param for proper encoding
		return $.param(a);
	};

	/**
	 * Returns the value(s) of the element in the matched set.  For example, consider the following form:
	 *
	 *  <form><fieldset>
	 *	  <input name="A" type="text" />
	 *	  <input name="A" type="text" />
	 *	  <input name="B" type="checkbox" value="B1" />
	 *	  <input name="B" type="checkbox" value="B2"/>
	 *	  <input name="C" type="radio" value="C1" />
	 *	  <input name="C" type="radio" value="C2" />
	 *  </fieldset></form>
	 *
	 *  var v = $(':text').fieldValue();
	 *  // if no values are entered into the text inputs
	 *  v == ['','']
	 *  // if values entered into the text inputs are 'foo' and 'bar'
	 *  v == ['foo','bar']
	 *
	 *  var v = $(':checkbox').fieldValue();
	 *  // if neither checkbox is checked
	 *  v === undefined
	 *  // if both checkboxes are checked
	 *  v == ['B1', 'B2']
	 *
	 *  var v = $(':radio').fieldValue();
	 *  // if neither radio is checked
	 *  v === undefined
	 *  // if first radio is checked
	 *  v == ['C1']
	 *
	 * The successful argument controls whether or not the field element must be 'successful'
	 * (per http://www.w3.org/TR/html4/interact/forms.html#successful-controls).
	 * The default value of the successful argument is true.  If this value is false the value(s)
	 * for each element is returned.
	 *
	 * Note: This method *always* returns an array.  If no valid value can be determined the
	 *	array will be empty, otherwise it will contain one or more values.
	 */
	$.fn.fieldValue = function(successful) {
		for (var val=[], i=0, max=this.length; i < max; i++) {
			var el = this[i];
			var v = $.fieldValue(el, successful);
			if (v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length)) {
				continue;
			}
			v.constructor == Array ? $.merge(val, v) : val.push(v);
		}
		return val;
	};

	/**
	 * Returns the value of the field element.
	 */
	$.fieldValue = function(el, successful) {
		var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
		if (successful === undefined) {
			successful = true;
		}

		if (successful && (!n || el.disabled || t == 'reset' || t == 'button' ||
			(t == 'checkbox' || t == 'radio') && !el.checked ||
			(t == 'submit' || t == 'image') && el.form && el.form.clk != el ||
			tag == 'select' && el.selectedIndex == -1)) {
				return null;
		}

		if (tag == 'select') {
			var index = el.selectedIndex;
			if (index < 0) {
				return null;
			}
			var a = [], ops = el.options;
			var one = (t == 'select-one');
			var max = (one ? index+1 : ops.length);
			for(var i=(one ? index : 0); i < max; i++) {
				var op = ops[i];
				if (op.selected) {
					var v = op.value;
					if (!v) { // extra pain for IE...
						v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
					}
					if (one) {
						return v;
					}
					a.push(v);
				}
			}
			return a;
		}
		return $(el).val();
	};	
	
})(jQuery);

jQuery(document).ready(function(){
	jQuery('.hijax-element').extbaseHijax();
	jQuery.extbaseHijax.start();
});
