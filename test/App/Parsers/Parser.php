<?php

namespace App\Parsers;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Throwable;

class Parser
{
    private $url;
    private $client;
    private $results = [];
    private $xpath;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->client = new Client();
    }

    public function parse(): array
    {
        $this->getPageData($this->url);
        $pagination = $this->getPagination();

        if ($pagination !== null) {
            for ($i = 2; $i <= $pagination; $i++) {
                $pageUrl = "{$this->url}/page/{$i}/";
                $this->getPageData($pageUrl);
            }
        }

        return $this->results;
    }

    private function getPageData(string $url): void
    {
        try {
            $request = new Request('GET', $url);
            $response = $this->client->send($request);
            $body = $response->getBody()->getContents();
            $dom = new DOMDocument();
            @$dom->loadHTML($body);

            $this->xpath = new DOMXPath($dom);
            $productNodes = $this->xpath->evaluate('//div[contains(@class, "jet-woo-products__item jet-woo-builder-product col-desk-3 col-tab-2")]');

            foreach ($productNodes as $productNode) {
                $productNameNode = $this->xpath->query('.//h5[contains(@class, "jet-woo-product-title")]/a', $productNode)->item(0);
                $productName = $productNameNode !== null ? trim($productNameNode->nodeValue) : null;

                $productTagsNode = $this->xpath->query('.//div[contains(@class, "jet-woo-product-tags")]//ul/li/a', $productNode)->item(0);
                $productTags = $productTagsNode !== null ? trim($productTagsNode->nodeValue) : null;

                $productThumbnailNode = $this->xpath->query('.//div[contains(@class, "jet-woo-product-thumbnail")]/a/img', $productNode)->item(0);
                $productThumbnail = $productThumbnailNode !== null ? trim($productThumbnailNode->nodeValue) : null;


                $this->results[] = [
                    'name' => $productName,
                    'tags' => $productTags,
                    'thumbnail' => $productThumbnail,

                ];
            }
            print_r($this->results);
        } catch (GuzzleException $e) {
            // Обробка помилок Guzzle
        } catch (Throwable $e) {
            // Обробка інших помилок
        }
    }

    public function getPagination(): ?string
    {
        $pageUrl = null;

        // шукаємо елемент з посиланням на наступну сторінку
        $nextPageLink = $this->xpath->query("//a[contains(@class, 'next-page-link')]");

        if ($nextPageLink->length > 0) {
            // якщо елемент знайдено, отримуємо посилання на наступну сторінку
            $pageUrl = $nextPageLink[0]->getAttribute('href');

            // перевіряємо, щоб посилання містило протокол та домен
            if (parse_url($pageUrl, PHP_URL_HOST) === null) {
                $pageUrl = $this->url . $pageUrl;
            }
        }

        return $pageUrl;
    }

}



