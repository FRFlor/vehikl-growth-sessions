<?php

namespace App\Http\Requests;

use App\GrowthSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthSessionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('create', GrowthSession::class);
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:45',
            'topic' => 'required|string',
            'location' => 'required|string',
            'start_time' => 'required|date_format:h:i a',
            'end_time' => 'sometimes|required|after:start_time|date_format:h:i a',
            'date' => 'required|date|after_or_equal:today',
            'attendee_limit' => 'sometimes|integer|min:4',
            'discord_channel_id' => 'sometimes|string',
            'is_public' => 'sometimes|boolean',
            'create_zoom_meeting' => 'sometimes|boolean',
        ];
    }
}
