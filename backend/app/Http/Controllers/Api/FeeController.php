<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $query = FeeInvoice::with('student:id,name,class_name');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($class = $request->query('class')) {
            $query->where('class_name', $class);
        }
        if ($studentId = $request->query('student_id')) {
            $query->where('student_id', $studentId);
        }
        if ($s = $request->query('search')) {
            $query->where(fn ($w) => $w->where('invoice_no', 'like', "%{$s}%")
                ->orWhereHas('student', fn ($st) => $st->where('name', 'like', "%{$s}%")));
        }
        return $query->latest('id')->paginate($this->perPage($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_no'  => ['nullable', 'string', 'max:40', 'unique:fee_invoices,invoice_no'],
            'student_id'  => ['nullable', 'exists:students,id'],
            'class_name'  => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:190'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'due_on'      => ['nullable', 'date'],
        ]);
        $data['invoice_no'] ??= FeeInvoice::nextNumber('invoice_no', 'INV');
        $data['class_name'] ??= isset($data['student_id']) ? Student::find($data['student_id'])?->class_name : null;
        $data['paid_amount'] = 0;
        $data['status'] = 'due';

        $invoice = FeeInvoice::create($data);
        $this->syncStudent($invoice);

        return response()->json($invoice->load('student:id,name,class_name'), 201);
    }

    /** Raise the same invoice for every student in a class (e.g. term fees). */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'class_name'  => ['required', 'string', 'exists:students,class_name'],
            'description' => ['required', 'string', 'max:190'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'due_on'      => ['nullable', 'date'],
        ]);

        $created = DB::transaction(function () use ($data) {
            return Student::where('class_name', $data['class_name'])->get()->map(function (Student $s) use ($data) {
                $invoice = FeeInvoice::create($data + [
                    'invoice_no' => FeeInvoice::nextNumber('invoice_no', 'INV'),
                    'student_id' => $s->id, 'paid_amount' => 0, 'status' => 'due',
                ]);
                $s->refreshFeeStatus();
                return $invoice->invoice_no;
            });
        });

        return response()->json(['message' => count($created).' invoices created.', 'invoices' => $created], 201);
    }

    public function show(FeeInvoice $invoice)
    {
        return response()->json($invoice->load('student'));
    }

    public function update(Request $request, FeeInvoice $invoice)
    {
        $data = $request->validate([
            'invoice_no'  => ['sometimes', 'string', 'max:40', 'unique:fee_invoices,invoice_no,'.$invoice->id],
            'student_id'  => ['nullable', 'exists:students,id'],
            'class_name'  => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:190'],
            'amount'      => ['sometimes', 'numeric', 'min:1'],
            'due_on'      => ['nullable', 'date'],
        ]);
        $invoice->fill($data);
        $invoice->status = $invoice->computedStatus();
        $invoice->save();
        $this->syncStudent($invoice);

        return response()->json($invoice->load('student:id,name,class_name'));
    }

    /**
     * Record a payment. `amount` defaults to the full balance, so the old
     * one-click "Collect" still works; a smaller amount makes it partial.
     */
    public function collect(Request $request, FeeInvoice $invoice)
    {
        $balance = (float) $invoice->balance;
        abort_if($balance <= 0, 422, 'This invoice is already fully paid.');

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:1', 'max:'.$balance],
            'mode'   => ['nullable', 'in:cash,upi,card,bank,cheque,online'],
        ]);

        DB::transaction(function () use ($invoice, $data, $balance) {
            $invoice->paid_amount = (float) $invoice->paid_amount + (float) ($data['amount'] ?? $balance);
            $invoice->status = $invoice->computedStatus();
            $invoice->paid_on = now()->toDateString();
            $invoice->payment_mode = $data['mode'] ?? 'cash';
            $invoice->receipt_no = FeeInvoice::nextNumber('receipt_no', 'RCPT');
            $invoice->save();
            $this->syncStudent($invoice);
        });

        return response()->json($invoice->load('student:id,name,class_name'));
    }

    /** Everything a printable receipt needs, school header included. */
    public function receipt(FeeInvoice $invoice)
    {
        abort_if((float) $invoice->paid_amount <= 0, 422, 'No payment has been recorded on this invoice yet.');

        return response()->json([
            'school'  => Setting::pluck('value', 'key')->only(['school_name', 'address', 'phone', 'email', 'currency', 'academic_year']),
            'invoice' => $invoice->load('student'),
        ]);
    }

    public function destroy(FeeInvoice $invoice)
    {
        $student = $invoice->student;
        $invoice->delete();
        $student?->refreshFeeStatus();
        return response()->noContent();
    }

    private function syncStudent(FeeInvoice $invoice): void
    {
        $invoice->student?->refreshFeeStatus();
    }
}
