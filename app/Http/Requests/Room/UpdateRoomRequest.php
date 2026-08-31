<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomId = $this->route('room');

        return [
            'floor_id' => [
                'sometimes',
                'integer',
                'exists:floors,id',
            ],

            'name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'room_type' => [
                'nullable',
                'string',
                'in:study_area,quiet_zone,group_room,computer_lab',
            ],
        ];
    }
}
