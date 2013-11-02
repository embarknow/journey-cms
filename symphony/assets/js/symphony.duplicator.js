/*-----------------------------------------------------------------------------
	Duplicator plugin
-----------------------------------------------------------------------------*/

	jQuery(document).ready(function() {
		var $ = jQuery;
		var select = {
			instances:			'> .content > .instances > *',
			instance_parent:	'> .content > .instances',
			tabs:				'> .content > .tabs > *',
			tab_parent:			'> .content > .tabs',
			templates:			'> .templates > *',
			template_parent:	'> .templates',
			controls_add:		'> .controls > .add',
			controls_parent:	'> .controls'
		};
		var block = function() {
			return false;
		};

	/*-------------------------------------------------------------------------
		Tabs
	-------------------------------------------------------------------------*/

		$(document)
			// Initialize:
			.on('tab-initialize', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var object = tab.closest('.duplicator-widget');
				var index = tab.prevAll().length;
				var name = tab.find('.name');
				var instance = object.find(select.instances + ':eq(' + index + ')');
				var name = tab.find('.name');

				// Store data:
				tab.data('instance', instance);
				tab.data('name', name);

				tab.addClass('orderable-item orderable-handle')
					.trigger('tab-refresh');

				object.addClass('content-visible');

				// Tab contains a name:
				if (instance.find('input[name=name]').length) {
					var type = name.find('em');
					var input = instance.find('input[name=name]');
					var rename = function() {
						name.text(input.val());
						tab.trigger('duplicator-tab-refresh');
						name.append(type);
					};

					if (type.length == 0) {
						type = jQuery('<em />')
							.text(name.text())
							.appendTo(name);
					}

					input
						.bind('change', rename)
						.bind('keyup', rename);

					rename();
				}
			})

			// Refresh:
			.on('tab-refresh', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var index = tab.prevAll().length;
				var name = tab.data('name');

				if (!name.text()) {
					name.text(Symphony.Language.get('Untitled'));
				}

				tab.data('index', index);
			})

			// Remove:
			.on('tab-remove', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var object = tab.closest('.duplicator-widget');
				var instance = tab.data('instance');

				tab.filter('.ordering').trigger('orderable-stop');

				if (tab.siblings().length == 0) {
					object.removeClass('content-visible');
				}

				tab.remove(); instance.remove();
			})

			// Reorder:
			.on('tab-reorder', '.duplicator-widget' + select.tabs, function() {
				var tab = jQuery(this);
				var object = tab.closest('.duplicator-widget');
				var new_index = tab.prevAll().length;
				var old_index = tab.data('index');

				// Nothing to do:
				if (new_index == old_index) return;

				var items = object.find(select.instances);
				var parent = items.parent();
				var places = [];

				items.not(items[old_index]).each(function(index) {
					if (index == new_index) {
						places.push(null);
					}

					places.push(this);
				});

				places[new_index] = items[old_index];

				parent.empty().append(places);
			})

			// Select/deselect:
			.on('tab-select', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var object = tab.closest('.duplicator-widget');
				var instance = tab.data('instance');

				tab.addClass('active');
				instance.addClass('active');
				object.addClass('content-visible');
			})
			.on('tab-deselect', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var instance = tab.data('instance');

				tab.removeClass('active');
				instance.removeClass('active');
			})

			// Reorder actions:
			.on('orderable-started', '.duplicator-widget' + select.tabs, function() {
				$(this)
					.trigger('tab-select')
					.siblings('.active')
					.trigger('tab-deselect');
			})
			.on('orderable-ordered', '.duplicator-widget' + select.tabs, function() {
				var tab = $(this);
				var object = tab.closest('.duplicator-widget');

				tab.trigger('tab-reorder');
				object.find(select.tabs)
					.trigger('tab-refresh');
			})

			// Click actions:
			.on('click', '.duplicator-widget' + select.tabs, function(event) {
				var tab = $(this);
				var target = $(event.target);

				// Remove:
				if (target.is('.remove')) {
					// Select another tab first:
					if (tab.is('.active') || !tab.siblings('.active').length > 0) {
						if (tab.next().length) tab.next().trigger('tab-select');
						else if (tab.prev().length) tab.prev().trigger('tab-select');
					}

					tab.trigger('tab-remove');
				}

				// Select:
				else {
					if (event.shiftKey == true) {
						if (tab.is('.active') && tab.siblings('.active').length > 0) {
							tab.trigger('tab-deselect');
						}

						else {
							tab.trigger('tab-select');
						}
					}

					// Deselect everything else:
					else {
						tab
							.trigger('tab-select')
							.siblings('.active')
							.trigger('tab-deselect');
					}
				}
			})

			// Ignore mouse clicks:
			.on('mousedown', '.duplicator-widget' + select.tabs, block)

			// Initialize:
			.find('.duplicator-widget' + select.tabs)
			.trigger('tab-initialize');

	/*-------------------------------------------------------------------------
		Templates
	-------------------------------------------------------------------------*/

		// Toggle template pallet:
		$(document)
			.on('click', '.duplicator-widget' + select.controls_add, function() {
				var button = $(this);
				var object = button.closest('.duplicator-widget');
				var pallet = object.find(select.template_parent);

				if (pallet.is(':visible')) {
					object.removeClass('templates-visible');
				}

				else {
					object.addClass('templates-visible');
				}
			})

			// Ignore mouse clicks:
			.on('mousedown', '.duplicator-widget' + select.controls_add, block);

		$(document)
			// Insert template:
			.on('template-insert', '.duplicator-widget' + select.templates, function() {
				var template = $(this);
				var object = template.closest('.duplicator-widget');
				var instance = $('<li />')
					.append(template.find('> :not(.name)').clone(true))
					.appendTo(object.find(select.instance_parent));
				var tab = $('<li />')
					.append(
						$('<span />')
							.addClass('name')
							.html(template.find('> .name').html())
					)
					.append(
						$('<a />')
							.addClass('remove')
							.text('×')
					)
					.appendTo(object.find(select.tab_parent))
					.trigger('tab-initialize')
					.trigger('tab-select')
					.siblings('.active')
					.trigger('tab-deselect');
			})

			// Click actions:
			.on('click', '.duplicator-widget' + select.templates, function() {
				$(this)
					.trigger('template-insert');
			})

			// Ignore mouse clicks:
			.on('mousedown', '.duplicator-widget' + select.templates, block);

		// Remove templates on form submit:
		$(document)
			.bind('submit', function() {
				$('.duplicator-widget' + select.templates).remove();
			});

	/*-------------------------------------------------------------------------
		Initialise
	-------------------------------------------------------------------------*/

		$('.duplicator-widget').each(function() {
			var object = $(this);

			// Show templates if there are no instances:
			if (object.find(select.instances).length == 0) {
				//object.find(select.controls_add).trigger('click');
			}
		});
	});

/*---------------------------------------------------------------------------*/