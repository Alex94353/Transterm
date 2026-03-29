<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferenceResource extends JsonResource
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
            'user_id' => $this->user_id,
            'source' => $this->source,
            'type' => $this->type,
            'language' => $this->language,
            'term_references_count' => $this->whenCounted('termReferences'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'user' => $this->whenLoaded('user', function () {
                return $this->user
                    ? [
                        'id' => $this->user->id,
                        'username' => $this->user->username,
                        'email' => $this->user->email,
                        'name' => $this->user->name,
                        'surname' => $this->user->surname,
                    ]
                    : null;
            }),

            'term_references' => $this->whenLoaded('termReferences', function () {
                return $this->termReferences->map(function ($termReference) {
                    return [
                        'id' => $termReference->id,
                        'term_translation_id' => $termReference->term_translation_id,
                        'reference_id' => $termReference->reference_id,
                        'type' => $termReference->type,
                        'created_at' => $termReference->created_at,
                        'updated_at' => $termReference->updated_at,
                        'term_translation' => $termReference->relationLoaded('termTranslation') && $termReference->termTranslation
                            ? [
                                'id' => $termReference->termTranslation->id,
                                'term_id' => $termReference->termTranslation->term_id,
                                'language_id' => $termReference->termTranslation->language_id,
                                'title' => $termReference->termTranslation->title,
                                'plural' => $termReference->termTranslation->plural,
                                'definition' => $termReference->termTranslation->definition,
                                'context' => $termReference->termTranslation->context,
                                'synonym' => $termReference->termTranslation->synonym,
                                'notes' => $termReference->termTranslation->notes,
                                'language' => $termReference->termTranslation->relationLoaded('language') && $termReference->termTranslation->language
                                    ? [
                                        'id' => $termReference->termTranslation->language->id,
                                        'name' => $termReference->termTranslation->language->name,
                                        'code' => $termReference->termTranslation->language->code,
                                    ]
                                    : null,
                            ]
                            : null,
                    ];
                });
            }),
        ];
    }
}
