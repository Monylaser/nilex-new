<?php

namespace App\Console\Commands;

use App\Models\AdCampaign;
use Illuminate\Console\Command;

class ExpireCampaigns extends Command
{
    protected $signature = 'campaigns:expire';

    protected $description = 'Mark ad campaigns as expired when their end date has passed';

    public function handle(): int
    {
        AdCampaign::query()
            ->where('ends_at', '<', now())
            ->where('status', '!=', 'expired')
            ->each(function ($campaign) {
                $campaign->update(['status' => 'expired']);
            });

        $this->info('Expired campaigns updated.');

        return self::SUCCESS;
    }
}
