<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject} from "vue";
import {keys} from "lodash";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VersionHeader from "@/Components/DataDisplay/Genie/VersionHeader.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import Flex from "@/Components/Layout/Flex.vue";
import EditorClassic from "@/Components/Package/EditorClassic.vue";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    version: {
        type: Object,
        required: true
    },
    record: {
        type: Object,
        required: true
    },
    groupType: {
        type: String
    },
    locale: {
        type: Object,
        required: true
    }
})

const confirmation = inject('confirmation');
const {onError} = useRouter();

const form = useForm({
    name: props.record.name,
    description: props.record.description,
    sub_description: props.record.sub_description,
    display_faq_title: props.record.display_faq_title,
    display_faq_text: props.record.display_faq_text
});


const submit = () => {
    form.put(route('genie.admin.versions.fields.update-translation-field', {
        version: props.version.id,
        field: props.record.id,
        locale: props.locale.long,
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, update);
        },
        preserveState: (page) => {
            return keys(page.props.errors).length > 0;
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
    router.get(
        route(
        'genie.admin.versions.fields.index-translate',
        {version: props.version.id}
        ),
        {group_type: props.groupType}
    );
}

</script>
<template>

    <Head :title="$t('genie.translate_field')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.translate_field') + ' - ' + locale.english + ' (' + locale.long + ')'"/>

        <VersionHeader />

        <div class="row-px">
            <form
                method="post"
                @submit.prevent="submit"
            >
                <Panel>
                    <template #title>{{ record.name }}</template>


                    <VerticalGroup class="form-field mt-lg">
                        <template #title>
                            <label for="name">{{ $t("general.name") }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form.name"
                            type="text"
                            id="name"
                            required
                        />

                        <template #footer>
                            <Error :message="form.errors.name"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="props.record.description !== ''"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="description">{{ $t("genie.description") }}</label>
                        </template>

                        <Textarea
                            v-model="form.description"
                            :error="form.errors.description !== undefined"
                            id="description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="props.record.sub_description !== ''"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="sub_description">{{ $t("genie.sub_description") }}</label>
                        </template>

                        <Textarea
                            v-model="form.sub_description"
                            :error="form.errors.sub_description !== undefined"
                            id="sub_description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            :required=true
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.sub_description"/>
                        </template>
                    </VerticalGroup>

                    <VerticalGroup
                        v-if="props.record.display_faq_title !== ''"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="display_faq_title">{{ $t("genie.display_faq_title") }}</label>
                        </template>

                        <Textarea
                            v-model="form.display_faq_title"
                            :error="form.errors.display_faq_title !== undefined"
                            id="display_faq_title"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.display_faq_title"/>
                        </template>
                    </VerticalGroup>


                    <VerticalGroup
                        v-if="props.record.display_faq_text !== ''"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="display_faq_text">{{ $t("genie.display_faq_text") }}</label>
                        </template>

                        <EditorClassic
                            :value="form.display_faq_text"
                            @update="form.display_faq_text = $event"
                            :error="form.errors.display_faq_text !== undefined"
                            id="display_faq_text"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.display_faq_text"/>
                        </template>
                    </VerticalGroup>


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
                            {{ $t("general.update") }}
                            <template #icon>
                                <Save/>
                            </template>
                        </PrimaryButton>

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
