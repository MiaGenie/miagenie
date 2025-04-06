<script setup>
import CheckIcon from "@/Icons/Check.vue"
defineProps({
    active: {
        type: Boolean,
        default: false
    }
})

defineEmits(['click'])
</script>
<template>
    <div class="w-full relative cursor-pointer box-border">
        <slot></slot>
        <div @click="$emit('click')" class="absolute flex items-center justify-center top-0 right-0 mr-md mt-md w-5 h-5 rounded-full border border-white" :class="{'bg-primary-500': active, 'bg-black bg-opacity-20': !active}">
            <CheckIcon
                v-if="active"
                class="text-white"
            />
        </div>
    </div>
</template>
