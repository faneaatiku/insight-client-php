<?php
require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class InsightClient {
  private $client;

  public function __construct($base_uri, $handler=null) {
    $options = ['base_uri' => $base_uri];
    if ($handler) {
      $options['handler'] = $handler;
    }
    $this->client = new GuzzleHttp\Client($options);
  }

  public function getBlock($hash) {
    if (!$hash) {
      throw new Error('Block hash parameter is required');
    }

    $response = $this->client->request('GET', "block/{$hash}");
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get block');
    }

    $body = $response->getBody();
    return json_decode($body, true);
  }

  public function getBlockByHeight($index) {
    if (!$index) {
      throw new Error('Block index parameter is required');
    }

    $response = $this->client->request('GET', "block-index/{$index}");
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get block');
    }

    $body = $response->getBody();
    $bodyDecoded = json_decode($body, true);
    return $this->getBlock($bodyDecoded['blockHash']);
  }

  public function getBlockSummaries($date=null, $limit=100) {
    $queryParams = [
      'limit' => $limit
    ];
    if ($date) {
      $queryParams['date'] = $date;
    }

    $response = $this->client->request('GET', "blocks", [
      'query' => $queryParams
    ]);
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get list of blocks');
    }

    $body = $response->getBody();
    return json_decode($body, true);
  }

  public function getCurrency() {
    $response = $this->client->request('GET', 'currency');
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get currency');
    }

    $body = $response->getBody();
    $bodyDecoded = json_decode($body, true);
    return $bodyDecoded['data']['bitstamp'];
  }

  public function getRawBlock($hash) {
    if (!$hash) {
      throw new Error('Block hash parameter is required');
    }

    $response = $this->client->request('GET', "rawblock/{$hash}");
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get block');
    }

    $body = $response->getBody();
    $bodyDecoded = json_decode($body, true);
    return $bodyDecoded['rawblock'];
  }

  public function getRawBlockByHeight($index) {
    if (!$index) {
      throw new Error('Block index parameter is required');
    }

    $response = $this->client->request('GET', "block-index/{$index}");
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get block');
    }

    $body = $response->getBody();
    $bodyDecoded = json_decode($body, true);
    return $this->getRawBlock($bodyDecoded['blockHash']);
  }

  public function getTransaction($transactionId) {
    if (!$transactionId) {
      throw new Error('Transaction Id parameter is required');
    }

    $response = $this->client->request('GET', "tx/{$transactionId}");
    $code = $response->getStatusCode();
    if ($code != 200) {
      throw new Error('Unable to get transaction');
    }

    $body = $response->getBody();
    return json_decode($body, true);
  }
}
?>
