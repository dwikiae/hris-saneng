<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstanceModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->resource['code'],
            'name' => $this->resource['name'],
            'version' => $this->resource['version'],
            'description' => $this->resource['description'],
            'isMandatory' => $this->resource['isMandatory'],
            'dependencies' => $this->resource['dependencies'],
            'missingDependencies' => $this->resource['missingDependencies'],
            'isInstalled' => $this->resource['isInstalled'],
            'installedAt' => $this->resource['installedAt'],
            'status' => $this->resource['status'],
        ];
    }
}
