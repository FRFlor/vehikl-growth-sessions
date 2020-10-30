@extends('layouts.app')

@section('content')
    <activity-view class="mt-6"
        :user="{{ json_encode(auth()->user()->toArray()) }}"
        :hosted_mobs="{{ json_encode($hosted_mobs) }}"
        :joined_mobs="{{ json_encode($joined_mobs) }}"
        :peers="{{ json_encode($peers) }}"
    ></activity-view>

    <p class="mx-2 mt-4 text-lg font-bold text-blue-700">Have suggestions for this app? </p>
    <a class="inline-block px-4 py-2 mx-2 mt-2 font-bold text-white bg-blue-600 rounded hover:bg-blue-700 focus:bg-blue-700"
       target="_blank"
       href="https://github.com/FRFlor/social-mob/issues">
        Please, share them here!
    </a>
@endsection
