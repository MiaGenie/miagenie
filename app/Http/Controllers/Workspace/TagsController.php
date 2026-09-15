<?php

namespace App\Http\Controllers\Workspace;

use App\Genie\Posts\GenieTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Http\Base\Requests\Workspace\StoreTag;
use Inovector\Mixpost\Http\Base\Requests\Workspace\UpdateTag;
use Inovector\Mixpost\Models\Tag;

/**
 * Mixpost's tag store, update and delete, with the Genie tag protected.
 *
 * Genie tags every draft it produces so Mixpost's posts list can filter them, and finds that tag
 * by name. It is a system tag: users cannot create one with that name, rename another tag to it,
 * rename it or delete it; changing its colour is fine. Registered over the package routes of the
 * same method and URI.
 */
class TagsController extends Controller
{
    public function store(StoreTag $storeTag): RedirectResponse
    {
        if ($this->isReservedName($storeTag->input('name'))) {
            return $this->refuse(__('genie.genie_tag_reserved'));
        }

        $storeTag->handle();

        return redirect()->back();
    }

    public function update(UpdateTag $updateTag): RedirectResponse
    {
        if ($this->isGenieTag($updateTag->route('tag')) && $updateTag->input('action') !== 'color') {
            return $this->refuse(__('genie.genie_tag_protected'));
        }

        if ($updateTag->input('action') === 'name' && $this->isReservedName($updateTag->input('name'))) {
            return $this->refuse(__('genie.genie_tag_reserved'));
        }

        $updateTag->handle();

        return redirect()->back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($this->isGenieTag($request->route('tag'))) {
            return $this->refuse(__('genie.genie_tag_protected'));
        }

        Tag::where('uuid', $request->route('tag'))->delete();

        return redirect()->back();
    }

    /**
     * A refusal the tag components read as a failed visit: with errors in the response Inertia
     * fires `onError` rather than `onSuccess`, so no "deleted" toast and no tag gets selected; the
     * flash still carries the message for the notification bar.
     */
    protected function refuse(string $message): RedirectResponse
    {
        return redirect()->back()->withErrors(['tag' => $message])->with('error', $message);
    }

    /**
     * The system tag's name is reserved, whatever the case.
     */
    protected function isReservedName(mixed $name): bool
    {
        return is_string($name) && mb_strtolower(trim($name)) === mb_strtolower(GenieTag::NAME);
    }

    protected function isGenieTag(?string $uuid): bool
    {
        return $uuid !== null && Tag::where('uuid', $uuid)->where('name', GenieTag::NAME)->exists();
    }
}
