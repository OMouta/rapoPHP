<?php

namespace App\Components;

use Rapo\Component;
use Rapo\Validator;
use function Rapo\h;

class ContactForm extends Component {
    protected $state = [
        'email' => '',
        'message' => '',
        'errors' => [],
        'success' => null
    ];

    public function validate($data) {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'message' => 'required|min:10'
        ]);

        if ($errors = $validator->errors()) {
            $this->state['errors'] = $errors;
            $this->state['success'] = null;
        } else {
            $this->state['success'] = "Real-time validation passed! (No reload)";
            $this->state['errors'] = [];
        }
        
        // Sync state back from inputs
        $this->state['email'] = $data['email'] ?? '';
        $this->state['message'] = $data['message'] ?? '';
    }

    public function view(): string {
        $errors = $this->state['errors'];
        $success = $this->state['success'];

        return h('div', ['class' => 'bg-white p-6 rounded-xl shadow-sm border mb-12'],
            h('h3', ['class' => 'font-bold mb-4'], 'Real-time Hydrated Form'),
            $success ? h('div', ['class' => 'mb-4 p-3 bg-green-100 text-green-700 rounded'], $success) : '',
            
            h('form', ['method' => 'POST', 'class' => 'text-left'],
                h('div', ['class' => 'mb-4'],
                    h('label', ['class' => 'block text-sm font-medium mb-1'], 'Email'),
                    h('input', [
                        'name' => 'email', 
                        'value' => $this->state['email'],
                        'class' => 'w-full border p-2 rounded ' . (isset($errors['email']) ? 'border-red-500' : ''),
                        'rapo-input' => 'email' // Sync state on typing if we wanted, but let's do click validate
                    ]),
                    isset($errors['email']) ? h('span', ['class' => 'text-red-500 text-xs'], $errors['email'][0]) : ''
                ),
                h('div', ['class' => 'mb-4'],
                    h('label', ['class' => 'block text-sm font-medium mb-1'], 'Message (min:10)'),
                    h('textarea', [
                        'name' => 'message', 
                        'class' => 'w-full border p-2 rounded ' . (isset($errors['message']) ? 'border-red-500' : ''),
                    ], $this->state['message']),
                    isset($errors['message']) ? h('span', ['class' => 'text-red-500 text-xs'], $errors['message'][0]) : ''
                ),
                h('button', [
                    'type' => 'button', 
                    'class' => 'bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors',
                    'rapo-click' => 'validate'
                ], 'Validate Instantly')
            )
        );
    }
}
