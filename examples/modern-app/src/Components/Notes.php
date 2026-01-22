<?php

namespace App\Components;

use Rapo\Component;
use App\Models\Note;
use function Rapo\h;

class Notes extends Component {
    public function addNote($data) {
        $title = $data['title'] ?? '';
        if (empty($title)) return;

        $note = new Note(['title' => $title, 'content' => 'Sample content']);
        $note->save();

        [$flash, $sendFlash] = $this->useFlash();
        $sendFlash('success', 'Note added successfully!');
    }

    public function deleteNote() {
        $id = $_POST['note_id'] ?? null;
        if ($id) {
            $note = Note::find($id);
            if ($note) $note->delete();
        }
    }

    public function view(): string {
        $notes = Note::all();
        [$flashMessages] = $this->useFlash();

        return h('div', ['class' => 'bg-white p-6 rounded-xl shadow-lg mt-8'],
            h('h2', ['class' => 'text-2xl font-bold mb-4'], 'Persistent Notes (SQLite)'),
            
            // Flash Messages
            h('div', ['class' => 'space-y-2 mb-4'],
                ...array_map(fn($f) => 
                    h('div', ['class' => 'p-3 rounded bg-green-100 text-green-700'], $f['message'])
                , $flashMessages)
            ),

            h('form', ['rapo-submit' => 'addNote', 'class' => 'flex gap-2 mb-4'],
                h('input', ['name' => 'title', 'placeholder' => 'Add a persistent note...', 'class' => 'flex-grow border p-2 rounded']),
                h('button', ['type' => 'submit', 'class' => 'bg-purple-600 text-white px-4 py-2 rounded'], 'Save')
            ),

            h('div', ['class' => 'space-y-2'],
                ...array_map(fn($note) => 
                    h('div', ['class' => 'flex justify-between items-center p-3 border-b'],
                        h('span', [], $note->title),
                        h('button', ['rapo-click' => 'deleteNote', 'note_id' => $note->id, 'class' => 'text-red-500'], 'Delete')
                    )
                , $notes)
            )
        );
    }
}
