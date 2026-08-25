<script setup>
import {computed, inject, onBeforeUnmount, provide, ref, watch} from "vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import {cloneDeep, debounce, find} from "lodash";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import WizardField from "@/Components/Form/Genie/WizardField.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Flex from "@/Components/Layout/Flex.vue";
import ArrowLeft from "@/Icons/ArrowLeft.vue";
import ChevronRight from "@/Icons/ChevronRight.vue";
import Sparkles from "@/Icons/Sparkles.vue";
import Check from "@/Icons/Check.vue";
import X from "@/Icons/X.vue";
import Strategies from "@/Icons/Genie/Strategies.vue";

const props = defineProps({
    mode: {
        type: String,
        default: () => usePage().props.mode ?? 'create',
    },
    fieldList: {
        type: Object,
        default: () => usePage().props.fieldList,
    },
    fieldTypes: {
        type: Array,
        default: () => usePage().props.fieldTypes,
    },
    record: {
        type: Object,
        default: () => usePage().props.record,
    }
});

const {t: $t} = useI18n();
const workspaceCtx = inject('workspaceCtx');
const confirmation = inject('confirmation');
const {onError} = useRouter();

const isCreate = computed(() => props.mode === 'create');

const typeOf = (field) => {
    return find(props.fieldTypes, ['value', Number(field.field_type)]);
};

/**
 * The customer reads the long-form question as the label, so the admin labels are shifted one
 * level down. The short admin name is kept as `label` for validation messages.
 */
const questions = cloneDeep(props.fieldList.briefings).map((field) => {
    return {
        ...field,
        label: field.name,
        name: field.description,
        description: field.sub_description,
    };
});

/**
 * Every question is seeded, then stored answers are laid over the top. A question added to the
 * version after the briefing was saved therefore still gets an empty answer of the right shape
 * instead of being undefined.
 */
const answers = questions.reduce((seed, field) => {
    const type = typeOf(field);
    const hasOptions = type.hasOptions || type.name === 'FILE';

    seed[field.code_name] = hasOptions ? [] : '';

    if (hasOptions) {
        field.options.forEach((group) => {
            group.filter((option) => option.checked === 1)
                .forEach((option) => seed[field.code_name].push(option.code_name));
        });
    }

    return seed;
}, {});

const form = useForm(isCreate.value
    ? answers
    : {...answers, ...cloneDeep(props.record.content)}
);

provide('form', form);

const answerCount = (field) => {
    const answer = form[field.code_name];

    if (Array.isArray(answer)) {
        return answer.filter((item) => item !== null && item !== undefined && item !== '').length;
    }

    if (answer === null || answer === undefined) {
        return 0;
    }

    if (typeof answer === 'object') {
        return 1;
    }

    return String(answer).trim().length ? 1 : 0;
};

/**
 * Whether the question has an answer, with no view on whether it had to be given: an optional
 * question left empty is still a gap the customer may want to come back to.
 *
 * A RADIO_GROUP asks one thing per option group, so it is only answered once every group is.
 */
const isAnswered = (field) => {
    if (typeOf(field).name === 'RADIO_GROUP') {
        return answerCount(field) === field.options.length;
    }

    return answerCount(field) > 0;
};

/**
 * The genie cannot run on a briefing with gaps in what it reads, so finishing is held back until
 * every genie-required question has an answer. This is stricter than the per-question `required`
 * check, which only blocks moving on.
 */
const isComplete = computed(() => questions.every((field) => !field.genie_required || isAnswered(field)));

/**
 * A briefing is picked up where its answers stop, so someone returning to an unfinished one is not
 * made to click past everything they already wrote. A briefing with no gaps opens at the start.
 */
const firstGap = questions.findIndex((field) => !isAnswered(field));

const index = ref(firstGap === -1 ? 0 : firstGap);
const direction = ref(1);
const finished = ref(false);

/**
 * A draft save runs on the way between questions, so the spinner has to say which button was
 * pressed rather than only that a request is in flight.
 */
const finishing = ref(false);

const total = computed(() => questions.length);
const question = computed(() => questions[index.value]);
const isLast = computed(() => index.value === total.value - 1);
const progress = computed(() => Math.round((index.value / total.value) * 100));

