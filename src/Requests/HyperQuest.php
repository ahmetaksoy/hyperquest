<?php namespace AhmetAksoy\HyperQuest\Requests;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;
use AhmetAksoy\HasErrors\HasErrors;
use AhmetAksoy\HasErrors\HasErrorsContract;
use AhmetAksoy\HyperQuest\Contracts\HyperQuestContract;
use AhmetAksoy\HyperQuest\HyperQuestLog;

class HyperQuest implements HyperQuestContract, HasErrorsContract
{
    use HasErrors;

    const TYPE_DETECT = 0;
    const TYPE_ANY = 0;
    const TYPE_OBJECT = 1;
    const TYPE_ARRAY = 2;
    const TYPE_COUNTABLE = 3;
    const TYPE_CALLABLE = 4;
    const TYPE_SCALAR = 5;
    const TYPE_NUMERIC = 6;
    const TYPE_INTEGER = 7;
    const TYPE_FLOAT = 8;
    const TYPE_STRING = 9;
    const TYPE_ARRAYABLE = 10;

    /**
     * The HyperQuest parameter(s)
     *
     * @var array
     */
    protected $parameters = [
        'method' => 'GET',
    ];

    /**
     * The HTTP Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     *
     * @var string
     */
    protected $bodyType = 'none';

    /**
     * Whether to execute the request as soon as the model is constructed
     *
     * @var boolean
     */
    protected $autoExecute = false;

    /**
     * Full url of the request
     *
     * @var string
     */
    protected $fullUrl = '';

    /**
     * Guzzle options
     *
     * @var array
     */
    protected $guzzleOptions = [];

    /**
     * Request status code
     *
     * @var integer
     */
    private $statusCode = 400;

    /**
     * The Guzzle response instance
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * The Log Instance
     *
     * @var \AhmetAksoy\HyperQuest\Models\HyperQuestLog[]
     */
    private $logs = [];

    protected array $encrypt = [
        'user','password','token','authorization'
    ];

    protected $credentials;

    protected $credentialsUpdated;

    protected $content;

    protected function onError(): void
    {}
    protected function onSuccess(): void
    {}
    protected function onComplete(): void
    {}
    protected function requestReady(): bool
    {
        return $this->fullUrlIsValid() && $this->hasNoErrors();
    }
    protected function prepareRequest(): void
    {}
    protected function fullUrlIsValid(): bool
    {
        return true;
        return filter_var($this->fullUrl, FILTER_VALIDATE_URL) !== false;
    }

    public function __construct($parameters = null)
    {
        if (is_bool($parameters)) {
            $this->autoExecute = $parameters;
        } elseif (is_string($parameters)) {
            $this->setFullUrl($parameters);
        } elseif (is_object($parameters)) {
            $this->parameters = array_merge($this->parameters, (array) $parameters);
        } elseif (is_array($parameters)) {
            $this->parameters = array_merge($this->parameters, $parameters);
        }

        $this->calculateFullUrl();
        if (array_key_exists('autoExecute', $this->parameters)) {
            $this->autoExecute = $this->parameters['autoExecute'];
        }

        if (array_key_exists('headers', $this->parameters)) {
            $this->headers = array_merge($this->headers, $this->parameters['headers']);
        }

        if (array_key_exists('bearerToken', $this->parameters)) {
            $this->headers['authorization'] = 'Bearer ' . $this->parameters['bearerToken'];
        }

        if ($this->autoExecute) {
            $this->execute();
        }
    }

    public function setCredentials($credentials): HyperQuestContract
    {
        $this->credentials = $credentials;
        return $this;
    }

    public function credentialsUpdated(): bool
    {
        return $this->credentialsUpdated ?? false;
    }

    public function getCredentials()
    {
        return $this->credentials;
    }

    protected function newLog(): HyperQuestLog
    {
        $this->logs[] = $log = new HyperQuestLog();
        $log->encrypt  = $this->encrypt;
        $log->user_id = auth()->id() ?? 0;
        $log->request_class = static::class;
        return $log;
    }

    protected function saveRequestInfoToLog(HyperQuestLog $log)
    {
        $requestObjectForLog = $this->guzzleOptions;
        if (
            isset($requestObjectForLog['headers']) &&
            isset($requestObjectForLog['headers']['content-type']) &&
            $requestObjectForLog['headers']['content-type'] == 'application/json'
        ) {
            if (isset($requestObjectForLog['body'])) {
                $requestObjectForLog['body'] = json_decode($requestObjectForLog['body']);
            }
        }
        $log->remote_path = $this->fullUrl;
        $log->request = $requestObjectForLog;
        $log->save();
    }

