jQuery(document).ready(function($) {

    var display_event_list_form = function($trigger) {
        var left              = 0;
        var BackgroundColor   = '#fff';
        var $wait_list_parent = $trigger.closest('form');
        // console_log('wait list form', $wait_list_parent.attr('id'), false);
        if ($wait_list_parent.length) {
            BackgroundColor = eeGetParentBackgroundColor($wait_list_parent);
            BackgroundColor = eeRgbToHex(BackgroundColor);
            $wait_list_parent.css({'background': BackgroundColor});
            left = $wait_list_parent.innerWidth();
        }
        // console_log('wait list form inputs', $wait_list_form.attr('id'), false);
        var $wait_list_form = $('#' + $trigger.data('inputs'));

        if (parseFloat(left)) {
            left = (left - $wait_list_form.outerWidth()) / 2;
        } else {
            left = 0;
        }
        $wait_list_form.css({'background': BackgroundColor, 'left': left + 'px'}).show();
    };

    //  display form when Join button is clicked
    $('.ee-join-wait-list-btn').on('click', function(e) {
        // console_log('wait list button', $(this).attr('id'), true);
        e.preventDefault();
        e.stopPropagation();
        display_event_list_form($(this));
    });

    //  close form when cancel link is clicked
    $('.ee-wait-list-cancel-lnk').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var wait_list_form_id = '#' + $(this).data('inputs');
        // console_log('wait_list_form_id', wait_list_form_id, true);
        $(wait_list_form_id).hide();
    });

    //  display form if opened from iframe
    $('.ee-display-wait-list-form').each(function() {
        if ($(this).val() === '1') {
            display_event_list_form($(this));
        }
    });

    //  check form before submitting
    $('.wait-list-ticket-selection').on('change', function(e) {
        valid_ticket_selection($(this));
    });

    //  check form before submitting
    $('input.ee-submit-wait-list-btn').on('click', function(event) {
        var $inputs = $(this).parents('form:first').find(':input');
        $inputs.each(function() {
            valid_ticket_selection($(this), event);
        });
        // once submitted, disable the submit button
        $(this).parents('form:first').one('submit', function() {
            $('input.ee-submit-wait-list-btn').prop('disabled', true);
        });
    });

    var valid_ticket_selection = function($selector, event) {
        var id  = $selector.attr('id');
        if (id.indexOf('hidden-inputs-ticket') !== -1) {
            if ($.isNumeric($selector.val()) === false){
                var $error = $selector.parents('form:first').find('.invalid-wait-list-ticket-selection-error');
                $error.show();
                $selector.removeClass('valid').addClass('error');
                event.preventDefault();
                event.stopPropagation();
            } else {
                $(this).removeClass('error').addClass('valid');
                $('.invalid-wait-list-ticket-selection-error').hide();
            }
        }

    }
});
