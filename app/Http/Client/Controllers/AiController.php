<?php

namespace App\Http\Client\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use OpenAI\Laravel\Facades\OpenAI;

final class AiController extends Controller
{
    public function askAssistant(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:2048',
        ]);

        $user = $request->user();

        $thReadId = $user?->thread_id ?: $this->threadCreate($user);

        // Добавляємо повідомлення в thread
        $this->addMessage($thReadId, $request->text);

        // Запускаємо run в цьому thread
        $run = OpenAI::threads()->runs()->create($thReadId, [
            'assistant_id' => config('openai.assistant'),
        ])->toArray();

        // Перевірка статусу повідомлення
        $maxAttempts = 30; // Максимальна кількість спроб
        $attempt = 0; // Лічильник спроб

        while (Arr::get($run, 'status') !== 'completed') {
            // Перевірка кількості спроб
            if ($attempt >= $maxAttempts) {
                // Додаткова обробка, якщо цикл не завершується
                throw new \Exception('Exceeded maximum attempts to complete the run.');
            }

            $run = OpenAI::threads()->runs()->retrieve($thReadId, Arr::get($run, 'id'))->toArray();
            sleep(1);
            $attempt++;
        }


        $messages = [];
        $messagesRaw = OpenAI::threads()->messages()->list($thReadId)->toArray();

        foreach (Arr::get($messagesRaw, 'data', []) as $messageRaw) {
            $messages[] = Arr::get($messageRaw, 'role') . ': ' .Arr::get($messageRaw, 'content.0.text.value');
        }

        return $messages;
    }

    public function threadCreate(User $user): string
    {
        $thread = OpenAI::threads()->create([])->toArray();
        $threadId = (string) Arr::get($thread, 'id');

        $user->setAttribute('thread_id', $threadId)->saveQuietly();

        return $threadId;
    }

    protected function addMessage(string $thread, string $text)
    {
        return OpenAI::threads()->messages()->create($thread, [
            'role' => 'user',
            'content' => $text,
        ])->toArray();
    }
}
