<?php

namespace App\Http\Requests\Workspace\Draft;

use App\Models\Draft;
use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class IdeaDrafts extends FormRequest
{
    public function handle(): Collection
    {
        $idea = Idea::findByUuid($this->route('idea'));

        $records = Draft::with('draftPost')->where('idea_id', $idea->id)->get();

        return $records;
    }
}