    public function execute(): HyperQuestContract
    {
        $log = $this->newLog();

        try {
            $this->prepareRequest();
            $this->saveRequestInfoToLog($log);

            if ($this->requestReady()) {
                $this->makeRequest();
            } else {
                $this->addError('Request fired before ready.');
            }

        } catch (BadResponseException $exception) {

            $this->addError($exception->getMessage());
            $this->setStatusCode($exception->getResponse()->getStatusCode());
            $this->setResponse($exception->getResponse());

        } catch (Exception $exception) {

            $log->setMessage($exception->getMessage());
            $this->saveRequestInfoToLog($log);
            $this->addError($exception->getMessage() . json_encode($exception->getTrace()));

        }

        $log->response = $this->ensureJson($this->getContent(102400));
        $log->http_status = $this->getStatusCode();

        if ($this->hasErrors()) {
            $this->onError();
        } else {
            $this->onSuccess();
        }

        $this->onComplete();

        if ($this->hasErrors()) {
            $log->failed = true;
            $log->errors = $this->getErrors();
        } else {
            $log->failed = false;
        }

        $log->save();

        return $this;
    }

    protected function calculateFullUrl(): void
    {
        if (array_key_exists('url', $this->parameters)) {
            if (!array_key_exists('baseUrl', $this->parameters)) {
                $this->parameters['baseUrl'] = $this->parameters['url'];
            } elseif (!array_key_exists('remotePath', $this->parameters)) {
                $this->parameters['remotePath'] = $this->parameters['url'];
            } else {
                $this->addError('Use only two of the following parameters: url, baseUrl, remotePath');
            }
        }

        if (!array_key_exists('baseUrl', $this->parameters)) {
            $this->parameters['baseUrl'] = '';
        }
        if (!array_key_exists('remotePath', $this->parameters)) {
            $this->parameters['remotePath'] = '';
        }
        if (
            $this->parameters['baseUrl'] !== '' and
            substr($this->parameters['baseUrl'], -1) !== '/' and
            $this->parameters['remotePath'] !== '' and
            substr($this->parameters['remotePath'], 0, 1) !== '/'
        ) {
            $this->parameters['remotePath'] = '/' . $this->parameters['remotePath'];
        }

        $this->fullUrl = $this->parameters['baseUrl'] . $this->parameters['remotePath'];
    }

    public function setFullUrl(string $url, string $path = ''): HyperQuestContract
    {
        if ($path == '') {
            $path = $url;
            $url = '';
        }

        $this->parameters['baseUrl'] = $url;
        $this->parameters['remotePath'] = $path;
        $this->calculateFullUrl();
        return $this;
    }

    public function getFullUrl(): string
    {
        return $this->fullUrl;
    }

    public function setBaseUrl(string $baseUrl): HyperQuestContract
    {
        $this->parameters['baseUrl'] = $baseUrl;
        $this->calculateFullUrl();
        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->parameters['baseUrl'];
    }

    public function setRemotePath(string $remotePath): HyperQuestContract
    {
        $this->parameters['remotePath'] = $remotePath;
        $this->calculateFullUrl();
        return $this;
    }

    public function getRemotePath(): string
    {
        return $this->parameters['remotePath'];
    }

    public function setBody($data, string $type = 'urlencoded'): HyperQuestContract
    {
        switch ($type) {
            case 'multipart':
                $this->setGuzzleOption('multipart', $data);
                $this->bodyType = $type;
                return $this;
            case 'urlencoded':
                $this->setGuzzleOption('form_params', $data);
                $this->bodyType = $type;
                return $this;
            case 'json':
                $this->setGuzzleOption('json', $data);
                $this->bodyType = $type;
                return $this;
            default:
                $this->addError('Unrecognized body type');
                return $this;
        }
    }

    public function getBody()
    {
        switch ($this->bodyType) {
            case 'none':
                return null;
            case 'multipart':
                return $this->getGuzzleOption('multipart');
            case 'urlencoded':
                return $this->getGuzzleOption('form_params');
            case 'json':
                return $this->getGuzzleOption('json');
        }
    }

    public function getBodyType(): string
    {
        return $this->bodyType;
    }

