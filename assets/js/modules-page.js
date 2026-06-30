(function ($) {
    'use strict';

    function initModulesPage() {
        var $form = $('#builder-modules-form');
        if ($form.length === 0) {
            return;
        }

        var $modeField = $('#module_mode');
        var $fullBuilderFields = $('#full-builder-fields');
        var $fullBuilderExistingModule = $('#full-builder-existing-module');
        var $existingModulePreset = $('#existing_module_preset');
        var $fullModuleNameField = $('#full_module_name');
        var $fullModuleKeyField = $('#full_module_key');
        var $selectAllButton = $('#builder-elements-select-all');
        var $deselectAllButton = $('#builder-elements-deselect-all');

        function setFullBuilderVisibility(mode) {
            var isFull = mode === 'full';
            $fullBuilderFields.toggle(isFull);
            $fullBuilderExistingModule.toggle(isFull);
        }

        setFullBuilderVisibility($modeField.val());

        $modeField.off('change.builderModules').on('change.builderModules', function () {
            setFullBuilderVisibility($(this).val());
        });

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

            $modeField.val('full').trigger('change.builderModules');
        });

        $selectAllButton.off('click.builderModules').on('click.builderModules', function () {
            $('.element-checkbox').prop('checked', true);
        });

        $deselectAllButton.off('click.builderModules').on('click.builderModules', function () {
            $('.element-checkbox').prop('checked', false);
        });
    }

    $(document).on('rex:ready', initModulesPage);
})(jQuery);
