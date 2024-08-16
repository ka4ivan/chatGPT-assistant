<?php

namespace App\Http\Client\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
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

        $messagesRaw = $this->getMessages($thReadId, config('openai.assistant.chat'));
        $messages = [];

        foreach (Arr::get($messagesRaw, 'data', []) as $messageRaw) {
            $messages[] = Arr::get($messageRaw, 'role') . ': ' .Arr::get($messageRaw, 'content.0.text.value');
        }

        return $messages;
    }

    public function askAssistantWithImage(Request $request)
    {
        $request->validate([
            'text' => 'nullable|string|max:2048',
            'image' => 'required|image',
        ]);

        $thReadId = $this->threadCreate();

        $file = $this->fileUpload($request->file('image'));
        $fileId = Arr::get($file, 'id');

        // Добавляємо повідомлення в thread
        $this->addMessageWithImage($thReadId, $fileId);

        $res =  $this->getMessages($thReadId, config('openai.assistant.image'));

        $this->fileDelete($fileId);

        return response()->json([
            'data' => json_decode(Arr::get($res, 'data.0.content.0.text.value'), true),
        ]);
    }

    public function threadCreate(User $user = null): string
    {
        $thread = OpenAI::threads()->create([])->toArray();
        $threadId = (string) Arr::get($thread, 'id');

        if ($user) {
            $user->setAttribute('thread_id', $threadId)->saveQuietly();
        }

        return $threadId;
    }

    protected function fileUpload(UploadedFile $file)
    {
        $fileName = $file->getClientOriginalName();

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . config('openai.api_key'),
        ])->attach(
            'file', $file->openFile(), $fileName
        )->post('https://api.openai.com/v1/files', [
            'purpose' => 'user_data',
        ]);

//        return \OpenAI\Laravel\Facades\OpenAI::files()->upload([
//            'file' => $file->openFile(),
//            'purpose' => 'user_data',
//        ])->toArray();
    }

    protected function fileDelete(string $fileId)
    {
        return \OpenAI\Laravel\Facades\OpenAI::files()->delete($fileId)->toArray();
    }

    protected function addMessage(string $thread, string $text)
    {
        return OpenAI::threads()->messages()->create($thread, [
            'role' => 'user',
            'content' => $text,
        ])->toArray();
    }

    protected function getMessages(string $thReadId, string $assistantId)
    {
        // Запускаємо run в цьому thread
        $run = OpenAI::threads()->runs()->create($thReadId, [
            'assistant_id' => $assistantId,
        ])->toArray();

        // Перевірка статусу повідомлення
        $maxAttempts = 100; // Максимальна кількість спроб
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

        $messagesRaw = OpenAI::threads()->messages()->list($thReadId)->toArray();

        return $messagesRaw;
    }

    protected function addMessageWithImage(string $thread, string $imageId)
    {
        return OpenAI::threads()->messages()->create($thread, [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'image_file',
                    'image_file' => [
                        'file_id' => $imageId,
                        'detail' => 'high',
                    ],
                 ]
            ],
        ])->toArray();
    }
}
