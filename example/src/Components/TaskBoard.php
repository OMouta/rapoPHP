<?php

namespace App\Components;

use Rapo\Component;
use App\Models\Task;
use function Rapo\h;

class TaskBoard extends Component {
    public function addTask($data) {
        $title = $data['title'] ?? '';
        if (empty($title)) return;
        
        $task = new Task(['title' => $title, 'status' => 'todo']);
        $task->save();
        
        [, $setNewTask] = $this->useState('new_task', '');
        $setNewTask(''); // Reset input
    }

    public function toggleTask() {
        $id = $_POST['task_id'] ?? null;
        if ($id) {
            $task = Task::find($id);
            if ($task) {
                $task->status = ($task->status === 'done') ? 'todo' : 'done';
                $task->save();
            }
        }
    }

    public function view(): string {
        $tasks = Task::all();
        [$newTask] = $this->useState('new_task', '');

        return h('div', ['class' => 'bg-white rounded-2xl shadow-xl overflow-hidden'],
            h('div', ['class' => 'p-6 bg-gradient-to-r from-blue-600 to-indigo-600'],
                h('h2', ['class' => 'text-xl font-bold text-white'], 'Global Task Board')
            ),
            h('div', ['class' => 'p-6'],
                h('form', ['rapo-submit' => 'addTask', 'class' => 'mb-8 flex gap-3'],
                    h('input', [
                        'id' => 'task-input',
                        'name' => 'title',
                        'rapo-input' => 'new_task',
                        'value' => $newTask,
                        'placeholder' => 'What needs doing?',
                        'class' => 'flex-grow border-2 border-gray-100 p-3 rounded-xl focus:border-blue-500 outline-none transition'
                    ]),
                    h('button', ['type' => 'submit', 'class' => 'bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:shadow-lg transition'], 'Add Task')
                ),
                h('div', ['class' => 'space-y-3'],
                    ...array_map(fn($t) => 
                        h('div', ['class' => 'flex items-center justify-between p-4 rounded-xl border-2 ' . ($t->status === 'done' ? 'bg-gray-50 border-transparent' : 'bg-white border-gray-50')],
                            h('div', ['class' => 'flex items-center gap-4'],
                                h('button', [
                                    'rapo-click' => 'toggleTask', 
                                    'task_id' => $t->id,
                                    'class' => 'w-6 h-6 rounded-lg border-2 ' . ($t->status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-200')
                                ], $t->status === 'done' ? '✓' : ''),
                                h('span', ['class' => $t->status === 'done' ? 'line-through text-gray-400' : 'text-gray-700 font-medium'], $t->title)
                            )
                        )
                    , $tasks)
                )
            )
        );
    }
}
