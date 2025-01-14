<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Episode;
use App\Models\Seasons;
use App\Models\Video;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use App\Http\Traits\StoreResourceTrait;
use Auth;

class EpisodeController extends Controller
{
    use StoreResourceTrait;

    public function episodesOrderEdit($id)
    {
        $episodes = Episode::where('season_id', '=', $id)
            ->orderBy('episodes.order', 'asc')
            ->select([
                'id', 
                'title', 
                'order', 
            ])
            ->get();

        return view('episodes.order', [
            'episodes' => $episodes,
            'id' => $id
        ]);
    }

    public function episodesOrderUpdate(Request $request, $id)
    {
        //Iterate throughout the order list and add validation rules to all of them

        //Add the first item to the array
        $orderItem = 0;

        $validationArray = [];

        //Add validation rules
        for($i=0;$i<$request->countEpisodes;$i++)
        {
            $validationArray['order' . $i] = 'required|string';
            $validationArray['episode' . $i] = 'required|numeric';
        }

        $validated = $request->validate($validationArray);

        //Add seasons id to seasons array
        $episodesIds = [];

        for($i=0;$i<$request->countEpisodes;$i++)
        {
            array_push($episodesIds, $request['episode' . $i]);
        }

        //Get all the seasons that are being modified
        $episodes = Episode::whereIn('id', $episodesIds)->orderBy('order', 'ASC')->get();

        //Update order and save to the database
        foreach($episodes as $key => $episode)
        {
            $episode->order = $request['order' . $key];
            $episode->save();
        }

        return redirect()->route('seasonDashboard');
    }

