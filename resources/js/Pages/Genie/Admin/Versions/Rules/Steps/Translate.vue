<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";

defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    rule: {
        type: Object,
        required: true
    },
    version: {
        type: Object,
        required: true
    },
    record: {
        type: Object,
        required: true
    },
    locale: {
        type: Object,
        required: true
    }
})

const {onError} = useRouter();
const confirmation = inject('confirmation');

const form = useForm({
    instructions: props.record.instructions,
    json_schema: props.record.json_schema,
    message: props.record.message,
    review_message_user: props.record.review_message_user,
    review_message_system: props.record.review_message_system
});

const submit = () => {
    form.put(route('genie.admin.versions.rules.steps.update-translation', {
        version: props.version.id,
        rule: props.rule.id,
        step: props.record.id,
        locale: props.locale.long,
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
    });
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
    router.get(route(
        'genie.admin.versions.rules.steps.index-translate',
        {
            version: props.version.id,
            rule: props.rule.id
        }
    ));
}


</script>
<template>
    <Head :title="$t('genie.translate_step')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.translate_step') + ' - ' + locale.english + ' (' + locale.long + ')'"/>

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ record.name }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="instructions">{{ $t("genie.step_instructions") }}</label>
                        </template>

                        <Textarea v-model="form.instructions"
                                  :error="form.errors.instructions !== undefined"
                                  id="instructions"
                                  class="w-full"
                                  rows="10"
                        />

                        <template #footer>
                            <Error :message="form.errors.instructions"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_message") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.message"
                                  :error="form.errors.message !== undefined"
                                  id="message"
                                  class="w-full"
                                  rows="12"
                                  required/>

                        <template #footer>
                            <Error :message="form.errors.message"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="record.response_format==='json_schema'"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="json_schema">{{ $t("genie.step_json_schema") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.json_schema"
                                  :error="form.errors.json_schema !== undefined"
                                  id="response_format"
                                  class="w-full"
                                  required
                                  rows="10"/>

                        <template #footer>
                            <Error :message="form.errors.json_schema"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="record.requires_review" class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_review_message_user") }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea v-model="form.review_message_user"
                                  :error="form.errors.review_message_user !== undefined"
                                  id="review_message_user"
                                  class="w-full"
                                  rows="12"
                                  required/>

                        <template #footer>
                            <Error :message="form.errors.review_message_user"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup v-if="record.requires_review && form.review_message_system !== ''" class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{ $t("genie.step_review_message_system") }}</label>
                        </template>

                        <Textarea v-model="form.review_message_system"
                                  :error="form.errors.review_message_system !== undefined"
                                  id="review_message_system"
                                  class="w-full"
                                  rows="12"/>

                        <template #footer>
                            <Error :message="form.errors.review_message_system"/>
                        </template>
                    </VerticalGroup>

                </Panel>

                <div class="flex flex-row items-center justify-between mt-lg">
                    <div class="flex gap-6">

                        <PrimaryButton
                            type="submit"
                            :isLoading="form.processing"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.update") }}
                            <template #icon>
                                <Save/>
                            </template>
                        </PrimaryButton>

                        <SecondaryButton
                            @click="attemptClose"
                            type="button"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen=true
                        >
                            {{ $t("general.close") }}
                            <template #icon>
                                <X/>
                            </template>
                        </SecondaryButton>

                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
