<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GlossaryResource extends JsonResource
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
            'approved' => (bool) $this->approved,
            'is_public' => (bool) $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'owner' => $this->whenLoaded('owner', function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'surname' => $this->owner->surname,
                    'email' => $this->owner->email,
                ];
            }),

            'field' => $this->whenLoaded('field', function () {
                return [
                    'id' => $this->field->id,
                    'name' => $this->field->name,
                    'code' => $this->field->code,
                ];
            }),

            'language_pair' => $this->whenLoaded('languagePair', function () {
                return [
                    'id' => $this->languagePair->id,
                    'source_language' => $this->languagePair->relationLoaded('sourceLanguage') && $this->languagePair->sourceLanguage
                        ? [
                            'id' => $this->languagePair->sourceLanguage->id,
                            'name' => $this->languagePair->sourceLanguage->name,
                            'code' => $this->languagePair->sourceLanguage->code,
                        ]
                        : null,
                    'target_language' => $this->languagePair->relationLoaded('targetLanguage') && $this->languagePair->targetLanguage
                        ? [
                            'id' => $this->languagePair->targetLanguage->id,
                            'name' => $this->languagePair->targetLanguage->name,
                            'code' => $this->languagePair->targetLanguage->code,
                        ]
                        : null,
                ];
            }),

            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->map(function ($translation) {
                    return [
                        'id' => $translation->id,
                        'language_id' => $translation->language_id,
                        'title' => $translation->title,
                        'description' => $translation->description,
                    ];
                });
            }),
        ];
    }
}
