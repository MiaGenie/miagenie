<script setup>
import Draggable from "vuedraggable";
import useSubFields from "@/Composables/Genie/useSubFields.js";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";
import Input from "@/Components/Form/Input.vue";
import Label from "@/Components/Form/Label.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Select from "@/Components/Form/Select.vue";
import Switch from "@/Components/Form/Switch.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import Flex from "@/Components/Layout/Flex.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import GripVertical from "@/Icons/GripVertical.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    depth: {
        type: Number,
        default: 0,
    },
});

const {
    subFieldTypes,
    addSubField,
    removeSubField,
    typeOf,
    setCodeName,
    resetForType,
    canBeEditable,
    syncEditable,
    hasLengthFields,
    hasEnumFields,
} = useSubFields();

const fieldId = (name, index) => `sub_field_${props.depth}_${index}_${name}`;

/**
 * The enum list is stored as an array but edited as a comma separated string.
 */
const enumText = (subField) => (subField.enum_values ?? []).join(", ");

const setEnumText = (subField, value) => {
    subField.enum_values = value
        .split(",")
        .map((item) => item.trim())
        .filter((item) => item !== "");

    syncEditable(subField);
};
</script>
<template>
    <Flex :col="true" class="items-start w-full">
        <ListGroup v-if="modelValue.length" class="w-full">
            <Draggable
                :list="modelValue"
                :group="`sub_fields_${depth}`"
                handle=".handle"
                item-key="sub_code_name"
            >
                <template #item="{ element, index }">
                    <ListItem class="group">
                        <Flex class="justify-between my-xs" :responsive="false">
                            <PureButton class="handle mr-xs cursor-move">
                                <template #icon>
                                    <GripVertical />
                                </template>
                            </PureButton>

                            <Flex
                                :responsive="false"
                                :col="true"
                                class="w-full"
                            >
                                <Flex
                                    :responsive="false"
                                    :wrap="true"
                                    class="w-full justify-between gap-2 mt-sm"
                                >
                                    <VerticalGroup class="w-full sm:w-[23%]">
                                        <template #title>
                                            <label
                                                :for="fieldId('type', index)"
                                            >
                                                {{ $t("genie.sub_field_type") }}
                                                <LabelSuffix :danger="true"
                                                    >*</LabelSuffix
                                                >
                                            </label>
                                        </template>

                                        <Select
                                            v-model="element.type"
                                            :id="fieldId('type', index)"
                                            @change="resetForType(element)"
                                            required
                                        >
                                            <option
                                                v-for="option in subFieldTypes"
                                                :key="option.value"
                                                :value="option.value"
                                            >
                                                {{ option.title }}
                                            </option>
                                        </Select>
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[23%]">
                                        <template #title>
                                            <label
                                                :for="fieldId('icon', index)"
                                            >
                                                {{ $t("genie.sub_field_icon") }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.icon"
                                            type="text"
                                            :id="fieldId('icon', index)"
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[23%]">
                                        <template #title>
                                            <label
                                                :for="fieldId('class', index)"
                                            >
                                                {{
                                                    $t("genie.sub_field_class")
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.class"
                                            type="text"
                                            :id="fieldId('class', index)"
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[23%]">
                                        <template #title>
                                            <label
                                                :for="fieldId('block', index)"
                                            >
                                                {{
                                                    $t("genie.sub_field_block")
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.block"
                                            type="text"
                                            :id="fieldId('block', index)"
                                        />
                                    </VerticalGroup>
                                </Flex>

                                <Flex
                                    :responsive="false"
                                    :wrap="true"
                                    class="w-full justify-between gap-2"
                                >
                                    <VerticalGroup class="w-full sm:w-[48%]">
                                        <template #title>
                                            <label
                                                :for="fieldId('name', index)"
                                            >
                                                {{ $t("genie.sub_field_name") }}
                                                <LabelSuffix :danger="true"
                                                    >*</LabelSuffix
                                                >
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.name"
                                            type="text"
                                            :id="fieldId('name', index)"
                                            @focusout="setCodeName(element)"
                                            required
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[48%]">
                                        <template #title>
                                            <label
                                                :for="
                                                    fieldId(
                                                        'sub_code_name',
                                                        index,
                                                    )
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_code_name",
                                                    )
                                                }}
                                                <LabelSuffix :danger="true"
                                                    >*</LabelSuffix
                                                >
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.sub_code_name"
                                            type="text"
                                            :id="
                                                fieldId('sub_code_name', index)
                                            "
                                            placeholder="(snake_case)"
                                            required
                                        />
                                    </VerticalGroup>
                                </Flex>

                                <VerticalGroup class="w-full mt-sm">
                                    <template #title>
                                        <label
                                            :for="fieldId('description', index)"
                                        >
                                            {{
                                                $t(
                                                    "genie.sub_field_description",
                                                )
                                            }}
                                        </label>
                                    </template>

                                    <Textarea
                                        v-model="element.description"
                                        :id="fieldId('description', index)"
                                        class="w-full"
                                        rows="2"
                                    />
                                </VerticalGroup>

                                <Flex
                                    v-if="hasLengthFields(element)"
                                    :responsive="false"
                                    :wrap="true"
                                    class="w-full justify-between gap-2 mt-sm"
                                >
                                    <VerticalGroup class="w-full sm:w-[20%]">
                                        <template #title>
                                            <label
                                                :for="
                                                    fieldId('min_length', index)
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_min_length",
                                                    )
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.min_length"
                                            type="number"
                                            min="0"
                                            :id="fieldId('min_length', index)"
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[20%]">
                                        <template #title>
                                            <label
                                                :for="
                                                    fieldId('max_length', index)
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_max_length",
                                                    )
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.max_length"
                                            type="number"
                                            min="0"
                                            :id="fieldId('max_length', index)"
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup
                                        class="w-full sm:w-[55%]"
                                    >
                                        <template #title>
                                            <label
                                                :for="fieldId('pattern', index)"
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_pattern",
                                                    )
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.pattern"
                                            type="text"
                                            :id="fieldId('pattern', index)"
                                        />
                                    </VerticalGroup>
                                </Flex>

                                <Flex
                                    v-if="typeOf(element).hasItems"
                                    :responsive="false"
                                    :wrap="true"
                                    class="w-full justify-between gap-2 mt-sm"
                                >
                                    <VerticalGroup class="w-full sm:w-[48%]">
                                        <template #title>
                                            <label
                                                :for="
                                                    fieldId('min_items', index)
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_min_items",
                                                    )
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.min_items"
                                            type="number"
                                            min="0"
                                            :id="fieldId('min_items', index)"
                                        />
                                    </VerticalGroup>

                                    <VerticalGroup class="w-full sm:w-[48%]">
                                        <template #title>
                                            <label
                                                :for="
                                                    fieldId('max_items', index)
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "genie.sub_field_max_items",
                                                    )
                                                }}
                                            </label>
                                        </template>

                                        <Input
                                            v-model="element.max_items"
                                            type="number"
                                            min="0"
                                            :id="fieldId('max_items', index)"
                                        />
                                    </VerticalGroup>
                                </Flex>

                                <VerticalGroup
                                    v-if="hasEnumFields(element)"
                                    class="w-full mt-sm"
                                >
                                    <template #title>
                                        <label
                                            :for="fieldId('enum_values', index)"
                                        >
                                            {{
                                                $t(
                                                    "genie.sub_field_enum_values",
                                                )
                                            }}
                                        </label>
                                    </template>

                                    <template #description>
                                        {{
                                            $t(
                                                "genie.sub_field_enum_values_hint",
                                            )
                                        }}
                                    </template>

                                    <Input
                                        :model-value="enumText(element)"
                                        @update:model-value="
                                            setEnumText(element, $event)
                                        "
                                        type="text"
                                        :id="fieldId('enum_values', index)"
                                    />
                                </VerticalGroup>

                                <Flex
                                    :responsive="false"
                                    class="w-full gap-6 mt-sm"
                                >
                                    <Flex :col="true">
                                        <Label
                                            :for="fieldId('required', index)"
                                        >
                                            {{ $t("genie.sub_field_required") }}
                                        </Label>

                                        <Switch
                                            v-model="element.required"
                                            :id="fieldId('required', index)"
                                        />
                                    </Flex>

                                    <Flex
                                        v-if="canBeEditable(element)"
                                        :col="true"
                                    >
                                        <Label
                                            :for="fieldId('editable', index)"
                                        >
                                            {{ $t("genie.sub_field_editable") }}
                                        </Label>

                                        <Switch
                                            v-model="element.editable"
                                            :id="fieldId('editable', index)"
                                        />
                                    </Flex>
                                </Flex>

                                <div
                                    v-if="typeOf(element).hasChildren"
                                    class="w-full mt-sm pl-md border-l"
                                >
                                    <VersionFieldSubFields
                                        v-model="element.children"
                                        :depth="depth + 1"
                                    />
                                </div>
                            </Flex>

                            <PureButton
                                @click="removeSubField(modelValue, index)"
                                :destructive="true"
                                v-tooltip="$t('genie.delete_sub_field')"
                                class="group-visible sm:mr-sm"
                            >
                                <template #icon>
                                    <X />
                                </template>
                            </PureButton>
                        </Flex>
                    </ListItem>
                </template>
            </Draggable>
        </ListGroup>

        <PrimaryButton size="xs" @click="addSubField(modelValue)">
            <template #icon>
                <Plus />
            </template>
            {{
                depth === 0
                    ? $t("genie.add_sub_field")
                    : $t("genie.add_child_sub_field")
            }}
        </PrimaryButton>
    </Flex>
</template>
