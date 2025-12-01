<script setup>
import {computed} from "vue";
import AboutIcon from "@/Icons/Genie/Strategy/About.vue";
import BigIdeaIcon from "@/Icons/Genie/Strategy/BigIdea.vue";
import BrandValuesIcon from "@/Icons/Genie/Strategy/BrandValues.vue";
import CompetitorIcon from "@/Icons/Genie/Strategy/Competitor.vue";
import DnaIcon from "@/Icons/Genie/Strategy/Dna.vue";
import GoalsIcon from "@/Icons/Genie/Strategy/Goals.vue";
import GlobeIcon from "@/Icons/Genie/Strategy/Globe.vue";
import FunnelIcon from "@/Icons/Genie/Strategy/Funnel.vue";
import PeopleIcon from "@/Icons/Genie/Strategy/People.vue";
import PillarsIcon from "@/Icons/Genie/Strategy/Pillars.vue";
import StarsIcon from "@/Icons/Genie/Strategy/Stars.vue";
import VoiceIcon from "@/Icons/Genie/Strategy/Voice.vue";
import VisualIcon from "@/Icons/Genie/Strategy/Visual.vue";

const props = defineProps({
    provider: {
        type: String,
        required: true
    }
})

const providers = {
    'about': AboutIcon,
    'brand_values': BrandValuesIcon,
    'goals_group_title': GoalsIcon,
    'content_dna_title': DnaIcon,
    'big_idea': BigIdeaIcon,
    'tone_of_voice': VoiceIcon,
    'visual_guidelines': VisualIcon,
    'content_pillars': PillarsIcon,
    'audience_journey_group_title': StarsIcon,
    'audience_title': PeopleIcon,
    'competitor': CompetitorIcon,
    'journey_title': FunnelIcon,
    'digital_channels_guidelines_title': GlobeIcon,
};

const component = computed(() => {
    return providers[props.provider];
});

</script>
<template>
    <component :is="component"/>
</template>
