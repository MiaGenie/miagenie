<script setup>
import useButtonSize from "@/Composables/useButtonSize"

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    size: {
        type: String,
        default: 'lg'
    },
    colorStyle: {
        type: String,
        default: ''
    },
    hiddenTextOnSmallScreen: {
        type: Boolean,
        default: false,
    }
});

const { sizeClass } = useButtonSize(props.size);
const btnStyle = () => {
    switch (props.colorStyle) {
        default:
            return  '';
        case 'strategy':
            return  'purple-500';
        case 'ideas':
            return  'yellow-500';
        case 'posts':
            return  'blue-500';
        case 'support':
            return  'green-500';
    }
}

</script>

<template>
    <button :type="type" :class="sizeClass" class="max-w-48 w-[47%] sm:w-[23%] relative inline-flex items-center bg-white text-gray-800 border border-gray-400 rounded-md font-medium text-xs tracking-widest rtl:tracking-normal hover:text-primary-500 hover:border-primary-500 active:text-primary-500 active:border-primary-800 focus:border-primary-800 focus:shadow-outline-indigo disabled:text-gray-500 disabled:cursor-not-allowed transition ease-in-out duration-200">
        <span v-if="$slots.icon" class="inline-flex"
                      :class="['text-' + btnStyle(), {'sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': $slots.default, 'mr-0 sm:mr-xs rtl:sm:mr-0 rtl:sm:ml-xs': hiddenTextOnSmallScreen, 'mr-xs rtl:mr-xs rtl:ml-xs': !hiddenTextOnSmallScreen && $slots.default}]">
            <slot name="icon"/>
        </span>

        <span v-if="$slots.default" class="w-full inline-flex justify-center items-center text-lg md:text-xl" :class="{'hidden sm:inline': hiddenTextOnSmallScreen}">
            <slot/>
        </span>
    </button>
</template>
