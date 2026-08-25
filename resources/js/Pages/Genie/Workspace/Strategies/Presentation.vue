<script setup>
import {computed, inject, ref} from "vue";
import {Head, router} from '@inertiajs/vue3';
import {cloneDeep, find, isEqual} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import SectionCard from "@/Components/Genie/Strategy/SectionCard.vue";
import SchemaNode from "@/Components/Genie/Strategy/SchemaNode.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Save from "@/Icons/Genie/Save.vue";
import Check from "@/Icons/Check.vue";
import ArrowUturnLeft from "@/Icons/ArrowUturnLeft.vue";

const {t: $t} = useI18n();
const {notify} = useNotifications();
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    record: {
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
    strategyStatusTypes: {
        type: Array,
        default: () => [],
    }
});

const original = cloneDeep(props.record.content ?? {});
const content = ref(cloneDeep(original));
const processing = ref(false);

const status = computed(() => {
    return find(props.strategyStatusTypes, ['value', Number(props.record.status)])?.name ?? '';
});

const readOnly = computed(() => status.value === 'APPROVED');

/**
 * Only the fields the run actually wrote are worth a card; a version can carry fields an older
 * strategy never filled.
 */
const sections = computed(() => {
    return props.fieldList.filter((field) => {
        return props.schemas[field.code_name] && content.value[field.code_name] !== undefined;
    });
});

const isEdited = (codeName) => !isEqual(content.value[codeName], original[codeName]);

const editedCount = computed(() => sections.value.filter((field) => isEdited(field.code_name)).length);

const update = (codeName, value) => {
    content.value = {...content.value, [codeName]: value};
};

const reset = () => {
    content.value = cloneDeep(original);
};

const save = () => {
    processing.value = true;

    router.put(
        route('genie.strategies.presentation_update', {workspace: workspaceCtx.id, strategy: props.record.id}),
        {content: content.value},
        {
            preserveScroll: true,
            onSuccess: () => notify('success', $t('genie.strategy_updated')),
            onError: (errors) => notify('error', errors),
            onFinish: () => (processing.value = false),
        }
    );
};

const approve = () => {
    processing.value = true;

    router.put(
        route('genie.strategies.approve', {workspace: workspaceCtx.id, strategy: props.record.id}),
        {id: props.record.id},
        {
            preserveScroll: true,
            onSuccess: () => notify('success', $t('genie.strategy_approved')),
            onError: (errors) => notify('error', errors),
            onFinish: () => (processing.value = false),
        }
    );
};
</script>
<template>
    <div class="row-py">
        <Head :title="$t('genie.strategy')"/>

        <div class="mx-auto w-full max-w-[1200px] row-px">

            <header class="sticky top-0 z-10 -mx-1 mb-lg bg-white/90 px-1 py-md backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-sm">
                    <div class="min-w-0">
                        <h1 class="font-title text-2xl font-bold text-black">{{ $t('genie.strategy') }}</h1>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ sections.length }} · {{ status }}
                            <span v-if="editedCount" class="ml-1 text-primary-500">
                                · {{ editedCount }}
                            </span>
                        </p>
                    </div>

                    <div v-if="!readOnly" class="flex items-center gap-xs">
                        <SecondaryButton :disabled="!editedCount || processing" @click="reset">
                            <template #icon>
                                <ArrowUturnLeft/>
                            </template>
                        </SecondaryButton>

                        <SecondaryButton :disabled="!editedCount || processing" @click="save">
                            <template #icon>
                                <Save/>
                            </template>
                            {{ $t('general.save') }}
                        </SecondaryButton>

                        <PrimaryButton :disabled="processing" :isLoading="processing" @click="approve">
                            <template #icon>
                                <Check/>
                            </template>
                            {{ $t('genie.approve_strategy') }}
                        </PrimaryButton>
                    </div>
                </div>
            </header>

            <div class="flex items-start gap-lg">

                <nav class="sticky top-28 hidden w-56 shrink-0 lg:block">
                    <ul class="flex flex-col gap-0.5">
                        <li v-for="(field, index) in sections" :key="field.code_name">
                            <a
                                :href="`#section-${field.code_name}`"
                                class="flex items-center gap-xs rounded-lg px-2 py-1.5 text-xs text-gray-500 transition-colors hover:bg-primary-50 hover:text-primary-500"
                            >
                                <span class="w-5 shrink-0 text-right font-mono text-[10px] text-gray-300">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                                <span class="truncate">{{ field.name }}</span>
                                <span
                                    v-if="isEdited(field.code_name)"
                                    class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-primary-500"
                                ></span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="flex min-w-0 flex-1 flex-col gap-lg">
                    <SectionCard
                        v-for="(field, index) in sections"
                        :id="`section-${field.code_name}`"
                        :key="field.code_name"
                        :title="field.name"
                        :description="field.description"
                        :index="index"
                        :accent="index"
                        :edited="isEdited(field.code_name)"
                    >
                        <template #badge>{{ $t('general.edit') }}</template>

                        <SchemaNode
                            :schema="schemas[field.code_name]"
                            :meta="meta[field.code_name]"
                            :model-value="content[field.code_name]"
                            :read-only="readOnly"
                            @update:model-value="update(field.code_name, $event)"
                        />
                    </SectionCard>
                </div>

            </div>
        </div>
    </div>
</template>
