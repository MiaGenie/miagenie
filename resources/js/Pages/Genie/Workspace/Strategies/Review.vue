<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, provide, ref} from "vue";
import {cloneDeep} from "lodash";
import {useI18n} from "vue-i18n";
import useNotifications from "@/Composables/useNotifications";
import useRouter from "@/Composables/useRouter";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import SectionTitle from "@/Components/DataDisplay/SectionTitle.vue";
import ViewReviewField from "@/Components/Form/Genie/ViewReviewField.vue";
import EditReviewField from "@/Components/Form/Genie/EditReviewField.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Panel from "@/Components/Surface/Panel.vue";
import PencilSquare from "@/Icons/PencilSquare.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import Check from "@/Icons/Check.vue";

const {t: $t} = useI18n();

const props = defineProps({
    field: {
        type: Object,
        required: true,
    },
    fieldTypes: {
        type: Object,
        required: true,
    },
    step: {
        type: Object,
        required: true,
    },
    record: {
        type: Object,
        required: true,
    }
})

const routePrefix = inject('routePrefix');
const workspaceCtx = inject('workspaceCtx')
const confirmation = inject('confirmation');
const editing = ref(false);

const {notify} = useNotifications();
const {onError} = useRouter();

const form = useForm({'target_audiences': cloneDeep(props.record)
});

const update = () => {
    form.transform((data) => ({
        ...data,
        'field' : props.field.code_name
    })).put(route(`genie.strategies.review_update`, {
        'workspace': workspaceCtx.id,
        'strategy': route().params.strategy
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
}

const submit = () => {
        update();
}

const attemptClose = () => {
    if (!form.isDirty) {
        backToList();
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm(() => {
            backToList();
        })
        .show();
}

const backToList = () => {
    router.get(route('genie.strategies.index', {
        workspace: workspaceCtx.id
    }));
}

provide(/* key */ 'form', /* value */ form);

</script>
<template>

    <Head :title="$t('genie.strategy_review')"/>

    <div class="w-full mx-auto row-py">
        <PageHeader :title="$t('genie.strategy_review')" />

        <div class="row-px">

            <form method="post" @submit.prevent="submit">

                <Panel>

                    <SectionTitle>
                        {{ step.review_message_user }}
                    </SectionTitle>

                    <template v-for="(content, index) in props.record" :key="index">
                        <EditReviewField
                            v-if="editing"
                            :fieldTypes="props.fieldTypes"
                            :field="props.field"
                            :index="index"
                        />

                        <ViewReviewField
                            v-if="!editing"
                            :fieldTypes="props.fieldTypes"
                            :field="props.field"
                            :index="index"
                        />
                    </template>
                </Panel>

                <Flex
                    class="flex-row items-center justify-between mt-lg"
                    :responsive="false"
                >
                    <Flex
                        class="gap-6"
                        :responsive="false"
                    >

                        <PrimaryButton
                            type="submit"
                            :isLoading="form.processing"
                            :disabled="form.processing"
                        >
                            <template #default v-if="!editing">
                                {{ $t("general.ok") }}
                            </template>
                            <template #default v-if="editing">
                                {{ $t("general.save") }}
                            </template>
                            <template #icon v-if="!editing">
                                <Check/>
                            </template>
                            <template #icon v-if="editing">
                                <Save/>
                            </template>
                        </PrimaryButton>

                        <SecondaryButton
                            type="button"
                            :disabled="form.processing"
                            @click="editing = true"
                            v-if="!editing"
                        >
                            {{ $t("general.edit") }}
                            <template #icon>
                                <PencilSquare/>
                            </template>
                        </SecondaryButton>

                        <SecondaryButton
                            @click="attemptClose"
                            type="button"
                            :disabled="form.processing"
                        >
                            {{ $t("general.close") }}
                            <template #icon>
                                <X/>
                            </template>
                        </SecondaryButton>

                    </Flex>
                </Flex>
            </form>
        </div>
    </div>
</template>
