<script setup>
import {computed, inject, ref} from "vue";
import {Head, Link, router} from '@inertiajs/vue3';
import {cloneDeep, isEqual} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import SectionCard from "@/Components/Genie/Strategy/SectionCard.vue";
import SchemaNode from "@/Components/Genie/Strategy/SchemaNode.vue";
import RunProgress from "@/Components/Genie/Strategy/RunProgress.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Check from "@/Icons/Check.vue";
import ArrowUturnLeft from "@/Icons/ArrowUturnLeft.vue";

const {t: $t} = useI18n();
const {notify} = useNotifications();
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    runStep: {
        type: Object,
        required: true,
    },
    fieldList: {
        type: Array,
        required: true,
    },
    schemas: {
        type: Object,
        default: () => ({}),
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
    content: {
        type: Object,
        default: () => ({}),
    }
});

const original = cloneDeep(props.content);
const content = ref(cloneDeep(original));
const processing = ref(false);

const isEdited = (codeName) => !isEqual(content.value[codeName], original[codeName]);

const editedCount = computed(() => props.fieldList.filter((field) => isEdited(field.code_name)).length);

const update = (codeName, value) => {
    content.value = {...content.value, [codeName]: value};
};

const reset = () => {
    content.value = cloneDeep(original);
};

const approve = () => {
    processing.value = true;

    router.put(
        route('genie.strategies.step_review_approve', {
            workspace: workspaceCtx.id,
            runStep: props.runStep.id,
        }),
        {content: content.value},
        {
            onError: (errors) => notify('error', errors),
            onFinish: () => (processing.value = false),
        }
    );
};
</script>
<template>
    <div class="w-full max-w-[1200px] mx-auto row-py">

        <Head :title="$t('genie.review_strategy')"/>

        <PageHeader :title="$t('genie.review_strategy')"/>

        <div class="row-px mt-lg flex flex-col gap-lg">

            <div class="mx-auto w-full max-w-3xl">
                <RunProgress
                    :done="runStep.position - 1"
                    :total="runStep.total"
                    :step="runStep.name"
                />

                <p v-if="runStep.message" class="mt-md whitespace-pre-line text-sm leading-relaxed text-gray-500">
                    {{ runStep.message }}
                </p>
            </div>

            <div class="mx-auto flex w-full max-w-3xl flex-col gap-lg">
                <SectionCard
                    v-for="(field, index) in fieldList"
                    :key="field.code_name"
                    :title="field.name"
                    :description="field.description"
                    :index="index"
                    :accent="index"
                    :edited="isEdited(field.code_name)"
                >
                    <template #badge>{{ $t('general.edit') }}</template>

                    <!--
                        Review locks structure only: the reviewer is here to correct what the model
                        wrote, so every value stays editable even where the sub-field is not.
                    -->
                    <SchemaNode
                        :schema="schemas[field.code_name] ?? {}"
                        :meta="meta[field.code_name]"
                        :structure-only="true"
                        :model-value="content[field.code_name]"
                        @update:model-value="update(field.code_name, $event)"
                    />
                </SectionCard>
            </div>

            <div class="mx-auto flex w-full max-w-3xl items-center justify-end gap-xs">
                <SecondaryButton :disabled="!editedCount || processing" @click="reset">
                    <template #icon>
                        <ArrowUturnLeft/>
                    </template>
                </SecondaryButton>

                <Link :href="route('genie.strategies.overview', {workspace: workspaceCtx.id})">
                    <SecondaryButton :disabled="processing">{{ $t('general.close') }}</SecondaryButton>
                </Link>

                <PrimaryButton :isLoading="processing" :disabled="processing" @click="approve">
                    <template #icon>
                        <Check/>
                    </template>
                    {{ $t('genie.approve_strategy') }}
                </PrimaryButton>
            </div>

        </div>
    </div>
</template>
