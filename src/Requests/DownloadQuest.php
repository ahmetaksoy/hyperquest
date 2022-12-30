<?php namespace AhmetAksoy\HyperQuest\Requests;

use Psr\Http\Message\StreamInterface;

class DownloadQuest extends HyperQuest
{
    protected $autoExecute = true;

    protected function onError(): void
    {}
    protected function onSuccess(): void
    {}
    protected function onComplete(): void
    {}
    protected function requestReady(): bool
    {
        if (!array_key_exists('localPath', $this->parameters)) {
            if (array_key_exists('sink', $this->guzzleOptions)) {
                $this->parameters['localPath'] = $this->guzzleOptions['sink'];
            } else {
                return false;
            }
        }

        if (
            $this->parameters['localPath'] instanceof StreamInterface ||
            is_resource($this->parameters['localPath']) ||
            is_string($this->parameters['localPath'])
        ) {
            return $this->fullUrlIsValid();
        } else {
            return false;
        }
    }
    protected function prepareRequest(): void
    {
        if (
            is_array($this->guzzleOptions) &&
            !array_key_exists('sink', $this->guzzleOptions)
        ) {
            $this->guzzleOptions['sink'] = $this->parameters['localPath'];
        }
    }

    public function download(string $url, string $localPath = null): DownloadQuest
    {
        $this->parameters['remotePath'] = $url;
        $this->parameters['localPath']  = $localPath;
        $this->execute();
        return $this;
    }

    public function saveTo(string $localPath): DownloadQuest
    {
        $this->parameters['localPath'] = $localPath;
        $this->execute();
        return $this;
    }

}
