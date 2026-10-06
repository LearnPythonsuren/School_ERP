<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    private const RULES = [
        'title'    => ['string', 'max:150'],
        'body'     => ['string', 'max:2000'],
        'channel'  => ['in:sms,email,push'],
        'audience' => ['in:all,parents,staff,class'],
    ];

    public function index(Request $request)
    {
        return Announcement::latest('id')->paginate($this->perPage($request));
    }

    /** Create a draft announcement (not sent). */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules('required'));
        $data['status'] = 'draft';
        return response()->json(Announcement::create($data), 201);
    }

    public function show(Announcement $announcement)
    {
        return response()->json($announcement);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate($this->rules('sometimes'));
        $announcement->update($data);
        return response()->json($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return response()->noContent();
    }

    /**
     * Create + send an announcement. Recipients are counted from the
     * school's records. In-app delivery works out of the box (the
     * announcements feed); to also deliver by SMS/e-mail/push, dispatch to
     * your gateway (MSG91, Twilio, FCM, SMTP…) at the marked line.
     */
    public function send(Request $request)
    {
        $data = $request->validate($this->rules('required'));
        $recipients = match ($data['audience']) {
            'staff' => StaffMember::count(),
            'all'   => Student::count() + StaffMember::count(),
            default => Student::count(),
        };
        $announcement = Announcement::create([
            ...$data, 'recipients' => $recipients, 'status' => 'sent', 'sent_at' => now(),
        ]);

        // Gateway hook: e.g. dispatch(new SendAnnouncement($announcement));

        return response()->json($announcement, 201);
    }

    private function rules(string $presence): array
    {
        return array_map(fn ($r) => [$presence, ...$r], self::RULES);
    }
}
