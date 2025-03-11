<script setup>
import {computed, inject, onBeforeMount, onMounted} from "vue";
import useFieldOptions from "@/Composables/Genie/useFieldOptions.js";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";
import FieldOptions from "./FieldOptions.vue";


const props = defineProps({
    modelValue: {
        type: Array,
        default: [],
    }
});

const currentFieldType = inject('currentFieldTypeCtx');

onMounted( () => {
    initOptions();
});

const {
    initOptions,
    addOptionGroup,
    removeOptionGroup
} = useFieldOptions(props.modelValue);

const activeGroups = computed (() => {
    return currentFieldType().value.hasGroups ? props.modelValue : props.modelValue.slice(0, 1);
})

</script>
<template>
    <Flex :col="true" class="items-start w-full">
        <template v-if="modelValue.length">

            <ListGroup class="w-full">
                <template class="item-group" v-for="(element, index) in activeGroups">

                    <ListItem class="group">

                        <Flex :responsive="false" class="justify-between gap-2 sm:gap-6">

                            <Flex class="grow">

                                <FieldOptions :options="modelValue" :group="index" />

                            </Flex>

                            <PureButton
                                v-if="currentFieldType().value.hasGroups && index > 0"
                                @click="removeOptionGroup(index)"
                                :destructive="true"
                                v-tooltip="$t('genie.delete_field_options_group')"
                                class="sm:mr-sm"
                            >
                                <template #icon>
                                    <X/>
                                </template>
                            </PureButton>

                        </Flex>

                    </ListItem>
                </template>

            </ListGroup>
        </template>

        <PrimaryButton
            v-if="currentFieldType().value.hasGroups"
            size="xs"
            @click="addOptionGroup()"
        >
            <template #icon>
                <Plus/>
            </template>
            {{ $t('genie.add_field_options_group') }}
        </PrimaryButton>

    </Flex>
</template>
