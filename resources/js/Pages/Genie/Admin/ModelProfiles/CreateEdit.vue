<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { inject } from "vue";
import { useI18n } from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import { cloneDeep } from "lodash";
import usePageMode from "@/Composables/usePageMode";
import AdminLayout from "@/Layouts/Admin.vue";
import DangerButton from "@/Components/Button/DangerButton.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Select from "@/Components/Form/Select.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";

defineOptions({ layout: AdminLayout });

const { t: $t } = useI18n();

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: "create",
    },
    record: {
        type: Object,
    },
    providers: {
        type: Array,
        default: () => [],
    },
    modelTiers: {
        type: Array,
        default: () => [],
    },
});

const { isCreate, isEdit } = usePageMode();
const { onError } = useRouter();
const confirmation = inject("confirmation");

const form = useForm(
    isEdit.value
        ? cloneDeep(props.record)
        : {
              name: "",
              provider: "openai",
              model_tier: "default",
              model: "",
              timeout: null,
          },
);

const store = () => {
    form.post(route("genie.admin.model_profiles.store"), {
        onError: (errors) => {
            onError(errors, store);
        },
    });
};

const update = () => {
    form.put(
        route("genie.admin.model_profiles.update", {
            model_profile: props.record.id,
        }),
        {
            preserveScroll: true,
            onError: (errors) => {
                onError(errors, update);
            },
        },
    );
};

const submit = () => {
    if (isCreate.value) {
        store();
    }

    if (isEdit.value) {
        update();
    }
};

const attemptClose = () => {
    if (!form.isDirty) {
        backToList();
        return;
    }

    confirmation()
        .title($t("genie.are_you_sure"))
        .description($t("genie.unsaved_will_lost"))
        .btnConfirmName($t("genie.discard"))
        .onConfirm(() => {
            backToList();
        })
        .show();
};

const backToList = () => {
    router.get(route("genie.admin.model_profiles.index"));
};

const deleteModelProfile = () => {
    confirmation()
        .title($t("genie.delete_model_profile"))
        .description($t("genie.delete_model_profile_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route("genie.admin.model_profiles.delete", {
                    model_profile: props.record.id,
                }),
                {
                    preserveScroll: true,
                    onFinish() {
                        dialog.reset();
                    },
                },
            );
        })
        .show();
};
</script>
<template>
    <Head
        :title="
            mode === 'create'
                ? $t('genie.create_model_profile')
                : $t('genie.edit_model_profile')
        "
    />

    <div class="w-full mx-auto row-py">
        <PageHeader
            :title="
                mode === 'create'
                    ? $t('genie.create_model_profile')
                    : $t('genie.edit_model_profile')
            "
        />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="name"
                                >{{ $t("general.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.name"
                            :error="form.errors.name !== undefined"
                            type="text"
                            id="name"
                            :autofocus="isCreate"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.name" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="provider"
                                >{{ $t("genie.model_profile_provider") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Select
                            v-model="form.provider"
                            :error="form.errors.provider !== undefined"
                            id="provider"
                            required
                        >
                            <option
                                v-for="option in providers"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.provider" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="model_tier"
                                >{{ $t("genie.model_profile_model") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <template #description>
                            {{ $t("genie.model_profile_tier_description") }}
                        </template>

                        <Select
                            v-model="form.model_tier"
                            :error="form.errors.model_tier !== undefined"
                            id="model_tier"
                            required
                        >
                            <option
                                v-for="option in modelTiers"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.model_tier" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="form.model_tier === 'other'"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="model"
                                >{{ $t("genie.model_profile_model_name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.model"
                            :error="form.errors.model !== undefined"
                            type="text"
                            id="model"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.model" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="timeout">{{
                                $t("genie.model_profile_timeout")
                            }}</label>
                        </template>

                        <Input
                            v-model="form.timeout"
                            type="number"
                            min="1"
                            max="3600"
                            id="timeout"
                        />

                        <template #footer>
                            <Error :message="form.errors.timeout" />
                        </template>
                    </VerticalGroup>
                </Panel>

                <div class="flex flex-row items-center justify-between mt-lg">
                    <div class="flex gap-6">
                        <PrimaryButton
                            type="submit"
                            :isLoading="form.processing"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen="true"
                        >
                            {{
                                isCreate
                                    ? $t("general.create")
                                    : $t("general.update")
                            }}
                            <template #icon>
                                <Save />
                            </template>
                        </PrimaryButton>

                        <SecondaryButton
                            @click="attemptClose"
                            type="button"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen="true"
                        >
                            {{ $t("general.close") }}
                            <template #icon>
                                <X />
                            </template>
                        </SecondaryButton>
                    </div>
                    <div v-if="isEdit">
                        <DangerButton
                            @click="deleteModelProfile"
                            :disabled="form.processing"
                            :hidden-text-on-small-screen="true"
                        >
                            {{ $t("general.delete") }}
                            <template #icon>
                                <Trash />
                            </template>
                        </DangerButton>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
