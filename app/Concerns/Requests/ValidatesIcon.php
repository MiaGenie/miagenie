<?php

namespace App\Concerns\Requests;

/**
 * An icon picked from Material Design Icons: its name (`mdi:rocket-launch`) and the SVG path the
 * picker sends with it. The path is stored so the pages that show the icon draw it without loading
 * the icon set, which is why it is held to the characters of SVG path data: it is bound to a
 * `<path d>` and must never carry markup.
 *
 * The form decides through `acceptsIcon()` whether the record may carry an icon at all; one that
 * may not stores none, whatever was sent.
 */
trait ValidatesIcon
{
    /**
     * Comfortably above the longest MDI path (about 4,600 characters).
     */
    public const MAX_ICON_PATH_LENGTH = 20000;

    abstract protected function acceptsIcon(): bool;

    /**
     * @return array<string, array<int, string>>
     */
    protected function iconRules(): array
    {
        return [
            'icon' => ['nullable', 'string', 'max:255', 'regex:/^mdi:[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'icon_path' => [
                'nullable',
                'required_with:icon',
                'string',
                'max:'.self::MAX_ICON_PATH_LENGTH,
                'regex:/^[MmLlHhVvCcSsQqTtAaZz0-9.,\s+-]+$/',
            ],
        ];
    }

    /**
     * What to store: the icon and its path, or neither when none was picked or the record may not
     * carry one.
     *
     * @return array{icon: string|null, icon_path: string|null}
     */
    protected function iconAttributes(): array
    {
        $icon = $this->input('icon');

        if (blank($icon) || ! $this->acceptsIcon()) {
            return ['icon' => null, 'icon_path' => null];
        }

        return ['icon' => $icon, 'icon_path' => $this->input('icon_path')];
    }
}
