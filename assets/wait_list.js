jQuery(document).ready(function ($) {

    $('.ee-join-wait-list-btn').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var wait_list_form_id = '#' + $(this).data('inputs');
        $(wait_list_form_id).show();
    });

    $('.ee-wait-list-cancel-lnk').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var wait_list_form_id = '#' + $(this).data('inputs');
        $(wait_list_form_id).hide();
    });

});