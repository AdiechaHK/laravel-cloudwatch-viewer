<?php

namespace Adiechahk\CloudWatchViewer\Tests\Fakes;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Adiechahk\CloudWatchViewer\Http\Controllers\LogViewerController;

class FakeLogViewerController extends LogViewerController
{
    private CloudWatchLogsClient $fakeClient;

    public function setClient(CloudWatchLogsClient $client): void
    {
        $this->fakeClient = $client;
    }

    protected function makeClient(): CloudWatchLogsClient
    {
        return $this->fakeClient;
    }

    // No sleeping in tests
    protected function pollIntervalSeconds(): int { return 0; }
    protected function maxPollAttempts(): int { return 3; }
}
