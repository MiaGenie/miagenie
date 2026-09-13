<script setup>
import {computed, inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {isEqual} from "lodash";
import useNotifications from "@/Composables/useNotifications";
import useTranslations from "@/Composables/Genie/useTranslations";
import DialogModal from "@/Components/Modal/DialogModal.vue";
import PrimaryButton from "@/Components/Button/PrimaryButton.vue";
import SecondaryButton from "@/Components/Button/SecondaryButton.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import Input from "@/Components/Form/Input.vue";
import Textarea from "@/Components/Form/Textarea.vue";
import VerticalGroup from "@/Components/Layout/VerticalGroup.vue";
import Flex from "@/Components/Layout/Flex.vue";
import Preloader from "@/Components/Util/Preloader.vue";
import Sparkles from "@/Icons/Sparkles.vue";
import CircleLoading from "@/Icons/CircleLoading.vue";
import Save from "@/Icons/Genie/Save.vue";
import X from "@/Icons/X.vue";

const {t: $t} = useI18n();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        required: true,
    },
    id: {
        type: String,
        required: true,
    },
    attribute: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);

const confirmation = inject('confirmation');
const {notify} = useNotifications();
const {load, save, translate} = useTranslations();

const isLoading = ref(false);
const isSaving = ref(false);
const sourceLocale = ref('');
const source = ref('');
const locales = ref([]);
const translations = ref({});
const saved = ref({});
const pending = ref([]);

const targetLocales = computed(() => locales.value.filter((locale) => locale.long !== sourceLocale.value));
const isDirty = computed(() => !isEqual(translations.value, saved.value));
const isTranslating = computed(() => pending.value.length > 0);
const isBusy = computed(() => isLoading.value || isSaving.value || isTranslating.value);
/**
 * A list attribute (a sub-field's allowed values) is translated value by value, in order.
 */
const isList = computed(() => Array.isArray(source.value));
const hasSource = computed(() => isList.value ? source.value.length > 0 : source.value.trim() !== '');

const itemOf = (locale, index) => translations.value[locale]?.[index] ?? '';

const setItem = (locale, index, value) => {
    const items = [...(translations.value[locale] ?? [])];
    while (items.length < source.value.length) {
        items.push('');
    }
    items[index] = value;
    translations.value = {...translations.value, [locale]: items};
};

const applyTranslations = (data) => {
    translations.value = {...data};
    saved.value = {...data};
};

const fetchTranslations = () => {
    isLoading.value = true;

    load(props.type, props.id, props.attribute)
        .then((data) => {
            sourceLocale.value = data.source_locale;
            source.value = data.source ?? '';
            locales.value = data.locales;
            applyTranslations(data.translations);
        })
        .catch(() => emit('close'))
        .finally(() => {
            isLoading.value = false;
        });
};

watch(() => props.show, (shown) => {
    if (shown) {
        fetchTranslations();
    }
});

const translateTo = (to) => {
    pending.value = [...to];

    translate(props.type, props.id, props.attribute, to)
        .then((data) => {
            // The endpoint has already stored the AI text; keep any manual edit on the other rows.
            const edited = {...translations.value};
            to.forEach((locale) => {
                edited[locale] = data.translations[locale];
            });
            translations.value = edited;
            saved.value = {...saved.value, ...Object.fromEntries(to.map((locale) => [locale, data.translations[locale]]))};

            notify('success', data.message);
        })
        .catch(() => {})
        .finally(() => {
            pending.value = [];
        });
};

const translateAll = () => translateTo(targetLocales.value.map((locale) => locale.long));

const isPending = (locale) => pending.value.includes(locale);

const submit = () => {
    isSaving.value = true;

    save(props.type, props.id, props.attribute, translations.value)
        .then((data) => {
            applyTranslations(data.translations);
            notify('success', data.message);
            emit('close');
        })
        .catch(() => {})
        .finally(() => {
            isSaving.value = false;
        });
};

