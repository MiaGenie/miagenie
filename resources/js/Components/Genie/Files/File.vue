<script setup>
import {computed, defineAsyncComponent, inject} from "vue";
import ExclamationCircleIcon from "@/Icons/ExclamationCircle.vue"
import ArrowDownTray from "@/Icons/Genie/ArrowDownTray.vue"

const props = defineProps({
    media: {
        type: Object,
        required: true
    },
    imgHeight: {
        type: String,
        default: 'full'
    },
    imgWidthFull: {
        type: Boolean,
        default: true
    },
    showCaption: {
        type: Boolean,
        default: true
    },
    showDownload: {
        type: Boolean,
        default: true
    },
    mimeTypes: {
        type: Object,
        default: {}
    }
})

const mimeTypes = inject("mimeTypes");

const fileTypeIcon = computed(() => {
    if (Object.keys(mimeTypes).length > 0) {
        return defineAsyncComponent(
            () => import(
                `../../../Icons/Genie/FileTypes/${mimeTypes[props.media.mime_type].split(" ")[0]}.vue`
                )
        )
    }
})

const fileSize = computed(() => {
    let units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
    let l = 0, n = parseInt(props.media.size, 10) || 0;
    while(n >= 1024 && ++l){
        n = n/1024;
    }
    return(n.toFixed(n < 10 && l > 0 ? 1 : 0) + ' ' + units[l]);
})


const imgHeightClass = computed(() => {
    return {
        'full': 'h-full',
        'sm': 'h-20'
    }[props.imgHeight]
})
</script>
<template>
    <figure :class="{'border border-gray-200 rounded-md p-xs bg-stone-500': showCaption}" class="group relative">
        <slot/>
        <div
            class="relative flex rounded"
            :class="{'border border-red-500 p-md': media.hasOwnProperty('error')}"
        >
            <component :is="fileTypeIcon"></component>
            <div class="mt-xs text-sm">{{ media.name }}</div>

            <div v-if="media.hasOwnProperty('error')" class="text-center">
                <ExclamationCircleIcon class="w-8 h-8 mx-auto text-red-500"/>
                <div class="mt-xs">{{ media.name }}</div>
                <div class="mt-xs text-red-500">
                    {{  media.error ? media.error : $t('media.error_uploading_media')  }}
                </div>
            </div>
        </div>
        <template v-if="showCaption">
            <figcaption class="mt-xs px-1 text-xs flex justify-between items-center gap-2">

                {{ fileSize }}
                <div @click="$emit('download')">
                    <ArrowDownTray />
                </div>
            </figcaption>
        </template>
   </figure>
</template>
