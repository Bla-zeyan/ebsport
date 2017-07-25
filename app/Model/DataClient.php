<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DataClient extends Model
{
    protected $table = 'data_client';
    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $dateFormat = 'U';
    protected $guarded = ['uuid'];

}
