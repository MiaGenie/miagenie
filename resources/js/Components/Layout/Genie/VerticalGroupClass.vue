<script setup>
defineProps({
    forceFullWidth: {
        type: Boolean,
        default: false,
    },
    bodyClass: {
        type: String,
    },
    id: {
        type: String,
        default: null
    }
})
</script>
<template>
    <div :id="id ? id + '-title' : null" :class="bodyClass" class="mt-md">
        <div class="flex items-center pt-lg">
            <div class="flex flex-col sm:items-center sm:justify-between">
                <div v-if="$slots.title || $slots.description" class="flex flex-col">
                    <div v-if="$slots.title" class="font-bold font-title">
                        <slot name="title"/>
                    </div>

                    <div v-if="$slots.description" class="text-sm italic">
                        <slot name="description"/>
                    </div>
                </div>

                <div v-if="$slots.default" class="w-full flex mt-xs">
                    <slot/>
                </div>
            </div>
        </div>
        <div v-if="$slots.footer" class="mt-xs">
            <slot name="footer"/>
        </div>
    </div>
</template>
