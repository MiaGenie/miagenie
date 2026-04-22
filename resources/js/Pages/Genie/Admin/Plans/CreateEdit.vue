<script setup>
import {Head, router, useForm} from '@inertiajs/vue3';
import {computed, inject} from "vue";
import {cloneDeep, keys} from "lodash";
import {useI18n} from "vue-i18n";
import useRouter from "@/Composables/useRouter";
import AdminLayout from "@/Layouts/Admin.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";
import Error from "@/Components/Form/Error.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Panel from "@/Components/Surface/Panel.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";
import Flex from "@/Components/Layout/Flex.vue";
import EditorClassic from "@/Components/Package/EditorClassic.vue";


defineOptions({layout: AdminLayout});

const {t: $t} = useI18n()

const props = defineProps({
    record: {
        type: Object
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
const isEdit = computed(() => props.record.id !== undefined);

const form = useForm(isEdit.value ? cloneDeep(props.record) : {
    description: props.record.description
});


const submit = () => {
    form.put(route('genie.admin.plans_info.store', {
        plan: props.record.id,
        locale: props.locale.long,
    }), {
        preserveScroll: true,
        onError: (errors) => {
            //onError(errors, update);
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

    <Head :title="$t('genie.payment_plan_description')"/>

    <div class="w-full mx-auto row-py">

        <PageHeader :title="$t('genie.payment_plan') + ' - ' + locale.english + ' (' + locale.long + ')'"/>

        <div class="row-px">
            <form
                method="post"
                @submit.prevent="submit"
            >
                <Panel>
                    <template #title>{{ record.name }}</template>

                    <VerticalGroup
                        class="form-field mt-lg"
                    >
                        <template #title>
                            <label for="description">{{ $t("genie.payment_plan_description") }}</label>
                        </template>

                        <EditorClassic
                            :value="form.description"
                            @update="form.description = $event"
                            :error="form.errors.description !== undefined"
                            id="description"
                            class="w-full placeholder:italic placeholder:text-sm"
                            rows="5"
                        />

                        <template #footer>
                            <Error :message="form.errors.description"/>
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
