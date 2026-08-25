import { snakeCase } from "lodash";
import { usePage } from "@inertiajs/vue3";
import { find } from "lodash";

/**
 * Helpers for editing a strategy field's sub-field tree.
 *
 * Every level of the tree is a plain array, so these operate on whichever list the
 * calling component owns rather than holding state of their own.
 */
const useSubFields = () => {
    const subFieldTypes = usePage().props.subFieldTypes ?? [];

    const defaultType = () => {
        const string = find(subFieldTypes, ["name", "STRING"]);

        return string ? string.value : 3;
    };

    const emptySubField = () => ({
        id: null,
        name: "",
        sub_code_name: "",
        description: "",
        type: defaultType(),
        min_length: null,
        max_length: null,
        pattern: "",
        min_items: null,
        max_items: null,
        required: 1,
        editable: 1,
        enum_values: [],
        icon: "",
        class: "",
        block: "",
        children: [],
    });

    const addSubField = (list) => {
        list.push(emptySubField());
    };

    const removeSubField = (list, index) => {
        list.splice(index, 1);
    };

    const typeOf = (subField) => {
        return find(subFieldTypes, ["value", Number(subField.type)]) ?? {};
    };

    const setCodeName = (subField) => {
        subField.sub_code_name =
            subField.sub_code_name !== ""
                ? subField.sub_code_name
                : snakeCase(subField.name);
    };

    /**
     * Whether the length, pattern and enum inputs apply.
     *
     * On an array they constrain each string item, so they stop applying the moment the array
     * gains children and starts describing objects.
     */
    const hasItemConstraints = (subField) => {
        const type = typeOf(subField);

        return type.hasChildren ? (subField.children ?? []).length === 0 : true;
    };

    const hasLengthFields = (subField) =>
        typeOf(subField).hasLength && hasItemConstraints(subField);

    const hasEnumFields = (subField) =>
        typeOf(subField).hasEnum && hasItemConstraints(subField);

    /**
     * Whether a sub-field may be edited by the workspace before the strategy is approved.
     *
     * Only values are editable: an object, an array of objects and an enum are shape rather
     * than content. HandlesSubFields recomputes the same rule when the tree is saved.
     */
    const canBeEditable = (subField) => {
        const type = typeOf(subField);

        if (type.name === "OBJECT") {
            return false;
        }

        if (type.hasChildren) {
            return (subField.children ?? []).length === 0;
        }

        return (subField.enum_values ?? []).length === 0;
    };

    /**
     * Drop the constraints that no longer apply once the type changes, so a field cannot
     * keep sending, say, minItems on a string.
     */
    const resetForType = (subField) => {
        const type = typeOf(subField);

        if (!hasLengthFields(subField)) {
            subField.min_length = null;
            subField.max_length = null;
            subField.pattern = "";
        }

        if (!type.hasItems) {
            subField.min_items = null;
            subField.max_items = null;
        }

        if (!hasEnumFields(subField)) {
            subField.enum_values = [];
        }

        if (!type.hasChildren) {
            subField.children = [];
        }

        syncEditable(subField);
    };

    /**
     * Keep the stored answer in step with the shape, so a sub-field that just became a
     * structure stops claiming to be editable.
     */
    const syncEditable = (subField) => {
        if (!canBeEditable(subField)) {
            subField.editable = 0;
        }
    };

    return {
        subFieldTypes,
        emptySubField,
        addSubField,
        removeSubField,
        typeOf,
        setCodeName,
        resetForType,
        canBeEditable,
        syncEditable,
        hasLengthFields,
        hasEnumFields,
    };
};

export default useSubFields;
