<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
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
    public function rules()
    {
        return [
            'title' => match ($this->method()) {
                'PUT' => ['required', 'string', 'max:255', Rule::unique('posts')->ignore($this->id)],
                default => 'required|string|max:255'
            },
            'content' => 'required|string',
            'post_categories' => 'required|array',
            'post_categories.*' => 'integer|exists:post_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
