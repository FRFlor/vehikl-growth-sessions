<?php

namespace Tests\Feature;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    public function testTheOwnerOfAGrowthSessionCanEditIt()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();
        $newTopic = 'A brand new topic!';
        $newTitle = 'A whole new title!';

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'topic' => $newTopic,
            'title' => $newTitle,
        ])->assertSuccessful();

        $this->assertEquals($newTopic, $growthSession->fresh()->topic);
        $this->assertEquals($newTitle, $growthSession->fresh()->title);
    }

    public function testTheOwnerOfAGrowthSessionCanChangeTheAttendeeLimit()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 10;
        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertSuccessful();

        $this->assertEquals($newAttendeeLimit, $growthSession->fresh()->attendee_limit);
    }

    public function testTheOwnerOfAGrowthSessionCanNotChangeTheAttendeeLimitBelowTheCurrentAttendeeCount()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 6]);
        $users = User::factory()->times(5)->create();
        $growthSession->attendees()->attach($users->pluck('id'));

        $newAttendeeLimit = 4;
        $this->assertTrue($newAttendeeLimit < $growthSession->attendees()->count());
        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 5.']);
    }

    public function testTheNewAttendeeLimitHasToBeANumber()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 'bananas';
        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be an integer.']);
    }

    public function testItAllowsTheAttendeeLimitToBeUnset()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'attendee_limit' => null
        ])->assertSuccessful();

        $this->assertEquals(GrowthSession::NO_LIMIT, $growthSession->fresh()->attendee_limit);
    }

    public function testTheOwnerCanChangeTheDateOfAnUpcomingGrowthSession()
    {
        $this->setTestNow('2020-01-01');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-02"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertSuccessful();

        $this->assertEquals($newDate, $growthSession->fresh()->toArray()['date']);
    }

    public function testTheDateOfTheGrowthSessionCannotBeSetToThePast()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-06"]);
        $newDate = '2020-01-03';

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTheOwnerCannotUpdateAGrowthSessionThatAlreadyHappened()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-01"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotEditIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->putJson(route('growth_sessions.update',
            ['growth_session' => $growthSession->id]), [
            'topic' => 'Anything',
        ])->assertForbidden();
    }

    public function testTheOwnerCanDeleteAnExistingGrowthSession()
    {
        $this->withoutExceptionHandling();
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $this->actingAs($growthSession->owner)->deleteJson(route('growth_sessions.destroy',
            ['growth_session' => $growthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($growthSession->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotDeleteIt()
    {
        $growthSession = GrowthSession::factory()->create();
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->deleteJson(route('growth_sessions.destroy',
            ['growth_session' => $growthSession->id]))
            ->assertForbidden();
    }

    public function testAGivenUserCanRSVPToAGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEquals($user->id, $existingGrowthSession->attendees->first()->id);
    }

    public function testAUserCannotJoinTheSameGrowthSessionTwice()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $existingGrowthSession->id]))
            ->assertForbidden();

        $this->assertCount(1, $existingGrowthSession->attendees);
    }

    public function testAUserCanLeaveTheGrowthSession()
    {
        $existingGrowthSession = GrowthSession::factory()->create();
        $user = User::factory()->create();
        $existingGrowthSession->attendees()->attach($user);

        $this->actingAs($user)
            ->postJson(route('growth_sessions.leave', ['growth_session' => $existingGrowthSession->id]))
            ->assertSuccessful();

        $this->assertEmpty($existingGrowthSession->attendees);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '04:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(2), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => $monday->addDays(4), 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        GrowthSession::factory()
            ->create([
                'date' => $monday->addDays(8),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ]); // GrowthSessions on another week

        $expectedResponse = [
            $monday->toDateString() => [$mondayGrowthSession],
            $monday->addDays(1)->toDateString() => [],
            $monday->addDays(2)->toDateString() => [$earlyWednesdayGrowthSession, $lateWednesdayGrowthSession],
            $monday->addDays(3)->toDateString() => [],
            $monday->addDays(4)->toDateString() => [$fridayGrowthSession],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('growth_sessions.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfTheCurrentWeekForAuthenticatedUserEvenOnFridays()
    {
        $this->setTestNow('Next Friday');

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => Carbon::parse('Last Monday'), 'attendee_limit' => 4])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create(['date' => today(), 'attendee_limit' => 4])
            ->toArray();

        $expectedResponse = [
            Carbon::parse('Last Monday')->toDateString() => [$mondayGrowthSession],
            Carbon::parse('Last Tuesday')->toDateString() => [],
            Carbon::parse('Last Wednesday')->toDateString() => [],
            Carbon::parse('Last Thursday')->toDateString() => [],
            today()->toDateString() => [$fridayGrowthSession],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('growth_sessions.week'));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItCanProvideAllGrowthSessionsOfASpecifiedWeekForAuthenticatedUserIfADateIsGiven()
    {
        $weekThatHasNoGrowthSessions = '2020-05-25';
        $this->setTestNow($weekThatHasNoGrowthSessions);
        $weekThatHasTheGrowthSessions = '2020-05-04';
        $mondayOfWeekWithGrowthSessions = CarbonImmutable::parse($weekThatHasTheGrowthSessions);

        $mondayGrowthSession = GrowthSession::factory()
            ->create(['date' => $mondayOfWeekWithGrowthSessions, 'start_time' => '03:30 pm', 'attendee_limit' => 4])
            ->toArray();
        $lateWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '04:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();
        $earlyWednesdayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(2),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();
        $fridayGrowthSession = GrowthSession::factory()
            ->create([
                'date' => $mondayOfWeekWithGrowthSessions->addDays(4),
                'start_time' => '03:30 pm',
                'attendee_limit' => 4
            ])
            ->toArray();

        $expectedResponse = [
            $mondayOfWeekWithGrowthSessions->toDateString() => [$mondayGrowthSession],
            $mondayOfWeekWithGrowthSessions->addDays(1)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(2)->toDateString() => [
                $earlyWednesdayGrowthSession,
                $lateWednesdayGrowthSession
            ],
            $mondayOfWeekWithGrowthSessions->addDays(3)->toDateString() => [],
            $mondayOfWeekWithGrowthSessions->addDays(4)->toDateString() => [$fridayGrowthSession],
        ];

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('growth_sessions.week',
            ['date' => $weekThatHasTheGrowthSessions]));
        $response->assertSuccessful();
        $response->assertJson($expectedResponse);
    }

    public function testItDoesNotShowVehiklOnlyGrowthSessionsOfASpecifiedWeekForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(2),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(3),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'date' => $monday->addDays(4),
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);

        $response = $this->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertExactJson([
                $monday->toDateString() => [],
                $monday->addDays(1)->toDateString() => [],
                $monday->addDays(2)->toDateString() => [],
                $monday->addDays(3)->toDateString() => [],
                $monday->addDays(4)->toDateString() => [],
            ]);
    }

    public function testItShowsPublicGrowthSessionsToAnonymousUsers()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm', 'attendee_limit' => 4]);
        $isPublic = GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4
        ]);

        $response = $this->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $isPublic->id]);
    }

    public function testItDoesNotProvideLocationOfAGrowthSessionForAnonymousUser()
    {
        $this->withoutExceptionHandling();
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->get(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItCanProvideGrowthSessionLocationForAuthenticatedUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $user = User::factory()->create(['is_vehikl_member' => false]);
        $response = $this->actingAs($user)->get(route('growth_sessions.show', $growthSession));

        $response->assertSuccessful();
        $response->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItProvidesASummaryOfTheGrowthSessionsOfTheDay()
    {
        $today = '2020-01-02';
        $tomorrow = '2020-01-03';
        $this->setTestNow($today);
        $user = User::factory()->create();

        $todayGrowthSessions = GrowthSession::factory()->times(2)->create(['date' => $today, 'attendee_limit' => 4]);
        GrowthSession::factory()->times(2)->create(['date' => $tomorrow, 'attendee_limit' => 4]);

        $response = $this->actingAs($user)->getJson(route('growth_sessions.day'));

        $response->assertJson($todayGrowthSessions->toArray());
    }

    public function testTheSlackBotCanSeeTheGrowthSessionLocation()
    {
        $this->seed();
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $this->getJson(route('growth_sessions.day'), ['Authorization' => 'Bearer ' . config('auth.slack_token')])
            ->assertJsonFragment(['location' => $growthSession->location]);
    }

    public function testAnAttendeeLimitCanBeSetWhenCreatingAtGrowthSession()
    {
        $user = User::factory()->vehiklMember()->create();

        $expectedAttendeeLimit = 420;
        $this->actingAs($user)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertSuccessful();

        $this->assertEquals($expectedAttendeeLimit, $user->growthSessions->first()->attendee_limit);
    }

    public function testAnAttendeeLimitCannotBeLessThanFour()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $expectedAttendeeLimit = 3;
        $this->actingAs($vehiklMember)->postJson(
            route('growth_sessions.store'),
            $this->defaultParameters(['attendee_limit' => $expectedAttendeeLimit])
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 4']);
    }

    public function testAGrowthSessionCannotBeJoinedIfTheAttendeeLimitIsMet()
    {
        $user = User::factory()->create();
        $attendess = User::factory()->times(4)->create();
        /** @var GrowthSession $growthSession */
        $growthSession = GrowthSession::factory()->create(['attendee_limit' => 4]);
        $growthSession->attendees()->attach($attendess);


        $response = $this->actingAs($user)
            ->postJson(route('growth_sessions.join', ['growth_session' => $growthSession->id]));

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => 'The attendee limit has been reached.']);
    }

    public function testVehiklUsersCanViewPrivateGrowthSessions()
    {
        $this->setTestNow('2020-01-15');

        $vehiklMember = User::factory()->vehiklMember()->create();
        $monday = CarbonImmutable::parse('Last Monday');
        $vehiklOnlySession = GrowthSession::factory()->create([
            'date' => $monday,
            'start_time' => '03:30 pm',
            'attendee_limit' => 4,
            'is_public' => false
        ]);
        GrowthSession::factory()->create([
            'is_public' => true,
            'date' => $monday->addDays(1),
            'start_time' => '04:30 pm',
            'attendee_limit' => 4
        ]);

        $response = $this->actingAs($vehiklMember)->getJson(route('growth_sessions.week'));

        $response->assertSuccessful()
            ->assertJsonFragment(['id' => $vehiklOnlySession->id]);
    }

    public function testAUserCanCreateAPubliclyAvailableGrowthSession()
    {
        // Create a session
        $user = User::factory()->create(['is_vehikl_member' => true]);
        $this->actingAs($user)->postJson(
            route('growth_sessions.store'), $this->defaultParameters(['is_public' => true]));

        // check if the session is public
        $this->assertTrue(GrowthSession::find(1)->is_public);
    }

    public function testANonMemberUserCannotAccessAPrivateGrowthSession()
    {
        // Create a session
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $user = User::factory()->create(['is_vehikl_member' => false]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAGuestCannotAccessAPrivateGrowthSession()
    {
        // Create a session
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAMemberCanAccessAPrivateGrowthSession()
    {
        // Create a session
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertSuccessful()
            ->assertSee($growthSession->title);
    }

    /**
     * @param int $expectedAttendeeLimit
     * @return array
     */
    private function defaultParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'date' => today(),
            'discord_channel' => null,
        ], $params);
    }

    /** @test */
    public function itCanShowGuestForNonVehiklUsers()
    {
        $nonVehiklMember = User::factory()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->actingAs($nonVehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($guestMember->name)
            ->assertDontSee($growthSession->avatar)
            ->assertDontSee($guestMember->github_nickname);
    }

    /** @test */
    public function itCanShowGuestDetailsForVehiklUsers()
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->actingAs($vehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee($guestMember->name)
            ->assertSee($growthSession->avatar)
            ->assertSee($guestMember->github_nickname)
            ->assertDontSee('images\\/guest-avatar.webp')
            ->assertDontSee('Guest');
    }

    /** @test */
    public function itCannotShowGuestDetailsForUnauthenicatedUsers()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create();

        $guestMember = $growthSession->attendees()->first();

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($guestMember->name)
            ->assertDontSee($growthSession->avatar)
            ->assertDontSee($guestMember->github_nickname);
    }
    /** @test */
    public function vehiklMembersCanCreateAGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertSuccessful();

        $this->assertNotEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    /** @test */
    public function nonVehiklMembersCannotCreateAGrowthSession(): void
    {
        $nonVehiklMember = User::factory()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($nonVehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertForbidden();

        $this->assertEmpty(GrowthSession::query()->where('title', $growthSessionsAttributes['title'])->first());
    }

    /** @test */
    public function includesAttendeesInformationEvenForANewlyCreatedGrowthSession(): void
    {
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertJsonFragment([
                'attendees' => []
            ]);
    }

    /** @test */
    public function allowsAUserToWatchAGrowthSession(): void
    {
        $this->withoutExceptionHandling();
        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create();

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.watch', $growthSession))
            ->assertSuccessful();

        $this->assertTrue($growthSession->watchers()->first()->is($vehiklMember));
    }


    /** @test */
    public function allowsAUserToUnwatchAGrowthSession(): void
    {
        $watcher = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create();
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $watcher = $growthSession->watchers()->first();

        $this->actingAs($watcher)
            ->post(route('growth_sessions.leave', $growthSession))
            ->assertSuccessful();

        $this->assertEmpty($growthSession->watchers);
    }

    /** @test */
    public function aZoomCallCanBeCreatedWhenCreatingGrowthSession(): void
    {
        Http::fake([
            'https://api.zoom.us/v2/users/me/meetings' => Http::response(['id' => 999], 200),
        ]);

        $vehiklMember = User::factory()->vehiklMember()->create();
        $growthSessionsAttributes = GrowthSession::factory()->make()->toArray();
        $growthSessionsAttributes['create_zoom_meeting'] = true;

        $this->actingAs($vehiklMember)
            ->post(route('growth_sessions.store'), $growthSessionsAttributes)
            ->assertSuccessful();

        $this->assertNotEmpty(GrowthSession::query()->where('zoom_meeting_id', 999)->first());
    }
}
