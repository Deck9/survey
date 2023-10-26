<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->load('formSessionResponses.formBlock');

        return [
            'id' => $this->id,
            'uid' => $this->token,
            'started_at' => $this->created_at->toDateTimeString(),
            'completed_at' => (string) $this->getRawOriginal('is_completed'),
            'params' => $this->params,
            'responses' => $this->getResponses(),
            'webhooks' => FormSessionWebhookResource::collection($this->whenLoaded('webhooks')),
        ];
    }

    public function getResponses()
    {
        return collect(
            FormSessionResponseResource::collection($this->formSessionResponses)
                ->resolve()
        )
            ->groupBy('id')
            ->map(function ($response) {
                $concat = implode(', ', $response->pluck('value')->sortBy('value')->all());

                return [
                    'answer' => $concat,
                    'message' => $response->pluck('message')[0],
                    'data' => $response->toArray(),
                ];
            })->toArray();
    }
}
