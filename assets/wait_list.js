jQuery(document).ready(function ($) {

    $('.ee-join-wait-list-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var left = 0;
        var BackgroundColor = '#fff';
        var $wait_list_parent = $('#event_wait_list-frm').parent();
        if ($wait_list_parent.length){
            BackgroundColor = getParentBackgroundColor($wait_list_parent);
            BackgroundColor = rgb2hex(BackgroundColor);
            $wait_list_parent.css({'background': BackgroundColor});
            left = $wait_list_parent.innerWidth();
        }
        var $wait_list_form = $('#' + $(this).data('inputs'));
        if (parseFloat(left)) {
            left = (left - $wait_list_form.outerWidth()) / 2;
        } else {
            left = 0;
        }
        $wait_list_form.css({'background': BackgroundColor, 'left': left + 'px'}).show();
    });

    $('.ee-wait-list-cancel-lnk').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var wait_list_form_id = '#' + $(this).data('inputs');
        $(wait_list_form_id).hide();
    });



});