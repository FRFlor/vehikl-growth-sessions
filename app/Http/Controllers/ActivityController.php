<?php

namespace App\Http\Controllers;

use App\SocialMob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int)$request->user()->id;

        $hostedMobs = $request->user()->socialMobs;

        $joinedMobIds = DB::table('social_mob_user')
            ->select('social_mob_id')
            ->where('user_id', $userId)
            ->pluck('social_mob_id');

        $joinedMobs = SocialMob::query()
            ->whereIn('id', $joinedMobIds->concat($hostedMobs->pluck('id')))
            ->get();

        $peers = $hostedMobs
            ->concat($joinedMobs)
            ->reduce(function ($users, $mob) {
                return $users
                    ->concat([$mob->owner])
                    ->concat($mob->attendees);
            }, collect([]))
            ->filter(function ($user) use ($userId) {
                return $user->id !== $userId;
            })
            ->reduce(function ($collection, $user)  {
                if (isset($collection[$user->id])) {
                    $collection[$user->id]['count']++;
                } else {
                    $collection[$user->id] = [
                        'count' => 1,
                        'user' => $user->toArray()
                    ];
                }

                return $collection;
            }, []);

        return view('activity', [
            'hosted_mobs' => $hostedMobs,
            'joined_mobs' => $joinedMobs,
            'peers' => array_values($peers),
        ]);
    }
}
