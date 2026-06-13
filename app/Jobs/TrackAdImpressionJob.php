<?php

namespace App\Jobs;

use App\Models\AdCampaign;
use App\Models\AdCampaignLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class TrackAdImpressionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $campaignId,
        public string $ip,
        public ?string $userAgent,
        public ?int $userId,
    ) {}

    public function middleware(): array
    {
        $uaHash = md5($this->userAgent ?? '');

        return [
            (new WithoutOverlapping("ad_imp_{$this->campaignId}_{$this->ip}_{$uaHash}"))
                ->expireAfter(60),
        ];
    }

    public function handle(): void
    {
        $campaign = AdCampaign::find($this->campaignId);

        if (! $campaign) {
            return;
        }

        $campaign->increment('views_count');

        AdCampaignLog::create([
            'ad_campaign_id' => $this->campaignId,
            'user_id'        => $this->userId,
            'ip_address'     => $this->ip,
            'user_agent'     => $this->userAgent,
            'event_type'     => 'impression',
        ]);
    }
}
