jQuery(document).ready(function($) {
    // The existing "Unlocked Content Indicator" checkbox
    var unlockedCheckbox = $('input[name="paybutton_scroll_to_unlocked"]');
    // The <tbody> that has our color pickers
    var colorFields = $('#unlockedIndicatorColors');

    function toggleColorFields() {
        if (unlockedCheckbox.is(':checked')) {
            colorFields.show();
        } else {
            colorFields.hide();
        }
    }

    // On page load
    toggleColorFields();
    // On checkbox change
    unlockedCheckbox.on('change', toggleColorFields);

    //NEW: Toggle the “Unlock Count Color” row visibility
    var enableUnlockCountCheckbox   = $('#paybutton_enable_frontend_unlock_count');
    var frontendUnlockColorRow      = $('#paybutton_frontend_unlock_color_row');

    function toggleFrontendUnlockColorRow() {
    if ( enableUnlockCountCheckbox.is(':checked') ) {
        frontendUnlockColorRow.show();
    } else {
        frontendUnlockColorRow.hide();
    }
    }

    // On page load
    toggleFrontendUnlockColorRow();
    // On checkbox change
    enableUnlockCountCheckbox.on('change', toggleFrontendUnlockColorRow);
});