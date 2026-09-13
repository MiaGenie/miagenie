import {find} from "lodash";
import {usePage} from "@inertiajs/vue3";
import {useI18n} from "vue-i18n";

const useIdeaLabels = (item) => {
    const {t: $t} = useI18n();

    const ideaStatusTypes = usePage().props.ideaStatusTypes;
    const postFormats = usePage().props.postFormats;
    const funnelStages = usePage().props.funnelStages;

    const ideaStatus = () => {
        return find(ideaStatusTypes, ['value', Number(item.status)]);
    }

    const formatLabel = () => {
        const format = find(postFormats, ['value', Number(item.post_format)]);

        return format ? $t('genie.post_format_' + format.title) : '';
    }

    const stageLabel = () => {
        const stage = find(funnelStages, ['value', Number(item.funnel_stage)]);

        return stage ? $t('genie.funnel_stage_' + stage.title) : '';
    }

    const statusBadge = () => {
        switch (ideaStatus().name) {
            case 'APPROVED':
                return 'success';
            case 'PENDING_REVIEW':
                return 'warning';
            case 'TRASH':
                return 'error';
            default:
                return 'neutral';
        }
    }

    return {
        ideaStatus,
        formatLabel,
        stageLabel,
        statusBadge
    }
}

export default useIdeaLabels;
