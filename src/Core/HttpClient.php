<?php


namespace JiaLeo\Laravel\Core;


use App\Exceptions\ApiException;

class HttpClient
{

    /**
     * @var \GuzzleHttp\Client
     */
    public $client;
    public $headers = array();

    public function __construct($option = array('timeout' => 2))
    {
        $this->client = new \GuzzleHttp\Client($option);
    }

    public function get($url, $params = array())
    {
        return $this->request('GET', $url, $params);
    }

    public function post($url, $params, $content_type = 'multipart/form-data')
    {
        return $this->request('POST', $url, $content_type, $params);
    }


    public function request($method = 'GET', $url, $params = array(), $content_type = 'multipart/form-data')
    {

        if (strtoupper($method) == 'GET') {
            $str = 'query';

        } else {
            if ($content_type == 'multipart/form-data') {
                $str = 'multipart';

            } elseif ($content_type == 'application/x-www-form-urlencoded') {
                $str = 'form_params';
            } elseif ($content_type == 'application/json') {
                $str = 'json';
            } elseif ($content_type == 'raw') {
                $str = 'body';
            } else {
                throw new ApiException('错误的Content-Type');
            }
        }

        $params = array(
            $str => $params
        );

        //发起请求
        try {
            $response = $this->client->request($method, $url, $params);
            return $response;

        } catch (\GuzzleHttp\Exception\RequestException $e) {

            if ($e->hasResponse()) {
                $response = $e->getResponse();

            } else {
                return false;
            }
        }

        return $response;
    }


}