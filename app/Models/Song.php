<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Song extends Model
{

    use SoftDeletes;

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
//    protected $with = ['addedUser', 'approvedUser', 'artists', 'singers', 'musicians', 'writers', 'producers',];

    protected $appends = ['song_status'];

    protected $guarded = ['id'];

    public function fileName()
    {
        return pathinfo(public_path($this->file_path))['basename'];
    }

    public function fileSize()
    {
        return Storage::disk('public')->size("songs/{$this->fileName()}");
    }

    public function isSinger($artist_id)
    {
        return $this->singers->contains($artist_id);
    }

    public function isMusician($artist_id)
    {
        return $this->musicians->contains($artist_id);
    }

    public function isWriter($artist_id)
    {
        return $this->writers->contains($artist_id);
    }

    public function isProducer($artist_id)
    {
        return $this->producers->contains($artist_id);
    }

    public function isArtist($artist_id)
    {
        return $this->artists->contains($artist_id);
    }


    public function matches()
    {
        return $this->hasMany('App\Models\Match', 'song_id');
    }

    public function addedUser(){
        return $this->belongsTo('App\Models\User', 'added_by');
    }

    public function approvedUser(){
        return $this->belongsTo('App\Models\User', 'approved_by');
    }

    public function artists(){
        return $this->belongsToMany('App\Models\Artist', 'song_artists')->withPivot('type');
    }

    public function singers(){
        return $this->belongsToMany('App\Models\Artist', 'song_artists')
            ->withPivot('type')
            ->wherePivot('type', '=', '1');
//        return $this->artists()->wherePivot('type', '=', '1')->get();
    }

    public function musicians(){
        return $this->belongsToMany('App\Models\Artist', 'song_artists')
            ->withPivot('type')
            ->wherePivot('type', '=', '2');
//        return $this->artists()->wherePivot('type', '=', '2')->get();
    }


    public function writers(){
        return $this->belongsToMany('App\Models\Artist', 'song_artists')
            ->withPivot('type')
            ->wherePivot('type', '=', '3');
//        return $this->artists()->wherePivot('type', '=', '3')->get();
    }

    public function producers(){
        return $this->belongsToMany('App\Models\Artist', 'song_artists')
            ->withPivot('type')
            ->wherePivot('type', '=', '4');
//        return $this->artists()->wherePivot('type', '=', '4')->get();
    }

    public function getSongStatusAttribute()
    {
        if ($this->hash_status == 0){
            return "Uploading to Main server failed!";
        }
        elseif ($this->hash_status == 1){
            return "Uploaded to Main server";
        }
        elseif ($this->hash_status == 2){
            return "Uploaded, but hashing failed!";
        }
        elseif ($this->hash_status == 3){
            return "Uploaded & Hashed!";
        }
        else{
            return "Unknown state!";
        }
    }


    /*public function getStatus()
    {
        if ($this->status == 1){
            return "Active (Added by Admin)";
        }
        elseif ($this->status == 2){
            return "Approved (Added by Artist)";
        }
        elseif ($this->status == 3){
            return "Pending";
        }
        elseif ($this->status == 4){
            return "Rejected";
        }
        else{
            return "Undefined";
        }
    }*/

}
