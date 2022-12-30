<?php namespace AhmetAksoy\HyperQuest\Contracts;

use Psr\Http\Message\ResponseInterface;
use AhmetAksoy\HyperQuest\HyperQuestLog;

interface HyperQuestContract
{
    function setCredentials($credentials): HyperQuestContract;
    function credentialsUpdated(): bool;
    function getCredentials();

    function setFullUrl(string $url): HyperQuestContract;
    function setBaseUrl(string $baseUrl): HyperQuestContract;
    function setRemotePath(string $remotePath): HyperQuestContract;
    function setBody($data, string $type = 'urlencoded'): HyperQuestContract;
    function setHeader(string $name, string $value): HyperQuestContract;
    function execute(): HyperQuestContract;

    function getFullUrl(): string;
    function getBaseUrl(): string;
    function getRemotePath(): string;
    function getBody();
    function getBodyType(): string;
    function getHeader(string $name = '');

    function getStatusCode(): int;
    function getResponse(): ResponseInterface;
    function getContent(): string;
    function getLog(): ?HyperQuestLog;
    function hasErrors(): bool;
    function getErrors(): array;
}
