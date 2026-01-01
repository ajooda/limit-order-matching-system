<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'symbol'    => $this->symbol,
            'side'      => $this->side,
            'status'    => $this->status,
            'price'     => (string) $this->price,
            'amount'    => (string) $this->amount,
            'locked_usd'=> (string) $this->locked_usd,
            'created_at'=> $this->created_at?->toISOString(),
            'updated_at'=> $this->updated_at?->toISOString(),
        ];

    }
}
