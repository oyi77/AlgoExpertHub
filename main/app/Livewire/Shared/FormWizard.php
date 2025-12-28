<?php

namespace App\Livewire\Shared;

use Livewire\Component;

abstract class FormWizard extends Component
{
    public $currentStep = 1;
    public $data = [];
    
    // To be implemented by child classes
    abstract public function steps(): array;
    abstract public function rules(): array;
    abstract public function submit();

    public function nextStep()
    {
        $this->validateStep($this->currentStep);
        
        $this->currentStep++;
        $this->dispatch('step-changed', ['step' => $this->currentStep]);
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatch('step-changed', ['step' => $this->currentStep]);
        }
    }

    public function goToStep($step)
    {
        // Only allow going back or one step forward if current is valid
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
            $this->dispatch('step-changed', ['step' => $this->currentStep]);
        } elseif ($step == $this->currentStep + 1) {
            $this->nextStep();
        }
    }
    
    protected function validateStep($step)
    {
        // Get rules for current step
        // We assume rules are keyed like 'data.field_name'
        // Child classes should define rules() returning full array
        // We filter rules based on fields present in the step's view or defined in steps() config
        
        // Simplified for abstract: validate specific fields if mapped, or all 'data.*' if simplistic
        // A robust way: steps() returns ['label' => 'Step 1', 'fields' => ['data.name', 'data.email']]
        
        $stepConfig = $this->steps()[$step - 1] ?? null;
        if ($stepConfig && isset($stepConfig['fields'])) {
            $rules = array_intersect_key($this->rules(), array_flip($stepConfig['fields']));
            $this->validate($rules);
        }
    }

    public function render()
    {
        return view('livewire.shared.form-wizard', [
            'steps' => $this->steps(),
        ]);
    }
}
