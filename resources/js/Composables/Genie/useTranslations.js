import useNotifications from "@/Composables/useNotifications";

/**
 * The inline translation endpoints for one translatable attribute of one record.
 *
 * `type` is a key of App\Support\Translatables (field, option, sub_field, step), `id` the
 * record's uuid and `attribute` one of the model's translatable attributes.
 */
const useTranslations = () => {
    const {notify} = useNotifications();

    const params = (type, id, attribute) => ({type, record: id, attribute});

    const fail = (error) => {
        notify('error', error.response?.data?.message ?? error);

        throw error;
    };

    const load = (type, id, attribute) => {
        return axios.get(route('genie.admin.translations.show', params(type, id, attribute)))
            .then((response) => response.data)
            .catch(fail);
    };

    const save = (type, id, attribute, translations) => {
        return axios.put(route('genie.admin.translations.update', params(type, id, attribute)), {translations})
            .then((response) => response.data)
            .catch(fail);
    };

    const translate = (type, id, attribute, to) => {
        return axios.post(route('genie.admin.translations.translate', params(type, id, attribute)), {to})
            .then((response) => response.data)
            .catch(fail);
    };

    return {
        load,
        save,
        translate,
    };
};

export default useTranslations;
