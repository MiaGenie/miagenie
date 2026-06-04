<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Inovector\Mixpost\Abstracts\User;
use Inovector\Mixpost\Broadcast;
use Inovector\Mixpost\Concerns\UsesAuth;
use Inovector\Mixpost\Concerns\UsesUserResource;
use Inovector\Mixpost\Facades\Settings;
use Inovector\Mixpost\Facades\Theme;
use Inovector\Mixpost\Features;
use Inovector\Mixpost\Mixpost;
use Inovector\Mixpost\Util;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    use UsesAuth;
    use UsesUserResource;

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param Request $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        if (file_exists($manifest = public_path('genie/manifest.json'))) {
            return md5_file($manifest);
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param Request $request
     * @return array
     */
    public function share(Request $request): array
    {
        $ziggy = new Ziggy();

        return array_merge(parent::share($request), [
            'auth' => $this->auth(),
            'ziggy' => function () use ($request, $ziggy) {
                return array_merge($ziggy->filter(['genie.*', 'mixpost.*'])->toArray(), [
                    'location' => $request->url(),
                    'workspace_id' => self::getWorkspaceId($request)
                ]);
            },
            'broadcast' => Broadcast::echoOptions(),
            'is_admin_console' => Util::isAdminConsole($request),
            'flash' => function () use ($request) {
                return [
                    'success' => $request->hasSession() ? $request->session()->get('success') : null,
                    'warning' => $request->hasSession() ? $request->session()->get('warning') : null,
                    'error' => $request->hasSession() ? $request->session()->get('error') : null,
                    'info' => $request->hasSession() ? $request->session()->get('info') : null,
                ];
            },
            'app' => [
                'name' => Config::get('app.name'),
                'horizon_path' => Config::get('horizon.path'),
            ],
            'genie' => [
                'core_path' => 'genie',
                'mime_types' => Config::get('genie.mime_types')
            ],
            'mixpost' => [
                'core_path' => Util::corePath(),
                'mime_types' => Config::get('genie.mime_types'),
                'settings' => [
                    'locale' => Settings::get('locale'),
                    'timezone' => Settings::get('timezone'),
                    'time_format' => Settings::get('time_format'),
                    'week_starts_on' => Settings::get('week_starts_on'),
                ],
                'theme' => [
                    'logo' => Theme::config()->get('logo_url'),
                    'colors' => Theme::colors()
                ],
                'features' => [
                    'api_access_tokens' => Features::isApiAccessTokenEnabled()
                ],
                'enterpriseConsole' => [
                    'url' => Mixpost::getEnterpriseConsoleUrl(),
                    'registration_url' => Mixpost::getRegistrationUrl(),
                    'create_workspace_url' => Mixpost::getCreateWorkspaceUrl(),
                    'has_workspace_urls' => Mixpost::hasWorkspaceUrls(),
                    'workspace_settings_url' => Mixpost::getWorkspaceSettingsUrl(),
                    'workspace_billing_url' => Mixpost::getWorkspaceBillingUrl(),
                    'workspace_upgrade_url' => Mixpost::getWorkspaceUpgradeUrl(),
                    'stop_impersonating_url' => Mixpost::getStopImpersonatingUrl(),
                    'multiple_workspace_enabled' => Mixpost::getMultipleWorkspaceEnabled(),
                    'is_system_webhook_enabled' => Mixpost::isSystemWebhookEnabled(),
                ],
            ]
        ]);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected static function isAdminConsole(Request $request): bool
    {
        return $request->route() && Str::contains($request->route()->getPrefix(), 'genie/admin');
    }

    /**
     * @return array
     */
    protected function auth(): array
    {
        if (!self::getAuthGuard()->check()) {
            return [
                'user' => null,
                'workspaces' => [],
                'impersonating' => false,
            ];
        }

        $user = self::getAuthGuard()->user();

        // If `Auth Middleware` was not resolved first
        // return empty auth
        if (!$user instanceof User) {
            return [];
        }

        $userResourceClass = self::getUserResourceClass();

        return [
            'user' => new $userResourceClass($user->load(['admin', 'workspaces'])),
            'impersonating' => Mixpost::impersonating(),
        ];
    }

    /**
     * @param Request $request
     * @return object|string|null
     */
    protected function getWorkspaceId(Request $request): object|string|null
    {
        // Exclude from Admin Console
        if (Util::isAdminConsole($request)) {
            return null;
        }

        return $request->route('workspace');
    }
}
