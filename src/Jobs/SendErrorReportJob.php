<?php

namespace Luismabenitez\Beacon\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SendErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $backoff = 10;

    /** @var array */
    protected $payload;

    /** @var string */
    protected $endpoint;

    /** @var string */
    protected $projectKey;

    /** @var int */
    protected $timeout;

    /**
     * @param array $payload
     * @param string $endpoint
     * @param string $projectKey
     * @param int $timeout
     */
    public function __construct(array $payload, string $endpoint, string $projectKey, int $timeout = 5)
    {
        $this->payload = $payload;
        $this->endpoint = $endpoint;
        $this->projectKey = $projectKey;
        $this->timeout = $timeout;
    }

    public function handle()
    {
        try {
            $client = new Client([
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            $response = $client->post($this->endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Beacon-Key' => $this->projectKey,
                ],
                'json' => $this->payload,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                Log::warning('Beacon: Queued report failed', [
                    'status' => $statusCode,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Beacon: Queued HTTP request failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array
     */
    public function tags()
    {
        return ['beacon', 'error-report'];
    }
}
