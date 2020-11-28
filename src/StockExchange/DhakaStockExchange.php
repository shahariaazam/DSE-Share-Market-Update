<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2020 Shaharia Azam <mail@shaharia.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace ShahariaAzam\BDStockExchange\StockExchange;

use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use ShahariaAzam\BDStockExchange\PricingEntity;
use ShahariaAzam\BDStockExchange\StockExchangeInterface;
use Symfony\Component\DomCrawler\Crawler;

class DhakaStockExchange implements StockExchangeInterface
{
    const PRICING_HTTP_ENDPOINT = 'https://www.dsebd.org';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var array
     */
    private $httpHeaders;

    public function __construct(ClientInterface $client, array $httpHeaders = [])
    {
        $this->httpClient = $client;
        $this->httpHeaders = $httpHeaders;
    }

    /**
     * @return PricingEntity[]
     * @throws ClientExceptionInterface
     */
    public function getPricing()
    {
        $response = $this->httpClient->sendRequest(
            new Request('GET', self::PRICING_HTTP_ENDPOINT)
        );

        $dom = new Crawler((string)$response->getBody());

        $pricing = [];
        foreach ($dom->filterXPath("//a[contains(concat(' ',normalize-space(@class),' '),' abhead ')]") as $node) {
            $pricing[] = $this->cleanData($node->nodeValue);
        }

        return $pricing;
    }

    /**
     * @param null $data
     * @return PricingEntity
     */
    private function cleanData($data)
    {
        $data = utf8_decode($data);
        preg_match_all('([\w!\d+(?:.\d+)?!]+)', $data, $cleaned);

        $pricingData = array_pop($cleaned);

        $pricingEntity = new PricingEntity();
        $pricingEntity->setCompany((string) $pricingData[0]);

        if (isset($pricingData[1])) {
            $pricingEntity->setLastTradeValue((float) $pricingData[1]);
        }

        if (isset($pricingData[2])) {
            $pricingEntity->setChangeInAmount((float) $pricingData[2]);
        }

        if (isset($pricingData[3])) {
            $pricingEntity->setChangeInPercentage((float) $pricingData[3]);
        }

        return $pricingEntity;
    }
}
