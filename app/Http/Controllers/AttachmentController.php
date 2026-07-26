<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        return AttachmentResource::collection(
            $task->attachments()->with('user')->latest()->get()
        );
    }

    public function store(StoreAttachmentRequest $request, Task $task)
    {
        if ($task->attachments()->count() >= 10) {
            return response()->json(['message' => 'Nombre maximum de pièces jointes atteint (10).'], 422);
        }

        $file = $request->file('file');
        $path = $file->store('attachments', 'local');

        $attachment = $task->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return new AttachmentResource($attachment->load('user'));
    }

    public function download(Attachment $attachment)
    {
        $this->authorize('view', $attachment->task);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
