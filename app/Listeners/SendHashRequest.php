<?php

namespace App\Listeners;

use App\Error;
use App\Events\SongUploaded;
use App\Models\Fingerprint;
use GuzzleHttp\RequestOptions;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use GuzzleHttp\Client;

use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class SendHashRequest implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SongUploaded  $event
     * @return void
     */
    public function handle(SongUploaded $event)
    {
        $song = $event->song;
        $file_path = $event->file_path;


        $register_url = config('app.song_register_url');

        /*$error = new Error;
        $error->message = "SYS REQ EVENT CALLED IN Hashing: REG_URL: {$register_url}, {$song->id}, FILEPATH: {$file_path}";
        $error->save();*/

        DB::connection('mysql_system')
            ->table('fingerprints')
            ->where('song_id', '=', $song->id)
            ->delete();

        $client = new Client();
        $promise = $client->postAsync($register_url, [
            'json' => [
                'songId' => $song->id,
                'path' => $file_path
            ]
        ])->then(
            function (ResponseInterface $res) use ($song){
                //TODO: Uncomment
                $song->refresh();
                $response_status = $res->getStatusCode();

                $fp_count = Fingerprint::where('song_id', $song->id)
                    ->count();
                if ($response_status >= 200 && $response_status < 300 && $fp_count > 0){
                    $song->hash_status = 3;
                }
                else{
                    $song->hash_status = 2;
                }
                $song->save();

                /*$error = new Error;
                $error->message = "SYS REQ OK IN Hashing/REQUEST OK:  {$song->id}, RESPONSE: {$res->getBody()->getContents()}, RESPONSE_CODE: {$res->getStatusCode()}";
                $error->save();*/

            },
            function (RequestException $e) use ($song) {

                $song->refresh();
                if ($song->hash_status > 2){
                    $song->hash_status = 2;
                    $song->save();
                }

                $message = $e->getMessage();
                $method = $e->getRequest()->getMethod();

//                $error = new Error;
//                $res_body = "";
//                $res_body .= $e->getResponse()->getBody()->getContents();
//                /*$h = "";
//                foreach ($e->getResponse()->getHeaders() as $headers) {
//                    foreach ($headers as $hd => $header) {
//                        $h .= "==".$h." : ".$header;
//                    }
//                }*/
//                $error->message = "SYSTEM:HTTP:ERR SONG: {$song->id}, MSG: ".$message.", METHOD: ".$method.", REASON: ".$e->getResponse()->getReasonPhrase().", BODY: ".$res_body;
//                $error->save();


                /*$error = new Error;
                $error->message = "SYS REQ:ERR IN Hashing/REQUEST FAILED:  {$song->id}, RESPONSE: {$e->getMessage()}, RESPONSE_CODE: {$e->getCode()}";
                $error->save();*/

            }
        );

        $response = $promise->wait();
    }

    /**
     * Handle a job failure.
     *
     * @param  \App\Events\SongUploaded  $event
     * @param  \Exception  $exception
     * @return void
     */

    public function failed(SongUploaded $event, $exception)
    {
        $song = $event->song;

        $error = new Error;
        $error->message = "SYSTEM:EVENT: SONG_ID:{$song->id}, ERROR_CODE: {$exception->getCode()}, ERROR: {$exception->getMessage()}, ERROR_TRACE: {$exception->getTraceAsString()}, ERROR_FILE: {$exception->getFile()}, ERROR_LINE: {$exception->getLine()}";
        $error->save();
    }
}
