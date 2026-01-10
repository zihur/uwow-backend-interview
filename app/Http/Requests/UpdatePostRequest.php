<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdatePostRequest extends FormRequest
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
        $postId = $this->route('post');
        return [
            'title' => [
                'required',
                'string',
                'max:100',
                Rule::unique('posts')->ignore($postId),
            ],
            'img_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'content'  => 'required|string',
            'status'   => 'required|integer|in:0,1,2',
            'published_at' => 'nullable|date|required_if:status,2',
            'finished_at'  => 'nullable|date|required_if:status,2|after_or_equal:published_at',
            'sort_order'   => 'required|numeric',
        ];
    }
}
