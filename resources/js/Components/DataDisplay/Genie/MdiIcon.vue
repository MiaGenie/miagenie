<script setup>
/**
 * Draws a Material Design Icon from its stored SVG path.
 *
 * Every MDI icon is a single path on a 24×24 grid, so the path is all that is needed: the icon
 * set itself is never loaded on the pages that show an icon, only by the admin IconPicker.
 */
defineProps({
    path: {
        type: String,
        required: true,
    },
});
</script>
<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        aria-hidden="true"
        class="w-5 h-5"
    >
        <path :d="path"/>
    </svg>
</template>
