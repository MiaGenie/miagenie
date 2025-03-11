import {maxBy, snakeCase} from "lodash";

const useFieldOptions = (options = [], group = 0, currentFieldType) => {

    const initOptions = () => {
        if (options.length === 0) addOptionGroup();
    }

    const addOptionGroup = () => {
        options.push([emptyOptionGroup()]);
        updatePositions();
    }

    const emptyOptionGroup = () => {
        return {
            'name': '',
            'code_name': '',
            'checked': 0,
            'group': newGroupPosition(),
            'position': 1
        }
    }

    const newGroupPosition = () => {
        if (options.length === 0) return 0;

        const current = options.reduce(
            (groupNumber, group) => {
                groupNumber = maxBy(group, 'group').group;
                return groupNumber;
            }, 0);

        return current + 1;
    }

    const removeOptionGroup = (index) => {
        options.splice(index, 1);
        updatePositions();
    }

    const addOptionItem = () => {
        options[group].push(emptyOption());
        updatePositions();
    }

    const emptyOption = () => {
        return {
            'name': '',
            'code_name': '',
            'checked': 0,
            'group': group,
            'position': newItemPosition()
        }
    }

    const newItemPosition = () => {
        if (options[group].length === 0) return 0;

        return maxBy(options[group], 'position').position + 1;
    }

    const removeOptionItem = (index) => {
        options[group].splice(index, 1);
        updatePositions();
    }

    const optionChecked = (element, index) => {
        if (currentFieldType().value.hasMulti) return;

        options[group].map((option, key) => {
            option.checked = key === index ? option.checked : false;
            return option;
        });
    }

    const setCodeName = (element) => {
        element.code_name = element.code_name !== '' ? element.code_name : snakeCase(element.name);
    }

    const updatePositions = () => {
        options.forEach(
            (group, groupIndex) => group.forEach(
                (option, index) => {
                    option['group'] = groupIndex;
                    option['position'] = index;
                }
            )
        );
    }

    return {
        initOptions,
        addOptionGroup,
        removeOptionGroup,
        addOptionItem,
        removeOptionItem,
        optionChecked,
        setCodeName,
        updatePositions
    }
}

export default useFieldOptions;