    public function indexDashboard()
    {
        $episodes = Episode::orderByDesc('id')
            ->join('seasons', 'seasons.id', '=', 'episodes.season_id')
            ->select([
                'episodes.id', 
                'episodes.title', 
                'episodes.created_at', 
                'seasons.title as season_title'
            ])
            ->simplePaginate(10);

        return view('episodes.index', [
            'episodes' => $episodes, 
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $seasons = Seasons::orderBy('seasons.id', 'desc')
            ->join('series', 'seasons.series_id', '=', 'series.id')
            ->get(['seasons.id', 'seasons.title', 'series.title as series_title']);

        return view('episodes.create', [
            'seasons' => $seasons,
        ]);
    }

    private function timeToSeconds(string $time): int
    {
        $d = explode(':', $time);
        return (intval($d[0]) * 3600) + (intval($d[1]) * 60);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validationArray = [
            'title' => 'required|max:255',
            'description' => 'required|max:500',
            'length' => 'required',
            'thumbnail' => 'required|max:45',
            'public' => 'required|boolean',
            'season_id' => 'required|numeric'
        ];
        
        //Platform for video 
        if($request->platformVideo == 'html5')
            $validationArray['video'] = 'required|max:45';
        else
            $validationArray['videoId'] = 'required|max:45';

        $validated = $request->validate($validationArray);

        $seconds = $this->timeToSeconds($request->length);

        $episode = new Episode();
        $episode->title = $request->title;
        $episode->description = $request->description;
        $episode->length = $seconds;
        $episode->public = $request->public;
        $episode->season_id = $request->season_id;

        //Link the thumbnail from images table to episodes table
        $episode->thumbnail = $this->storeImage($request->thumbnail);

        //Store video to the database and file
        if($request->platformVideo == 'html5')
        {
            //Link the video from videos table to episodes table
            $episode->video = $this->storeVideo($request->video);
        }
        else
        {
            $episode->video = $this->storeVideoExternal($request->videoId, $request->platformVideo);
        }

        //Set the order of the season as the last season of the series
        $lastOrder = Episode::where('season_id', '=', $request->season_id)
            ->orderBy('order', 'desc')
            ->first(['order']);

        if($lastOrder != null)
            $episode->order = $lastOrder['order'] + 1;
        else
            $episode->order = 1;

        $episode->save();
        return redirect()->route('episodeDashboard');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //Get the current episode
        $currentEpisode = Episode::where([
            ['episodes.id', '=', $id],
            ['episodes.public', '=', 1]
        ])
            ->join('seasons', 'seasons.id', '=', 'episodes.season_id')
            ->join('series', 'series.id', '=', 'seasons.series_id')
            ->join('videos', 'videos.id', '=', 'episodes.video')
            ->first([
                'episodes.title', 
                'episodes.description', 
                'episodes.video', 
                'episodes.order', 
                'episodes.season_id',
                'series.year',
                'series.genre',
                'series.cast',
                'series.rating',
                'seasons.series_id',
                'seasons.order as season_order',
                'videos.name as video_name',
                'videos.storage as video_storage',
            ]);

        //Check if that episode exists
        if($currentEpisode == null)
            return abort(404);

        //Set order of the current episode
        $currentEpisodeOrder = $currentEpisode['order'];

        //Set current season id
        $currentSeasonId = $currentEpisode['season_id'];

        //Get the next episode
        $nextEpisode = Episode::where([
            ['order', '>', $currentEpisodeOrder],
            ['season_id', '=', $currentSeasonId]
        ])
            ->orderBy('order', 'asc')
            ->limit(1)
            ->select('id')
            ->first();

        //Get the previous episode
        $prevEpisode = Episode::where([
            ['order', '<', $currentEpisodeOrder],
            ['season_id', '=', $currentSeasonId]
        ])
            ->orderBy('order', 'desc')
            ->limit(1)
            ->select('id')
            ->first();
    
        //Create an array with all the prev, next, current
        $episodes = [];

        $episodes['previous'] = $prevEpisode;
        $episodes['current'] = $currentEpisode;
        $episodes['next'] = $nextEpisode;

        //checks if the user is subscribed
        $user = Auth::user();
        $subscribed = false;

        $cast = explode(", ", $currentEpisode['cast']);
        $genre = explode(", ", $currentEpisode['genre']);

        return view('episodes.show', [
            'episodes' => $episodes,
            'cast' => $cast, 
            'genre' => $genre,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $episode = Episode::find($id);
       
        if($episode != null)
            $content = $episode;
        else
            abort();

        $seasons = Seasons::orderBy('id', 'desc')
            ->get(['id', 'title']);

        $video = Video::where('id', '=', $episode['video'])->first(['name', 'storage']);

        return view('episodes.edit', [
            'content' => $content,
            'id' => $id,
            'seasons' => $seasons,
            'video' => $video
        ]);
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
        $validationArray = [
            'title' => 'required|max:255',
            'description' => 'required|max:500',
            'length' => 'required',
            'public' => 'required|boolean',
            'season_id' => 'required|numeric'
        ];

        //Check for updated thumbnail
        if($request->thumbnail != null)
            $validationArray['thumbnail'] = 'required|max:45';

        //Check for updated video
        if($request->video != null)  
        {
            //Platform for video 
            if($request->platformVideo == 'html5')
                $validationArray['video'] = 'required|max:45';
            else
                $validationArray['videoId'] = 'required|max:45';
        }  

        $validated = $request->validate($validationArray);
        $seconds = $this->timeToSeconds($request->length);

        $episode = Episode::find($id);
        $episode->title = $request->title;
        $episode->description = $request->description;
        $episode->length = $seconds;
        $episode->public = $request->public;

        //If you update the season of a episode then set the order as the last episode of the season
        if($episode->season_id != $request->season_id)
        {
            //Get the last value order for episodes
            $lastOrder = Episode::where("season_id", "=", $request->season_id)
                            ->orderBy('order', 'DESC')
                            ->limit(1)
                            ->first('order');

            //Set it to 1 if there is not last order
            if($lastOrder == null)
                $episode->order = 1;
            else
                $episode->order = $lastOrder->order + 1;
        }

        $episode->season_id = $request->season_id;
        
        //Update thumbnail
        if($request->thumbnail != null)
        {
            $this->updateImageResource($request->thumbnail, $episode->thumbnail, config('app.storage_disk'));
        }

        //Update video
        if($request->platformVideo == 'html5')
        {
            if($request->video != null)
            {
                //Update the old video field with the new uploaded video
                $this->updateVideoResource($request->video, $episode->video, config('app.storage_disk'));
            } 
        }
        else if($request->platformVideo == 'youtube' || $request->platformVideo == 'vimeo')
        {
            if($request->videoId != null)
            {
                //Update the old video field with the new video id
                $this->updateVideoResource($request->videoId, $episode->video, $request->platformVideo);
            }
        }

        $episode->save();

        return redirect()->route('episodeDashboard');
    }
}
