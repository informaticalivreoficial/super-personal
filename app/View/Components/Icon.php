<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Ícone Heroicons (outline 24) via <x-icon name="users" class="w-4 h-4" />.
 * Arquivos em resources/views/components/icons/{nome}.blade.php.
 */
class Icon extends Component
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = preg_replace('/[^a-z0-9-]/', '', strtolower($name)) ?: 'question-mark-circle';

        if (! is_file(resource_path('views/components/icons/'.$this->name.'.blade.php'))) {
            $this->name = 'question-mark-circle';
        }
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
