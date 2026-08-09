(function ($) {
    'use strict';

    function initModulesPage() {
        var $fullForm = $('#builder-modules-form-full');
        var $singleForm = $('#builder-modules-form-single');
        if ($fullForm.length === 0 && $singleForm.length === 0) {
            return;
        }

        var $existingModulePreset = $('#builder-full-existing-module-preset');
        var $fullModuleNameField = $('#full_module_name');
        var $fullModuleKeyField = $('#full_module_key');

        $existingModulePreset.off('change.builderModules').on('change.builderModules', function () {
            var $selectedOption = $(this).find('option:selected');
            var selectedKey = String($selectedOption.data('moduleKey') || '');
            var selectedName = String($selectedOption.data('moduleName') || '');

            if (selectedKey !== '') {
                $fullModuleKeyField.val(selectedKey);
            }

            if (selectedName !== '') {
                $fullModuleNameField.val(selectedName);
            }
        });
    }

    $(document).on('rex:ready', initModulesPage);
})(jQuery);
