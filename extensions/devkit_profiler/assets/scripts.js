var FORMAT_EXPRESSION = /%([a-zA-Z][a-zA-Z_-]*)(\$(.*?[bcdeEufFgGosxX]))?/g;

var Utilities = {
	uuid: function() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random() * 16 | 0,
				v = c == 'x' ? r : (r&0x3|0x8);

			return v.toString(16);
		});
	},

	formatDescription: function(description, data) {
		return description.replace(FORMAT_EXPRESSION, function(match, name, all, format) {
			if (typeof data[name] !== undefined) {
				if (typeof format !== 'undefined' && format !== '') {
					return sprintf('%' + format, data[name][1]);
				}

				else {
					return data[name][1];
				}
			}

			return null;
		});
	}
}

var TextFilter = function(text, negate) {
	var self = this;

	self.key = Utilities.uuid();
	self.text = text;
	self.negate = negate;

	self.appendLabel = function($parent) {
		var format = self.negate
			? 'Not containing: %s'
			: 'Containing: %s';

		$('<a />')
			.addClass('filter')
			.appendTo($parent)
			.data('filter', self)
			.text(sprintf(format, self.text));
	};

	self.apply = function(result) {
		var show = result.description.toLowerCase()
			.indexOf(self.text.toLowerCase()) !== -1;

		return negate ? !show : show;
	};
};

var TypeFilter = function(type, negate) {
	var self = this;

	self.key = Utilities.uuid();
	self.type = type;
	self.negate = negate;

	self.appendLabel = function($parent) {
		var format = self.negate
			? 'Not of type %s'
			: 'Of type %s';

		$('<a />')
			.addClass('filter')
			.appendTo($parent)
			.data('filter', self)
			.text(sprintf(format, self.type));
	};

	self.apply = function(result) {
		var show = false;

		for (var name in result.data) {
			if (result.data[name][0].indexOf(self.type) !== -1) {
				show = true;

				break;
			}
		}

		return negate ? !show : show;
	};
};

var TimeFilter = function(seconds, negate) {
	var self = this;

	self.key = Utilities.uuid();
	self.seconds = seconds;
	self.negate = negate;

	self.appendLabel = function($parent) {
		var format = self.negate
			? 'Less than %s seconds'
			: 'More than %s seconds';

		$('<a />')
			.addClass('filter')
			.appendTo($parent)
			.data('filter', self)
			.text(sprintf(format, self.seconds));
	};

	self.apply = function(result) {
		var show = false;

		if (
			typeof result.data['time-begin'] !== 'undefined'
			&& typeof result.data['time-end'] !== 'undefined'
		) {
			var diff = result.data['time-end'][1]
				- result.data['time-begin'][1];

			show = diff >= self.seconds;
		}

		return negate ? !show : show;
	};
};

var Preset = function(name) {
	var self = this;

	self.name = name;
	self.key = Utilities.uuid();
	self.filters = {};

	for (var index in arguments) {
		if (index == 0) continue;

		var filter = arguments[index];

		self.filters[filter.key] = filter;
	}

	self.appendLabel = function($parent) {
		$('<a />')
			.addClass('filter')
			.appendTo($parent)
			.data('filter', self)
			.text(self.name);
	};

	self.apply = function(result) {
		var tests = [];

		for (var key in self.filters) {
			if (self.filters[key].apply(result)) {
				tests.push(true);
			}

			else {
				tests.push(false);
			}
		};

		return eval(tests.join(' && '));
	};
};

