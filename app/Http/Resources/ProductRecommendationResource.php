<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRecommendationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $device = $request->device ?? 'mobile';
        $fontSize = ($device === 'tablet') ? '24px' : '16px';
        $descriptionContent = $this->description;
        $descriptionContent = preg_replace('/font-size:[^;]*;?/i', '', $descriptionContent);
        $modifiedDescription = '<div style="font-size: ' . $fontSize . ';">' . $descriptionContent . '</div>';

        return  [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'color' => $this->color,
            'title_color' => $this->title_color,
            'description' => $modifiedDescription,
            // 'image' => asset($this->image),
            'image' => $this->when(
                $this->image,
                getImagePathUrl($this->image, 'uploads/product')
            ),

            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