    public function getHeader(string $name = '')
    {
        if ($name == '') {
            return $this->headers;
        }

        if (!is_array($this->headers)) {
            return null;
        }

        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        } else {
            return null;
        }
    }

    public function setHeader(string $name, string $value): HyperQuestContract
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setGuzzleOption(string $name, $value): HyperQuestContract
    {
        if ($name == '') {
            $this->guzzleOptions = $value;
            return $this;
        }

        $optionPath = explode('.', $name);
        $optionPointer = &$this->guzzleOptions;
        foreach ($optionPath as $partial) {
            if (!is_array($optionPointer)) {
                $optionPointer = [];
            }
            if (!array_key_exists($partial, $optionPointer)) {
                $optionPointer[$partial] = [];
            }
            $optionPointer = &$optionPointer[$partial];
        }
        $optionPointer = $value;

        return $this;
    }

    public function getGuzzleOption(string $name = '')
    {
        if ($name == '') {
            return $this->guzzleOptions;
        }

        if (!is_array($this->guzzleOptions)) {
            return null;
        }

        $optionPath = explode('.', $name);
        $value = $this->guzzleOptions;
        foreach ($optionPath as $partial) {
            if (array_key_exists($partial, $value)) {
                $value = $value[$partial];
            } else {
                return null;
            }
        }

        return $value;
    }

    protected function makeRequest(): void
    {
        $client = new Client(['base_uri' => $this->getFullUrl()]);
        $this->setGuzzleOption('headers', $this->headers);
        $response = $client->request($this->parameters['method'], '', $this->guzzleOptions);
        $this->setResponse($response);
        $this->setStatusCode($response->getStatusCode());
    }

    protected function ensureJson($input)
    {
        if (
            is_string($input) &&
            (
                $input == 'null' ||
                json_decode($input) !== null
            )
        ) {
            return $input;
        } else {
            return json_encode($input);
        }
    }

    protected function setStatusCode(int $statusCode): HyperQuestContract
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    protected function setResponse(ResponseInterface $response): HyperQuestContract
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getContent(int $length = 0): string
    {
        if (isset($this->response)) {
            $body = $this->response->getBody();
            if ($body->eof()) {
                return $this->content;
            }

            if (is_null($this->content)) {
                $this->content = '';
            }

            if ($length <= 0) {
                return $this->content .= $body->getContents();
            }

            $retrievedContentLength = strlen($this->content);
            if ($length < $retrievedContentLength) {
                return substr($this->content, 0, 20);
            } elseif ($length == $retrievedContentLength) {
                return $this->content;
            } else {
                return $this->content .= $body->read($length - $retrievedContentLength - 1);
            }

        } else {
            return '';
        }
    }

    public function getLog($logIndex = 0): ?HyperQuestLog
    {
        return $this->logs[$logIndex];
    }

    protected function checkField(string $fieldName, $container, string $prefix, int $fieldType = self::TYPE_ANY, int $containerType = self::TYPE_DETECT)
    {
        if ($containerType === self::TYPE_DETECT) {
            if (is_object($container)) {
                $containerType = self::TYPE_OBJECT;
            } elseif (is_array($container)) {
                $containerType = self::TYPE_ARRAY;
            } else {
                $this->addError("${prefix}bad container type provided for to check field $fieldName");
            }
        }

        switch ($containerType) {
            case self::TYPE_OBJECT:
                if (!isset($container->$fieldName)) {
                    $this->addError("${prefix}missing field `$fieldName`.");
                    return;
                }
                $value = $container->$fieldName;
                break;
            case self::TYPE_ARRAY:
                if (!array_key_exists($fieldName, $container)) {
                    $this->addError("${prefix}missing field `$fieldName`");
                    return;
                }
                $value = $container[$fieldName];
                break;
            default:
                $this->addError("${prefix}unrecognized type parameter.");
                return;
        }

        switch ($fieldType) {
            case self::TYPE_OBJECT:
                if (!is_object($value)) {
                    $this->addError("${prefix}must be an object.");
                }
                break;
            case self::TYPE_ARRAY:
                if (!is_array($value)) {
                    $this->addError("${prefix}must be an array.");
                }
                break;
            case self::TYPE_COUNTABLE:
                if (!is_countable($value)) {
                    $this->addError("${prefix}must be countable.");
                }
                break;
            case self::TYPE_CALLABLE:
                if (!is_callable($value)) {
                    $this->addError("${prefix}must be callable.");
                }
                break;
            case self::TYPE_SCALAR:
                if (!is_scalar($value)) {
                    $this->addError("${prefix}must be scalar.");
                }
                break;
            case self::TYPE_NUMERIC:
                if (!is_numeric($value)) {
                    $this->addError("${prefix}must be numeric.");
                }
                break;
            case self::TYPE_INTEGER:
                if (!is_integer($value)) {
                    $this->addError("${prefix}must be an integer.");
                }
                break;
            case self::TYPE_FLOAT:
                if (!is_float($value)) {
                    $this->addError("${prefix}must be a float.");
                }
                break;
            case self::TYPE_STRING:
                if (!is_string($value)) {
                    $this->addError("${prefix}must be a string.");
                }
                break;
            case self::TYPE_ARRAYABLE:
                if (!is_array($value) and !(is_object($value)));
        }
    }
}
