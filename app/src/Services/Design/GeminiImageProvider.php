<?php
declare(strict_types=1);

namespace App\Services\Design;

/** Slike preko Google Gemini API-ja (Gemini image modeli, „Nano Banana"). */
final class GeminiImageProvider implements ImageProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly float $costPerImage,
    ) {
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function generate(array $design, array $request): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 110,
            CURLOPT_HTTPHEADER => [
                'x-goog-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'contents' => [[
                    'parts' => [['text' => PromptBuilder::imagePrompt($design, $request, !empty($request['generic']))]],
                ]],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE'],
                    'imageConfig' => ['aspectRatio' => '1:1'],
                ],
            ]),
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Gemini nije dostupan: ' . $error);
        }
        $data = json_decode((string)$body, true);
        if ($status !== 200) {
            throw new \RuntimeException('Gemini: ' . ($data['error']['message'] ?? ('HTTP ' . $status)));
        }
        foreach ($data['candidates'][0]['content']['parts'] ?? [] as $part) {
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if (!empty($inline['data'])) {
                $mime = $inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png';
                return [
                    'bytes' => base64_decode($inline['data']),
                    'ext' => $mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/webp' ? 'webp' : 'png'),
                    'cost_usd' => $this->costPerImage,
                    'model' => $this->model,
                ];
            }
        }
        // Bez slike: najčešće je zahtev blokiran (npr. zaštićen lik).
        $reason = $data['candidates'][0]['finishReason'] ?? ($data['promptFeedback']['blockReason'] ?? 'bez slike u odgovoru');
        throw new ContentRefusedException('Gemini nije vratio sliku: ' . $reason);
    }
}
