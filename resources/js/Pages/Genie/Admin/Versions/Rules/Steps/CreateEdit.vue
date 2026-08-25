<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { inject, onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import { cloneDeep, find, toArray } from "lodash";
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
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import Trash from "@/Icons/Trash.vue";
import X from "@/Icons/X.vue";
import Switch from "@/Components/Form/Switch.vue";
import Flex from "@/Components/Layout/Flex.vue";
import TableCell from "@/Components/DataDisplay/TableCell.vue";
import Checkbox from "@/Components/Form/Checkbox.vue";

defineOptions({ layout: AdminLayout });

const { t: $t } = useI18n();

const props = defineProps({
    mode: {
        required: true,
        type: String,
        default: "create",
    },
    rule: {
        type: Object,
        required: true,
    },
    ruleTypes: {
        type: Object,
        required: true,
    },
    ruleSubTypes: {
        type: Object,
        required: true,
    },
    version: {
        type: Object,
        required: true,
    },
    modelProfiles: {
        type: Array,
        required: true,
    },
    outputFields: {
        type: Object,
        required: true,
    },
    outputIdeaFields: {
        type: Array,
        required: true,
    },
    outputDraftFields: {
        type: Array,
        required: true,
    },
    outputPrePostFields: {
        type: Array,
        required: true,
    },
    record: {
        type: Object,
    },
});

const { isCreate, isEdit } = usePageMode();
const { onError } = useRouter();
const confirmation = inject("confirmation");

const form = useForm(
    isEdit.value
        ? cloneDeep(props.record)
        : {
              rule_sub_type: isEdit.value ? props.record.rule_sub_type : "",
              name: isEdit.value ? props.record.name : "",
              description: isEdit.value ? props.record.description : "",
              instructions: isEdit.value ? props.record.instructions : "",
              model_profile_id: isEdit.value
                  ? props.record.model_profile_id
                  : "",
              response_format: isEdit.value
                  ? (props.record.response_format ?? "")
                  : "",
              link_upstream: isEdit.value ? props.record.link_upstream : 0,
              message: isEdit.value ? props.record.message : "",
              output: isEdit.value ? props.record.output : [],
              requires_review: isEdit.value ? props.record.requires_review : 0,
              review_message_user: isEdit.value
                  ? props.record.review_message_user
                  : "",
              review_message_system: isEdit.value
                  ? props.record.review_message_system
                  : "",
              optional: isEdit.value ? props.record.optional : 0,
              depends_on_field: isEdit.value
                  ? props.record.depends_on_field
                  : "",
              depends_on_option: isEdit.value
                  ? props.record.depends_on_option
                  : "",
          },
);

const isMultiple = ref(false);

/**
 * A profile on a tier has no model name of its own — the SDK resolves one per provider.
 */
const profileModel = (profile) =>
    profile.model_tier === "other" ? profile.model : profile.model_tier;

const ruleSubType = ref({});
const ruleType =
    find(props.ruleTypes, ["value", parseInt(props.rule.rule_type)]) ?? {};

onMounted(() => {
    ruleSubType.value =
        find(props.ruleSubTypes, ["value", parseInt(form.rule_sub_type)]) ?? {};
});

watch(
    () => form.rule_sub_type,
    () => {
        ruleSubType.value =
            find(props.ruleSubTypes, ["value", parseInt(form.rule_sub_type)]) ??
            {};
        isMultiple.value = ruleSubType.value?.name === "BRIEFINGS_MULTIPLE";

        if (isMultiple.value) {
            form.response_format = "json_schema";
        }
    },
);

const dependsOnOptions = () => {
    return (
        find(props.outputFields, ["id", parseInt(form.depends_on_field)]) ?? {}
    );
};

const addTag = (newTag) => {
    const tag = {
        name: newTag,
        code: newTag.substring(0, 2) + Math.floor(Math.random() * 10000000),
    };
    this.options.push(tag);
    this.value.push(tag);
};

const store = () => {
    form.post(
        route("genie.admin.versions.rules.steps.store", {
            version: props.version.id,
            rule: props.rule.id,
        }),
        {
            onError: (errors) => {
                onError(errors, store);
            },
        },
    );
};

const update = () => {
    form.put(
        route("genie.admin.versions.rules.steps.update", {
            version: props.version.id,
            rule: props.rule.id,
            step: props.record.id,
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
    router.get(
        route("genie.admin.versions.rules.steps.index", {
            version: props.version.id,
            rule: props.rule.id,
        }),
    );
};

const deleteStep = () => {
    confirmation()
        .title($t("genie.delete_step"))
        .description($t("genie.delete_step_confirm"))
        .destructive()
        .onConfirm((dialog) => {
            dialog.isLoading(true);

            router.delete(
                route("genie.admin.versions.rules.steps.delete", {
                    version: props.version.id,
                    rule: props.rule.id,
                    step: props.record.id,
                }),
            );
        })
        .show();
};
</script>
<template>
    <Head
        :title="
            mode === 'create' ? $t('genie.create_step') : $t('genie.edit_step')
        "
    />

    <div class="w-full mx-auto row-py">
        <PageHeader
            :title="
                mode === 'create'
                    ? $t('genie.create_step')
                    : $t('genie.edit_step')
            "
        />

        <div class="row-px">
            <form method="post" @submit.prevent="submit">
                <Panel>
                    <template #title>{{ $t("general.details") }}</template>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="rule_sub_type">{{
                                $t("genie.rule_sub_type")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.rule_sub_type"
                            :disabled="isEdit"
                            id="rule_sub_type"
                            required
                        >
                            <option
                                v-for="option in ruleSubTypes"
                                :value="option.value"
                            >
                                {{ option.title }}
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.rule_sub_type" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="
                            ruleType['name'] === 'IDEAS' ||
                            ruleSubType['name'] === 'CHANNELS' ||
                            (ruleType['name'] === 'DRAFTS' &&
                                !props.rule.link_upstream &&
                                ruleSubType['name'] === 'DRAFTS')
                        "
                        class="form-field"
                    >
                        <template #title>
                            <label for="link_upstream">{{
                                $t("genie.step_link_upstream")
                            }}</label>
                        </template>

                        <Switch
                            v-model="form.link_upstream"
                            id="link_upstream"
                        />

                        <template #footer>
                            <Error :message="form.errors.link_upstream" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="
                            ruleSubType['name'] === 'CHANNELS' ||
                            (ruleSubType['name'] === 'IDEAS_MULTIPLE' &&
                                form.link_upstream)
                        "
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="depends_on_field">{{
                                $t("genie.step_depends_on_field")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.depends_on_field"
                            :error="form.errors.depends_on_field !== undefined"
                            id="depends_on_field"
                            required
                        >
                            <template v-for="field in outputFields">
                                <option
                                    v-if="field.is_linkable"
                                    :value="field.id"
                                >
                                    {{ field.code_name }} - {{ field.name }}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.depends_on_field" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="
                            ruleSubType['name'] === 'CHANNELS' &&
                            form.depends_on_field !== ''
                        "
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="depends_on_option">{{
                                $t("genie.step_depends_on_option")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.depends_on_option"
                            :error="form.errors.depends_on_option !== undefined"
                            id="depends_on_option"
                            required
                        >
                            <template
                                v-for="option in dependsOnOptions().sub_fields"
                            >
                                <option
                                    :value="option.id"
                                    v-if="option.type === 4"
                                >
                                    {{ option.sub_code_name }} -
                                    {{ option.name }}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.depends_on_option" />
                        </template>
                    </VerticalGroup>

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
                            <label for="description">{{
                                $t("genie.description")
                            }}</label>
                        </template>

                        <Textarea
                            v-model="form.description"
                            :error="form.errors.description !== undefined"
                            id="description"
                            class="w-full"
                            rows="3"
                        />

                        <template #footer>
                            <Error :message="form.errors.description" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="instructions">{{
                                $t("genie.step_instructions")
                            }}</label>
                        </template>

                        <Textarea
                            v-model="form.instructions"
                            :error="form.errors.instructions !== undefined"
                            id="instructions"
                            class="w-full"
                            rows="10"
                        />

                        <template #footer>
                            <Error :message="form.errors.instructions" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="model_profile_id">{{
                                $t("genie.step_model_profile")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.model_profile_id"
                            :error="form.errors.model_profile_id !== undefined"
                            id="model_profile_id"
                            required
                        >
                            <option
                                v-for="option in modelProfiles"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }} ({{ option.provider }} /
                                {{ profileModel(option) }})
                            </option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.model_profile_id" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="message">{{
                                $t("genie.step_message")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea
                            v-model="form.message"
                            :error="form.errors.message !== undefined"
                            id="message"
                            class="w-full"
                            rows="12"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.message" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="
                            ruleType['name'] === 'STRATEGY' &&
                            ruleSubType['name'] !== 'BRIEFINGS_MULTIPLE' &&
                            ruleSubType['name'] !== 'IDEAS_INITIAL' &&
                            ruleSubType['name'] !== 'DRAFTS_INITIAL'
                        "
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="output">{{
                                $t("genie.step_output")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.output[0]"
                            :error="form.errors.output !== undefined"
                            id="output"
                            required
                        >
                            <template v-for="output in outputFields">
                                <option :value="output.code_name">
                                    {{ output.code_name }} - {{ output.name }}
                                </option>
                            </template>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.output" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="
                            (ruleType['name'] === 'IDEAS' ||
                                ruleType['name'] === 'DRAFTS' ||
                                ruleType['name'] === 'PRE_POSTS' ||
                                ruleSubType['name'] === 'BRIEFINGS_MULTIPLE') &&
                            ruleSubType['name'] !== 'IDEAS_INITIAL' &&
                            ruleSubType['name'] !== 'DRAFTS_INITIAL' &&
                            ruleSubType['name'] !== 'PRE_POSTS_INITIAL'
                        "
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="output">{{
                                $t("genie.step_output")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <TableCell class="">
                            <template
                                v-if="ruleType['name'] === 'STRATEGY'"
                                v-for="(strategy_output, index) in outputFields"
                                :key="strategy_output.code_name"
                            >
                                <Flex class="py-sm">
                                    <Checkbox
                                        v-model:checked="form.output"
                                        :value="strategy_output.code_name"
                                    />
                                    {{ strategy_output.name }}
                                </Flex>
                            </template>
                            <template
                                v-else-if="ruleType['name'] === 'IDEAS'"
                                v-for="(idea_output, index) in outputIdeaFields"
                                :key="idea_output"
                            >
                                <Flex class="py-sm">
                                    <Checkbox
                                        v-model:checked="form.output"
                                        :value="idea_output"
                                    />
                                    {{ idea_output }}
                                </Flex>
                            </template>
                            <template
                                v-else-if="ruleType['name'] === 'DRAFTS'"
                                v-for="(
                                    draft_output, index
                                ) in outputDraftFields"
                                :key="draft_output"
                            >
                                <Flex class="py-sm">
                                    <Checkbox
                                        v-model:checked="form.output"
                                        :value="draft_output"
                                    />
                                    {{ draft_output }}
                                </Flex>
                            </template>
                            <template
                                v-else-if="ruleType['name'] === 'PRE_POSTS'"
                                v-for="(
                                    pre_post_output, index
                                ) in outputPrePostFields"
                                :key="pre_post_output"
                            >
                                <Flex class="py-sm">
                                    <Checkbox
                                        v-model:checked="form.output"
                                        :value="pre_post_output"
                                    />
                                    {{ pre_post_output }}
                                </Flex>
                            </template>
                        </TableCell>

                        <template #footer>
                            <Error :message="form.errors.output" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="response_format">{{
                                $t("genie.step_response_format")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Select
                            v-model="form.response_format"
                            :error="form.errors.response_format !== undefined"
                            id="response_format"
                            :disabled="isMultiple.valueOf()"
                            required
                        >
                            <option value="text">text</option>
                            <option value="json_schema">json_schema</option>
                        </Select>

                        <template #footer>
                            <Error :message="form.errors.response_format" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field">
                        <template #title>
                            <label for="requires_review">{{
                                $t("genie.step_requires_review")
                            }}</label>
                        </template>

                        <Switch
                            v-model="form.requires_review"
                            id="requires_review"
                        />

                        <template #footer>
                            <Error :message="form.errors.requires_review" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="form.requires_review"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="message">{{
                                $t("genie.step_review_message_user")
                            }}</label>
                            <LabelSuffix :danger="true">*</LabelSuffix>
                        </template>

                        <Textarea
                            v-model="form.review_message_user"
                            :error="
                                form.errors.review_message_user !== undefined
                            "
                            id="review_message_user"
                            class="w-full"
                            rows="12"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.review_message_user" />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="form.requires_review"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="message">{{
                                $t("genie.step_review_message_system")
                            }}</label>
                        </template>

                        <Textarea
                            v-model="form.review_message_system"
                            :error="
                                form.errors.review_message_system !== undefined
                            "
                            id="review_message_system"
                            class="w-full"
                            rows="12"
                        />

                        <template #footer>
                            <Error
                                :message="form.errors.review_message_system"
                            />
                        </template>
                    </VerticalGroup>

                    <VerticalGroup class="form-field">
                        <template #title>
                            <label for="optional">{{
                                $t("genie.step_optional")
                            }}</label>
                        </template>

                        <Switch v-model="form.optional" id="optional" />

                        <template #footer>
                            <Error :message="form.errors.optional" />
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
                            @click="deleteStep"
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