/**
 * Mirrors the option rules of useValidateVersionField for a single field, so the customer is
 * told about a missing answer before the final submit rather than after a 422.
 */
const checkQuestion = (field) => {
    form.clearErrors(field.code_name);

    if (!field.genie_required || isAnswered(field)) {
        return true;
    }

    const type = typeOf(field).name;

    form.setError(
        field.code_name,
        type === 'RADIO_GROUP'
            ? $t('genie.validation.group_option_required')
            : type === 'CHECKBOX' || type === 'RADIO'
                ? $t('genie.validation.option_required')
                : $t('validation.required', {attribute: field.label})
    );

    return false;
};

const goTo = (next) => {
    direction.value = next > index.value ? 1 : -1;
    index.value = next;
};

const previous = () => {
    if (index.value > 0) {
        save(true);
        goTo(index.value - 1);
    }
};

/**
 * Nothing is blocked on the way through: a gap costs the customer the Finish button, not the next
 * question, and the draft saved on every step means a skipped answer can be filled in later.
 *
 * The last question has nothing to move on to. Finishing is a decision, so it comes from the Finish
 * button alone — never from the Enter that submits this form.
 */
const next = () => {
    if (isLast.value) {
        return;
    }

    save(true);
    goTo(index.value + 1);
};

/**
 * Server rules are keyed `content.<code_name>`; the fields are keyed by `code_name` alone.
 */
const handleErrors = (errors) => {
    const codes = [];

    form.clearErrors();

    Object.entries(errors).forEach(([key, message]) => {
        const code = key.replace(/^content\./, '');

        codes.push(code);
        form.setError(code, message);
    });

    const first = questions.findIndex((field) => codes.includes(field.code_name));

    if (first !== -1) {
        goTo(first);
    }

    onError(errors, submit);
};

/**
 * An answer to a FILE question is the picked `{file}` until the server has stored it, so it is
 * exchanged for the `{id, path}` the briefing now holds. Left alone it would be uploaded again on
 * every following question, writing a workspace file each time.
 *
 * `usePage()` is read rather than `props.record`, whose default factory is evaluated once and so
 * never sees the record a save just created.
 */
const syncStoredFiles = () => {
    const content = usePage().props.record?.content ?? {};

    questions
        .filter((field) => typeOf(field).name === 'FILE')
        .forEach((field) => {
            if (content[field.code_name] !== undefined) {
                form[field.code_name] = cloneDeep(content[field.code_name]);
            }
        });
};

/**
 * Write what the wizard holds. A draft is what the customer has answered so far, sent on the way
 * between questions; anything else is the finished briefing.
 *
 * The endpoint upserts the workspace's briefing, so the same call serves both and the page never
 * has to know whether the record already exists.
 */
const save = (draft, onSaved = null) => {
    if (draft && !form.isDirty) {
        return;
    }

    finishing.value = !draft;

    form.transform((data) => ({
        content: {...data},
        draft,
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })).post(
        route('genie.briefings.wizard_save', {workspace: workspaceCtx.id}),
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                syncStoredFiles();

                form.defaults();

                if (onSaved) {
                    onSaved();
                }
            },
            onError: handleErrors,
            onFinish: () => (finishing.value = false),
        }
    );
};

const submit = () => {
    const invalid = questions.find((field) => !checkQuestion(field));

    if (invalid) {
        goTo(questions.indexOf(invalid));
        return;
    }

    save(false, () => {
        finished.value = true;
    });
};

const lastQuestion = questions[questions.length - 1];

/**
 * Every other question is written by the Next that leaves it. The last one has no Next, so its
 * answer would reach the server only when Finish is pressed, and closing from here would drop it.
 *
 * This is a draft like any other: it stores the answer without finishing the briefing, which is the
 * customer's decision alone. Leaving the field flushes rather than waiting the timer out, and the
 * last field is watched by name so that paging around cannot trigger it.
 */
const saveLastQuestion = debounce(() => save(true), 800);

watch(() => form[lastQuestion.code_name], saveLastQuestion, {deep: true});

onBeforeUnmount(() => saveLastQuestion.cancel());