var Profiler = {
	filters: {},
	$filters: null,
	presets: {},
	$presets: null,
	$root: null,
	items: [],

	append: function($parent) {
		Profiler.$presets = $('<nav />')
			.addClass('presets')
			.appendTo($parent);
		Profiler.$filters = $('<nav />')
			.addClass('filters')
			.appendTo($parent);
		Profiler.$root = $('<div />')
			.addClass('results')
			.appendTo($parent);
	},

	addFilter: function(filter) {
		Profiler.removeFilter(filter);
		Profiler.filters[filter.key] = filter;

		return true;
	},

	addPreset: function(preset) {
		Profiler.removePreset(preset);
		Profiler.presets[preset.key] = preset;

		return true;
	},

	removeFilter: function(filter) {
		if (typeof Profiler.filters[filter.key] === 'undefined') return false;

		delete Profiler.filters[filter.key];

		return true;
	},

	removePreset: function(preset) {
		if (typeof Profiler.filters[preset.key] === 'undefined') return false;

		delete Profiler.filters[preset.key];

		return true;
	},

	prepare: function(results) {
		var prepare = function(results, depth, total) {
			for (var index in results) {
				var result = results[index],
					classes = ['item'],
					itemNode = document.createElement('li');

				result.node = itemNode;
				result.depth = depth;
				result.hasData = false;
				result.hasResults = typeof result.results !== 'undefined'
					&& result.results.length > 0;

				if (typeof total === 'undefined') {
					total = result;
				}

				if (result.hasResults) {
					prepare(result.results, depth + 1, total);
				}

				// Calculate additional values:
				for (var name in result.data) {
					var data = result.data[name],
						types = data[0],
						value = data[1];

					if (name === 'memory-end') {
						result.data['memory-growth'] = [
							['system/memory', 'data/number', 'data/bytes'],
							value - result.data['memory-begin'][1]
						];
						result.data['memory-percent'] = [
							['system/memory', 'data/number', 'data/percentage'],
							result.data['memory-growth'][1] /
							(total.data['memory-end'][1] - total.data['memory-begin'][1]) * 100
						];
					}

					if (name === 'time-end') {
						result.data['time-growth'] = [
							['system/time', 'data/number', 'data/microseconds'],
							value - result.data['time-begin'][1]
						];
						result.data['time-percent'] = [
							['system/time', 'data/number', 'data/percentage'],
							result.data['time-growth'][1] /
							(total.data['time-end'][1] - total.data['time-begin'][1]) * 100
						];
					}

					if (
						types.indexOf('system/memory') === -1
						&& types.indexOf('system/time') === -1
					) {
						result.hasData = true;
					}
				}

				// Any item that took 20% or more is 'poor':
				if (result.data['memory-percent'][1] >= 20) {
					classes.push('terrible');
				}

				// Any item that took 20% or more is 'poor':
				else if (result.data['time-percent'][1] >= 20) {
					classes.push('terrible');
				}

				// Any item that took 2% or more is 'slow':
				else if (result.data['memory-percent'][1] >= 2) {
					classes.push('worrying');
				}

				// Any item that took 2% or more is 'slow':
				else if (result.data['time-percent'][1] >= 2) {
					classes.push('worrying');
				}

				// Mark any parents that are not slow runners as hidden:
				if (result.hasResults || result.hasData) {
					classes.push('parent');

					// If the item time taken was insignificant or took less than 20% of the total page load, collapse it:
					if (
						result.data['time-growth'][1] < 0.01
						|| result.data['time-percent'][1] < 20
					) {
						classes.push('hidden');
					}
				}

				itemNode.setAttribute('class', classes.join(' '));

				// Description:
				var descriptionNode = document.createElement('p'),
					linkNode = document.createElement('a');

				descriptionNode.setAttribute('class', 'description');
				descriptionNode.appendChild(linkNode);
				linkNode.textContent = Utilities.formatDescription(result.description, result.data);
				itemNode.appendChild(descriptionNode);

				if (result.hasResults) {
					var valueNode = document.createElement('span');

					valueNode.setAttribute('class', 'more-info');
					valueNode.textContent = sprintf(' (%d more)', result.results.length);
					linkNode.appendChild(valueNode);
				}

				// Data:
				var dataNode = document.createElement('dl');

				dataNode.setAttribute('class', 'data');
				itemNode.appendChild(dataNode);

				for (var name in result.data) {
					var data = result.data[name],
						types = data[0],
						value = data[1],
						classes = (name + ' ' + types.join(' '))
							.replace(/\//g, '_');

					// Title:
					var titleNode = document.createElement('dt');

					titleNode.setAttribute('class', classes);
					titleNode.textContent = name;
					dataNode.appendChild(titleNode);

					// Value:
					if (types.indexOf('system/memory') !== -1) {
						var format = '%.3f MB';

						if (types.indexOf('data/percentage') !== -1) {
							format = '%.1f %%';
						}

						else if (types.indexOf('data/bytes') !== -1) {
							value = value / 1024 / 1024;
						}

						var valueNode = document.createElement('dd');

						valueNode.setAttribute('class', classes);
						valueNode.textContent = sprintf(format, value);
						dataNode.appendChild(valueNode);
					}

					else if (types.indexOf('system/time') !== -1) {
						var format = '%.5f s';

						if (types.indexOf('data/percentage') !== -1) {
							format = '%.1f %%';
						}

						else if (value >= total.data['time-begin'][1]) {
							value -= total.data['time-begin'][1];
						}

						var valueNode = document.createElement('dd');

						valueNode.setAttribute('class', classes);
						valueNode.textContent = sprintf(format, value);
						dataNode.appendChild(valueNode);
					}

					else {
						var valueNode = document.createElement('dd');

						valueNode.setAttribute('class', classes);
						valueNode.textContent = value;
						dataNode.appendChild(valueNode);
					}

					var typesNode = document.createElement('dd');

					typesNode.setAttribute('class', classes);
					typesNode.textContent = 'Filter by';
					dataNode.appendChild(typesNode);

					for (var index in types) {
						var linkNode = document.createElement('a');

						linkNode.setAttribute('data-type', types[index]);
						linkNode.textContent = types[index];
						typesNode.appendChild(linkNode);
					}
				}
			}
		};

		Profiler.items = results;

		prepare(results, 0);
	},

	render: function() {
		var found = Profiler.items;
		var running = 0;

		// Filter searching utility:
		var search = function(filter, results, depth) {
			for (var index in results) {
				var result = results[index];

				if (filter.apply(result, depth)) {
					found.push(result);
				}

				if (result.hasResults) {
					search(filter, result.results, depth + 1);
				}
			}
		};

		// Rendering utility:
		var render = function(parentNode, results, depth) {
			setTimeout(function() {
				var listNode = document.createElement('ol');

				listNode.setAttribute('class', 'tree');
				parentNode.appendChild(listNode);

				for (var index in results) {
					var result = results[index],
						itemNode = result.node.cloneNode(true);

					listNode.appendChild(itemNode);

					if (depth === 0) {
						itemNode.setAttribute('class', itemNode.getAttribute('class') + ' root');
					}

					if (result.hasResults) {
						render(itemNode, result.results, depth + 1);
					}
				}
			}, 0);
		};

		// Apply filters:
		for (var key in Profiler.filters) {
			var results = found;

			found = [];

			search(Profiler.filters[key], results, 0);
		}

		// Clear last state:
		Profiler.$presets.empty();
		Profiler.$filters.empty();
		Profiler.$root.empty();

		for (var key in Profiler.presets) {
			var preset = Profiler.presets[key];

			preset.appendLabel(Profiler.$presets);
		}

		for (var key in Profiler.filters) {
			var filter = Profiler.filters[key];

			filter.appendLabel(Profiler.$filters);
		}

		// Begin rendering:
		render(Profiler.$root.get(0), found, 0);
	}
};

// Renderer:
$(document).ready(function() {
	Profiler.append($('body'));

	Profiler.addPreset(new Preset(
		'Slow datasources',
		new TypeFilter('system/datasource'),
		new TimeFilter('0.01')
	));

	Profiler.addPreset(new Preset(
		'Slow database queries',
		new TypeFilter('system/database-query'),
		new TimeFilter('0.01')
	));

	Profiler.addPreset(new Preset(
		'Extensions loaded',
		new TypeFilter('action/loaded'),
		new TypeFilter('system/extension')
	));

	Profiler.addPreset(new Preset(
		'Fields loaded',
		new TypeFilter('action/loaded'),
		new TypeFilter('system/field')
	));

	Profiler.addPreset(new Preset(
		'Sections loaded',
		new TypeFilter('action/loaded'),
		new TypeFilter('system/section')
	));

	Profiler.addPreset(new Preset(
		'Datasources executed',
		new TypeFilter('action/executed'),
		new TypeFilter('system/datasource')
	));

	Profiler.addPreset(new Preset(
		'Delegates executed',
		new TypeFilter('action/executed'),
		new TypeFilter('system/delegate')
	));

	if (location.hash) {
		eval(location.hash.substring(1));
	}

	Profiler.render();

	$('div.results')
		// Show/hide children:
		.on('click', 'li.item.parent > p.description a', function() {
			var $item = $(this).closest('li');

			$item.toggleClass('hidden');
		})

		.on('mousedown', 'li.item.parent > p.description a', function() {
			return false;
		})

		// Add a type filter:
		.on('click', 'a[data-type]', function() {
			Profiler.addFilter(
				new TypeFilter($(this).attr('data-type'))
			);
			Profiler.render();
		});

	$('nav.filters')
		.on('mousedown', 'a', function() {
			return false;
		})

		// Remove a filter:
		.on('click', 'a.filter', function() {
			Profiler.removeFilter(
				$(this).data('filter')
			);
			Profiler.render();
		});

	$('nav.presets')
		.on('mousedown', 'a', function() {
			return false;
		})

		// Add a filter:
		.on('click', 'a.filter', function() {
			Profiler.addFilter(
				$(this).data('filter')
			);
			Profiler.render();
		});
});