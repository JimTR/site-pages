jQuery(function($) {

    var html_element = document.getElementById("page_html");

    if (html_element) {
        var html_editor = CodeMirror.fromTextArea(html_element, {
            lineNumbers: true,
            lineWrapping: true,
            foldGutter: true,
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            viewportMargin: Infinity,
            indentWithTabs: true,
            indentUnit: 4,
            mode: "text/html",
            matchBrackets: true,
            matchTags: {bothTags: true},
            autoCloseTags: true,
            theme: "mybb"
        });

        html_editor.setSize("100%", 320);
    }

    var php_element = document.getElementById("page_php");

    if (php_element) {
        var php_editor = CodeMirror.fromTextArea(php_element, {
            lineNumbers: true,
            lineWrapping: true,
            foldGutter: true,
            gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
            viewportMargin: Infinity,
            indentWithTabs: true,
            indentUnit: 4,
            mode: "application/x-httpd-php",
            matchBrackets: true,
            theme: "mybb"
        });

        php_editor.setSize("100%", 320);
    }

});

function toggleGroupDropdown() {
    var options = document.getElementById('visible_options');

    if (options.style.display === 'block') {
        options.style.display = 'none';
    } else {
        options.style.display = 'block';
    }
}

function home_page_layout(page_id)
{
    $.ajax({
        url: 'index.php?module=pages/info&action=layout',
        type: 'POST',
        data: {
            id: page_id,
          my_post_key: jQuery('input[name=\"my_post_key\"]').val()
        },
        success: function(response) {
            // Display returned modal HTML
           // console.log(response);
            jQuery('#page_layout_modal .modal-content').html(response);
			jQuery('#page_layout_modal').show();
        },
        error: function(xhr) {
            alert('Unable to load page layout.');
        }
    });
}

function home_close_layout_modal() {

	jQuery('#page_layout_modal').hide();

	// Remove open_layout from the main page form's return URL
	var return_url = jQuery(
		'form[action*="action=editpage"] input[name="return_url"]'
	);

	if (return_url.length) {
		var params = new URLSearchParams(return_url.val());

		params.delete('open_layout');

		return_url.val(params.toString());
	}

	// Also clean up the browser address bar
	var url = new URL(window.location.href);

	url.searchParams.delete('open_layout');

	window.history.replaceState(
		{},
		document.title,
		url.pathname + url.search + url.hash
	);
}

jQuery(document).on('change', 'input[name="zone"]', function() {
   home_refresh_zone();
jQuery("#selected-zone").show();
});


jQuery(document).on('change', '#available_blocks', function() {

    var zone = jQuery('input[name="zone"]:checked').val();

    if (zone === undefined) {
        alert('Please select a zone first.');
        return;
    }

    var zone_name = '';

    switch (zone) {
        case '0': zone_name = 'Top'; break;
        case '1': zone_name = 'Left'; break;
        case '2': zone_name = 'Right'; break;
        case '3': zone_name = 'Bottom'; break;
    }

    jQuery('#available_blocks option:selected').each(function() {

        var block_id = jQuery(this).val();
        var title = jQuery(this).text();

        // Check for an existing assignment in this zone
        var exists = jQuery('#current_blocks option').filter(function() {
            return jQuery(this).val() === block_id &&
                   jQuery(this).attr('data-zone') === zone;
        }).length > 0;

        if (exists) {
            return;
        }

        // Add to selected zone
        jQuery('#zone_blocks').append(
            jQuery('<option>', {
                value: block_id,
                text: title
            })
        );

        // Add to overall page layout
        jQuery('#current_blocks').append(
            jQuery('<option>', {
                value: block_id,
                text: title + ' (' + zone_name + ')'
            })
            .attr('data-zone', zone)
            .attr('data-title', title)
        );

    });

    // Keep overall layout grouped by zone
    home_sort_layout();
    jQuery('#zone_blocks option[value=""]').remove();
     jQuery('#available_blocks option:selected').prop('selected', false);

});


