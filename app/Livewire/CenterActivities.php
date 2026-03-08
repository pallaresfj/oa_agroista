<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Center;

class CenterActivities extends Component
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
        $activities = Center::findOrFail($this->centerId)
            ->activities()
            ->simplePaginate(5);

        return view('livewire.center-activities', compact('activities'));
    }
}
