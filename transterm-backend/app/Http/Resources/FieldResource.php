<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
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
            'field_group_id' => $this->field_group_id,
            'name' => $this->name,
            'code' => $this->code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'field_group' => $this->whenLoaded('fieldGroup', function () {
                return [
                    'id' => $this->fieldGroup->id,
                    'name' => $this->fieldGroup->name,
                    'code' => $this->fieldGroup->code,
                    'created_at' => $this->fieldGroup->created_at,
                    'updated_at' => $this->fieldGroup->updated_at,
                ];
            }),
        ];
    }
}
