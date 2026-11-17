<?php

namespace App\Console\Commands;

use App\Models\ChatChannelMessage;
use App\Models\Message;
use App\Services\Chat\ChatAttachmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Chat attachments used to be saved on the public disk, where anyone with the
 * link could open them. This moves them to private storage, after which they
 * are only served to people in the conversation.
 */
class SecureChatAttachments extends Command
{
    protected $signature = 'chat:secure-attachments {--dry-run : Show what would move without moving anything}';

    protected $description = 'Move chat attachments from public storage to private storage';

    public function handle(): int
    {
        $public  = Storage::disk('public');
        $private = Storage::disk(ChatAttachmentService::DISK);
        $moved = $missing = 0;

        foreach ([Message::class, ChatChannelMessage::class] as $model) {
            $model::whereNotNull('attachment_path')->orderBy('id')->chunkById(200, function ($rows) use ($public, $private, &$moved, &$missing) {
                foreach ($rows as $row) {
                    $path = $row->attachment_path;

                    if ($private->exists($path)) {
                        continue; // already private
                    }
                    if (!$public->exists($path)) {
                        $missing++;
                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("Would move {$path}");
                    } else {
                        $private->put($path, $public->get($path));
                        $public->delete($path);
                        if (!$row->attachment_name) {
                            $row->forceFill(['attachment_name' => basename($path)])->saveQuietly();
                        }
                    }
                    $moved++;
                }
            });
        }

        $verb = $this->option('dry-run') ? 'would be moved' : 'moved to private storage';
        $this->info("{$moved} attachments {$verb}." . ($missing ? " {$missing} files were already missing." : ''));

        return self::SUCCESS;
    }
}
