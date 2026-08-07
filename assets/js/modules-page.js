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

        function bindSelectButtons(selectAllId, deselectAllId, checkboxSelector) {
            $(selectAllId).off('click.builderModules').on('click.builderModules', function () {
                $(checkboxSelector).prop('checked', true);
            });

            $(deselectAllId).off('click.builderModules').on('click.builderModules', function () {
                $(checkboxSelector).prop('checked', false);
            });
        }

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

        bindSelectButtons('#builder-elements-select-all-full', '#builder-elements-deselect-all-full', '.builder-element-checkbox-full');
        bindSelectButtons('#builder-elements-select-all-single', '#builder-elements-deselect-all-single', '.builder-element-checkbox-single');
    }

    $(document).on('rex:ready', initModulesPage);
})(jQuery);
