<script setup>
defineProps({
    withPadding: {
        type: Boolean,
        default: true
    }
})
</script>
<template>
    <div class="w-full border border-gray-100 rounded-lg group-hover:border-primary-100 transition-colors ease-in-out duration-200">

        <slot/>

    </div>
</template>
