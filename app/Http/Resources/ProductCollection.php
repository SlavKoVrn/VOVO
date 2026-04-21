<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection,
            'total' => $this->total(),
            'page' => $this->currentPage(),
            'page_size' => $this->perPage(),
            'last_page' => $this->lastPage(),
        ];
    }
}
