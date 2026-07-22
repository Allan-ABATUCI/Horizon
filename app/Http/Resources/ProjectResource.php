<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "description"=> $this->description,
            "created_at"=> $this->created_at ? $this->created_at->format("Y-m-d") : null,
            "updated_at"=> $this->updated_at? $this->updated_at->format("Y-m-d") : null,
            "end_date"=> $this->end_date ? $this->end_date->format("Y-m-d") : null,
            "image_path"=> $this->image_path,
            "created_by"=> new UserResource($this->creator),
            "updated_by"=> new UserResource($this->editor),
            "status"=> $this->status
        ];
    }
}