const attemptClose = () => {
    if (!isDirty.value) {
        emit('close');
        return;
    }

    confirmation()
        .title($t('genie.are_you_sure'))
        .description($t('genie.unsaved_will_lost'))
        .btnConfirmName($t('genie.discard'))
        .onConfirm(() => {
            emit('close');
        })
        .show();
};
</script>
<template>
    <DialogModal
        :show="show"
        max-width="2xl"
        :scrollable-body="true"
        :closeable="!isBusy"
        @close="attemptClose"
    >
        <template #header>
            <Flex class="items-center gap-md" :responsive="false">
                <span>{{ $t('genie.translations') }}<template v-if="label"> — {{ label }}</template></span>

                <PureButton
                    :disabled="isBusy || !hasSource"
                    v-tooltip="$t('genie.translate_all')"
                    @click="translateAll"
                >
                    <template #icon>
                        <CircleLoading v-if="isTranslating" class="animate-spin"/>
                        <Sparkles v-else/>
                    </template>
                    {{ $t('genie.translate_all') }}
                </PureButton>
            </Flex>
        </template>

        <template #body>
            <Preloader v-if="isLoading" class="py-lg"/>

            <template v-else>
                <VerticalGroup class="form-field">
                    <template #title>
                        <label>{{ $t('genie.translation_source', {locale: sourceLocale}) }}</label>
                    </template>

                    <ul v-if="isList" class="list-disc pl-lg text-gray-700">
                        <li v-for="(item, index) in source" :key="index">{{ item }}</li>
                    </ul>

                    <Textarea
                        v-else
                        :model-value="source"
                        class="w-full bg-gray-50"
                        rows="4"
                        readonly
                    />
                </VerticalGroup>

                <p class="text-sm text-gray-500 mt-sm">{{ $t('genie.translation_ai_hint') }}</p>

                <VerticalGroup
                    v-for="locale in targetLocales"
                    :key="locale.long"
                    class="form-field mt-lg"
                >
                    <template #title>
                        <Flex class="items-center justify-between w-full" :responsive="false">
                            <label :for="`translation_${locale.long}`">
                                {{ locale.english }} ({{ locale.long }})
                            </label>

                            <PureButton
                                :disabled="isBusy || !hasSource"
                                v-tooltip="$t('genie.translate_to', {locale: locale.english})"
                                @click="translateTo([locale.long])"
                            >
                                <template #icon>
                                    <CircleLoading v-if="isPending(locale.long)" class="animate-spin"/>
                                    <Sparkles v-else/>
                                </template>
                            </PureButton>
                        </Flex>
                    </template>

                    <Flex v-if="isList" :col="true" class="gap-xs w-full">
                        <Flex
                            v-for="(item, index) in source"
                            :key="index"
                            class="items-center gap-sm w-full"
                            :responsive="false"
                        >
                            <span class="w-1/3 text-sm text-gray-500 truncate" :title="item">{{ item }}</span>

                            <Input
                                :model-value="itemOf(locale.long, index)"
                                @update:model-value="setItem(locale.long, index, $event)"
                                :id="`translation_${locale.long}_${index}`"
                                :disabled="isPending(locale.long)"
                                type="text"
                                class="w-2/3"
                            />
                        </Flex>
                    </Flex>

                    <Textarea
                        v-else
                        v-model="translations[locale.long]"
                        :id="`translation_${locale.long}`"
                        :disabled="isPending(locale.long)"
                        class="w-full"
                        rows="4"
                    />
                </VerticalGroup>
            </template>
        </template>

        <template #footer>
            <Flex class="gap-6" :responsive="false">
                <PrimaryButton
                    :is-loading="isSaving"
                    :disabled="isBusy || !isDirty"
                    @click="submit"
                >
                    {{ $t('general.update') }}
                    <template #icon>
                        <Save/>
                    </template>
                </PrimaryButton>

                <SecondaryButton
                    :disabled="isBusy"
                    @click="attemptClose"
                >
                    {{ $t('general.close') }}
                    <template #icon>
                        <X/>
                    </template>
                </SecondaryButton>
            </Flex>
        </template>
    </DialogModal>
</template>
