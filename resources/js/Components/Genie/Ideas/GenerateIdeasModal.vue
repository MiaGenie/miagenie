<script setup>
import {inject, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Select from "@/Components/Form/Select.vue";
import Radio from "@/Components/Form/Radio.vue";
import Error from "@/Components/Form/Error.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import WarningButton from "@/Components/Button/WarningButton.vue";
import Lamp from "@/Icons/Genie/Lamp.vue";
import Flex from "@/Components/Layout/Flex.vue";

const {t: $t} = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    postFormats: {
        type: Object,
        required: true
    },
    funnelStages: {
        type: Object,
        required: true
    },
    strategyPillars: {
        type: Array,
        default: () => []
    },
    counts: {
        type: Array,
        default: () => [5, 10, 20]
    }
});

const emit = defineEmits(['close', 'started']);

const workspaceCtx = inject('workspaceCtx');

// GLOBAL is the first funnel stage and stands for "any".
const form = useForm({
    post_format: '',
    content_pillar: '',
    funnel_stage: 1,
    count: 10,
});

watch(() => props.show, (shown) => {
    if (shown) {
        form.clearErrors();
    }
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        post_format: data.post_format === '' ? null : data.post_format,
        content_pillar: data.content_pillar === '' ? null : data.content_pillar,
    })).post(route('genie.ideas.generate', {workspace: workspaceCtx.id}), {
        preserveScroll: true,
        onSuccess: () => {
            emit('started');
            emit('close');
        },
    });
}
</script>
<template>
    <DialogModal :show="show" max-width="lg" @close="$emit('close')">
        <template #header>
            {{ $t('genie.generate_ideas') }}
        </template>

        <template #body>
            <VerticalGroup class="form-field">
                <template #title>
                    <label for="generate_post_format">{{ $t('genie.generate_ideas_format') }}</label>
                </template>

                <Select
                    v-model="form.post_format"
                    :error="form.errors.post_format !== undefined"
                    id="generate_post_format"
                >
                    <option value="">{{ $t('genie.post_format_any') }}</option>
                    <option
                        v-for="format in postFormats"
                        :key="format.value"
                        :value="format.value"
                    >
                        {{ $t(`genie.post_format_${format.title}`) }}
                    </option>
                </Select>

                <template #footer>
                    <Error :message="form.errors.post_format"/>
                </template>
            </VerticalGroup>

            <VerticalGroup class="form-field mt-lg">
                <template #title>
                    <label for="generate_content_pillar">{{ $t('genie.generate_ideas_pillar') }}</label>
                </template>

                <Select
                    v-model="form.content_pillar"
                    :error="form.errors.content_pillar !== undefined"
                    id="generate_content_pillar"
                >
                    <option value="">{{ $t('genie.content_pillar_all') }}</option>
                    <option
                        v-for="pillar in strategyPillars"
                        :key="pillar"
                        :value="pillar"
                    >
                        {{ pillar }}
                    </option>
                </Select>

                <template #footer>
                    <Error :message="form.errors.content_pillar"/>
                </template>
            </VerticalGroup>

            <VerticalGroup class="form-field mt-lg">
                <template #title>
                    <label for="generate_funnel_stage">{{ $t('genie.generate_ideas_stage') }}</label>
                </template>

                <Select
                    v-model="form.funnel_stage"
                    :error="form.errors.funnel_stage !== undefined"
                    id="generate_funnel_stage"
                >
                    <option
                        v-for="stage in funnelStages"
                        :key="stage.value"
                        :value="stage.value"
                    >
                        {{ $t(`genie.funnel_stage_${stage.title}`) }}
                    </option>
                </Select>

                <template #footer>
                    <Error :message="form.errors.funnel_stage"/>
                </template>
            </VerticalGroup>

            <VerticalGroup class="form-field mt-lg">
                <template #title>
                    <label>{{ $t('genie.generate_ideas_count') }}</label>
                </template>

                <Flex :responsive="false" class="gap-lg">
                    <label
                        v-for="count in counts"
                        :key="count"
                        class="flex items-center gap-xs cursor-pointer"
                    >
                        <Radio v-model:checked="form.count" :value="count"/>
                        {{ count }}
                    </label>
                </Flex>

                <template #footer>
                    <Error :message="form.errors.count"/>
                </template>
            </VerticalGroup>
        </template>

        <template #footer>
            <SecondaryButton @click="$emit('close')" class="mr-xs rtl:mr-0 rtl:ml-xs">
                {{ $t('general.cancel') }}
            </SecondaryButton>

            <WarningButton
                @click="submit"
                :disabled="form.processing"
                :isLoading="form.processing"
            >
                <template #icon>
                    <Lamp/>
                </template>
                {{ $t('genie.generate_ideas') }}
            </WarningButton>
        </template>
    </DialogModal>
</template>
