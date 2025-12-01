import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import DefineOptions from 'unplugin-vue-define-options/vite'
import {resolve} from "path";

let ziggyPath = resolve('/vendor/tightenco/ziggy/dist/vue.m');

export default defineConfig(({command, mode}) => {
    return {
        publicDir: 'genie',
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                publicDirectory: '/public',
                buildDirectory: 'genie',
                refresh: true
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            DefineOptions()
        ],
        resolve: {
            alias: {
                'ziggy': ziggyPath,
                '@mRs': '/vendor/inovector/mixpost-pro-team/resources',
                '@mJs': '/vendor/inovector/mixpost-pro-team/resources/js',
                '@mCss': '/vendor/inovector/mixpost-pro-team/resources/css',
                '@mimg': '/vendor/inovector/mixpost-pro-team/resources/img',
                '@meRs': '/vendor/inovector/mixpost-enterprise/resources',
                '@meJs': '/vendor/inovector/mixpost-enterprise/resources/js',
                '@meCss': '/vendor/inovector/mixpost-enterprise/resources/css',
                '@css': '/resources/css',
                '@img': 'resources/img'
            },
        },
        server: {
            port: 5173
        }
    }
});