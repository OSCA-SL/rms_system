<?php

namespace App\Http\Controllers;

use App\Error;
use App\Events\SongUploaded;
use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        /*$song = Song::findOrFail(2508);
        $file_name = "/var/www/html/osca/storage/app/public/songs/2508.mp3";
        event(new SongUploaded($song, storage_path("app/public/songs/{$file_name}")));
        return "request sent";*/
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $username = config('app.radio_username', 'fdsfasda');
        $password = Hash::make(config('app.radio_password', 'dsafdsafsaf'));

//        dd($username);
        $user = $request->input('username');
        $pass = $request->input('password');

        /*$error = new Error;
        $error->message = "SYS: Request OK: BEFORE Auth";
        $error->save();*/

        if ($username == $user && Hash::check($pass, $password) ){

            /*$error = new Error;
            $error->message = "SYS: Request OK: AFTER Auth";
            $error->save();*/

            $file_name = $request->input('file_name');
            $exists = Storage::disk('public')->exists("songs/{$file_name}");

            if ($request->has('ftp') && $request->input('ftp') == true && $exists == true){

                $id = $request->input('id');
                $song = Song::findOrFail($id);

                $file_name = $request->input('file_name');


                /*$error = new Error;
                $error->message = "Request OK: {$song->id}, FTP: {$request->input('ftp')}, FILENAME: {$file_name}";
                $error->save();*/


                event(new SongUploaded($song, storage_path("app/public/songs/{$file_name}")));

                return response("Successfully Uploaded the song", 200);
            }
            else{
                return response("No Song File was received", 403);
            }


            /*if ($request->hasFile('song_file')){
                $file = $request->file('song_file');
                $file_name = $id.".".$file->getClientOriginalExtension();
                $file->storeAs('public/songs', $file_name);

                event(new SongUploaded($id, storage_path('app/public/songs/').$file_name));

                return response("Successfully Uploaded the song", 200);
            }
            else{
                return response("No Song File was received", 403);
            }*/
        }
        else{
            return response("Unauthorized request", 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
