<?php

namespace Tests\Feature;

use App\SocialMob;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;


    public function testItReturnsATallyOfMentoredBy()
    {
        $this->withoutExceptionHandling();
        $me = factory(User::class)->create();
        $mobA = factory(SocialMob::class)->create();
        $mobB = factory(SocialMob::class)->create();
        $mobC = factory(SocialMob::class)->create(['owner_id' => $mobB->owner_id]);

        $mobA->attendees()->attach($me);
        $mobB->attendees()->attach($me);
        $mobC->attendees()->attach($me);

        $expectedPeers = [
            [
                'count' => 1,
                'user' => $mobA->owner->toArray(),
            ],
            [
                'count' => 2,
                'user' => $mobB->owner->toArray(),
            ],
        ];

        $this->actingAs($me)
             ->get(route('activity'))
             ->assertViewHas('peers', $expectedPeers);
    }
}
