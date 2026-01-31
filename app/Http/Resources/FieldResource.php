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
            'name' => $this->name,
            'type' => $this->type,
            'location' => $this->location,
            'district' => $this->district,
            'price' => (float) $this->price,
            'status' => $this->status,
            'rating' => (float) $this->rating,
            'openTime' => $this->openTime,
            'closeTime' => $this->closeTime,
            'image' => $this->image,
            'rule' => $this->rule,
            'features' => $this->features,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
