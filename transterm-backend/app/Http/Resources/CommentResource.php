<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'term_id' => $this->term_id,
            'user_id' => $this->user_id,
            'body' => $this->body,
            'is_spam' => (bool) $this->is_spam,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'term' => $this->whenLoaded('term', function () {
                return [
                    'id' => $this->term->id,
                    'glossary_id' => $this->term->glossary_id,
                    'field_id' => $this->term->field_id,
                    'created_by' => $this->term->created_by,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                    'name' => $this->user->name,
                    'surname' => $this->user->surname,
                ];
            }),
        ];
    }
}
