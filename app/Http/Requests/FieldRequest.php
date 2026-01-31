<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|',
            'location' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance',
            'rating' => 'nullable|numeric|min:0|max:5',
            'openTime' => 'required|date_format:H:i',
            'closeTime' => 'required|date_format:H:i|after:openTime',
            'image' => 'nullable|string',
            'rule' => 'nullable|string',
            'features' => 'nullable|array',
        ];

        // Khi update, cho phép một số field không bắt buộc
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên sân bóng là bắt buộc',
            'type.required' => 'Loại sân là bắt buộc',
            'type.in' => 'Loại sân phải là: 5 người, 7 người hoặc 11 người',
            'location.required' => 'Địa chỉ là bắt buộc',
            'district.required' => 'Quận/Huyện là bắt buộc',
            'price.required' => 'Giá thuê là bắt buộc',
            'price.numeric' => 'Giá thuê phải là số',
            'price.min' => 'Giá thuê phải lớn hơn hoặc bằng 0',
            'status.required' => 'Trạng thái là bắt buộc',
            'status.in' => 'Trạng thái phải là: available, occupied hoặc maintenance',
            'rating.numeric' => 'Đánh giá phải là số',
            'rating.min' => 'Đánh giá phải từ 0 đến 5',
            'rating.max' => 'Đánh giá phải từ 0 đến 5',
            'openTime.required' => 'Giờ mở cửa là bắt buộc',
            'openTime.date_format' => 'Giờ mở cửa phải có định dạng H:i (VD: 08:00)',
            'closeTime.required' => 'Giờ đóng cửa là bắt buộc',
            'closeTime.date_format' => 'Giờ đóng cửa phải có định dạng H:i (VD: 22:00)',
            'closeTime.after' => 'Giờ đóng cửa phải sau giờ mở cửa',
            'features.array' => 'Tiện ích phải là một mảng',
        ];
    }
}
