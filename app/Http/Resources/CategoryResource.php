<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->whenHas('id'),
            'name' => $this->whenHas('name'),
            'slug' => $this->whenHas('slug'),
            'parent_id' => $this->whenHas('parent_id'),
            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                ] : null;
            }),
            'icon' => $this->whenHas('icon', fn() => $this->icon ? Storage::url($this->icon) : null),
            'is_active' => $this->whenHas('is_active', fn() => (bool)$this->is_active),
            'meta_title' => $this->whenHas('meta_title'),
            'meta_keyword' => $this->whenHas('meta_keyword', fn() => $this->meta_keyword ?? []),
            'meta_description' => $this->whenHas('meta_description'),
            'meta_image' => $this->whenHas('meta_image', fn() => $this->meta_image ? Storage::url($this->meta_image) : null),
            'created_at' => $this->whenHas('created_at'),
            'updated_at' => $this->whenHas('updated_at'),
        ];
    }
}
