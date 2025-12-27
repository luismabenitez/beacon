<?php

namespace Luismabenitez\Beacon\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        protected array $payload,
        protected string $endpoint,
        protected string $projectKey,
        protected int $timeout = 5
    ) {}

    public function handle(): void
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Beacon-Key' => $this->projectKey,
            ])
            ->post($this->endpoint, $this->payload);

        if ($response->failed()) {
            Log::warning('Beacon: Queued report failed', [
                'status' => $response->status(),
            ]);
        }
    }

    public function tags(): array
    {
        return ['beacon', 'error-report'];
    }
}
