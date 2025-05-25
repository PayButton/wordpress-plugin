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
});