@extends('layouts.dashboard')

@section('title')
    Create new movie - 
@endsection

@section('pageTitle')
    Create new movie    
@endsection

@section('content')

<div class="container">
    <div class="row">
        <div class="col">
            <form action="{{ route('movieStore') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" name="title" class="form-control" id="title" value="{{ old('title') }}" required maxlength="255">
                                @error('title')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required maxlength="500">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>            
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="year">Year</label>
                                <input type="number" name="year" class="form-control" id="year" value="{{ old('year') }}" required min="0">
                                @error('year')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="rating">Rating</label>
                                <input type="text" name="rating" class="form-control" id="rating" value="{{ old('rating') }}" required maxlength="25">
                                @error('rating')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="length">Length</label>
                                <input type="text" name="length" class="form-control" id="length" value="{{ old('length') }}" required pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" placeholder="e.g 01:10">
                                @error('length')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            
                
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cast">Cast</label>
                                <input type="text" name="cast" class="form-control" id="cast" value="{{ old('cast') }}" required maxlength="500">
                                @error('cast')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="genre">Genre</label>
                                <input type="text" name="genre" class="form-control" id="genre" value="{{ old('genre') }}" required maxlength="500">
                                @error('genre')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="resourceFile" onchange="updateFileLabel('resourceFile')">
                                        <label class="custom-file-label" for="resourceFile">Upload files</label>
                                    </div>
                                </div>
                                    
                                <div class="progress">
                                    <div id="progressBar" class="progress-bar" role="progressbar"></div>
                                </div>   

                                <div class="card-body">
                                    <h5 class="card-title">Uploaded files</h5>

                                    <div id="uploadedFiles">
                                        <p class="card-text"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <div class="container mt-1">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="thumbnail">Thumbnail</label>
            
                                <select id="thumbnail" name="thumbnail" class="custom-select" required>
                                    <option></option>
                                </select>
            
                                @error('thumbnail')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
            
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="platformVideo">Select video platform</label><br>
            
                                <select name="platformVideo" class="custom-select" onchange="switchVideoOption('platformVideo', 'videoFields')" id="platformVideo">
                                    <option value="html5" @if (old('platformVideo') == "html5") selected @endif>HTML5</option>
                                    <option value="vimeo" @if (old('platformVideo') == "vimeo") selected @endif>Vimeo</option>
                                    <option value="youtube" @if (old('platformVideo') == "youtube") selected @endif>YouTube</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6" id="videoFields">
                            <div class="form-group" style="display: none" id="videoId">
                                <label for="videoIdField">Video ID</label><br>
                                <input type="text" name="videoId" class="form-control" id="videoIdField" value="{{ old('videoId') }}">
                                @error('videoId')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
            
                            <div class="form-group" id="uploadVideo">
                                <label for="video">Video</label>
            
                                <select id="video" name="video" class="custom-select" required>
                                    <option></option>
                                </select>
                                
                                @error('video')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>


                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="platformTrailer">Select trailer platform</label><br>
            
                                <select name="platformTrailer" class="custom-select" onchange="switchVideoOption('platformTrailer', 'trailerFields')" id="platformTrailer">
                                    <option value="html5" @if (old('platformTrailer') == "html5") selected @endif>HTML5</option>
                                    <option value="vimeo" @if (old('platformTrailer') == "vimeo") selected @endif>Vimeo</option>
                                    <option value="youtube" @if (old('platformTrailer') == "youtube") selected @endif>YouTube</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6" id="trailerFields">
                            <div class="form-group" style="display: none" id="trailerId">
                                <label for="trailerIdField">Trailer video ID</label><br>
                                <input type="text" name="trailerId" class="form-control" id="trailerIdField" value="{{ old('trailerId') }}">
                                @error('trailerId')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
            
                            <div class="form-group" id="uploadTrailer">
                                <label for="trailer">Trailer</label>
            
                                <select id="trailer" name="trailer" class="custom-select" required>
                                    <option></option>
                                </select>
                                
                                @error('trailer')
                                    <div class="alert alert-danger py-2 my-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="public">Select visibility</label><br>
            
                                <select name="public" class="custom-select" id="platform">
                                    <option value="0" @if (old('public') == 0) selected @endif>Private</option>
                                    <option value="1" @if (old('public') == 1) selected @endif>Public</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <input type="submit" class="btn btn-primary my-2">
                        </div>
                    </div>
                </div>
             
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
    <script src="{{ URL::asset('js/switchVideoOption.js') }}"></script>

    <script>
        const url = "{{route('resourceStoreApi')}}";
        //chunk size in bytes (1MB)
        const chunkSize = {{env('CHUNK_SIZE')}} * 1000000; 
    </script>

    <script src="{{ URL::asset('js/upload.js') }}"></script>

@endsection