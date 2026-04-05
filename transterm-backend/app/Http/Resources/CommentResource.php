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
                $termName = null;
                if ($this->term->relationLoaded('translations') && $this->term->translations->isNotEmpty()) {
                    $termName = $this->term->translations->first()->title;
                }

                $glossary = null;
                if ($this->term->relationLoaded('glossary') && $this->term->glossary) {
                    $glossaryName = null;
                    if (
                        $this->term->glossary->relationLoaded('translations')
                        && $this->term->glossary->translations->isNotEmpty()
                    ) {
                        $glossaryName = $this->term->glossary->translations->first()->title;
                    }

                    $glossary = [
                        'id' => $this->term->glossary->id,
                        'name' => $glossaryName ?: 'Glossary #'.$this->term->glossary->id,
                    ];
                }

                return [
                    'id' => $this->term->id,
                    'glossary_id' => $this->term->glossary_id,
                    'field_id' => $this->term->field_id,
                    'created_by' => $this->term->created_by,
                    'name' => $termName ?: 'Term #'.$this->term->id,
                    'glossary' => $glossary,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                if (! $this->user) {
                    return null;
                }

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
