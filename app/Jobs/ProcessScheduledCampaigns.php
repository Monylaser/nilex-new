<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Notifications\CampaignNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessScheduledCampaigns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $campaigns = Campaign::due()->get();

        foreach ($campaigns as $campaign) {
            $this->dispatchCampaign($campaign);
        }
    }

    private function dispatchCampaign(Campaign $campaign): void
    {
        try {
            $recipients = $campaign->resolveRecipients()->get();
            $count      = 0;

            foreach ($recipients as $user) {
                $user->notify(new CampaignNotification($campaign));
                $count++;
            }

            $campaign->update([
                'status'           => Campaign::STATUS_SENT,
                'sent_at'          => now(),
                'recipients_count' => $count,
            ]);

            Log::info("Campaign [{$campaign->id}] '{$campaign->title}' sent to {$count} recipients.");

        } catch (Throwable $e) {
            $campaign->update(['status' => Campaign::STATUS_FAILED]);
            Log::error("Campaign [{$campaign->id}] failed: " . $e->getMessage());
        }
    }
}
