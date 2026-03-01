<script setup>
import ListGroup from "@/Components/DataDisplay/ListGroup.vue";
import ListItem from "@/Components/DataDisplay/ListItem.vue";

defineProps({
    payment_method: {
        type: Object,
        required: true,
    }
})
</script>
<template>
    <ListGroup v-if="payment_method.type">
        <ListItem>
            <span class="font-bold">{{ $t('enterprise-general.type') }}</span>: <span class="capitalize">
            {{ payment_method.type }}</span>
        </ListItem>

        <ListItem v-if="payment_method.card_brand">
            <span class="font-bold">{{ $t('enterprise-util.brand') }}</span>: <span class="capitalize">{{ payment_method.card_brand }}</span>
        </ListItem>

        <ListItem v-if="payment_method.card_last_four">
            <span class="font-bold">{{ $t('enterprise-subscription.ending_in') }}</span>: {{ payment_method.card_last_four }}
        </ListItem>

        <ListItem v-if="payment_method.card_expires">
            <span class="font-bold">{{ $t('enterprise-subscription.expires_on') }}</span>: {{ payment_method.card_expires }}
        </ListItem>
    </ListGroup>
</template>
