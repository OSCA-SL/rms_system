<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelFee extends Model
{
    protected $guarded = ['id'];

    public function channel()
    {
        return $this->belongsTo('App\Models\Channel', 'channel_id');
    }
}
