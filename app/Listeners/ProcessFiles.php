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

            while ($channel->last_fetch_at != null && $now->gt(Carbon::parse($channel->last_fetch_at)) && Carbon::parse($channel->last_fetch_at)->diffInMinutes($now) >= 2 ){
//            while (strtotime($channel->last_fetch_at) <= strtotime(Carbon::now()->subMinutes(2)->toDateTimeString())){
                /*if (strtotime($channel->last_fetch_at) <= strtotime(Carbon::now()->subMinutes(2))){
                    break;
                }*/

                /*
                 * if match request is OK
                 * */
                if ($channel->isMatchRequestOk()){


                    $nextFetch = $channel->getNextFetch();

                    $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";


                    if (Storage::disk('ftp')->exists($ftp_path)){


                        $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

                        $ftp_clip1 = Storage::disk('ftp')->get($ftp_path);

                        $clip1_name = "{$channel->id}_{$nextFetch['day']}_{$nextFetch['hour']}_{$nextFetch['minute']}";
                        $clip1_path = "fetched/{$clip1_name}.wma";
                        Storage::disk('clips')->put($clip1_path, $ftp_clip1);

                        if (Storage::disk('clips')->exists($clip1_path)){

                            $lastModified = date('Y-m-d H:i:s', $lastModified);


                            $channel->last_fetch_at = Carbon::now()->toDateTimeString();
                            $channel->aired_time = $lastModified;
                            $channel->fetch_status = $channel->setFirstClipOk();
                            $channel->save();
//                            $channel->refresh();
                            $channel->setFetched($nextFetch);
//                            $channel->save();


//                            $channel->refresh();

                            $nextFetch = $channel->getNextFetch();

                            $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";

                            if (Storage::disk('ftp')->exists($ftp_path)) {

//                                $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

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
//                                    $channel->refresh();
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

                                        FFMpegFacade::fromDisk('clips')->open($merged_file_path)->export()->inFormat(new \FFMpeg\Format\Audio\Wav)->save($final_file_path);

                                        if (Storage::disk('clips')->exists($final_file_path)){

                                            $channel->fetch_status = $channel->setMergingOk();

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
                            else{
                                $channel->fetch_status = $channel->setSecondClipFailed();
                                $channel->save();


                            }
                        }

                        else{
                            $channel->fetch_status = $channel->setFirstClipFailed();
                            $channel->save();


                        }

                    }

                    else{
                        $channel->fetch_status = $channel->setFirstClipFailed();
                        $channel->save();


                    }

                }

                /*
                 * End if match request is OK
                 * */

                /*
                 * if match merging is OK
                 * */
                elseif ($channel->isMergingOk()){

                    $final_file_name = $channel->clip_path;

                    $merged_file_name = "{$final_file_name}";
                    $final_file_path = "merged/{$merged_file_name}.wav";

                    $channel_data[] =  [
                        'file_name' => $merged_file_name.".wav",
                        'timestamp' => $channel->aired_time,
                    ];
                    /*$post_data['channels'][] = [
                        'channel_id' => $channel->id,
                        'file_name' => Storage::disk('clips')->path($channel->clip_path),
                        'timestamp' => $channel->aired_time,
                    ];*/
                }
                /*
                 * End if match request is OK
                 * */

                /*
                 * if second clip is OK
                 * */
                elseif ($channel->isSecondClipOk()){

                    $final_file_name = $channel->clip_path;

                    $final_file = explode("-", $final_file_name);
                    $clip1_path = "fetched/{$final_file[0]}.wma";
                    $clip2_path = "fetched/{$final_file[1]}.wma";

                    $merged_file_name = "{$final_file_name}";
                    $merged_file_path = "merged/{$merged_file_name}.wma";
                    $final_file_path = "merged/{$merged_file_name}.wav";

                    $channel->clip_path = $merged_file_name;
                    $channel->save();

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
                        $channel->setMergingFailed();
                        $channel->save();
                    }


                }

                /*
                 * End if second clip is OK
                 * */

                /*
                 * if first clip is OK
                 * */

                elseif ($channel->isFirstClipOk()){

                    $currentFetch = $channel->getCurrentFetch();

                    $clip1_name = "{$channel->id}_{$currentFetch['day']}_{$currentFetch['hour']}_{$currentFetch['minute']}";
                    $clip1_path = "fetched/{$clip1_name}.wma";
                    /*$clip2_path = "{$final_file[1]}.wma";

                    $merged_file_name = "{$final_file_name}";
                    $merged_file_path = "merged/{$merged_file_name}.wma";
                    $final_file_path = "merged/{$merged_file_name}.wav";*/

                    $nextFetch = $channel->getNextFetch();

                    $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";

                    if (Storage::disk('ftp')->exists($ftp_path)) {
//                        $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

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
//                                    $channel->refresh();
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

                /*
                 * End if first clip is OK
                 * */

                /*
                 * Else
                 * */
                else{
                    $nextFetch = $channel->getCurrentFetch();

                    $ftp_path = "FM/logger{$channel->logger}/{$nextFetch['day']}/{$nextFetch['hour']}/{$nextFetch['minute']}.wma";

                    if (Storage::disk('ftp')->exists($ftp_path)){

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
//                                $lastModified = Storage::disk('ftp')->lastModified($ftp_path);

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
//                                                'file_name' => Storage::disk('clips')->path($final_file_path),
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

                    }
                }
                /*
                 * End Else
                 * */

                $channel->refresh();

            }

            /*
             * End while
             * */

            $post_data['channels'][$channel->id] = $channel_data;

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
                function (ResponseInterface $response) use ($channels){
                    foreach ($channels as $channel) {
                        $channel->fetch_status = $channel->setMatchRequestOk();
                        $channel->save();
                    }
                },
                function (RequestException $exception) use ($channels){
                    foreach ($channels as $channel) {
                        $channel->fetch_status = $channel->setMatchRequestFailed();
                        $channel->save();
                    }
                }
            );

            $response = $promise->wait();
        }


    }


}
