<?php

namespace App\Http\Controllers;

use App\Interfaces\CatInterface;

class CatBreedsController
{
    public function index(CatInterface $cat)
    {
        return response()->json($cat->getCatBreeds());
    }
}