jQuery(document).on('change', 'input[name="zone"]', function() {

    var zone = jQuery(this).val();

    jQuery('#zone_blocks').empty();

    jQuery('#current_blocks option').each(function() {

        if (jQuery(this).attr('data-zone') === zone) {

            jQuery('#zone_blocks').append(
                jQuery('<option>', {
                    value: jQuery(this).val(),
                    text: jQuery(this).attr('data-title')
                })
            );
			
        }

    });
if (jQuery('#zone_blocks option').length === 0) {
				jQuery('#zone_blocks').append(
				jQuery('<option>', {
				value: '',
				text: 'No Blocks defined',
				disabled: false,
				selected: false
				})
			);
}
});


function home_sort_layout() {

    var select = jQuery('#current_blocks');
    var options = select.find('option').get();

    options.sort(function(a, b) {

        var zone_a = parseInt(jQuery(a).attr('data-zone'), 10);
        var zone_b = parseInt(jQuery(b).attr('data-zone'), 10);

        return zone_a - zone_b;
    });

    jQuery.each(options, function(index, option) {
        select.append(option);
    });
}

jQuery(document).on('submit', '#layout_form', function() {

    // Remove any previously generated zone inputs
    jQuery('#layout_form input[name="zones[]"]').remove();

    jQuery('#current_blocks option').each(function() {

        var zone = jQuery(this).attr('data-zone');

        jQuery('#layout_form').append(
            jQuery('<input>', {
                type: 'hidden',
                name: 'zones[]',
                value: zone
            })
        );

    });

    // Ensure all block IDs are submitted
    jQuery('#current_blocks option').prop('selected', true);

});

function home_refresh_zone() {

    var zone = jQuery('input[name="zone"]:checked').val();

    if (zone === undefined) {
        return;
    }

    var zone_blocks = jQuery('#zone_blocks');

    zone_blocks.empty();

    jQuery('#current_blocks option').each(function() {

        if (jQuery(this).attr('data-zone') === zone) {

            zone_blocks.append(
                jQuery('<option>', {
                    value: jQuery(this).val(),
                    text: jQuery(this).attr('data-title')
                })
            );

        }

    });

    if (zone_blocks.find('option').length === 0) {
        zone_blocks.append(
            jQuery('<option>', {
                value: '',
                text: 'No Blocks defined',
                disabled: false,
                selected: false
            })
        );
    }

    home_update_zone_controls();
}

function home_update_zone_controls() {

    var zone_blocks = jQuery('#zone_blocks');
    var selected = zone_blocks.prop('selectedIndex');
    var count = zone_blocks.find('option:not(:disabled)').length;

    jQuery('#move-up').prop('disabled', count === 0 || selected <= 0);
    jQuery('#move-down').prop('disabled', count === 0 || selected >= count - 1);
    jQuery('#remove-block').prop('disabled', count === 0 || selected < 0);
}

jQuery(document).on('change', '#zone_blocks', function() {

    home_update_zone_controls();

});

function home_move_block(direction) {

    var selected = jQuery('#zone_blocks option:selected');

    if (selected.length === 0 || selected.is(':disabled')) {
        return;
    }

    var block_id = selected.val();
    var zone = jQuery('input[name="zone"]:checked').val();

    var current = jQuery('#current_blocks option').filter(function() {
        return jQuery(this).val() === block_id &&
               jQuery(this).attr('data-zone') === zone;
    });

    if (direction === 'up') {
        current.prevAll('[data-zone="' + zone + '"]').first().before(current);
    } else {
        current.nextAll('[data-zone="' + zone + '"]').first().after(current);
    }

    home_refresh_zone();

    jQuery('#zone_blocks option').filter(function() {
        return jQuery(this).val() === block_id;
    }).prop('selected', true);

    home_update_zone_controls();
}

jQuery(document).on('click', '#move-up', function() {
    home_move_block('up');
});

jQuery(document).on('click', '#move-down', function() {
    home_move_block('down');
});

jQuery(document).on('click', '#remove-block', function() {

    var selected = jQuery('#zone_blocks option:selected');

    if (selected.length === 0 || selected.is(':disabled')) {
        return;
    }

    var block_id = selected.val();
    var zone = jQuery('input[name="zone"]:checked').val();

    var current = jQuery('#current_blocks option').filter(function() {
        return jQuery(this).val() === block_id &&
               jQuery(this).attr('data-zone') === zone;
    });

    current.remove();

    home_refresh_zone();

});