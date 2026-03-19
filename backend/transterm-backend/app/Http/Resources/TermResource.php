<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TermResource extends JsonResource
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
            'glossary_id' => $this->glossary_id,
            'field_id' => $this->field_id,
            'created_by' => $this->created_by,
            'comments_count' => $this->whenCounted('comments'),
            'translations_count' => $this->whenCounted('translations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'glossary' => $this->whenLoaded('glossary', function () {
                return [
                    'id' => $this->glossary->id,
                    'language_pair_id' => $this->glossary->language_pair_id,
                    'field_id' => $this->glossary->field_id,
                    'created_by' => $this->glossary->created_by,
                    'approved' => (bool) $this->glossary->approved,
                    'is_public' => (bool) $this->glossary->is_public,
                    'created_at' => $this->glossary->created_at,
                    'updated_at' => $this->glossary->updated_at,

                    'language_pair' => $this->glossary->relationLoaded('languagePair') && $this->glossary->languagePair
                        ? [
                            'id' => $this->glossary->languagePair->id,
                            'source_language' => $this->glossary->languagePair->relationLoaded('sourceLanguage') && $this->glossary->languagePair->sourceLanguage
                                ? [
                                    'id' => $this->glossary->languagePair->sourceLanguage->id,
                                    'name' => $this->glossary->languagePair->sourceLanguage->name,
                                    'code' => $this->glossary->languagePair->sourceLanguage->code,
                                ]
                                : null,
                            'target_language' => $this->glossary->languagePair->relationLoaded('targetLanguage') && $this->glossary->languagePair->targetLanguage
                                ? [
                                    'id' => $this->glossary->languagePair->targetLanguage->id,
                                    'name' => $this->glossary->languagePair->targetLanguage->name,
                                    'code' => $this->glossary->languagePair->targetLanguage->code,
                                ]
                                : null,
                        ]
                        : null,

                    'translations' => $this->glossary->relationLoaded('translations')
                        ? $this->glossary->translations->map(function ($translation) {
                            return [
                                'id' => $translation->id,
                                'language_id' => $translation->language_id,
                                'language' => $translation->relationLoaded('language') && $translation->language
                                    ? [
                                        'id' => $translation->language->id,
                                        'name' => $translation->language->name,
                                        'code' => $translation->language->code,
                                    ]
                                    : null,
                                'title' => $translation->title,
                                'description' => $translation->description ?? null,
                            ];
                        })
                        : [],
                ];
            }),

            'field' => $this->whenLoaded('field', function () {
                return [
                    'id' => $this->field->id,
                    'name' => $this->field->name,
                    'code' => $this->field->code,
                    'field_group' => $this->field->relationLoaded('fieldGroup') && $this->field->fieldGroup
                        ? [
                            'id' => $this->field->fieldGroup->id,
                            'name' => $this->field->fieldGroup->name,
                            'code' => $this->field->fieldGroup->code,
                        ]
                        : null,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'username' => $this->creator->username,
                    'email' => $this->creator->email,
                    'name' => $this->creator->name,
                    'surname' => $this->creator->surname,
                    'profile' => $this->creator->relationLoaded('profile') && $this->creator->profile
                        ? [
                            'country_id' => $this->creator->profile->country_id,
                            'website' => $this->creator->profile->website,
                        ]
                        : null,
                ];
            }),

            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->map(function ($translation) {
                    return [
                        'id' => $translation->id,
                        'term_id' => $translation->term_id,
                        'language_id' => $translation->language_id,
                        'language' => $translation->relationLoaded('language') && $translation->language
                            ? [
                                'id' => $translation->language->id,
                                'name' => $translation->language->name,
                                'code' => $translation->language->code,
                            ]
                            : null,
                        'title' => $translation->title,
                        'plural' => $translation->plural,
                        'definition' => $translation->definition,
                        'context' => $translation->context,
                        'synonym' => $translation->synonym,
                        'notes' => $translation->notes,
                        'references' => $translation->relationLoaded('termReferences')
                            ? $translation->termReferences->map(function ($termReference) {
                                return [
                                    'id' => $termReference->id,
                                    'term_translation_id' => $termReference->term_translation_id,
                                    'reference_id' => $termReference->reference_id,
                                    'reference_type' => $termReference->reference_type ?? null,
                                    'reference' => $termReference->relationLoaded('reference') && $termReference->reference
                                        ? [
                                            'id' => $termReference->reference->id,
                                            'user_id' => $termReference->reference->user_id,
                                            'source' => $termReference->reference->source,
                                            'type' => $termReference->reference->type,
                                            'language_id' => $termReference->reference->language_id ?? null,
                                            'created_at' => $termReference->reference->created_at,
                                            'updated_at' => $termReference->reference->updated_at,
                                        ]
                                        : null,
                                ];
                            })
                            : [],
                    ];
                });
            }),

            'comments' => $this->whenLoaded('comments', function () {
                return $this->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'term_id' => $comment->term_id,
                        'user_id' => $comment->user_id,
                        'body' => $comment->body,
                        'is_spam' => (bool) $comment->is_spam,
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                        'user' => $comment->relationLoaded('user') && $comment->user
                            ? [
                                'id' => $comment->user->id,
                                'username' => $comment->user->username,
                                'email' => $comment->user->email,
                                'name' => $comment->user->name,
                                'surname' => $comment->user->surname,
                            ]
                            : null,
                    ];
                });
            }),
        ];
    }
}
