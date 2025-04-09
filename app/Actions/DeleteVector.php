<?php

namespace App\Actions;

use App\Enums\FileStatus;
use App\Models\Vector;
use App\Support\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

class DeleteVector
{

    /**
     * @param Vector $vector
     * @return bool
     */
    public function __invoke(Vector $vector): bool
    {

        $vectorDb = Vector::find($vector->id);
        $vectorDb->status = FileStatus::DELETING;
        $vectorDb->save();

        try {
            $upload = OpenAI::vectorStores()->delete($vector->vector_id);

            if ($upload->deleted) {
                return true;
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            $vectorDb->status = FileStatus::PENDING_DELETION;
            $vectorDb->save();
        }

        return false;
    }
}
