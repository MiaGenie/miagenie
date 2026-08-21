<script setup>
defineProps({
    forceFullWidth: {
        type: Boolean,
        default: false,
    },
})
</script>
<template>
    <div>
        <div :class="{'w-full': forceFullWidth,  'form-field': !forceFullWidth}" class="flex justify-between items-center">
            <div class="w-full flex flex-col sm:items-center sm:justify-between">
                <div v-if="$slots.title || $slots.description" class="flex flex-col w-full">
                    <div v-if="$slots.title" class="title">
                        <slot name="title"/>
                    </div>

                    <div v-if="$slots.description" class="text-gray-500 italic">
                        <slot name="description"/>
                    </div>
                </div>

                <div v-if="$slots.default" class="w-full flex">
                    <slot/>
                </div>
            </div>
        </div>
    </div>
</template>
