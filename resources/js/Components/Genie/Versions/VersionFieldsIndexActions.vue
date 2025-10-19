<script setup>
import {router, usePage, Link} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";
import {find} from "lodash";
import useNotifications from "@/Composables/useNotifications.js";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import SectionTitle from "@/Components/DataDisplay/SectionTitle.vue";
import ChevronLeft from "@/Icons/ChevronLeft.vue";
import Plus from "@/Icons/Plus.vue";
import Save from "@/Icons/Genie/Save.vue";
import Sort from "@/Icons/Genie/Sort.vue";
import X from "@/Icons/X.vue";
import Translation from "@/Icons/Genie/Translation.vue";

const {t: $t} = useI18n();

const props = defineProps({
    fieldList: {
        type: Object,
    },
    filter: {
        type: Object
    },
    isLoading: {
        type: Boolean,
        default: false
    },
    editingPositions: {
        type: Boolean,
        default: false
    }
})

const {notify} = useNotifications();
const emit = defineEmits(['editing-positions', 'is-loading']);

const version = usePage().props.version;
const groupTypes = usePage().props.groupTypes;
const createField = () => {
    router.get(
        route(
            'genie.admin.versions.fields.create',
            {version: version.id}
        ),
        {group_type: Number(props.filter.group_type)}
    );
}

const backToVersions = () => {
    router.get(route('genie.admin.versions.index'));
}

const openTranslations = () => {
    router.get(route('genie.admin.versions.fields.index-translate',
        {
            version: version.id,
        }),
        {group_type: Number(props.filter.group_type)}
    );
}

const currentGroup = () => {
    return find(groupTypes, ['value', Number(props.filter.group_type)])
}

const currentPositions = () => {
    return props.fieldList.map( (item, index) => {

        return {
            id: item.id,
            position: index + 1
        }

    });
}

const updatePositions = () => {
    emit('is-loading', true);

    axios.post(route(
            'genie.admin.versions.fields.positions',
            {version: version.id}
        ),
        {positions: currentPositions()}
    )
    .then( (response) => {
        notify('success', response.data.message);
    })
    .catch( (error) => {
        notify('error', error);
    })
    .finally( () => {
        emit('is-loading', false);
    });
}

</script>
<template>
    <div class="flex flex-row items-center row-mt content-stretch gap-6">
        <div
            v-if="!editingPositions"
            class="flex items-center grow gap-6"
        >

            <SecondaryButton
                @click="backToVersions"
                :hiddenTextOnSmallScreen="true"
                size="sm"
            >
                <template #icon>
                    <ChevronLeft/>
                </template>
                {{ $t("genie.back") }}
            </SecondaryButton>


                <PrimaryButton
                    @click="createField"
                    :hiddenTextOnSmallScreen="true"
                    :disabled="isLoading"
                    :isLoading="isLoading"
                    size="sm"
                >
                    <template #icon>
                        <Plus/>
                    </template>
                    {{ $t('genie.create_field') }}
                </PrimaryButton>


        </div>
        <div
            v-if="filter.group_type"
            class="flex flex-row items-center justify-end grow gap-6"
        >

            <template v-if="editingPositions">
                <SectionTitle>
                    {{ $t('genie.reorder') + ' ' + $t('genie.version_group_type_' + currentGroup().title) }}
                </SectionTitle>

                <div class="flex flex-row items-center justify-end grow gap-6">
                    <SecondaryButton
                        @click="$emit('editing-positions', false)"
                        :hiddenTextOnSmallScreen="true"
                        size="sm"
                    >

                        <template #icon>
                            <X/>
                        </template>
                        {{ $t("genie.close") }}
                    </SecondaryButton>

                    <PrimaryButton
                        @click="updatePositions"
                        :hiddenTextOnSmallScreen="true"
                        :disabled="isLoading"
                        :isLoading="isLoading"
                        size="sm"
                    >

                        <template #icon>
                            <Save/>
                        </template>
                        {{ $t('genie.save') }}
                    </PrimaryButton>
                </div>

            </template>

            <SecondaryButton
                v-if="!editingPositions"
                @click="$emit('editing-positions', true)"
                :disabled="isLoading"
                :isLoading="isLoading"
                :hiddenTextOnSmallScreen="true"
                size="sm"
                class="justify-self-end"
            >

                <template #icon>
                    <Sort/>
                </template>
                {{ $t('genie.reorder') }}
            </SecondaryButton>

        </div>

        <SecondaryButton
            v-if="!editingPositions"
            @click="openTranslations"
            :disabled="isLoading"
            :isLoading="isLoading"
            :hiddenTextOnSmallScreen="true"
            size="sm"
            class="justify-self-end"
        >

            <template #icon>
                <Translation/>
            </template>
            {{ $t('genie.translations') }}
        </SecondaryButton>

    </div>
</template>