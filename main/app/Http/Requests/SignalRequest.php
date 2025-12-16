<?php

namespace App\Http\Requests;

class SignalRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'currency_pair_id' => 'required|integer|exists:currency_pairs,id',
            'time_frame_id' => 'required|integer|exists:time_frames,id',
            'market_id' => 'required|integer|exists:markets,id',
            'open_price' => 'required|numeric|min:0',
            'sl' => 'required|numeric|min:0',
            'tp' => 'required|numeric|min:0',
            'direction' => 'required|in:buy,sell,long,short',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'plans' => 'nullable|array',
            'plans.*' => 'integer|exists:plans,id'
        ];

        // Additional validation for updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $signalId = $this->route('signal');
            
            // Allow partial updates
            $rules = array_map(function ($rule) {
                return str_replace('required|', 'sometimes|required|', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'currency_pair_id.required' => 'Please select a currency pair.',
            'currency_pair_id.exists' => 'The selected currency pair is invalid.',
            'time_frame_id.required' => 'Please select a time frame.',
            'time_frame_id.exists' => 'The selected time frame is invalid.',
            'market_id.required' => 'Please select a market.',
            'market_id.exists' => 'The selected market is invalid.',
            'open_price.required' => 'The entry price is required.',
            'open_price.numeric' => 'The entry price must be a valid number.',
            'sl.required' => 'The stop loss is required.',
            'sl.numeric' => 'The stop loss must be a valid number.',
            'tp.required' => 'The take profit is required.',
            'tp.numeric' => 'The take profit must be a valid number.',
            'direction.required' => 'Please select a direction.',
            'direction.in' => 'The direction must be buy, sell, long, or short.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.max' => 'The image may not be greater than 2MB.',
            'plans.array' => 'Plans must be an array.',
            'plans.*.exists' => 'One or more selected plans are invalid.'
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'currency_pair_id' => 'currency pair',
            'time_frame_id' => 'time frame',
            'market_id' => 'market',
            'open_price' => 'entry price',
            'sl' => 'stop loss',
            'tp' => 'take profit',
            'plans' => 'plans'
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Stop loss should be different from entry price
            if ($this->open_price && $this->sl && $this->open_price == $this->sl) {
                $validator->errors()->add('sl', 'Stop loss must be different from entry price.');
            }

            // Custom validation: Take profit should be different from entry price
            if ($this->open_price && $this->tp && $this->open_price == $this->tp) {
                $validator->errors()->add('tp', 'Take profit must be different from entry price.');
            }

            // Custom validation: For buy/long positions, TP should be higher than entry
            if (in_array($this->direction, ['buy', 'long'])) {
                if ($this->open_price && $this->tp && $this->tp <= $this->open_price) {
                    $validator->errors()->add('tp', 'For buy/long positions, take profit should be higher than entry price.');
                }
                if ($this->open_price && $this->sl && $this->sl >= $this->open_price) {
                    $validator->errors()->add('sl', 'For buy/long positions, stop loss should be lower than entry price.');
                }
            }

            // Custom validation: For sell/short positions, TP should be lower than entry
            if (in_array($this->direction, ['sell', 'short'])) {
                if ($this->open_price && $this->tp && $this->tp >= $this->open_price) {
                    $validator->errors()->add('tp', 'For sell/short positions, take profit should be lower than entry price.');
                }
                if ($this->open_price && $this->sl && $this->sl <= $this->open_price) {
                    $validator->errors()->add('sl', 'For sell/short positions, stop loss should be higher than entry price.');
                }
            }
        });
    }

    /**
     * Get the validated data for signal creation/update
     */
    public function getSignalData(): array
    {
        return $this->getValidatedData([
            'title',
            'description',
            'currency_pair_id',
            'time_frame_id',
            'market_id',
            'open_price',
            'sl',
            'tp',
            'direction',
            'image'
        ]);
    }

    /**
     * Get the selected plan IDs
     */
    public function getPlanIds(): array
    {
        return $this->validated()['plans'] ?? [];
    }
}