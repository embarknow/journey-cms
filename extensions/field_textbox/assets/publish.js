/*-----------------------------------------------------------------------------
	Text Box Interface
-----------------------------------------------------------------------------*/

	jQuery(document).ready(function() {
		jQuery('.field-textbox').each(function() {
			var self = jQuery(this),
				input = self.find('input, textarea');

			if (input.attr('maxlength') < 1) return;

			var label = self.find('em.maxlength'),
				message = label.text();

			var update = function() {
				var length = input.val().length;
				var limit = input.attr('maxlength');
				var remaining = limit - length;

				console.log('-xxxx-', message, message.replace('$1', remaining).replace('$2', limit));

				label
					.text(message.replace('$1', remaining).replace('$2', limit))
					.removeClass('invalid');

				if (remaining < 0) {
					label.addClass('invalid');
				}
			};

			input.bind('blur', update);
			input.bind('change', update);
			input.bind('focus', update);
			input.bind('keypress', update);
			input.bind('keyup', update);

			update();
		});
	});

/*---------------------------------------------------------------------------*/