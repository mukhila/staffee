<?php

namespace App\Notifications;

use App\Models\Monitoring\MonitoringAppRule;
use App\Services\Monitoring\ProductivityService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to each monitored employee about their own activity for the past week.
 */
class WeeklyActivitySummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array  $summary  result of ProductivityService::summarise(), with top_apps as a plain array
     */
    public function __construct(
        private readonly array $summary,
        private readonly string $weekStart,
        private readonly string $weekEnd,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $from = Carbon::parse($this->weekStart);
        $to   = Carbon::parse($this->weekEnd);
        $fmt  = fn (int $seconds) => ProductivityService::formatDuration($seconds);

        $mail = (new MailMessage)
            ->subject("Your activity summary: {$from->format('d M')} – {$to->format('d M Y')}")
            ->greeting("Hello {$notifiable->name},")
            ->line('Here is a summary of the activity recorded on your work computer last week. Only you receive this email.')
            ->line("**Active time:** {$fmt($this->summary['active_seconds'])}")
            ->line("**Productive:** {$fmt($this->summary['seconds']['productive'])} ({$this->summary['productive_pct']}%)")
            ->line("**Neutral:** {$fmt($this->summary['seconds']['neutral'])}")
            ->line("**Unproductive:** {$fmt($this->summary['seconds']['unproductive'])}")
            ->line("**Idle:** {$fmt($this->summary['idle_seconds'])}");

        if (!empty($this->summary['top_apps'])) {
            $mail->line('**Most used applications**');
            foreach ($this->summary['top_apps'] as $app) {
                $label = MonitoringAppRule::CATEGORY_LABELS[$app['category']] ?? ucfirst($app['category']);
                $mail->line("- {$app['app']}: {$fmt($app['seconds'])} ({$label})");
            }
        }

        return $mail
            ->action('View my activity', route('staff.my-activity.index', ['week' => $from->toDateString()]))
            ->line('If something looks wrong, such as a work tool counted as unproductive, please tell your manager or HR.')
            ->salutation('Regards, Staffee');
    }

    public function toArray(object $notifiable): array
    {
        $from = Carbon::parse($this->weekStart);

        return [
            'type'  => 'weekly_activity_summary',
            'title' => 'Your weekly activity summary',
            'body'  => 'Active ' . ProductivityService::formatDuration($this->summary['active_seconds'])
                . ", {$this->summary['productive_pct']}% productive for the week of {$from->format('d M')}.",
            'url'   => route('staff.my-activity.index', ['week' => $from->toDateString()]),
        ];
    }
}
