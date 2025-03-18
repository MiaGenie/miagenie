import NProgress from 'nprogress'
import {computed, nextTick, ref, watch} from "vue";
import {useI18n} from "vue-i18n";
import {debounce} from "lodash";
import useNotifications from "@/Composables/useNotifications";
import {router} from "@inertiajs/vue3";

const useFile = (routeName = 'genie.admin.files.fetchUploads', routeParams = {}) => {
    const {t: $t} = useI18n();
    const {notify} = useNotifications();

    const isLoaded = ref(false);
    const isDeleting = ref(false);
    const page = ref(1);
    const items = ref([]);
    const endlessPagination = ref(null);
    const keyword = ref('');

    const selected = ref([]);
    const toggleSelect = (media) => {
        const index = selected.value.findIndex(item => item.id === media.id);

        if (index < 0 && !media.hasOwnProperty('error')) {
            selected.value.push(media);
        }

        if (index >= 0) {
            selected.value.splice(index, 1);
        }
    }

    const deselectAll = () => {
        selected.value = [];
    }

    const isSelected = (media) => {
        const index = selected.value.findIndex(item => item.id === media.id);

        return index !== -1;
    }

    const fetchItems = (appendResult = true) => {
        if (!page.value) {
            return;
        }

        NProgress.start();

        axios.get(route(routeName, routeParams), {
            params: {
                page: page.value,
                keyword: keyword.value
            }
        }).then(function (response) {
            const nextLink = response.data.links.next;

            if (nextLink) {
                page.value = response.data.links.next.split('?page=')[1];
            }

            if (!nextLink) {
                page.value = 0;
            }

            if (!appendResult) {
                items.value = response.data.data;
            }

            if (appendResult) {
                items.value = [...items.value, ...response.data.data];
            }
        }).catch(() => {
            notify('error', $t('media.error_retrieving_media'));
        }).finally(() => {
            NProgress.done();
            isLoaded.value = true;
        });
    }

    const removeItems = (ids) => {
        items.value = items.value.filter((item) => !ids.includes(item.id));
    }

    const deletePermanently = (items, callback) => {
        isDeleting.value = true;
        NProgress.start();

        axios.delete(route('genie.admin.files.delete', routeParams), {
            data: {
                items
            }
        }).then(() => {
            callback();
        }).catch(() => {
            notify('error', $t('media.error_deleting_media'));
        }).finally(() => {
            isDeleting.value = false;
            NProgress.done();
            NProgress.remove();
        })
    }

    const createObserver = () => {
        const observer = new IntersectionObserver((entries) => {
            const isIntersecting = entries[0].isIntersecting;

            if (isIntersecting) {
                fetchItems();
            }
        });

        nextTick(() => {
            observer.observe(endlessPagination.value);
        });
    }

    watch(keyword, debounce(() => {
        page.value = 1;
        fetchItems(false);
    }, 300));

    const download2 = async (item) => {
        try {
            const response = await axios.get(route('genie.admin.files.download', {
                file: item.id,
            }), {
                responseType: 'blob',
            });

            const fileURL = window.URL.createObjectURL(response.data);
            const a = document.createElement('a');
            a.href = fileURL;
            a.download = item.name;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(fileURL); // Clean up
        } catch (error) {
            console.error("Download failed:", error);
        }
    }

    const downloadFile = (item) => {

        axios.get(route('genie.admin.files.download', {
            file: item.id,
        }), {
            responseType: 'arraybuffer',
        }).then((response) => {
            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.download = item.name;
            document.body.appendChild(link);
            link.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(link);
        }).catch((error) => {
            console.error("Download failed:", error);
        })
    }

    return {
        isLoaded,
        isDeleting,
        keyword,
        page,
        items,
        endlessPagination,
        selected,
        deletePermanently,
        removeItems,
        createObserver,
        toggleSelect,
        deselectAll,
        isSelected,
        downloadFile
    }
}

export default useFile;
