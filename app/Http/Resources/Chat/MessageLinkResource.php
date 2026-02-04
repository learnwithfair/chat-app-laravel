<?php
namespace App\Http\Resources\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'message_id' => $this['message_id'],
            'url'        => $this['url'],
            'created_at' => $this['created_at']?->toDateTimeString(),
        ];
    }
}
