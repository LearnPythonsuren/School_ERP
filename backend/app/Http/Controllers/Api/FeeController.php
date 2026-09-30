<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $query = FeeInvoice::with('student');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        return $query->latest()->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => ['required', 'string', 'unique:fee_invoices,invoice_no'],
            'student_id' => ['nullable', 'exists:students,id'],
            'class_name' => ['nullable', 'string'],
            'amount'     => ['required', 'numeric', 'min:0'],
            'status'     => ['nullable', 'in:paid,partial,due'],
        ]);
        return response()->json(FeeInvoice::create($data), 201);
    }

    public function show(FeeInvoice $invoice)
    {
        return response()->json($invoice->load('student'));
    }

    public function update(Request $request, FeeInvoice $invoice)
    {
        $data = $request->validate([
            'invoice_no' => ['sometimes', 'string', 'unique:fee_invoices,invoice_no,'.$invoice->id],
            'student_id' => ['nullable', 'exists:students,id'],
            'class_name' => ['nullable', 'string'],
            'amount'     => ['sometimes', 'numeric', 'min:0'],
            'status'     => ['sometimes', 'in:paid,partial,due'],
            'paid_on'    => ['nullable', 'date'],
        ]);
        $invoice->update($data);
        return response()->json($invoice);
    }

    /** Record a payment against an invoice. */
    public function collect(FeeInvoice $invoice)
    {
        $invoice->update(['status' => 'paid', 'paid_on' => now()->toDateString()]);
        return response()->json($invoice);
    }

    public function destroy(FeeInvoice $invoice)
    {
        $invoice->delete();
        return response()->noContent();
    }
}
