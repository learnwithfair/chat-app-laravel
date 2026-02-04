<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaLibraryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'media' => MessageAttachmentResource::collection($this['media'] ?? []),
            'audio' => MessageAttachmentResource::collection($this['audio'] ?? []),
            'files' => MessageAttachmentResource::collection($this['files'] ?? []),
            'links' => MessageLinkResource::collection($this['links'] ?? []),
        ];
    }
}
