<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Cats extends Controller
{
    /**
     * 1) get a lists of cat's breed using the API https://catfact.ninja/breeds. I want to show the breed and the country
     * 2) reduce the response time.
     * 3) The list is not changing to often, add a DB cache for the list. Use sqllite
     * 4) create a method to clear the cache
     *
     *
    179  sudo apt install php8.1-curl
    185  sudo apt install php8.1-xml
    252  sudo apt install php8.1-sqlite3
    253  sudo apt install php8.1-mbstring
     *
     * php artisan make:migration create_breeds_table
     * php artisan migrate
     * php artisan make:model Breeds
     */
    public function list(): JsonResponse
    {
        $breeds = Breed::all();
        if ($breeds->isEmpty()) {
            $breeds = [];
            $response = Http::get('https://catfact.ninja/breeds');
            $pagesCount = $response['last_page'] ?? 0;
            foreach ($response['data'] as $breed) {
                $breeds[] = Breed::create($breed);
            }
            if ($pagesCount) {
                Http::pool(function (Pool $pool) use ($pagesCount, &$breeds) {
                    foreach (range(2, $pagesCount) as $page) {
                        $pool->get("https://catfact.ninja/breeds?page=$page")->then(function ($response) use (&$breads) {
                            foreach ($response['data'] as $breed) {
                                $breeds[] = Breed::create($breed);
                            }
                        });
                    }
                });
            }
        }
        return response()->json(['success' => true, 'list' => $breeds]);
    }

    public function clear():JsonResponse
    {
        return response()->json(['success' => Breed::truncate()]);
    }
}
