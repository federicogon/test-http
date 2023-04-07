<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Breed extends Model
{
    use HasFactory;

    public static function create(array $data): Breed
    {
        $breed = new Breed();
        $breed->name = $data['breed'];
        $breed->country = $data['country'];
        $breed->save();
        return $breed;
    }
}
