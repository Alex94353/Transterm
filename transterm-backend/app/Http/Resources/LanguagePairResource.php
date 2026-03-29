<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguagePairResource extends JsonResource
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
            'source_language_id' => $this->source_language_id,
            'target_language_id' => $this->target_language_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'source_language' => $this->whenLoaded('sourceLanguage', function () {
                return [
                    'id' => $this->sourceLanguage->id,
                    'name' => $this->sourceLanguage->name,
                    'code' => $this->sourceLanguage->code,
                ];
            }),

            'target_language' => $this->whenLoaded('targetLanguage', function () {
                return [
                    'id' => $this->targetLanguage->id,
                    'name' => $this->targetLanguage->name,
                    'code' => $this->targetLanguage->code,
                ];
            }),
        ];
    }
}
