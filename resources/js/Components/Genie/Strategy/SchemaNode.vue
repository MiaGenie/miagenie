<script setup>
import {computed} from "vue";
import {blockProps, resolveBlock} from "@/Composables/Genie/useSchemaNode.js";
import BulletsBlock from "@/Components/Genie/Strategy/Blocks/BulletsBlock.vue";
import CardsBlock from "@/Components/Genie/Strategy/Blocks/CardsBlock.vue";
import CheckboxBlock from "@/Components/Genie/Strategy/Blocks/CheckboxBlock.vue";
import ChoicesBlock from "@/Components/Genie/Strategy/Blocks/ChoicesBlock.vue";
import NumberedCardsBlock from "@/Components/Genie/Strategy/Blocks/NumberedCardsBlock.vue";
import StackBlock from "@/Components/Genie/Strategy/Blocks/StackBlock.vue";
import TextBlock from "@/Components/Genie/Strategy/Blocks/TextBlock.vue";

const props = defineProps(blockProps);

const emit = defineEmits(['update:modelValue']);

/**
 * One component per StrategyBlock value (app/Enums/StrategyBlock.php). A node is drawn with the
 * block its sub-field names when that block fits the schema, otherwise with the one the schema's
 * shape suggests; its style tokens land on the block's root.
 */
const blocks = {
    stack: StackBlock,
    cards: CardsBlock,
    choices: ChoicesBlock,
    bullets: BulletsBlock,
    numbered_cards: NumberedCardsBlock,
    checkbox: CheckboxBlock,
    text: TextBlock,
};

const block = computed(() => blocks[resolveBlock(props.schema, props.meta)]);
</script>
<template>
    <component
        :is="block"
        v-bind="props"
        :class="meta?.class"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
