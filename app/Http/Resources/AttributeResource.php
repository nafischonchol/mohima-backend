<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attributeValues = $this->attributeValues;

        $valuesData = [];
        if ($attributeValues && $attributeValues->count() > 0) {
            foreach ($attributeValues as $val) {
                $valuesData[] = [
                    'id' => $val->id,
                    'value' => $val->value,
                    'image' => $val->image ? Storage::url($val->image) : null,
                    'image_relative' => $val->image,
                ];
            }
        } elseif (is_array($this->values)) {
            foreach ($this->values as $val) {
                if (is_array($val)) {
                    $valuesData[] = [
                        'id' => $val['id'] ?? null,
                        'value' => $val['value'] ?? '',
                        'image' => !empty($val['image']) ? Storage::url($val['image']) : null,
                        'image_relative' => $val['image'] ?? null,
                    ];
                } else {
                    $valuesData[] = [
                        'id' => null,
                        'value' => (string)$val,
                        'image' => null,
                        'image_relative' => null,
                    ];
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'values' => $valuesData,
            'is_active' => (bool)$this->is_active,
            'is_default_specification' => (bool)$this->is_default_specification,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
