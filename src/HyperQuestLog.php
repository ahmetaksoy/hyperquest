<?php


namespace AhmetAksoy\HyperQuest;

use App\Modules\Base\Facades\JobLog as Log;

class HyperQuestLog
{
    public array $encrypt = [];

    private array $context = [];

    private string $cipher;

    private string $uniqid;

    public string $message = 'HyperQuest Log';
    public int $user_id;
    public string $request_class;
    public string $remote_path;
    public int $http_status = 0;
    public ?array $request = null;
    public $response = null;
    public ?array $errors = null;
    public int $failed = 0;

    public function __construct()
    {
        $this->cipher = config('app.cipher', 'AES-128-CBC');
    }

    public function save()
    {
        if ($this->request) {
            $this->request = $this->encrypt($this->request);
        }

        if ($this->response) {
            if (($response = json_decode($this->response, true)) !== null && is_array($response)) {
                $this->response = $this->encrypt($response);
            }
        }

        $replaceKeysFunction = function ($key) {
            return str_replace('.', '-', $key);
        };
        $this->request = arrayChangeKeys($this->request, $replaceKeysFunction);
        $this->response = arrayChangeKeys($this->response, $replaceKeysFunction);

        $this->uniqid = md5(uniqid(mt_rand(), true));
        $this->context = [
            'request_id' => $this->uniqid,
            'user_id' => $this->user_id,
            'request_class' => $this->request_class,
            'remote_path' => $this->remote_path,
            'http_status' => $this->http_status,
            'request' => $this->request,
            'response' => $this->response,
            'errors' => $this->errors,
            'failed' => $this->failed
        ];

        if ($this->failed) {
            Log::error($this->message, $this->context);
        } else {
            Log::info($this->message, $this->context);
        }
    }

    public function getKey()
    {
        return $this->uniqid;
    }

    public function setMessage(string $message): self
    {
        if (!empty($message)) {
            $this->message = $message;
        }

        return $this;
    }

    private function encrypt($arr)
    {
        $encrypt = $this->encrypt;
        array_walk_recursive($arr, function (&$value, $key) use ($encrypt) {
            if (in_array($key, $encrypt, true) && !$this->isEncrypt($value)) {
                $value = encrypt($value, false);
            }
        });
        return $arr;
    }

    private function isEncrypt($payload)
    {
        $payload = json_decode(base64_decode($payload), true);

        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']) &&
            strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length($this->cipher);
    }
}
