<script setup>
import {computed} from "vue";
import MdiIcon from "@/Components/DataDisplay/Genie/MdiIcon.vue";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    /**
     * The SVG path of the field's icon, drawn beside the title when the field has one.
     */
    icon: {
        type: String,
        default: '',
    },
    /**
     * Cycles the header accent so neighbouring sections stay visually distinct without the
     * component needing to know which field it is showing. A field with a tone of its own
     * ignores it.
     */
    accent: {
        type: Number,
        default: 0,
    },
    index: {
        type: Number,
        default: 0,
    },
    edited: {
        type: Boolean,
        default: false,
    },
    /**
     * The field's StrategySection (app/Enums/StrategySection.php): `card`, `feature` or `compact`.
     */
    variant: {
        type: String,
        default: 'card',
    },
    /**
     * The field's StrategyStyle tokens, space separated.
     */
    styles: {
        type: String,
        default: '',
    }
});

const accents = [
    'strategy-tone-primary',
    'strategy-tone-miagenie',
    'strategy-tone-cyan',
    'strategy-tone-orange',
];

const tokens = computed(() => props.styles.split(/\s+/).filter(Boolean));

const token = (prefix) => tokens.value.find((entry) => entry.startsWith(prefix)) ?? '';

/**
 * A tone the field asks for colours the whole section. Without one, only the header takes the
 * rotating accent and the content keeps the page's own, as it always has.
 */
const tone = computed(() => token('strategy-tone-'));

const headerTone = computed(() => tone.value ? '' : accents[props.accent % accents.length]);

const emphasis = computed(() => token('strategy-emphasis-'));

const animation = computed(() => token('strategy-animate-'));

const isCompact = computed(() => props.variant === 'compact');

const isFeature = computed(() => props.variant === 'feature');

const number = computed(() => String(props.index + 1).padStart(2, '0'));
</script>
<template>
    <section
        :class="[
            'scroll-mt-24 overflow-hidden rounded-2xl border border-stone-600 bg-white shadow-mix',
            tone,
            animation,
        ]"
    >
        <header
            :class="[
                'flex items-center justify-between gap-sm px-lg',
                headerTone,
                isCompact
                    ? 'border-b border-l-4 border-b-stone-600 border-l-(--strategy-accent) py-sm'
                    : 'bg-linear-to-r from-(--strategy-accent) to-(--strategy-accent-strong)',
                isFeature ? 'py-xl' : '',
                !isCompact && !isFeature ? 'py-md' : '',
            ]"
        >
            <div class="flex min-w-0 items-center gap-sm">
                <span
                    v-if="icon"
                    :class="[
                        'flex shrink-0 items-center justify-center rounded-xl',
                        isCompact ? 'h-8 w-8 bg-(--strategy-accent-soft) text-(--strategy-accent)' : 'bg-white/15 text-white',
                        isFeature ? 'h-14 w-14' : '',
                        !isCompact && !isFeature ? 'h-10 w-10' : '',
                    ]"
                >
                    <MdiIcon :path="icon" :class="isFeature ? 'h-8! w-8!' : (isCompact ? 'h-5! w-5!' : 'h-6! w-6!')"/>
                </span>

                <div class="min-w-0">
                    <p
                        :class="[
                            'text-[11px] font-bold uppercase tracking-wider',
                            isCompact ? 'text-(--strategy-accent)' : 'text-white/70',
                        ]"
                    >
                        {{ number }}
                    </p>
                    <h2
                        :class="[
                            'truncate font-title font-bold',
                            isCompact ? 'text-base text-black' : 'text-white',
                            isFeature ? 'text-2xl' : '',
                            !isCompact && !isFeature ? 'text-lg' : '',
                        ]"
                    >
                        {{ title }}
                    </h2>
                    <p v-if="isFeature && description" class="mt-xs mb-0 text-sm text-white/80">{{ description }}</p>
                </div>
            </div>

            <span
                v-if="edited"
                :class="[
                    'shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold',
                    isCompact ? 'bg-(--strategy-accent-soft) text-(--strategy-accent)' : 'bg-white/20 text-white',
                ]"
            >
                <slot name="badge">edited</slot>
            </span>
        </header>

        <div :class="['px-lg py-lg', emphasis]">
            <p v-if="description && !isFeature" class="mb-md text-sm text-gray-500">{{ description }}</p>

            <slot/>
        </div>
    </section>
</template>
