<?php

namespace App\Pages\Dashboard;

use Rapo\Controller;
use function Rapo\h;

class Index extends Controller {
    public $revalidate = 30; // ISR: Cache for 30 seconds

    public function getServerSideProps($request, $params) {
        // Simulating data fetch
        return [
            'user' => 'Developer',
            'last_login' => date('Y-m-d H:i:s'),
            'notification' => $_SESSION['dashboard_msg'] ?? null
        ];
    }

    public function updatePreference($data) {
        // Server Action: Handle preference update
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['dashboard_msg'] = "Preference updated at " . date('H:i:s');
    }

    public function index() {
        $user = $this->props['user'] ?? 'Guest';
        $time = $this->props['last_login'] ?? 'Never';
        $msg = $this->props['notification'] ?? null;

        return h('div', ['class' => 'space-y-8'],
            h('div', ['class' => 'flex justify-between items-center'],
                h('div', [],
                    h('h2', ['class' => 'text-3xl font-bold text-gray-900'], "Welcome back, $user!"),
                    h('p', ['class' => 'text-gray-500'], "Your last login was: $time")
                ),
                h('div', ['class' => 'text-right'],
                    h('span', ['class' => 'block text-sm font-medium text-gray-400'], 'System Status'),
                    h('span', ['class' => 'inline-flex items-center text-green-600 text-sm font-bold'], 
                        h('span', ['class' => 'w-2 h-2 bg-green-500 rounded-full mr-2'], ''), 'Healthy'
                    )
                )
            ),

            $msg ? h('div', ['class' => 'p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 rounded-lg shadow-sm'], 
                h('div', ['class' => 'flex items-center'],
                    h('span', ['class' => 'mr-3 text-xl'], 'ℹ️'),
                    h('p', ['class' => 'font-medium'], $msg)
                )
            ) : '',
            
            h('div', ['class' => 'grid grid-cols-1 md:grid-cols-3 gap-6'],
                $this->statCard('Active Projects', '12', '+2 since last week'),
                $this->statCard('Pending Tasks', '4', 'Check with team'),
                $this->statCard('Total Hours', '128', 'On track')
            ),

            h('div', ['class' => 'bg-white border rounded-2xl overflow-hidden'],
                h('div', ['class' => 'p-6 border-b flex justify-between items-center'],
                    h('h3', ['class' => 'font-bold text-gray-800'], 'Recent Activities'),
                    h('button', ['class' => 'text-blue-600 text-sm font-bold'], 'View All')
                ),
                h('div', ['class' => 'divide-y'],
                    $this->activityRow('Product Launch', 'Admin', 'Logged in', '2 mins ago'),
                    $this->activityRow('Database Backup', 'System', 'Completed', '1 hour ago'),
                    $this->activityRow('New User Signup', 'Sarah', 'Success', '3 hours ago')
                )
            ),

            h('form', ['method' => 'POST', 'class' => 'bg-gray-50 p-6 rounded-2xl border border-dashed border-gray-300'],
                \Rapo\formAction('updatePreference'),
                h('div', ['class' => 'flex items-center justify-between'],
                    h('div', [],
                        h('h4', ['class' => 'font-bold text-gray-800'], 'Preference Sync'),
                        h('p', ['class' => 'text-sm text-gray-500'], 'Trigger a Server Action to update session preferences.')
                    ),
                    h('button', ['type' => 'submit', 'class' => 'bg-black text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-gray-800 transition-all active:scale-95'], 'Run Server Action')
                )
            )
        );
    }

    private function statCard($label, $value, $trend) {
        return h('div', ['class' => 'p-6 bg-white border rounded-2xl shadow-sm'],
            h('p', ['class' => 'text-sm text-gray-500 mb-1'], $label),
            h('h4', ['class' => 'text-3xl font-black text-gray-900 mb-2'], $value),
            h('p', ['class' => 'text-xs text-green-600 font-bold'], $trend)
        );
    }

    private function activityRow($title, $user, $status, $time) {
        return h('div', ['class' => 'p-4 flex items-center justify-between hover:bg-gray-50'],
            h('div', ['class' => 'flex items-center gap-4'],
                h('div', ['class' => 'w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-gray-500'], substr($user, 0, 1)),
                h('div', [],
                    h('p', ['class' => 'text-sm font-bold text-gray-800'], "$user $status $title"),
                    h('p', ['class' => 'text-xs text-gray-400'], $time)
                )
            ),
            h('span', ['class' => 'text-gray-400'], '→')
        );
    }
}
