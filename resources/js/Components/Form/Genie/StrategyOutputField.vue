<script setup>
import { useI18n } from "vue-i18n";
import {usePage} from '@inertiajs/vue3';
import {inject, provide, ref} from "vue";
import {find, pick} from "lodash";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Flex from "@/Components/Layout/Flex.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import X from "@/Icons/X.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import StrategySchemaField from "@/Components/Form/Genie/StrategySchemaField.vue";
import Save from "@/Icons/Genie/Save.vue";
import StrategySchemaFieldView from "@/Components/Form/Genie/StrategySchemaFieldView.vue";
import Panel from "@/Components/Surface/Panel.vue";
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";
import Collapse from "@/Components/Surface/Collapse.vue";
import CollapseSmall from "@/Components/Surface/Genie/CollapseSmall.vue";
import VerticalGroupClass from "@/Components/Layout/Genie/VerticalGroupClass.vue";
import Group from "@/Components/Surface/Genie/Group.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import StrategyFieldIcon from "@/Components/DataDisplay/Genie/StrategyFieldIcon.vue";

const {t: $t} = useI18n();

const props = defineProps({
    index: {
        type: Number,
        required: true,
    },
    id: {
        type: String
    },
    field: {
        type: Object,
        required: true,
    }
});

</script>
<template>

    <div class="mt-md italic">
        <Flex
            v-if="field.display_title"
            class="mb-md items-center"
            :responsive="false"
        >
            <span>
                <StrategyFieldIcon
                    :field="field.code_name"
                    :icon="field.code_name"
                />
            </span>
            <span class="font-medium text-2xl">
                {{ field.name }}
            </span>
        </Flex>

        <div class="mb-md" v-html="field.description"/>
    </div>

    <template v-if="field.display_faq_title">

        <CollapseSmall>

            <template #title>{{  field.display_faq_title  }}</template>
            <template #default>
                <span v-html="field.display_faq_text"></span>
            </template>

        </CollapseSmall>

    </template>

</template>
