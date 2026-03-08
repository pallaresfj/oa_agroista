<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Center;

class CenterStudents extends Component
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
        $students = Center::findOrFail($this->centerId)
            ->students()
            ->orderBy('grade')
            ->orderBy('full_name')
            ->simplePaginate(10);

        return view('livewire.center-students', compact('students'));
    }
}
