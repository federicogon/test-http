<?php

namespace App\Services;

use App\Interfaces\CatInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CatBreedService implements CatInterface
{

    private PendingRequest $client;

    protected const CAT_BREEDS_CACHE_KEY = "cat-breeds";

    public function __construct()
    {
        $this->client = Http::baseUrl(config('cats.base_url'));
    }

    public function getCatBreeds(): array
    {
        $start = microtime(true);

        $catBreeds = Cache::get(self::CAT_BREEDS_CACHE_KEY);

        if (!$catBreeds) {
            $catBreeds = $this->fetchCatBreedsViaHttp();
            Cache::set(self::CAT_BREEDS_CACHE_KEY, $catBreeds);
        }

        return $catBreeds;
    }

    public function fetchCatBreedsViaHttp()
    {
        try {
            $allResponse = $this->client->get('breeds')->throw()->json();

            $apiResponse = $this->fetchBreedAndCountry($allResponse['data']);

            $total = $allResponse['total'];
            $perPage = $allResponse['per_page'];

            $expectedApiCalls = ceil($total/$perPage);

            $responses = Http::pool(function (Pool $pool) use ($expectedApiCalls) {
                $requests = [];
                for ($i = 2; $i <= $expectedApiCalls; $i++) {
                    $requests[] = $pool->get(config('cats.base_url').'/breeds', ['page' => $i]);
                }

                return $requests;
            });

            foreach ($responses as $response) {
                $apiResponse = array_merge($apiResponse, $this->fetchBreedAndCountry($response->json()['data']));
            }

            return $apiResponse;

        } catch (RequestException $e) {
            report($e);
            return [];
        }
    }


    protected function fetchBreedAndCountry(array $cats)
    {
        $result = [];

        foreach ($cats as $index => $eachCat) {
            $result[$index]['breed'] = $eachCat['breed'];
            $result[$index]['country'] = $eachCat['country'];
        }

        return $result;
    }


}
