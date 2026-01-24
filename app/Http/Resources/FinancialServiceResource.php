<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Fetch types from relationship
        $data['types'] = $this->types->map(function ($type) {
            $typeData = [
                'id' => $type->id,
                'title' => $type->title,
                'description' => $type->description,
                'points' => $type->points,
                'images' => $type->images,
            ];

            if (isset($typeData['images']) && is_array($typeData['images'])) {
                $typeData['images'] = array_map(function ($image) {
                    return asset($image);
                }, $typeData['images']);
            }
            return $typeData;
        });

        return $data;
    }
}
