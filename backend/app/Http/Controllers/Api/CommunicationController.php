<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index()
    {
        return Announcement::latest()->paginate(25);
    }

    /** Create a draft announcement (not sent). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'    => ['required', 'string'],
            'body'     => ['required', 'string'],
            'channel'  => ['required', 'in:sms,email,push'],
            'audience' => ['required', 'in:all,parents,staff,class'],
        ]);
        $data['status'] = 'draft';
        return response()->json(Announcement::create($data), 201);
    }

    public function show(Announcement $announcement)
    {
        return response()->json($announcement);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'    => ['sometimes', 'string'],
            'body'     => ['sometimes', 'string'],
            'channel'  => ['sometimes', 'in:sms,email,push'],
            'audience' => ['sometimes', 'in:all,parents,staff,class'],
        ]);
        $announcement->update($data);
        return response()->json($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return response()->noContent();
    }

    /** Create + "send" an announcement. Wire your SMS/email/push provider here. */
    public function send(Request $request)
    {
        $data = $request->validate([
            'title'    => ['required', 'string'],
            'body'     => ['required', 'string'],
            'channel'  => ['required', 'in:sms,email,push'],
            'audience' => ['required', 'in:all,parents,staff,class'],
        ]);
        $recipients = match ($data['audience']) {
            'staff' => StaffMember::count(),
            'all'   => Student::count() + StaffMember::count(),
            default => Student::count(),
        };
        $announcement = Announcement::create([
            ...$data, 'recipients' => $recipients, 'status' => 'sent', 'sent_at' => now(),
        ]);
        return response()->json($announcement, 201);
    }
}
