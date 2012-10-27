/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
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

ExtbaseHijax = {};
ExtbaseHijax.CObjectViewIdCounter = 0;
ExtbaseHijax.CObjectView = Ember.View.extend({
	typoScriptObjectPath: '',
	loaders: '',
	defaultTemplate: Ember.Handlebars.compile('<div class="hijax-element" id="ember-cobject-' + (ExtbaseHijax.CObjectViewIdCounter++) + '" {{bindAttr data-hijax-loaders="loaders"}} {{bindAttr data-hijax-ajax-tssource="typoScriptObjectPath"}} data-hijax-result-wrap="false" data-hijax-result-target="jQuery(this)" data-hijax-element-type="ajax"><div class="hijax-content"><p>&nbsp;</p></div><div class="hijax-loading"></div></div>'),
	init: function() {
		this.set('context', Ember.Object.create({
			typoScriptObjectPath: '',
			loaders: ''
		}));
	this._super();
	},
	didInsertElement: function () {
		// console.log('CObjectView ' + this.typoScriptObjectPath + ' didInsertElement');
		this.get('context').set('typoScriptObjectPath', this.typoScriptObjectPath);
		this.get('context').set('loaders', this.loaders);
		Ember.run.schedule('render', this, function() {
			this.$().find('.hijax-element').extbaseHijax(true, true);
		});
	},
	willDestroyElement: function () {
		// console.log('CObjectView ' + this.typoScriptObjectPath + ' willDestroyElement');
	},
	afterRender: function(buffer) {
		// console.log('CObjectView ' + this.typoScriptObjectPath + ' afterRender');
		this._super(buffer);
	}
});