<?php

namespace App\Parsers;

use App\Exceptions\FileNotFoundException;
use DOMDocument;
use DOMXPath;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

class Parser
{
    private string $url;
    private Client $client;
    private array $results = [];
    private $dom;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->client = new Client();
        $this->dom = new DOMDocument();
    }

    public function parse(): array
    {
        $this->getPageData($this->url);
        $pagination = $this->getPagination($this->dom);

        if ($pagination !== null) {
            for ($i = 2; $i <= $pagination; $i++) {
                $pageUrl = "{$this->url}/page/{$i}/";
                $this->getPageData($pageUrl);
            }
        }

        return $this->results;
    }

    private function getPageData(string $url): int
    {
        try {

            $request = new Request('GET', $url);
            $response = $this->client->send($request);
            $body = $response->getBody()->getContents();
            $this->dom = new DOMDocument();
            @$this->dom->loadHTML($body);

            $xpath = new DOMXPath($this->dom);
            $productNodes = $xpath->query('//div[contains(@class, "analytics--product")]');

            foreach ($productNodes as $productNode) {
                $productNameNode = $xpath->evaluate('.//a[@class="product__name analytics--product--link"]', $productNode)->item(0);
                $productName = $productNameNode !== null ? trim($productNameNode->nodeValue) : null;

                $productPriceOldNode = $xpath->query(".//span[@class='price__old']", $productNode)->item(0);
                $productPriceOld = $productPriceOldNode !== null ? trim($productPriceOldNode->nodeValue) : null;

                $productPriceNewNode = $xpath->query(".//span[@class='price__default']", $productNode)->item(0);
                $productPriceNew = $productPriceNewNode !== null ? trim($productPriceNewNode->nodeValue) : null;

                $productImageNode = $xpath->query(".//a[contains(@class,'product__image')]", $productNode)->item(0);
                $productImage = $productImageNode !== null ? trim($productImageNode->nodeValue) : null;

                $productIsAvailableNode = $xpath->query(".//span[@class='product__availability product__availability__in_stock']", $productNode)->item(0);
                $productIsAvailable = $productIsAvailableNode !== null ? trim($productIsAvailableNode->nodeValue) : null;

                $this->results[] = [
                    'name' => $productName,
                    'price_new' => $productPriceNew,
                    'price_old' => $productPriceOld,
                    'image' => $productImage,
                    'discount' => '',
                    'is_available' => $productIsAvailable,
                    'discount_days' => '',
                ];
            }

            print_r($this->results);
            $this->toJson('products.json', $this->results);

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $response->getBody()->getContents();
            return $response;

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                var_dump($response->getStatusCode());
            }
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    return 1;
    }

    public function getPagination($dom): ?string
    {
        $pageUrl = null;
        $xpath = new DOMXPath($dom);

        $nextPageLink = $xpath->query("//a[contains(@class, 'next-page-link')]");

        if ($nextPageLink->length > 0) {

            $pageUrl = $nextPageLink[0]->getAttribute('href');

            if (parse_url($pageUrl, PHP_URL_HOST) === null) {
                $pageUrl = $this->url . $pageUrl;
            }
        }

        return $pageUrl;
    }

    private function toJson($filename, $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
    }

    public function runCli(): void
    {
        $options = getopt("u:p::");
        $url = $options['u'] ?? 'https://freshmart.com.ua/uk/catalog/action.html';
        $path = $options['p'] ?? 'result.json';

        if (empty($url)) {
            echo "Usage: php parser.php -u <url> [-p <path>]\n";
            exit;
        }

        try {
            $data = $this->parse();
            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
            file_put_contents($path, $jsonData);
            echo "Parsing completed successfully. Results saved to $path.\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit;
        }
    }
}



