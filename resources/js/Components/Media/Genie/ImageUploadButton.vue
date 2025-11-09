<script setup>
import {ref} from "vue";
import useNotifications from "@/Composables/useNotifications";
import {clone, cloneDeep} from "lodash";
import Flex from "@/Components/Layout/Flex.vue";
import Plus from "@/Icons/Plus.vue";
import X from "@/Icons/X.vue";
import PureButton from "@/Components/Button/PureButton.vue";
import {useI18n} from "vue-i18n";
import OneImage from "@/Components/ProviderGallery/Instagram/OneImage.vue";

const {t: $t} = useI18n()

const props = defineProps({
    withPreloader: {
        type: Boolean,
        default: true
    },
    withPreview: {
        type: Boolean,
        default: true
    },
    fieldName: {
        type: String,
        required: true
    },
    caption: {
        type: String
    },
    maxSize: {
        type: Number,
        default: 1048576
    },
    maxHeight: {
        type: Number,
        default: 1000
    },
    maxWidth: {
        type: Number,
        default: 1000
    },
    modelValue: {
    },
    error: {
    }
})

const emit = defineEmits(['update:modelValue']);
const {notify} = useNotifications();
const initialValue = cloneDeep(props.modelValue);
const input = ref(null);

const mimeTypes = [
    'image/jpg',
    'image/jpeg',
    'image/png'
];

const previewImage = ref(initialValue ? clone(initialValue.path) : '') ;

const filterFiles = (files) => {
    return Array.from(files).filter((file) => {
        return mimeTypes.includes(file.type);
    });
}

const browse = () => {
    input.value.value = null;
    input.value.click();
}

const reset = () => {
    if (previewImage.value === initialValue?.path) {
        previewImage.value = null;
        emit('update:modelValue', null);
    } else {
        previewImage.value = initialValue?.path;
        emit('update:modelValue', initialValue);
    }
}

const validateType = (files) => {
    return files === Array.from(files).filter((file) => {
        return mimeTypes.includes(file.type);
    });
}

const validateSize = (files) => {
    return Array.from(files).find((file) => {
        return file.size > props.maxSize;
    });
}

const validateDimensions = (event) => {
    let image = new Image();
    image.src = event.target.result;
    return image.width > props.maxWidth || image.height > props.maxHeight;
}

const formatBytes = (bytes, decimals = 0) => {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KB', 'MB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

const changeValue = (event) => {
    if (validateType(event.target.files)) {
        notify('error', $t(`genie.image_format_not_supported`));
        return;
    }
    if (validateSize(event.target.files)) {
        notify('error', $t(`genie.image_max_size`, {max : formatBytes(props.maxSize)}));
        return;
    }
    const file = event.target.files[0] ?? null;
    if (file) {
        let reader = new FileReader();
        reader.onload = (e) => {
            if (validateDimensions(e)) {
                notify('error', $t(`genie.image_max_dimensions`, {width : props.maxWidth, height : props.maxHeight}));
                return;
            }
            previewImage.value = e.target.result;
            emit('update:modelValue', {file: file});
        }
        reader.readAsDataURL(file);
    }
}

</script>
<template>

    <Flex class="mt-sm" :col="true" :responsive="false">

        <Flex :responsive="false" class="mt-xs">

            <input
                ref="input"
                type="file"
                :accept="mimeTypes.join(',')"
                class="hidden"
                @change="changeValue"
            />

            <PureButton
                v-if="!previewImage"
                @click="browse"
            >
                <template #icon>
                    <Plus/>
                </template>
                {{ $t('genie.load_image') }}
            </PureButton>

            <div
                v-if="withPreview && previewImage"
                class="h-auto p-2 border rounded-md"
            >
                <div class="w-full h-full inset-0 relative">
                    <img
                        :src="previewImage"
                        class="max-w-64 max-h-64"
                        alt="preview"
                    />
                </div>
            </div>

            <PureButton
                v-if="previewImage"
                :destructive="true"
                @click="reset"
            >
                <template #icon>
                    <X/>
                </template>
            </PureButton>

        </Flex>

        <Flex :col="true" :responsive="false" class="mt-sm">
            <span class="text-md">{{ caption }}</span>
            <span class="text-sm italic text-gray-600">{{ 'Max: ' + props.maxWidth + ' x ' + maxHeight + ' px - ' + formatBytes(maxSize)}}</span>

        </Flex>

    </Flex>

</template>
