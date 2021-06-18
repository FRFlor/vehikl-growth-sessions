<?php

namespace App\Http\Controllers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\GrowthSession;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\Policies\GrowthSessionPolicy;
use App\Services\Zoom\ZoomService;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GrowthSessionController extends Controller
{
    public function show(GrowthSession $growthSession)
    {
        // if the user is not allowed to view the growth session then return 404
        abort_unless((new GrowthSessionPolicy())->view(request()->user(), $growthSession), Response::HTTP_NOT_FOUND);

        return view('growth-session', ['growthSession' => new GrowthSessionResource($growthSession)]);
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $sessions = GrowthSession::allInTheWeekOf($request->input('date'))->filter(function (GrowthSession $session) use ($user) {
            return (new GrowthSessionPolicy())->view($user, $session);
        });
        return new GrowthSessionWeek($sessions);
    }

    public function day()
    {
        return GrowthSessionResource::collection(GrowthSession::today()->get());
    }

    public function store(StoreGrowthSessionRequest $request, ZoomService $zoom)
    {
        $newGrowthSession = new GrowthSession ($request->validated());

        if ($request->validated()['create_zoom_meeting']) {
            $newGrowthSession['zoom_meeting_id'] = $zoom->createMeeting($newGrowthSession);
        }

        $newGrowthSession->save();
        $request->user()->growthSessions()->attach($newGrowthSession, ['user_type_id' => UserType::OWNER_ID]);

        $newGrowthSession->fresh();
        event(new GrowthSessionCreated($newGrowthSession));

        return new GrowthSessionResource($newGrowthSession);
    }

    public function join(GrowthSession $growthSession, Request $request)
    {
        if ($growthSession->attendees()->count() === $growthSession->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        $growthSession->attendees()->attach($request->user(), ['user_type_id' => UserType::ATTENDEE_ID]);
        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return $growthSession;
    }

    public function watch(GrowthSession $growthSession, Request $request)
    {
        $growthSession->watchers()->attach($request->user(), ['user_type_id' => UserType::WATCHER_ID]);

        return $growthSession;
    }

    public function leave(GrowthSession $growthSession, Request $request)
    {
        $growthSession->watchers()->detach($request->user());
        $growthSession->attendees()->detach($request->user());

        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return $growthSession;
    }

    public function update(UpdateGrowthSessionRequest $request, GrowthSession $growthSession, ZoomService $zoom)
    {
        $originalValues = $growthSession->toArray();

        if ($request->validated()['create_zoom_meeting']) {
            $growthSession['zoom_meeting_id'] = $zoom->createMeeting($growthSession);
        } else {
            $zoom->deleteMeeting($growthSession['zoom_meeting_id']);
            $growthSession['zoom_meeting_id'] = null;
        }

        $growthSession->update($request->validated());
        event(new GrowthSessionUpdated($originalValues, $growthSession->toArray()));

        return $growthSession;
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->delete();
        event(new GrowthSessionDeleted($growthSession));
    }
}
