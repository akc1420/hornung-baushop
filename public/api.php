<?php


class shopwareApiClient
{

    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';

    protected $validMethods = array(
        self::METHOD_GET,
        self::METHOD_PUT,
        self::METHOD_POST,
        self::METHOD_DELETE
    );
    protected $apiUrl;
    protected $cURL;

    public function __construct($apiUrl, $username, $apiKey)
    {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        //Initializes the cURL instance
        $this->cURL = curl_init();
        curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->cURL, CURLOPT_USERAGENT, 'Shopware shopwareApiClient');
        curl_setopt($this->cURL, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($this->cURL, CURLOPT_USERPWD, $username . ':' . $apiKey);
        curl_setopt($this->cURL, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        ));
        curl_setopt($this->cURL, CURLINFO_HEADER_OUT, true);
    }

    public function call($url, $method = self::METHOD_GET, $data = array(), $params = array())
    {
        if (!in_array($method, $this->validMethods))
        {
            throw new Exception('Invalid HTTP-Methode: ' . $method);
        }
        $queryString = '';
        if (!empty($params))
        {
            $queryString = http_build_query($params);
        }
        $url = rtrim($url, '?') . '?';
        $url = $this->apiUrl . $url . $queryString;
        $dataString = json_encode($data);
        curl_setopt($this->cURL, CURLOPT_URL, $url);
        curl_setopt($this->cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $dataString);
        $result = curl_exec($this->cURL);
        curl_getinfo($this->cURL, CURLINFO_HEADER_OUT );
        $httpCode = curl_getinfo($this->cURL, CURLINFO_HTTP_CODE);
        return $this->prepareResponse($result, $httpCode);
    }

    public function get($url, $params = array())
    {
        return $this->call($url, self::METHOD_GET, array(), $params);
    }

    public function post($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHOD_POST, $data, $params);
    }

    public function put($url, $data = array(), $params = array())
    {
        return $this->call($url, self::METHOD_PUT, $data, $params);
    }

    public function delete($url, $params = array())
    {
        return $this->call($url, self::METHOD_DELETE, array(), $params);
    }

    protected function prepareResponse($result, $httpCode)
    {
        echo "<h2>HTTP: $httpCode</h2>";
        if (null === $decodedResult = json_decode($result, true))
        {
            $jsonErrors = array(
                JSON_ERROR_NONE => 'No error occurred',
                JSON_ERROR_DEPTH => 'The maximum stack depth has been reached',
                JSON_ERROR_CTRL_CHAR => 'Control character issue, maybe wrong encoded',
                JSON_ERROR_SYNTAX => 'Syntaxerror',
            );
            echo "<h2>Could not decode json</h2>";
            echo "json_last_error: " . $jsonErrors[json_last_error()];
            echo "<br>Raw:<br>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            return;
        }
        if (!isset($decodedResult['success']))
        {
            echo "Invalid Response";
            return;
        }
        if (!$decodedResult['success'])
        {
            echo "<h2>No Success</h2>";
            echo "<p>" . $decodedResult['message'] . "</p>";
            return;
        }
        echo "<h2>Success</h2>";
        if (isset($decodedResult['data']))
        {
            echo "<pre>" . print_r($decodedResult['data'], true) . "</pre>";
        }
        return $decodedResult;
    }
}