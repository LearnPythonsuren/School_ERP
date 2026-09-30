<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\Room;
use App\Models\Setting;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Seeds one school with the same demo data the live prototype uses, so a
 * fresh install looks alive immediately. Run inside a tenant:
 * `php artisan tenants:seed --class=DemoDataSeeder`
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            ['name' => 'Aarav Sharma',  'class_name' => 'Class 10-A', 'roll_no' => 1, 'fee_status' => 'paid',    'attendance_pct' => 96],
            ['name' => 'Diya Patel',    'class_name' => 'Class 9-C',  'roll_no' => 2, 'fee_status' => 'due',     'attendance_pct' => 88],
            ['name' => 'Vihaan Reddy',  'class_name' => 'Class 8-A',  'roll_no' => 3, 'fee_status' => 'paid',    'attendance_pct' => 91],
            ['name' => 'Ananya Nair',   'class_name' => 'Class 10-A', 'roll_no' => 4, 'fee_status' => 'partial', 'attendance_pct' => 99],
            ['name' => 'Kabir Singh',   'class_name' => 'Class 7-B',  'roll_no' => 5, 'fee_status' => 'paid',    'attendance_pct' => 74],
            ['name' => 'Ishita Gupta',  'class_name' => 'Class 9-C',  'roll_no' => 6, 'fee_status' => 'paid',    'attendance_pct' => 93],
            ['name' => 'Arjun Mehta',   'class_name' => 'Class 6-A',  'roll_no' => 7, 'fee_status' => 'due',     'attendance_pct' => 82],
            ['name' => 'Saanvi Rao',    'class_name' => 'Class 8-A',  'roll_no' => 8, 'fee_status' => 'paid',    'attendance_pct' => 97],
        ];
        foreach ($students as $s) {
            Student::create($s);
        }

        $fees = [
            ['invoice_no' => 'INV-2401', 'student_id' => 1, 'class_name' => 'Class 10-A', 'amount' => 24500, 'status' => 'paid',    'paid_on' => '2025-09-12'],
            ['invoice_no' => 'INV-2402', 'student_id' => 2, 'class_name' => 'Class 9-C',  'amount' => 21000, 'status' => 'due'],
            ['invoice_no' => 'INV-2403', 'student_id' => 4, 'class_name' => 'Class 10-A', 'amount' => 24500, 'status' => 'partial', 'paid_on' => '2025-09-08'],
            ['invoice_no' => 'INV-2404', 'student_id' => 6, 'class_name' => 'Class 9-C',  'amount' => 21000, 'status' => 'paid',    'paid_on' => '2025-09-14'],
            ['invoice_no' => 'INV-2405', 'student_id' => 7, 'class_name' => 'Class 6-A',  'amount' => 18000, 'status' => 'due'],
        ];
        foreach ($fees as $f) {
            FeeInvoice::create($f);
        }

        $staff = [
            ['name' => 'Priya Verma',  'role' => 'Mathematics Teacher', 'department' => 'Academics',      'status' => 'present'],
            ['name' => 'Sunil Kumar',  'role' => 'Science Teacher',     'department' => 'Academics',      'status' => 'present'],
            ['name' => 'Meena Joshi',  'role' => 'Class Teacher 9-C',   'department' => 'Academics',      'status' => 'leave'],
            ['name' => 'Rakesh Iyer',  'role' => 'Accountant',          'department' => 'Administration', 'status' => 'present'],
            ['name' => 'Neha Das',     'role' => 'Librarian',           'department' => 'Support',        'status' => 'absent'],
        ];
        foreach ($staff as $s) {
            StaffMember::create($s);
        }

        $results = [
            ['student_id' => 1, 'exam_name' => 'Unit Test 2', 'class_name' => 'Class 10-A', 'maths' => 92, 'science' => 88, 'english' => 85, 'grade' => 'A'],
            ['student_id' => 4, 'exam_name' => 'Unit Test 2', 'class_name' => 'Class 10-A', 'maths' => 78, 'science' => 95, 'english' => 90, 'grade' => 'A'],
            ['student_id' => 3, 'exam_name' => 'Unit Test 2', 'class_name' => 'Class 8-A',  'maths' => 65, 'science' => 72, 'english' => 68, 'grade' => 'B'],
            ['student_id' => 8, 'exam_name' => 'Unit Test 2', 'class_name' => 'Class 8-A',  'maths' => 88, 'science' => 81, 'english' => 79, 'grade' => 'A'],
            ['student_id' => 5, 'exam_name' => 'Unit Test 2', 'class_name' => 'Class 7-B',  'maths' => 54, 'science' => 60, 'english' => 58, 'grade' => 'C'],
        ];
        foreach ($results as $r) {
            ExamResult::create($r);
        }

        $admissions = [
            ['application_no' => 'APP-2601', 'applicant_name' => 'Riya Kapoor',  'class_applied' => 'Class 6-A',  'guardian_name' => 'Sanjay Kapoor', 'guardian_phone' => '98765 43210', 'status' => 'pending',  'applied_on' => '2025-09-20'],
            ['application_no' => 'APP-2602', 'applicant_name' => 'Aditya Menon', 'class_applied' => 'Class 9-C',  'guardian_name' => 'Latha Menon',   'guardian_phone' => '98220 11122', 'status' => 'pending',  'applied_on' => '2025-09-21'],
            ['application_no' => 'APP-2603', 'applicant_name' => 'Zara Khan',    'class_applied' => 'Class 7-B',  'guardian_name' => 'Imran Khan',    'guardian_phone' => '99001 23456', 'status' => 'approved', 'applied_on' => '2025-09-18'],
            ['application_no' => 'APP-2604', 'applicant_name' => 'Rohan Das',    'class_applied' => 'Class 10-A', 'guardian_name' => 'Bikram Das',    'guardian_phone' => '97400 55667', 'status' => 'rejected', 'applied_on' => '2025-09-15'],
        ];
        foreach ($admissions as $a) {
            Admission::create($a);
        }

        // A day of periods for Class 10-A (extend for every class/day).
        $periods = [
            ['day' => 'Mon', 'period_no' => 1, 'subject' => 'Mathematics', 'teacher' => 'Priya Verma', 'start_time' => '09:00', 'end_time' => '09:45'],
            ['day' => 'Mon', 'period_no' => 2, 'subject' => 'Science',     'teacher' => 'Sunil Kumar', 'start_time' => '09:45', 'end_time' => '10:30'],
            ['day' => 'Mon', 'period_no' => 3, 'subject' => 'English',     'teacher' => 'Meena Joshi', 'start_time' => '10:45', 'end_time' => '11:30'],
            ['day' => 'Tue', 'period_no' => 1, 'subject' => 'Science',     'teacher' => 'Sunil Kumar', 'start_time' => '09:00', 'end_time' => '09:45'],
            ['day' => 'Tue', 'period_no' => 2, 'subject' => 'Mathematics', 'teacher' => 'Priya Verma', 'start_time' => '09:45', 'end_time' => '10:30'],
            ['day' => 'Wed', 'period_no' => 1, 'subject' => 'English',     'teacher' => 'Meena Joshi', 'start_time' => '09:00', 'end_time' => '09:45'],
        ];
        foreach ($periods as $p) {
            TimetableSlot::create(array_merge(['class_name' => 'Class 10-A'], $p));
        }

        $books = [
            ['title' => 'Wings of Fire',            'author' => 'A.P.J. Abdul Kalam', 'category' => 'Biography', 'total_copies' => 5, 'available_copies' => 3],
            ['title' => 'NCERT Mathematics X',      'author' => 'NCERT',              'category' => 'Textbook',  'total_copies' => 40, 'available_copies' => 31],
            ['title' => 'A Brief History of Time',  'author' => 'Stephen Hawking',    'category' => 'Science',   'total_copies' => 4, 'available_copies' => 4],
            ['title' => 'The Diary of a Young Girl','author' => 'Anne Frank',         'category' => 'Literature','total_copies' => 6, 'available_copies' => 2],
        ];
        foreach ($books as $b) {
            Book::create($b);
        }

        $vehicles = [
            ['bus_no' => 'TN-47-AB-1201', 'route_name' => 'Route 1 · North', 'driver' => 'Mani P.',   'capacity' => 42, 'status' => 'on_route',    'lat' => 10.9601, 'lng' => 78.0766, 'speed_kph' => 34, 'last_ping' => now()],
            ['bus_no' => 'TN-47-AB-1202', 'route_name' => 'Route 2 · East',  'driver' => 'Suresh K.', 'capacity' => 42, 'status' => 'on_route',    'lat' => 10.9711, 'lng' => 78.0921, 'speed_kph' => 18, 'last_ping' => now()],
            ['bus_no' => 'TN-47-AB-1203', 'route_name' => 'Route 3 · South', 'driver' => 'Ravi T.',   'capacity' => 36, 'status' => 'idle',        'lat' => 10.9500, 'lng' => 78.0600, 'speed_kph' => 0,  'last_ping' => now()],
            ['bus_no' => 'TN-47-AB-1204', 'route_name' => 'Route 4 · West',  'driver' => 'Anand M.',  'capacity' => 36, 'status' => 'maintenance', 'speed_kph' => 0],
        ];
        foreach ($vehicles as $v) {
            Vehicle::create($v);
        }

        $rooms = [
            ['block' => 'A', 'room_no' => '101', 'capacity' => 3, 'occupied' => 3, 'warden' => 'Mr. Ganesh'],
            ['block' => 'A', 'room_no' => '102', 'capacity' => 3, 'occupied' => 2, 'warden' => 'Mr. Ganesh'],
            ['block' => 'B', 'room_no' => '201', 'capacity' => 2, 'occupied' => 1, 'warden' => 'Mrs. Kavita'],
            ['block' => 'B', 'room_no' => '202', 'capacity' => 2, 'occupied' => 0, 'warden' => 'Mrs. Kavita'],
        ];
        foreach ($rooms as $r) {
            Room::create($r);
        }

        $announcements = [
            ['title' => 'PTM this Saturday',       'body' => 'Parent-teacher meeting on Saturday, 9 AM.', 'channel' => 'push',  'audience' => 'parents', 'recipients' => 1284, 'status' => 'sent', 'sent_at' => now()->subDay()],
            ['title' => 'Unit Test 2 results out',  'body' => 'Results are now visible in the parent app.', 'channel' => 'sms',   'audience' => 'parents', 'recipients' => 1284, 'status' => 'sent', 'sent_at' => now()->subDays(2)],
            ['title' => 'Staff meeting Monday',     'body' => 'All teaching staff, staff room, 8 AM.',      'channel' => 'email', 'audience' => 'staff',   'recipients' => 104,  'status' => 'sent', 'sent_at' => now()->subDays(3)],
        ];
        foreach ($announcements as $a) {
            Announcement::create($a);
        }

        $settings = [
            'school_name'   => 'Greenfield Public School',
            'academic_year' => '2025-26',
            'currency'      => 'INR',
            'plan'          => 'pro',
            'branch'        => 'Main',
        ];
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
