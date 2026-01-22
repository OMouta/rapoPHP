<?php

namespace App\Components;

use Rapo\Component;
use function Rapo\h;

class Counter extends Component {
    public function increment() {
        [$count, $setCount] = $this->useState('count', 0);
        $setCount($count + 1);
    }

    public function reset() {
        [$count, $setCount] = $this->useState('count', 0);
        $setCount(0);
    }

    public function view(): string {
        // Stateful Hook: persists across refreshes!
        [$count, $setCount] = $this->useState('count', 0);

        // JSX-style rendering using h() helper
        return h('div', ['class' => 'bg-white p-8 rounded-2xl shadow-xl max-w-sm mx-auto text-center'],
            h('h2', ['class' => 'text-2xl font-bold mb-4'], 'Live PHP Counter'),
            h('div', ['class' => 'text-6xl font-mono mb-6 text-blue-600'], (string)$count),
            h('div', ['class' => 'flex justify-center gap-4'],
                h('button', [
                    'rapo-click' => 'increment',
                    'class' => 'bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-full transition-all transform active:scale-95'
                ], 'Increment'),
                h('button', [
                    'rapo-click' => 'reset',
                    'class' => 'bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-full transition-all'
                ], 'Reset')
            ),
            h('p', ['class' => 'mt-4 text-xs text-gray-400 italic'], 'Note: This interaction is handled in PHP via AJAX (Rapo-Live)')
        );
    }
}
