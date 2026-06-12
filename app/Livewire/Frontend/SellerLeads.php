<?php

namespace App\Livewire\Frontend;

use App\Models\SellerLead;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SellerLeads extends Component
{
    use WithPagination;

    #[Url(as: 'period')]
    public string $period = 'all';

    public function setPeriod(string $period): void
    {
        $allowed = ['today', '7days', '30days', 'all'];

        $this->period = in_array($period, $allowed, true) ? $period : 'all';
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        $leads = SellerLead::query()
            ->forSeller($user)
            ->with(['listing', 'buyer'])
            ->inPeriod($this->period === 'all' ? null : $this->period)
            ->latest()
            ->paginate(15);

        return view('livewire.frontend.seller-leads', [
            'leads'  => $leads,
            'user'   => $user,
            'period' => $this->period,
        ]);
    }
}
