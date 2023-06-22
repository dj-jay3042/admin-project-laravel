<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $table = "tblUser";
    protected $primaryKey = "id";

    public function getKey() { return $this->primaryKey; }
}
