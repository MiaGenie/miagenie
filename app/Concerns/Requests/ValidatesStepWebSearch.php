<?php

namespace App\Concerns\Requests;

use App\Ai\StepAgentFactory;
use App\Enums\RuleSubType;
use App\Models\ModelProfile;
use Closure;
use Illuminate\Support\Str;

/**
 * The web search rules a rule step form shares between create and update.
 *
 * Only a sub type that supports it (the strategy steps) may switch web search on, and the step's
 * model profile must then name a provider that can search. The searches cap and the allowed
 * domains belong to the switch: a step that does not search stores neither.
 */
trait ValidatesStepWebSearch
{
    /**
     * The most searches one call may be allowed.
     */
    public const MAX_WEB_SEARCHES = 20;

    /**
     * The most domains a search may be restricted to: OpenAI's limit, the lowest of the providers.
     */
    public const MAX_WEB_SEARCH_DOMAINS = 20;

    /**
     * The sub type that decides whether web search is allowed: from the input on create, from the
     * stored step on update.
     */
    abstract protected function webSearchSubType(): ?RuleSubType;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function webSearchRules(): array
    {
        return [
            'web_search' => ['nullable', 'boolean', $this->webSearchSubTypeRule()],
            'web_search_max' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_WEB_SEARCHES],
            'web_search_domains' => ['nullable', 'array', 'max:'.self::MAX_WEB_SEARCH_DOMAINS],
            'web_search_domains.*' => ['string', 'max:253', $this->webSearchDomainRule()],
        ];
    }

    /**
     * The model_profile_id rule refusing a provider that cannot search, for a step that does.
     */
    protected function webSearchProviderRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! $this->wantsWebSearch()) {
                return;
            }

            $profile = ModelProfile::find($value);

            if ($profile && ! StepAgentFactory::supportsWebSearch($profile)) {
                $fail(__('genie.step_web_search_provider'));
            }
        };
    }

    /**
     * Accept the allowed domains as the editor's text (one per line, or separated by commas or
     * spaces) or as a list, and reduce each to a bare lower-case domain: a pasted URL loses its
     * scheme and trailing slash. Call from the request's prepareForValidation().
     */
    protected function normaliseWebSearchDomains(): void
    {
        $domains = $this->input('web_search_domains');

        if (is_string($domains)) {
            $domains = preg_split('/[\s,]+/', $domains) ?: [];
        }

        if (! is_array($domains)) {
            return;
        }

        $this->merge([
            'web_search_domains' => collect($domains)
                ->map(fn (mixed $domain) => is_string($domain)
                    ? Str::of($domain)->trim()->lower()->replaceMatches('#^https?://#', '')->rtrim('/')->toString()
                    : $domain)
                ->reject(fn (mixed $domain) => $domain === '' || $domain === null)
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    protected function wantsWebSearch(): bool
    {
        return $this->boolean('web_search');
    }

    /**
     * The columns web search decides, ready to merge into the step's attributes.
     *
     * @return array{web_search: bool, web_search_max: int|null, web_search_domains: list<string>|null}
     */
    protected function webSearchAttributes(): array
    {
        $searches = $this->wantsWebSearch();
        $domains = $this->input('web_search_domains');

        return [
            'web_search' => $searches,
            'web_search_max' => $searches && filled($this->input('web_search_max')) ? $this->integer('web_search_max') : null,
            'web_search_domains' => $searches && filled($domains) ? $domains : null,
        ];
    }

    protected function webSearchSubTypeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->wantsWebSearch() && ! ($this->webSearchSubType()?->supportsWebSearch() ?? false)) {
                $fail(__('genie.step_web_search_not_supported'));
            }
        };
    }

    /**
     * A bare domain, subdomains allowed: no scheme, no path, no port.
     */
    protected function webSearchDomainRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/', $value)) {
                $fail(__('genie.step_web_search_domain', ['domain' => is_string($value) ? $value : '']));
            }
        };
    }
}
