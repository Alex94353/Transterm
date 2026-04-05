<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldGroupResource extends JsonResource
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
            'fields_count' => $this->whenCounted('fields'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'fields' => $this->whenLoaded('fields', function () {
                return $this->fields->map(function ($field) {
                    return [
                        'id' => $field->id,
                        'field_group_id' => $field->field_group_id,
                        'name' => $field->name,
                        'code' => $field->code,
                        'created_at' => $field->created_at,
                        'updated_at' => $field->updated_at,
                    ];
                });
            }),
        ];
    }
}
