<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Center;

class CenterBudgets extends Component
{
    use WithPagination;

    public $centerId;

    protected $paginationTheme = 'tailwind';

    public function mount($centerId)
    {
        $this->centerId = $centerId;
    }

    public function render()
    {
        $center = Center::findOrFail($this->centerId);

        $budgets = $center->budgets()->orderBy('item')->simplePaginate(10);

        $totalBudgets = $center->budgets()
            ->get()
            ->sum(fn($b) => $b->quantity * $b->unit_value);

        return view('livewire.center-budgets', [
            'budgets' => $budgets,
            'totalBudgets' => $totalBudgets,
        ]);
    }
}
