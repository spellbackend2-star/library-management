<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'floor_id' => [
                'required',
                'integer',
                'exists:floors,id',
            ],

            'name' => [
                'required',
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
