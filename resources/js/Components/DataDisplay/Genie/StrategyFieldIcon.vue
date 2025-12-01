<script setup>
import DigitalChannelIcon from "@/Components/DataDisplay/Genie/DigitalChannelIcon.vue";
import StrategyIcon from "@/Components/DataDisplay/Genie/StrategyIcon.vue";

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

const strategyFields = [
    'about',
    'brand_values',
    'goals_group_title',
    'content_dna_title',
    'big_idea',
    'tone_of_voice',
    'visual_guidelines',
    'content_pillars',
    'audience_journey_group_title',
    'audience_title',
    'competitor',
    'journey_title',
    'digital_channels_guidelines_title',
];

const isDigitalChannel = () => {
    return digitalChannels.includes(props.icon);
}

const isStrategyField = () => {
    return strategyFields.includes(props.icon);
}

</script>
<template>
    <DigitalChannelIcon v-if="isDigitalChannel()" :provider="icon"/>
    <StrategyIcon v-if="isStrategyField()" :provider="icon"/>
</template>
