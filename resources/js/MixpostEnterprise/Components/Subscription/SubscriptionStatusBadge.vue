<script setup>
import Badge from "@/Components/DataDisplay/Badge.vue";

defineProps({
    status: {
        type: String,
        required: true
    }
})
</script>
<template>
    <Badge :variant="{
                'active': 'success',
                'trialing': 'info',
                'past_due': 'warning',
                'canceled': 'error',
                'generic': 'warning'
            }[status]">
        <span class="capitalize">{{ $t(`enterprise-subscription.status.${status}`) }}</span>
    </Badge>
</template>
