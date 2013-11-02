jQuery(document).ready(function() {
	var update = function() {
		jQuery('.context').hide()
			.filter('.context-' + this.value.replace(/\//g,"_"))
			.show();
	};
	
	jQuery('#context')
		.bind('change', update)
		.bind('keyup', update).change();
	
	jQuery(document).bind('submit', function() {
		var expression = /^fields\[parameters\]\[[0-9]+\]\[(.*)]$/;
		
		// Cleanup old contexts:
		jQuery('.context:not(:visible)').remove();
		
		// Set filter names:
		jQuery('.parameter-duplicator.context > .content > .instances > li').each(function(index) {
			var instance = jQuery(this);
			
			instance.find('[name]').each(function() {
				var input = jQuery(this);
				var name = input.attr('name');
				var match = null;
				
				// Extract name:
				if (match = name.match(expression)) name = match[1];
				
				input.attr(
					'name',
					'fields[parameters]['
					+ index
					+ ']['
					+ name
					+ ']'
				);
			});
		});
	});
})