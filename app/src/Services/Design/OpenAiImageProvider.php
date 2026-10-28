<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Slike preko OpenAI Images API-ja (gpt-image modeli). */
final class OpenAiImageProvider implements ImageProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $quality,
        private readonly float $costPerImage,
    ) {
    }

    public function name(): string
    {
        return 'openai';
    }

    public function generate(array $design, array $request): array
    {
        $ch = curl_init('https://api.openai.com/v1/images/generations');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 110,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $this->model,
                'prompt' => PromptBuilder::imagePrompt($design, $request, !empty($request['generic'])),
                'size' => '1024x1024',
                'quality' => $this->quality,
                'n' => 1,
            ]),
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Servis za slike nije dostupan: ' . $error);
        }
        $data = json_decode((string)$body, true);
        if ($status !== 200 || empty($data['data'][0]['b64_json'])) {
            $msg = $data['error']['message'] ?? ('HTTP ' . $status);
            throw new \RuntimeException('Servis za slike: ' . $msg);
        }
        return [
            'bytes' => base64_decode($data['data'][0]['b64_json']),
            'ext' => 'png',
            'cost_usd' => $this->costPerImage,
            'model' => $this->model,
        ];
    }
}
