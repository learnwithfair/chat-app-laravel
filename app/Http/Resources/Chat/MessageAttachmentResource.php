<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'size'       => $this->size,
            'path'       => url($this->path),
            'name'       => $this->name,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