const close = () => {
    if (!form.isDirty) {
        backToStrategy();
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm(() => {
            backToStrategy();
        })
        .show();
};

/**
 * The briefing exists to feed the strategy, so the wizard leaves for it rather than the config
 * page. The overview resolves the stage the workspace is at and redirects on to the presentation
 * once the strategy is there.
 */
const backToStrategy = () => {
    router.get(route('genie.strategies.overview', {workspace: workspaceCtx.id}));
};
</script>
<template>
    <div class="mx-auto w-full max-w-3xl row-py">

        <Panel v-if="finished" class="mx-auto">
            <div class="flex flex-col items-center py-lg text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-500 text-primary-context">
                    <Sparkles class="!h-8 !w-8"/>
                </span>

                <h2 class="mt-lg font-title text-2xl font-bold text-black">
                    {{ $t('genie.briefing_created') }}
                </h2>

                <p class="mt-xs text-sm text-gray-500">{{ $t('genie.briefings_desc') }}</p>

                <PrimaryButton class="mt-lg" @click="backToStrategy">
                    <template #icon>
                        <Strategies/>
                    </template>
                    {{ $t('genie.create_strategy') }}
                </PrimaryButton>
            </div>

        </Panel>

        <template v-else>

            <form method="post" @submit.prevent="next">

                <div class="sticky top-0 z-10 bg-white/90 px-md py-sm backdrop-blur">
                    <p class="text-end text-xs text-gray-400">{{ index + 1 }} / {{ total }}</p>

                    <div class="mt-xs h-1.5 w-full overflow-hidden rounded-full bg-stone-500">
                        <div class="h-full rounded-full bg-primary-500 transition-[width] duration-300 ease-out"
                             :style="{width: `${progress}%`}"></div>
                    </div>

                    <div class="mt-sm grid grid-cols-3 items-center gap-xs">
                        <div class="col-start-1 flex justify-start">
                            <SecondaryButton v-if="index > 0" type="button" :disabled="form.processing" @click="previous">
                                <template #icon>
                                    <ArrowLeft/>
                                </template>
                                {{ $t('genie.back') }}
                            </SecondaryButton>
                        </div>

                        <Flex class="col-start-2 items-center justify-center" :responsive="false">
                            <SecondaryButton type="button" :disabled="form.processing" @click="close">
                                <template #icon>
                                    <X/>
                                </template>
                                {{ $t('general.close') }}
                            </SecondaryButton>

                            <PrimaryButton type="button"
                                           :isLoading="finishing"
                                           v-if="isComplete || isLast"
                                           :disabled="!isComplete || form.processing"
                                           @click="submit">
                                {{ $t('genie.finish') }}
                                <template #icon>
                                    <Check/>
                                </template>
                            </PrimaryButton>
                        </Flex>

                        <div class="col-start-3 flex justify-end">
                            <PrimaryButton v-if="!isLast" type="submit"
                                           :isLoading="form.processing && !finishing"
                                           :disabled="form.processing">
                                {{ $t('general.next') }}
                                <template #icon>
                                    <ChevronRight/>
                                </template>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                <Panel class="mx-auto mt-lg" @focusout="isLast && saveLastQuestion.flush()">

                    <Transition :name="direction === 1 ? 'wizard-next' : 'wizard-back'" mode="out-in">
                        <WizardField :key="question.code_name" :field="question"/>
                    </Transition>

                    <p v-if="!question.genie_required" class="mt-sm text-center text-xs text-gray-400">
                        {{ $t('general.optional') }}
                    </p>

                </Panel>

            </form>

        </template>
    </div>
</template>
<style scoped>
.wizard-next-enter-active,
.wizard-next-leave-active,
.wizard-back-enter-active,
.wizard-back-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.wizard-next-enter-from,
.wizard-back-leave-to {
    opacity: 0;
    transform: translateX(2.5rem);
}

.wizard-next-leave-to,
.wizard-back-enter-from {
    opacity: 0;
    transform: translateX(-2.5rem);
}

@media (prefers-reduced-motion: reduce) {
    .wizard-next-enter-active,
    .wizard-next-leave-active,
    .wizard-back-enter-active,
    .wizard-back-leave-active {
        transition: opacity 0.25s ease;
    }

    .wizard-next-enter-from,
    .wizard-next-leave-to,
    .wizard-back-enter-from,
    .wizard-back-leave-to {
        transform: none;
    }
}
</style>
