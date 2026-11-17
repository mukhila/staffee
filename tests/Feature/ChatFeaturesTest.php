<?php

namespace Tests\Feature;

use App\Models\ChatChannel;
use App\Models\ChatChannelMessage;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Services\Chat\MentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $ann;
    private User $bob;
    private User $eve;
    private ChatChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->ann = User::factory()->create(['name' => 'Ann Lee', 'is_active' => true]);
        $this->bob = User::factory()->create(['name' => 'Bob Stone', 'is_active' => true]);
        $this->eve = User::factory()->create(['name' => 'Eve Outsider', 'is_active' => true]);

        $this->channel = ChatChannel::create(['name' => 'general', 'slug' => 'general', 'type' => 'general', 'created_by' => $this->ann->id]);
        $this->channel->members()->attach([$this->ann->id, $this->bob->id]);
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function test_direct_attachment_is_private_and_only_for_the_two_people(): void
    {
        $response = $this->actingAs($this->ann)->postJson(route('chat.send'), [
            'to_id' => $this->bob->id,
            'attachment' => UploadedFile::fake()->create('plan.pdf', 20, 'application/pdf'),
        ])->assertOk();

        $message = Message::firstOrFail();
        $this->assertSame('plan.pdf', $message->attachment_name);
        Storage::disk('local')->assertExists($message->attachment_path);
        Storage::disk('public')->assertMissing($message->attachment_path);

        // The stored path is not exposed; only the protected URL is.
        $response->assertJsonMissingPath('attachment_path');
        $response->assertJsonPath('attachment_url', route('chat.attachments.direct', $message->id));

        $this->actingAs($this->bob)->get(route('chat.attachments.direct', $message->id))
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->eve)->get(route('chat.attachments.direct', $message->id))->assertNotFound();
    }

    public function test_dangerous_file_types_are_refused(): void
    {
        foreach (['page.html' => 'text/html', 'logo.svg' => 'image/svg+xml', 'run.php' => 'application/x-php'] as $name => $mime) {
            $this->actingAs($this->ann)->postJson(route('chat.send'), [
                'to_id' => $this->bob->id,
                'attachment' => UploadedFile::fake()->createWithContent($name, '<script>alert(1)</script>')->mimeType($mime),
            ])->assertStatus(422)->assertJsonValidationErrors('attachment');

            $this->actingAs($this->ann)->postJson(route('chat.channels.send', $this->channel), [
                'attachment' => UploadedFile::fake()->createWithContent($name, '<script>alert(1)</script>')->mimeType($mime),
            ])->assertStatus(422);
        }

        $this->assertSame(0, Message::count() + ChatChannelMessage::count());
    }

    public function test_channel_attachment_only_for_members(): void
    {
        $this->actingAs($this->ann)->postJson(route('chat.channels.send', $this->channel), [
            'attachment' => UploadedFile::fake()->image('photo.png'),
        ])->assertOk();

        $msg = ChatChannelMessage::firstOrFail();
        Storage::disk('local')->assertExists($msg->attachment_path);

        $this->actingAs($this->bob)->get(route('chat.attachments.channel', $msg->id))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($this->eve)->get(route('chat.attachments.channel', $msg->id))->assertNotFound();
    }

    public function test_older_public_attachments_still_open_and_can_be_moved(): void
    {
        Storage::disk('public')->put('chat-attachments/old.pdf', 'pdf-bytes');
        $message = Message::create([
            'from_id' => $this->ann->id, 'to_id' => $this->bob->id,
            'attachment_path' => 'chat-attachments/old.pdf', 'attachment_type' => 'application/pdf',
        ]);

        $this->actingAs($this->bob)->get(route('chat.attachments.direct', $message->id))->assertOk();

        $this->artisan('chat:secure-attachments')->expectsOutputToContain('1 attachments moved')->assertSuccessful();

        Storage::disk('local')->assertExists('chat-attachments/old.pdf');
        Storage::disk('public')->assertMissing('chat-attachments/old.pdf');
        $this->actingAs($this->bob)->get(route('chat.attachments.direct', $message->id))->assertOk();
    }

    public function test_message_text_is_escaped_on_the_channel_page(): void
    {
        ChatChannelMessage::create(['channel_id' => $this->channel->id, 'user_id' => $this->bob->id, 'body' => '<img src=x onerror=alert(1)>']);

        $this->actingAs($this->ann)->get(route('chat.channels.show', $this->channel))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_polling_returns_only_newer_channel_messages(): void
    {
        $first  = ChatChannelMessage::create(['channel_id' => $this->channel->id, 'user_id' => $this->bob->id, 'body' => 'one']);
        $second = ChatChannelMessage::create(['channel_id' => $this->channel->id, 'user_id' => $this->bob->id, 'body' => 'two']);

        $this->actingAs($this->ann)->getJson(route('chat.channels.messages', $this->channel) . '?after=' . $first->id)
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $second->id);
    }

    // ── Search ────────────────────────────────────────────────────────────────

    public function test_search_finds_only_your_own_conversations(): void
    {
        Message::create(['from_id' => $this->ann->id, 'to_id' => $this->bob->id, 'body' => 'Budget review on Friday']);
        Message::create(['from_id' => $this->bob->id, 'to_id' => $this->eve->id, 'body' => 'Secret budget gossip']);
        ChatChannelMessage::create(['channel_id' => $this->channel->id, 'user_id' => $this->bob->id, 'body' => 'Budget draft attached']);

        $private = ChatChannel::create(['name' => 'hr', 'slug' => 'hr', 'type' => 'general', 'created_by' => $this->eve->id, 'is_private' => true]);
        $private->members()->attach($this->eve->id);
        ChatChannelMessage::create(['channel_id' => $private->id, 'user_id' => $this->eve->id, 'body' => 'Budget cuts list']);

        $this->actingAs($this->ann)->get(route('chat.search', ['q' => 'budget']))
            ->assertOk()
            ->assertSee('review on Friday')
            ->assertSee('draft attached')
            ->assertDontSee('gossip')
            ->assertDontSee('cuts list');
    }

    public function test_search_escapes_results(): void
    {
        Message::create(['from_id' => $this->ann->id, 'to_id' => $this->bob->id, 'body' => 'budget <script>alert(1)</script>']);

        $this->actingAs($this->ann)->get(route('chat.search', ['q' => 'budget']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<mark class="px-0">budget</mark>', false);
    }

    // ── Mentions ──────────────────────────────────────────────────────────────

    public function test_mention_notifies_member_but_not_sender_or_outsider(): void
    {
        $this->actingAs($this->ann)->postJson(route('chat.channels.send', $this->channel), [
            'body' => '@bob stone can you check this? cc @Eve Outsider and @Ann Lee',
        ])->assertOk();

        $this->assertSame(1, Notification::where('type', 'chat_mention')->count());
        $note = Notification::where('type', 'chat_mention')->firstOrFail();
        $this->assertSame($this->bob->id, $note->user_id);
        $this->assertStringContainsString('Ann Lee mentioned you in #general', $note->title);
        $this->assertStringContainsString('#message-', $note->url);
    }

    public function test_longer_names_win_and_partial_words_do_not_match(): void
    {
        $annie = User::factory()->create(['name' => 'Ann Lee Smith']);
        $this->channel->members()->attach($annie->id);
        $members = $this->channel->members()->get();

        $service = app(MentionService::class);

        $this->assertSame([$annie->id], $service->mentioned('hi @Ann Lee Smith', $members, $this->bob->id)->pluck('id')->all());
        $this->assertSame([], $service->mentioned('email @Bob Stoneware', $members, $this->ann->id)->pluck('id')->all());
        $this->assertSame([], $service->mentioned('no mentions here', $members, $this->ann->id)->all());
    }
}
