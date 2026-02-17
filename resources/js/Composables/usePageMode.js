import {computed} from "vue";
import {usePage} from "@inertiajs/vue3";

const usePageMode = () => {
    const mode = computed(() => {
        return usePage().props.mode;
    })

    const isCreate = computed(() => {
        return mode.value === 'create';
    })

    const isEdit = computed(() => {
        return mode.value === 'edit';
    })

    const isView = computed(() => {
        return mode.value === 'view';
    })

    return {
        isCreate,
        isEdit,
        isView
    }
}

export default usePageMode;
