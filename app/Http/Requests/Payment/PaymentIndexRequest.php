<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PaymentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'payment_references' => [
                'nullable',
                'string'
            ],


            'payment_method' => [
                'nullable',
                'in:CASH,KHALTI,ESEWA'
            ],


            'payment_status' => [
                'nullable',
                'in:pending,completed,failed,refunded'
            ],


            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],


            'booking_id' => [
                'nullable',
                'integer',
                'exists:bookings,id'
            ],


            'currency' => [
                'nullable',
                'string',
                'max:10'
            ],


            'date_from' => [
                'nullable',
                'date'
            ],


            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from'
            ],


            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],

        ];
    }
}