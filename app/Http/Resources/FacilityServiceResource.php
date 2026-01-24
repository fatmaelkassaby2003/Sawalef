<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityServiceResource extends JsonResource
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
            'price' => $this->price,
            'description' => $this->description, // مخفي
            'important_note' => $this->important_note,
            'icon' => $this->icon,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'types' => $this->types->map(function ($type) {
                $typeData = [
                    'id' => $type->id,
                    'title' => $type->title,
                    'description' => $type->description, // description الخاص بالـ type
                    'points' => $type->points,
                    'items' => $type->items,
                    'images' => $type->images,
                ];

                if (isset($typeData['images']) && is_array($typeData['images'])) {
                    $typeData['images'] = array_map(function ($image) {
                        return asset($image);
                    }, $typeData['images']);
                }
                
                return $typeData;
            }),
        ];
    }
}