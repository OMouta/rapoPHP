<?php

namespace App\Pages\examples\note_taking;

use Rapo\Component;
use Rapo\Components\Link;
use App\Models\Note;

class Page extends Component
{
    public function addNote($data)
    {
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';

        if (!empty($title)) {
            $note = new Note([
                'title' => $title,
                'content' => $content
            ]);
            $note->save();

            // Clear state after saving
            $this->useState('title', '')[1]('');
            $this->useState('content', '')[1]('');
        }
    }

    public function deleteNote()
    {
        $id = $_POST['note_id'] ?? null;
        if ($id) {
            $note = Note::find($id);
            if ($note) {
                $note->delete();
            }
        }
    }

    public function view(): string
    {
        $notes = Note::orderBy('created_at', 'DESC')->get();
        [$title, $setTitle] = $this->useState('title', '');
        [$content, $setContent] = $this->useState('content', '');

        return h('div', ['class' => 'max-w-4xl mx-auto py-12 px-4'], [
            component(Link::class, ['href' => '/examples', 'class' => 'text-slate-400 hover:text-sky-600 transition mb-8 inline-block'], '← Back to Examples'),
            
            h('div', ['class' => 'flex flex-col md:flex-row gap-12'], [
                // sidebar form
                h('div', ['class' => 'md:w-1/3'], [
                    h('h1', ['class' => 'text-3xl font-black mb-6'], 'Take a Note'),
                    h('form', ['rapo-submit' => 'addNote', 'class' => 'space-y-4 p-6 bg-white rounded-2xl border border-slate-100 shadow-sm'], [
                        h('div', [], [
                            h('label', ['class' => 'block text-sm font-bold text-slate-700 mb-1'], 'Title'),
                            h('input', [
                                'name' => 'title',
                                'type' => 'text',
                                'value' => $title,
                                'rapo-input' => 'title',
                                'placeholder' => 'Note title...',
                                'class' => 'w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 outline-none transition',
                                'required' => true
                            ])
                        ]),
                        h('div', [], [
                            h('label', ['class' => 'block text-sm font-bold text-slate-700 mb-1'], 'Content'),
                            h('textarea', [
                                'name' => 'content',
                                'rows' => 4,
                                'value' => $content,
                                'rapo-input' => 'content',
                                'placeholder' => 'What\'s on your mind?',
                                'class' => 'w-full px-4 py-2 rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 outline-none transition'
                            ], $content)
                        ]),
                        h('button', [
                            'type' => 'submit',
                            'class' => 'w-full py-3 bg-sky-600 text-white font-bold rounded-xl hover:bg-sky-700 transition'
                        ], 'Save Note')
                    ])
                ]),

                // notes list
                h('div', ['class' => 'md:w-2/3'], [
                    h('h2', ['class' => 'text-2xl font-bold mb-6'], 'Your Notes'),
                    empty($notes) ? 
                        h('div', ['class' => 'bg-slate-50 border-2 border-dashed border-slate-200 rounded-3xl p-12 text-center text-slate-400'], [
                            h('span', ['class' => 'text-4xl mb-4 block'], '∅'),
                            'No notes yet. Start by creating one!'
                        ]) :
                        h('div', ['class' => 'grid gap-4'], array_map(function($note) {
                            return h('div', ['class' => 'p-6 bg-white rounded-2xl border border-slate-100 shadow-sm flex justify-between items-start group hover:border-sky-100 transition'], [
                                h('div', [], [
                                    h('h3', ['class' => 'text-xl font-bold text-slate-900 mb-1'], $note->title),
                                    h('p', ['class' => 'text-slate-600'], $note->content),
                                    h('span', ['class' => 'text-[10px] text-slate-400 mt-4 block uppercase font-bold tracking-wider'], 
                                        date('M d, Y • H:i', strtotime($note->created_at))
                                    )
                                ]),
                                h('button', [
                                    'rapo-click' => 'deleteNote',
                                    'note_id' => $note->id,
                                    'class' => 'p-2 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition'
                                ], [
                                    h('span', ['class' => 'text-lg'], '✕')
                                ])
                            ]);
                        }, $notes))
                ])
            ])
        ]);
    }
}
