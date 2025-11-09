<script setup>
import {ref} from "vue";
import ChevronDown from "@/Icons/ChevronDown.vue";
import ChevronRight from "@/Icons/ChevronRight.vue";

defineProps({
    colorClass: {
        type: String,
        default: 'bg-gray-50 border-gray-200 hover:bg-gray-100'
    }
})
const state = ref(false);
</script>
<template>
    <div :class="colorClass" class="border rounded-lg transition-colors ease-in-out duration-200 mt-xs italic">
        <div @click="state = !state"
             role="button"
             class="cursor-pointer p-xs flex justify-start items-center">
            <div>
                <ChevronRight class="transform transition-transform ease-in-out duration-300 mr-2"
                             :class="{'rotate-90': state}"/>
            </div>
            <div>
                <div class="font-medium">
                    <slot name="title"/>
                </div>

               <div v-if="$slots.description" class="text-gray-500">
                   <slot name="description"/>
               </div>
            </div>
        </div>

        <div v-if="state" class="bg-white px-md py-md w-full flex justify-start mt-xs sm:mt-0 rounded-b-lg">
            <slot/>
        </div>
    </div>
</template>
