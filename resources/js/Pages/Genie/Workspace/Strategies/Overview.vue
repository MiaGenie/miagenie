<script setup>
import {computed, inject, onBeforeUnmount, onMounted, ref, watch} from "vue";
import {Head, Link, router} from '@inertiajs/vue3';
import {useI18n} from "vue-i18n";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import StateCard from "@/Components/Genie/Strategy/StateCard.vue";
import RunProgress from "@/Components/Genie/Strategy/RunProgress.vue";
import Briefings from "@/Icons/Genie/Briefings.vue";
import Strategies from "@/Icons/Genie/Strategies.vue";
import Sparkles from "@/Icons/Sparkles.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Support from "@/Icons/Genie/Support.vue";
import Exclamation from "@/Icons/Exclamation.vue";

const {t: $t} = useI18n();
const workspaceCtx = inject('workspaceCtx');

const props = defineProps({
    stage: {
        type: String,
        required: true,
    },
    briefing: {
        type: Object,
        required: true,
    },
    record: {
        type: Object,
        default: null,
    },
    progress: {
        type: Object,
        default: null,
    }
});

const generating = ref(false);

/**
 * A run only moves on the server, so the page re-asks for its own props while work is in flight.
 * The controller redirects to the presentation once the strategy is finished, which ends the poll
 * by navigating away.
 */
const isLive = computed(() => props.stage === 'running');

let poll = null;

const refresh = () => {
    router.get(
        route('genie.strategies.overview', {workspace: workspaceCtx.id}),
        {},
        {preserveState: true, preserveScroll: true, only: ['stage', 'record', 'progress', 'briefing']}
    );
};

const startPolling = () => {
    if (poll || !isLive.value) {
        return;
    }

    poll = setInterval(refresh, 5000);
};

const stopPolling = () => {
    clearInterval(poll);
    poll = null;
};

onMounted(startPolling);
onBeforeUnmount(stopPolling);

watch(isLive, (live) => (live ? startPolling() : stopPolling()));

const generate = () => {
    generating.value = true;

    router.post(
        route('genie.strategies.generate', {workspace: workspaceCtx.id}),
        {},
        {onFinish: () => (generating.value = false)}
    );
};
</script>
<template>
    <div class="w-full max-w-[1200px] mx-auto row-py">

        <Head :title="$t('genie.strategy')"/>

        <PageHeader :title="$t('genie.strategy')"/>

        <div class="row-px mt-lg">

            <!-- the briefing has to be answered before anything can be generated -->
            <StateCard
                v-if="stage === 'briefing'"
                :title="$t('genie.briefing')"
                :text="briefing.exists ? $t('genie.briefings_desc') : $t('genie.strategy_empty_desc')"
            >
                <template #icon>
                    <Briefings/>
                </template>

                <Link :href="route('genie.briefings.wizard', {workspace: workspaceCtx.id})">
                    <PrimaryButton>
                        <template #icon>
                            <Briefings/>
                        </template>
                        {{ briefing.exists ? $t('genie.edit_briefing') : $t('genie.create_briefing') }}
                    </PrimaryButton>
                </Link>

                <ul v-if="briefing.missing.length" class="flex flex-wrap justify-center gap-1">
                    <li
                        v-for="name in briefing.missing"
                        :key="name"
                        class="rounded-full bg-stone-400 px-2.5 py-0.5 text-xs text-gray-500"
                    >
                        {{ name }}
                    </li>
                </ul>
            </StateCard>

            <!-- everything is in place, nothing generated yet -->
            <StateCard
                v-else-if="stage === 'generate'"
                tone="accent"
                :title="$t('genie.create_strategy')"
                :text="$t('genie.genie_setup_description')"
            >
                <template #icon>
                    <Strategies/>
                </template>

                <PrimaryButton :isLoading="generating" :disabled="generating" @click="generate">
                    <template #icon>
                        <Sparkles/>
                    </template>
                    {{ $t('genie.create_strategy') }}
                </PrimaryButton>
            </StateCard>

            <!-- a run is in flight -->
            <StateCard
                v-else-if="stage === 'running'"
                :title="$t('genie.generating_strategy')"
                :text="$t('genie.strategy_running')"
            >
                <template #icon>
                    <Sparkles/>
                </template>

                <RunProgress
                    v-if="progress"
                    :done="progress.done"
                    :total="progress.total"
                    :step="progress.step"
                />
            </StateCard>

            <!-- a step needs a human before the run continues -->
            <StateCard
                v-else-if="stage === 'review'"
                tone="accent"
                :title="progress?.step ?? $t('genie.review_strategy')"
                :text="$t('genie.strategy_pending_review')"
            >
                <template #icon>
                    <PencilSquare/>
                </template>

                <RunProgress
                    v-if="progress"
                    :done="progress.done"
                    :total="progress.total"
                />

                <Link :href="route('genie.strategies.step_review', {workspace: workspaceCtx.id})">
                    <PrimaryButton>
                        <template #icon>
                            <PencilSquare/>
                        </template>
                        {{ $t('genie.review_strategy') }}
                    </PrimaryButton>
                </Link>
            </StateCard>

            <!-- the run stopped on an error -->
            <StateCard
                v-else
                tone="alert"
                :title="$t('general.failed')"
                :text="$t('genie.strategy_error')"
            >
                <template #icon>
                    <Exclamation/>
                </template>

                <Link href="mailto:hello@miagenie.com">
                    <SecondaryButton>
                        <template #icon>
                            <Support/>
                        </template>
                        {{ $t('genie.support') }}
                    </SecondaryButton>
                </Link>
            </StateCard>

        </div>
    </div>
</template>
