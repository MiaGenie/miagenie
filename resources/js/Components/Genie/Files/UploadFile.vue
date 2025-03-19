<script setup>
import {computed, inject, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {nanoid} from 'nanoid'
import Masonry from "@/Components/Layout/Masonry.vue";
import File from "@/Components/Genie/Files/File.vue";
import FileSelectable from "@/Components/Genie/Files/FileSelectable.vue";
import Preloader from "@/Components/Util/Preloader.vue"
import DocumentIcon from "@/Icons/Document.vue"

const props = defineProps({
    maxSelection: {
        type: Number,
        default: 1,
    },
    selected: {
        type: Array,
        default: []
    },
    toggleSelect: {
        type: Function
    },
    isSelected: {
        type: Function
    },
    downloadFile: {
        type: Function
    },
    columns: {
        type: Number,
        default: 4,
    }
})

const {t: $t} = useI18n()
const mimeTypes = inject("mimeTypes");
const emit = defineEmits(['mediaSelect'])
const input = ref(null);
const dragEnter = ref(false);

const onDrop = (e) => {
    if (isLoading.value) {
        return;
    }

    dragEnter.value = false;

    const files = filterFiles(e.dataTransfer.files);

    if (files.length) {
        dispatch(files);
    }
}

const onBrowse = (e) => {
    const files = filterFiles(e.target.files);

    if (files.length) {
        input.value.value = null;
        dispatch(files);
    }
}

const filterFiles = (files) => {
    return Array.from(files).filter((file) => {
        return Object.keys(mimeTypes).includes(file.type);
    });
}

const isLoading = ref(false);
const pending = ref([]);
const completed = ref([]);
const active = ref({});

watch(active, () => {
    processJob();

    isLoading.value = Object.keys(active.value).length > 0;
});

const processJob = () => {
    if (active.value.handler) {
        active.value.handler();
    }
}

const addJob = (job) => {
    pending.value.push(job);

    if (Object.keys(active.value).length === 0) {
        startNextJob();
    }
}

const startNextJob = (media) => {
    if (Object.keys(active.value).length > 0) {
        addCompletedJob(active.value, media);

        if (props.toggleSelect) {
            props.toggleSelect(media);
        }
    }

    if (pending.value.length > 0) {
        setActiveJob(pending.value[0]);
        popCurrentJob();
    } else {
        setActiveJob({});
    }
}

const setActiveJob = (job) => {
    active.value = job;
}

const popCurrentJob = () => {
    pending.value.shift();
}

const addCompletedJob = (job, media) => {
    completed.value.push(Object.assign(job, {media}));
}

const dispatch = (files) => {
    files.forEach((file) => {
        addJob({
            id: nanoid(),
            handler: async () => {
                await uploadFile(file).then((media) => {
                    startNextJob(media);
                }).catch((error) => {
                    startNextJob({
                        name: file.name,
                        error: error.response.data.message
                    });
                });
            }
        })
    });
}

const uploadFile = (file) => {
    const formData = new FormData();
    formData.append("file", file);

    return new Promise((resolve, reject) => {
        axios.post(route('genie.admin.files.upload'), formData)
            .then(function (response) {
                resolve(response.data);
            })
            .catch(function (error) {
                reject(error);
            });
    });
}

const completedJobs = computed(() => {
    return completed.value.filter(() => true).reverse();
});

</script>
<template>
    <div @dragenter.prevent="dragEnter = !isLoading"
         @drop.prevent="onDrop"
         @dragover.prevent
         :class="{'border-gray-700 bg-white': !dragEnter, 'border-cyan-500 bg-cyan-50': dragEnter}"
         class="relative w-full flex items-center justify-center rounded-lg p-10 border-2 border-dashed transition-colors ease-in-out duration-200">
        <div class="relative flex flex-col justify-center">
            <div v-if="dragEnter"
                 @dragleave.prevent="dragEnter = false"
                 @dragover.prevent
                 class="w-full h-full absolute"></div>
            <DocumentIcon :class="{'text-stone-700': !dragEnter, 'text-cyan-500': dragEnter}"
                       class="!w-16 !h-16 mx-auto mb-xs transition-colors ease-in-out duration-200"/>
            <div class="text-center mb-1">{{ $t('media.drag_drop_files') }}
                <label for="browse"
                       class="cursor-pointer text-primary-500 hover:text-primary-700 active:text-primary-700 focus:outline-none focus:text-primary-700 transition-colors ease-in-out duration-200">
                    {{ $t('general.browse') }}
                </label>
            </div>
            <div class="text-sm text-gray-400 text-center">{{ Object.values(mimeTypes).join(', ') }}</div>
        </div>
        <Preloader v-if="isLoading" size="xl" class="rounded-lg"/>
    </div>

    <input
        ref="input"
        id="browse"
        type="file"
        :accept="Object.keys(mimeTypes).join(',')"
        multiple="multiple"
        class="hidden"
        @change="onBrowse"
    />

    <div v-if="completedJobs.length" class="mt-lg">
        <Masonry :items="completedJobs" :columns="columns">
            <template #default="{item}">
                <FileSelectable
                    :active="isSelected(item.media)"
                    @click="toggleSelect(item.media)"
                >
                    <File
                        :media="item.media"
                        v-bind="props"
                        @download="downloadFile(item.media)"
                    />
                </FileSelectable>
            </template>
        </Masonry>
    </div>
</template>
