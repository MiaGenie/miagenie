<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {inject, onBeforeMount} from "vue";
import {cloneDeep, keys} from "lodash";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import Input from "@/Components/Form/Input.vue";
import LabelSuffix from "@/Components/Form/LabelSuffix.vue";
import VersionHeader from "@/Components/DataDisplay/Genie/VersionHeader.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import Flex from "@/Components/Layout/Flex.vue";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    version: {
        type: Object,
        required: true
    },
    field: {
        type: Object,
        required: true
    },
    records: {
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

const form = useForm(cloneDeep(props.records.data));

onBeforeMount( () => {
    form.defaults(cloneDeep(form.data()));
})


const submit = () => {
    form.put(route('genie.admin.versions.fields.update-translation-options', {
        version: props.version.id,
        field: props.field.id,
        locale: props.locale.long,
    }), {
        preserveScroll: true,
        onError: (errors) => {
            onError(errors, 'update-translation-options');
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

        <PageHeader :title="$t('genie.translate_field_options') + ' - ' + locale.english + ' (' + locale.long + ')'"/>

        <VersionHeader />

        <div class="row-px">
            <form
                method="post"
                @submit.prevent="submit"
            >
                <Panel>
                    <template #title>{{ field.name }}</template>

                    <VerticalGroup
                        v-for="(record, key) in props.records.data"
                        :key="record.id"
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="name">{{ record.code_name }}
                                <LabelSuffix :danger="true">*</LabelSuffix>
                            </label>
                        </template>

                        <Input
                            v-model="form[key].name"
                            type="text"
                            id="name"
                            required
                        />

                        <Input
                            v-model="form[key].id"
                            type="text"
                            id="name"
                            hidden
                        />

                        <Input
                            v-model="form[key].code_name"
                            type="text"
                            id="name"
                            hidden
                        />

                        <template #footer>
                            <Error :message="form.errors[key]?.name"/>
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
