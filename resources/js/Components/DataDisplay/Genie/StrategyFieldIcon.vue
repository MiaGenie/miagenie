<script setup>
import DigitalChannelIcon from "@/Components/DataDisplay/Genie/DigitalChannelIcon.vue";

const props = defineProps({
    field: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        default: '',
    }
})

const digitalChannels = [
    'twitter',
    'facebook',
    'instagram',
    'threads',
    'mastodon',
    'youtube',
    'pinterest',
    'linkedin',
    'tiktok',
    'bluesky'
];


const isDigitalChannel = () => {
    return digitalChannels.includes(props.icon);
}

</script>
<template>
    <DigitalChannelIcon v-if="isDigitalChannel()" :provider="icon"/>
</template>
