<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transforma el modelo a JSON limpio y tipado.
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'user'          => $this->whenLoaded('user', fn () => [
                                    'id'   => $this->user->id,
                                    'name' => $this->user->name,
                                ]),
            'timestamp_str' => $this->timestamp_str,
            'event'         => $this->event,
            'zone_idx'      => $this->zone_idx === null ? null : (int)$this->zone_idx,
            'level'         => $this->level,
            'save'          => (bool)$this->save,
            'filename'      => $this->filename,
            'forklift_name' => $this->forklift_name,
            'confidence'    => $this->confidence === null ? null : (float)$this->confidence,
            'meta'          => $this->meta, // ya casteado a array por el modelo
            'created_at'    => optional($this->created_at)->toISOString(),
        ];
    }
}
