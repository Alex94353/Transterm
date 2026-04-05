<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'flag_path' => $this->flag_path,
            'source_pairs_count' => $this->whenCounted('sourcePairs'),
            'target_pairs_count' => $this->whenCounted('targetPairs'),
            'glossary_translations_count' => $this->whenCounted('glossaryTranslations'),
            'term_translations_count' => $this->whenCounted('termTranslations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
