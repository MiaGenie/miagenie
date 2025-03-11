import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";
import {cloneDeep, filter, find, groupBy, includes, join, snakeCase, uniq, values} from "lodash";
import {useI18n} from "vue-i18n";

const useVersionField = (form) => {

    const {t: $t} = useI18n()

    const groupType = usePage().props.groupType;
    const groupTypes = usePage().props.groupTypes;
    const fieldTypes = usePage().props.fieldTypes;
    const inputTypes = usePage().props.inputTypes;

    const formatOptions = () => {
        form.options = values(groupBy(form.options, 'group'));
        form.defaults(cloneDeep(form.data()));
    }


    const validationOptions = computed (() => {
        return currentFieldType.value.hasGroups ? form.options : form.options.slice(0,1);
    })

    const checkEmptyOptions = () => {
        if (
            validationOptions.value.every(group => group.length === 0)
        ) {
            form.errors.options = $t('genie.field_options_required');
            return false;
        }
        return true;
    }

    const singleOptionGroups =() => {
        return validationOptions.value.some(
            group => group.length === 1
        )
    }

    const checkSingleOption = () => {
        if (
            singleOptionGroups()
            && currentFieldType.value.isRadio
        ) {
            form.errors.options = $t('genie.field_options_invalid_radio');
            return false;
        }
        return true;
    }

    const multipleChecked = () => {
        return validationOptions.value.some(
            group => group.filter(
                (option) => {
                    return option.checked
                }
            ).length > 1
        )
    }

    const checkMultipleChecked = () => {
        if (
            multipleChecked()
            && !currentFieldType.value.hasMulti
        ) {
            form.errors.options = $t('genie.field_options_invalid_checked');
            return false;
        }
        return true;
    }

    const checkForm = () => {
        if (!currentFieldType.value.hasOptions) return true;

        return checkEmptyOptions() && checkSingleOption() && checkMultipleChecked();
    }


    const setCodeName = () => {
        form.code_name = form.code_name !== '' ? form.code_name : snakeCase(form.name);
    }

    const currentGroupType = computed (() => {
        return find(groupTypes, ['value', Number(form.group_type)]);
    });

    const currentFieldType = computed (() => {
        return find(fieldTypes, ['value', Number(form.field_type)]);
    });

    const currentInputType = computed (() => {
        return find(inputTypes, ['value', Number(form.input_type)]);
    });

    const optionsErrors = () => {

        const errors = filter(form.errors, (error, key) => {
            return error ? includes(key, 'option') : false;
        });

        return join(uniq(errors), ', ');

    }


    return {
        formatOptions,
        checkForm,
        setCodeName,
        currentGroupType,
        currentFieldType,
        currentInputType,
        optionsErrors
    }
}

export default useVersionField;
