@extends('layouts.default')

@section('content')

<div class="page-header">
    <h1>Add New Thesaurus </h1>
</div>
<form method="post" action="" class="form col-md-12 center-block">

    {{ Form::token() }}

    <div class="form-group col-md-6">
        <input type="text" placeholder="Word " name="word1"  
               class="form-control input-lg" />
        <span class="help-block">These two words will be related in two ways.</span>

    </div> 
    <div class="form-group col-md-6">
        <input type="text" placeholder="Related Word " name="word2"  
               class="form-control input-lg" />
    </div>

    <div class="form-group col-md-12"> 
        <select class="form-control input-lg" name="level">
            <option value="100">High</option>
            <option value="50">Medium</option>
            <option value="10">Low</option>
        </select>
    </div>


    <div class="form-group col-md-12"> 
        <input type="text" name="lang" placeholder="Language in ISO-639-1 code (en,tr,fr,de) " class="form-control input-lg" />
        <span class="help-block">You can leave blank if language is "en"</span>
    </div>


    <div class="form-group col-md-12">
        <div class="controls">
            <button type="submit" class="btn btn-success btn-lg">Add</button>
        </div>
    </div>
</form>

@stop
