<?php

namespace App\Components;

use Rapo\Component;
use function Rapo\h;

class TodoList extends Component {
    public function addTask($data) {
        $taskName = $data['task'] ?? '';
        if (empty($taskName)) return;

        [$tasks, $setTasks] = $this->useState('tasks', []);
        $tasks[] = ['id' => uniqid(), 'text' => $taskName, 'done' => false];
        $setTasks($tasks);

        // Reset the input state
        $this->useState('current_input', '');
        $_SESSION['rapo_state'][static::class]['current_input'] = '';
    }

    public function toggleTask() {
        $taskId = $_POST['task_id'] ?? null;
        [$tasks, $setTasks] = $this->useState('tasks', []);
        
        foreach ($tasks as &$task) {
            if ($task['id'] === $taskId) {
                $task['done'] = !$task['done'];
            }
        }
        $setTasks($tasks);
    }

    public function deleteTask() {
        $taskId = $_POST['task_id'] ?? null;
        [$tasks, $setTasks] = $this->useState('tasks', []);
        $tasks = array_filter($tasks, fn($t) => $t['id'] !== $taskId);
        $setTasks(array_values($tasks));
    }

    public function view(): string {
        [$tasks] = $this->useState('tasks', [
            ['id' => '1', 'text' => 'Make RapoPHP even better', 'done' => true],
            ['id' => '2', 'text' => 'Build a cool app', 'done' => false],
        ]);

        [$currentInput] = $this->useState('current_input', '');

        return h('div', ['class' => 'bg-white p-6 rounded-xl shadow-lg border border-gray-100'],
            h('h2', ['class' => 'text-2xl font-bold mb-6 text-gray-800'], 'Rapo-Live Todo List'),
            
            // Live Form
            h('form', ['rapo-submit' => 'addTask', 'class' => 'flex gap-2 mb-6'],
                h('input', [
                    'id' => 'todo-input',
                    'name' => 'task',
                    'type' => 'text',
                    'placeholder' => 'What needs to be done?',
                    'class' => 'flex-grow border p-3 rounded-lg focus:ring-2 focus:ring-blue-400 outline-none transition',
                    'rapo-input' => 'current_input', // Two-way binding
                    'value' => $currentInput
                ]),
                h('button', [
                    'type' => 'submit',
                    'class' => 'bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-bold'
                ], 'Add')
            ),

            // Tasks List
            h('ul', ['class' => 'space-y-3'],
                ...array_map(fn($task) => 
                    h('li', ['class' => 'flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition shadow-sm'],
                        h('div', ['class' => 'flex items-center gap-3'],
                            h('button', [
                                'rapo-click' => 'toggleTask', 
                                'task_id' => $task['id'], // We'll add this to extra params in runtime
                                'class' => 'w-6 h-6 rounded-full border-2 flex items-center justify-center ' . ($task['done'] ? 'bg-green-500 border-green-500' : 'border-gray-300')
                            ], $task['done'] ? '✓' : ''),
                            h('span', [
                                'class' => 'text-gray-700 ' . ($task['done'] ? 'line-through opacity-50' : '')
                            ], $task['text'])
                        ),
                        h('button', [
                            'rapo-click' => 'deleteTask',
                            'task_id' => $task['id'],
                            'class' => 'text-red-400 hover:text-red-600 transition p-2'
                        ], '✕')
                    )
                , $tasks)
            ),

            h('div', ['class' => 'mt-6 text-sm text-gray-400 flex justify-between'],
                h('span', [], count(array_filter($tasks, fn($t) => !$t['done'])) . ' items left'),
                h('span', [], 'State: ' . ($currentInput ?: 'Empty'))
            )
        );
    }
}
