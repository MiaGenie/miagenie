<script setup>
import Draggable from 'vuedraggable'
import {inject, ref} from "vue";
import useFieldOptions from "@/Composables/Genie/useFieldOptions.js";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Switch from "@/Components/Form/Switch.vue";
import Flex from "@/Components/Layout/Flex.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import GripVertical from "@/Icons/GripVertical.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";

const props = defineProps({
    options: {
        type: Array,
        default: [],
    },
    group: {
        type: Number,
        default: 0
    }
});

const currentFieldType = inject('currentFieldTypeCtx');
const form = inject('formCtx');
const errors = ref(form.errors);

const {
    addOptionItem,
    removeOptionItem,
    optionChecked,
    setCodeName,
    updatePositions
} = useFieldOptions(props.options, props.group, currentFieldType);

const fieldId = (field, index) =>{
    return 'options_' + props.group + '_' + index + '_' + field;
}
</script>
<template>

    <Flex
        :col="true"
        class="items-start w-full"
    >
        <template v-if="options[props.group]">
            <ListGroup class="w-full">

                <Draggable
                    :list="options[group]"
                    group="options"
                    handle=".handle"
                    item-key="position"
                    @end="updatePositions()"
                >
                    <template #item="{element, index}">

                        <ListItem class="group">

                            <Flex
                                class="justify-between my-xs"
                                :responsive="false"
                            >

                                <PureButton class="handle mr-xs cursor-move">
                                    <template #icon>
                                        <GripVertical/>
                                    </template>
                                </PureButton>

                                <Flex
                                    :responsive="false"
                                    :col="true"
                                    class="w-full"
                                >

                                    <VerticalGroup class="w-full">
                                        <template #title>
                                            <label :for="fieldId('name', index)">
                                                {{ $t("genie.field_option_name") }}
                                                <LabelSuffix :danger="true">*</LabelSuffix>
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.name"
                                            type="text"
                                            :id="fieldId('name', index)"
                                            @focusout="setCodeName(element, index)"
                                            required
                                        />
                                    </VerticalGroup>

                                    <Flex
                                        :responsive="false"
                                        :wrap="true"
                                        class="w-full justify-between"
                                    >
                                        <VerticalGroup class="form-field w-full sm:w-[70%]">
                                            <template #title>

                                                <label :for="fieldId('code_name', index)">
                                                    {{ $t("genie.field_option_code_name") }}
                                                    <LabelSuffix :danger="true">*</LabelSuffix>
                                                </label>

                                            </template>

                                            <Input
                                                v-model="element.code_name"
                                                type="text"
                                                :id="fieldId('code_name', index)"
                                                :placeholder="'(snake_case)'"
                                                required
                                            />
                                        </VerticalGroup>

                                        <Flex
                                            :col="true"
                                            class="justify-center w-[20%]"
                                        >
                                            <Label :for="fieldId('checked', index)" >
                                                {{ $t('genie.checked') }}
                                            </Label>

                                            <Switch
                                                @click="optionChecked(element, index)"
                                                v-model="element.checked"
                                                :id="fieldId('checked', index)"
                                            />
                                        </Flex>

                                    </Flex>

                                </Flex>

                                <PureButton
                                    @click="removeOptionItem(index)"
                                    :destructive="true"
                                    v-tooltip="$t('genie.delete_field_option')"
                                    class="group-visible sm:mr-sm"
                                >
                                    <template #icon>
                                        <X/>
                                    </template>
                                </PureButton>
                            </Flex>
                        </ListItem>
                    </template>

                </Draggable>
            </ListGroup>
        </template>

        <PrimaryButton
            size="xs"
            @click="addOptionItem(index)"
        >
            <template #icon>
                <Plus/>
            </template>
            {{ $t('genie.add_field_option') }}
        </PrimaryButton>
    </Flex>
</template>
