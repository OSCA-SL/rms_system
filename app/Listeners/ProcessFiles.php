<?php

namespace App\Listeners;

use App\Error;
use App\Events\FileWanted;
use App\Models\Channel;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pbmedia\LaravelFFMpeg\FFMpegFacade;
use Psr\Http\Message\ResponseInterface;

class ProcessFiles implements ShouldQueue
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
     * @param  FileWanted  $event
     * @return void
     */
    public function handle(FileWanted $event)
    {

        $post_data = [
            "folder_path" => Storage::disk('clips')->getDriver()->getAdapter()->getPathPrefix()."merged",
            "channels" => [],
        ];


        $channels = Channel::all();


        /*
         * For each channels
         * */
        foreach ($channels as $channel) {

            $channel_data = [];

            $now = Carbon::now();

            /*
             * While
             * */
            if ($channel->last_fetch_at == null){
                $channel->last_fetch_at = Carbon::now()->subMinutes(20)->toDateTimeString();
                $channel->save();
                $channel->refresh();
            }

            if ($channel->aired_time == null){
                $channel->aired_time = Carbon::now()->subMinutes(20)->toDateTimeString();
                $channel->save();
                $channel->refresh();
            }

            if ($channel->isMatchRequestOk()){
                $nextFetch = $channel->getNextFetch();

            }
            else{
                $nextFetch = $channel->getCurrentFetch();
            }

            $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";


            if (Storage::disk('ftp')->exists($ftp_path)) {


                $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

                $lastModified = date('Y-m-d H:i:s', $lastModified);

                $difference = Carbon::parse($lastModified)->diffInMinutes($now);

                if ($now->gt(Carbon::parse($lastModified)) && $difference >= 4){

                    $fetches = $difference % 2 == 0?($difference - 2)/2:($difference - 3)/2;

                    for ($i = 0; $i < $fetches; $i++){

                        $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

                        $lastModified = date('Y-m-d H:i:s', $lastModified);

                        $ftp_clip1 = Storage::disk('ftp')->get($ftp_path);

                        $clip1_name = "{$channel->id}_{$nextFetch['day']}_{$nextFetch['hour']}_{$nextFetch['minute']}";
                        $clip1_path = "fetched/{$clip1_name}.wma";
                        Storage::disk('clips')->put($clip1_path, $ftp_clip1);

                        if (Storage::disk('clips')->exists($clip1_path)){

                            $channel->last_fetch_at = Carbon::now()->toDateTimeString();
                            $channel->aired_time = $lastModified;
                            $channel->fetch_status = $channel->setFirstClipOk();
                            $channel->save();
                            $channel->setFetched($nextFetch);



                            $nextFetch = $channel->getNextFetch();

                            $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";

                            if (Storage::disk('ftp')->exists($ftp_path)) {

                                $ftp_clip2 = Storage::disk('ftp')->get($ftp_path);

                                $clip2_name = "{$channel->id}_{$nextFetch['day']}_{$nextFetch['hour']}_{$nextFetch['minute']}";
                                $clip2_path = "fetched/{$clip2_name}.wma";

                                Storage::disk('clips')->put($clip2_path, $ftp_clip2);

                                if (Storage::disk('clips')->exists($clip2_path)){

                                    $merged_file_name = "{$clip1_name}-{$clip2_name}";

                                    $channel->clip_path = $merged_file_name;

                                    $channel->last_fetch_at = Carbon::now()->toDateTimeString();
                                    $channel->fetch_status = $channel->setSecondClipOk();
                                    $channel->save();
                                    $channel->setFetched($nextFetch);



                                    $merged_file_path = "merged/{$merged_file_name}.wma";
                                    $final_file_path = "merged/{$merged_file_name}.wav";

                                    $disk = FFMpegFacade::fromDisk('clips');
                                    $clip1 = $disk->open($clip1_path);
                                    $clip2 = $disk->open($clip2_path);

                                    $output = Storage::disk('clips')->getDriver()->getAdapter()->getPathPrefix().$merged_file_path;

                                    /*
                                     * If merged file already exists, delete it before concatenating using FFMPEG
                                     * */

                                    if (Storage::disk('clips')->exists($merged_file_path)){
                                        Storage::disk('clips')->delete($merged_file_path);
                                    }

                                    $clip1->concat(
                                        [
                                            $clip1->getPathfile(),
                                            $clip2->getPathfile(),
                                        ]
                                    )->saveFromSameCodecs($output, true);

                                    if (Storage::disk('clips')->exists($merged_file_path)){

                                        /*
                                         * If merged & converted (final) file already exists, delete it before converting using FFMPEG
                                         * */

                                        if (Storage::disk('clips')->exists($final_file_path)){
                                            Storage::disk('clips')->delete($final_file_path);
                                        }

                                        FFMpegFacade::fromDisk('clips')
                                            ->open($merged_file_path)
                                            ->export()
                                            ->inFormat(new \FFMpeg\Format\Audio\Wav)
                                            ->save($final_file_path);

                                        if (Storage::disk('clips')->exists($final_file_path)){


                                            $channel->fetch_status = $channel->setMergingOk();
                                            $channel->save();

                                            $channel_data[] =  [
                                                'file_name' => $merged_file_name.".wav",
                                                'timestamp' => $channel->aired_time,
                                            ];
                                        }

                                        else{
                                            $channel->fetch_status = $channel->setMergingFailed();
                                            $channel->save();
                                        }

                                    }
                                    else{
                                        $channel->fetch_status = $channel->setMergingFailed();
                                        $channel->save();
                                    }

                                }

                                else{
                                    $channel->fetch_status = $channel->setSecondClipFailed();
                                    $channel->save();
                                }
                            }
                        }

                        else{
                            $channel->fetch_status = $channel->setFirstClipFailed();
                            $channel->save();
                        }



                        $channel->refresh();
                        $nextFetch = $channel->getNextFetch();

                        $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";


                    }

                    if (count($channel_data) > 0){
                        $post_data['channels'][$channel->id] = $channel_data;
                    }

                }

            }


        }

        /*
         * End for each channels
         * */


        if (count($post_data['channels']) > 0){
            $match_url = config('app.match_url');

            $client = new Client();

            $promise = $client->postAsync($match_url, [
                'json' => $post_data,
            ])->then(
                function (ResponseInterface $response) use ($post_data){
                    $channel_ids = array_keys($post_data['channels']);
                    if (count($channel_ids) > 0){
                        Channel::whereIn('id', $channel_ids)
                            ->update(['fetch_status' => 7]);
                    }

                    /*foreach ($channels as $channel) {
                        $channel->fetch_status = $channel->setMatchRequestOk();
                        $channel->save();
                    }*/
                },
                function (RequestException $exception) use ($post_data){
                    $channel_ids = array_keys($post_data['channels']);
                    if (count($channel_ids) > 0){
                        Channel::whereIn('id', $channel_ids)
                            ->update(['fetch_status' => 6]);
                    }
                    /*foreach ($channels as $channel) {
                        $channel->fetch_status = $channel->setMatchRequestFailed();
                        $channel->save();
                    }*/
                }
            );

            $response = $promise->wait();
        }


    }


}
