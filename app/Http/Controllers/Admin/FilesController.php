<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\DeleteFiles;
use App\Http\Requests\Admin\UploadFile;
use App\Http\Requests\Admin\DownloadFile;
use App\Http\Resources\Admin\FileResource;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FilesController extends Controller
{

    /**
     * @return Response
     */
    public function index(): Response
    {

        $records = File::query()
            ->latest()
            ->paginate(100)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('Genie/Admin/Files', [
            'records' => FileResource::collection($records),
            'mimeTypes' => File::mimeTypes(),
        ]);

    }

    /**
     * @param UploadFile $uploadFile
     * @return JsonResponse
     */
    public function upload(UploadFile $uploadFile): JsonResponse
    {
        $file = $uploadFile->handle();

        return response()->json($file);
    }

    /**
     * @param Request $request
     * @return StreamedResponse
     */
    public function download(Request $request): StreamedResponse
    {
        $file = File::where('id', $request->route('file'))->firstOrFail();

        return Storage::download($file->path, $file->name);
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function fetchUploads(): AnonymousResourceCollection
    {
        $records = File::latest('created_at')->simplePaginate(30);

        return FileResource::collection($records);
    }

    /**
     * @param DeleteFiles $deleteFiles
     * @return HttpResponse
     */
    public function destroy(DeleteFiles $deleteFiles): HttpResponse
    {
        $deleteFiles->handle();

        return response()->noContent();
    }
}
