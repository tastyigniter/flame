// List Filter State Toggle
// Uses user cookie value to show/hide list filter bar
$(function () {
    var $listFilterButton = $('[data-toggle="list-filter"]')

    $listFilterButton.on('click', function () {
        var $button = $(this),
            $listFilterTarget = $button.closest('form').parent().prev('.list-filter'),
            listFilterStoreName = $listFilterTarget.data('storeName'),
            $dropdownButton = $listFilterTarget.find('[data-bs-toggle="dropdown"]')

        if (!listFilterStoreName || !listFilterStoreName.length)
            return

        $button.toggleClass('active')
        $dropdownButton.attr('data-bs-offset', "-50,"+Math.abs($button.closest('thead').offset().top-$('#toolbar').height()-$('.navbar-top').height()))
        $dropdownButton.click()
        Cookies.set(listFilterStoreName, $listFilterTarget.is(':visible') ? 1 : 0)
    })
})

$(function ($) {
    // List setup form sortables
    $('#lists-setup-modal-content').on('ajaxUpdate', function() {
        Sortable.create($('#lists-setup-sortable').get(0), {
            handle: '.form-check-handle',
        })
    })
})

// Bulk actions
$(function ($) {
    var checkedSelector = '.list-table input[name*=checked]:checked',
        $bulkActionsContainer = $('[data-control="bulk-actions"]'),
        $selectAllRecordsButton = $('[data-control="check-total-records"]')

    if (!$bulkActionsContainer.length)
        return;

    $(document).on('change', '.list-table input[name*=checked]', function (event) {
        onChangeListCheckboxes($(this))
    })

    $(document).on('change', '.list-table input[id^="checkboxAll-"]', function (event) {
        $('input[id^="checkboxAll-"]').prop('checked', this.checked)
        $selectAllRecordsButton.toggleClass('hide', !(this.checked && parseInt($bulkActionsContainer.data('actionTotalRecords')) > $(checkedSelector).length))
        onChangeListCheckboxes($(this))
    })

    $selectAllRecordsButton.on('click', function(event) {
        var $el = $(event.currentTarget)
        $el.toggleClass('active')
        $('[data-action-select-all]').prop('disabled', !$el.hasClass('active'))
        $('[data-action-counter]').html(
            $el.hasClass('active') ? $bulkActionsContainer.data('actionTotalRecords') : $(checkedSelector).length
        )
    })

    $(checkedSelector).trigger('change')

    function onChangeListCheckboxes($el) {
        var counter = $(checkedSelector).length
        if ($el.is(':checked')) {
            $bulkActionsContainer.removeClass('hide')
        }

        if (counter < 1) {
            $bulkActionsContainer.addClass('hide')
        }

        $('[data-action-counter]').html(counter)
        $('[data-action-select-all]').prop('disabled', true)
    }
})
