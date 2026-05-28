<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getAttribute('id'),
            'name' => $this->resource->getAttribute('name'),
            'legal_name' => $this->resource->getAttribute('legal_name'),
            'npwp' => $this->resource->getAttribute('npwp'),
            'address' => $this->resource->getAttribute('address'),
            'city' => $this->resource->getAttribute('city'),
            'phone' => $this->resource->getAttribute('phone'),
            'email' => $this->resource->getAttribute('email'),
            'logo_path' => $this->resource->getAttribute('logo_path'),
            'website' => $this->resource->getAttribute('website'),
            'timezone' => $this->resource->getAttribute('timezone'),
            'date_format' => $this->resource->getAttribute('date_format'),
            'language_default' => $this->resource->getAttribute('language_default'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
