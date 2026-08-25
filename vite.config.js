import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import DefineOptions from 'unplugin-vue-define-options/vite'
import fs from 'fs'
import path from 'path'
import { homedir } from 'os'
import { fileURLToPath } from 'url'

const projectRoot = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ _command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  let serverConfig = {}

  if (mode === 'development') {
      env.APP_URL = env.APP_URL || 'http://localhost'
      serverConfig = {
          port: 5173
      }

      if (!env.APP_URL) {
      console.error('[vite] APP_URL is required in your .env file.')
      return
    }

    const isSSLEnabled =
      env.ENABLE_SSL === 'true' ||
      (env.ENABLE_SSL !== 'false' && env.APP_URL?.startsWith('https://'))

    if (isSSLEnabled && !env.CERTIFICATES_KEY_PATH) {
      console.error('[vite] SSL is enabled but CERTIFICATES_KEY_PATH is not set in your .env file.')
      return
    }

    if (isSSLEnabled && !env.CERTIFICATES_CRT_PATH) {
      console.error('[vite] SSL is enabled but CERTIFICATES_CRT_PATH is not set in your .env file.')
      return
    }

    const homeDir = homedir()
    const host = new URL(env.APP_URL).host

    if (isSSLEnabled && host) {
      const keyPath = path.resolve(homeDir, env.CERTIFICATES_KEY_PATH)
      const crtPath = path.resolve(homeDir, env.CERTIFICATES_CRT_PATH)

      if (fs.existsSync(keyPath) && fs.existsSync(crtPath)) {
        serverConfig = {
          port: 5173,
          https: {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(crtPath)
          },
          hmr: { host },
          host
        }
      } else {
        console.error('[vite] SSL is enabled but one or both certificate files were not found.')
        return
      }
    }
  }

    return {
        publicDir: "genie",
        plugins: [
            laravel({
                input: "resources/js/app.js",
                publicDirectory: "/public",
                buildDirectory: "genie",
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            tailwindcss(),
            DefineOptions(),
        ],
        resolve: {
            alias: {
                "@mRs": "/vendor/inovector/mixpost-pro-team/resources",
                "@mJs": "/vendor/inovector/mixpost-pro-team/resources/js",
                "@mCss": "/vendor/inovector/mixpost-pro-team/resources/css",
                "@mimg": "/vendor/inovector/mixpost-pro-team/resources/img",
                "@meRs": "/vendor/inovector/mixpost-enterprise/resources",
                "@meJs": "/vendor/inovector/mixpost-enterprise/resources/js",
                "@meCss": "/vendor/inovector/mixpost-enterprise/resources/css",

                "@css": path.resolve(projectRoot, "resources/css"),
                "@img": path.resolve(projectRoot, "resources/img"),
            },
        },
        server: serverConfig,
    };
})
