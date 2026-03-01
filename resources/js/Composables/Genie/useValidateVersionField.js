import {usePage} from "@inertiajs/vue3";
import {filter, find} from "lodash";
import {useI18n} from "vue-i18n";
import {ref} from "vue";

const useValidateVersionField = (form) => {

    const {t: $t} = useI18n()
    const fieldList = usePage().props.fieldList;
    const fieldTypes = usePage().props.fieldTypes;
    const divRefs = ref({});

    const fieldType = (field) => {
        return find(fieldTypes, ['value', field.field_type]);
    };

    const validateFieldsList = (type) => {
        return filter(fieldList[type], (field, key) => {
            return field.required && (fieldType(field).name === 'CHECKBOX' || fieldType(field).name === 'RADIO' || fieldType(field).name === 'RADIO_GROUP');
        })
    }

    const setError = (field, msg) => {
        form.setError(field.code_name, msg);
    }

    const scrollToError = (field) => {
        let itemRef = divRefs.value[field.code_name];
        let top = itemRef.getBoundingClientRect().top;
        let scrollWindow = document.getElementsByTagName('main')[0];
        scrollWindow.scrollTo(0,scrollWindow.scrollTop + top);
    }

    const checkSingle = (field) => {
        return !!form[field.code_name].length;
    }

    const checkGroup = (field) => {
        return form[field.code_name].length === field.options.length;
    }

    const checkForm = (type) => {
        form.clearErrors();
        let errors = 0
        for (const field of validateFieldsList(type)) {
            if (fieldType(field).name === 'RADIO_GROUP' && !checkGroup(field)) {
                setError(field, $t('genie.validation.group_option_required'));
                errors++;
            } else if (!checkSingle(field)) {
                setError(field, $t('genie.validation.option_required'));
                errors++;
            }
            if (errors > 0) {
                scrollToError(field)
                return false;
            }
        }
        return true;
    }

    return {
        checkForm,
        divRefs
    }
}

export default useValidateVersionField;
