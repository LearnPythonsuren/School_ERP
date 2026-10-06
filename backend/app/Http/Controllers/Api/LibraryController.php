<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query();
        if ($search = $request->query('search')) {
            $query->where(fn ($w) => $w->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('isbn', 'like', "%{$search}%"));
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        return $query->orderBy('title')->paginate($this->perPage($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:190'],
            'author'       => ['nullable', 'string', 'max:120'],
            'category'     => ['nullable', 'string', 'max:60'],
            'isbn'         => ['nullable', 'string', 'max:20'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);
        $data['available_copies'] = $data['total_copies'];
        return response()->json(Book::create($data), 201);
    }

    public function show(Book $book)
    {
        return response()->json($book);
    }

    /** Changing total_copies shifts available_copies by the same amount. */
    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:190'],
            'author'       => ['nullable', 'string', 'max:120'],
            'category'     => ['nullable', 'string', 'max:60'],
            'isbn'         => ['nullable', 'string', 'max:20'],
            'total_copies' => ['sometimes', 'integer', 'min:1'],
        ]);
        if (isset($data['total_copies'])) {
            $onLoan = $book->total_copies - $book->available_copies;
            abort_if($data['total_copies'] < $onLoan, 422, "{$onLoan} copies are on loan; total cannot be lower than that.");
            $data['available_copies'] = $data['total_copies'] - $onLoan;
        }
        $book->update($data);
        return response()->json($book);
    }

    public function destroy(Book $book)
    {
        abort_if(
            BookIssue::where('book_id', $book->id)->where('status', '!=', 'returned')->exists(),
            422, 'This book has copies on loan. Return them before deleting.'
        );
        $book->delete();
        return response()->noContent();
    }

    /** Loans list: ?status=issued|returned|overdue */
    public function issues(Request $request)
    {
        $today = now()->toDateString();
        $query = BookIssue::with('book:id,title,author');
        match ($request->query('status')) {
            'issued'   => $query->where('status', '!=', 'returned'),
            'returned' => $query->where('status', 'returned'),
            'overdue'  => $query->where('status', '!=', 'returned')->whereDate('due_on', '<', $today),
            default    => null,
        };

        return $query->latest('id')->paginate($this->perPage($request))->through(fn (BookIssue $i) => $i->toArray() + [
            'is_overdue' => $i->status !== 'returned' && $i->due_on && $i->due_on->toDateString() < $today,
        ]);
    }

    public function issue(Request $request, Book $book)
    {
        $data = $request->validate([
            'borrower' => ['required', 'string', 'max:120'],
            'due_on'   => ['nullable', 'date', 'after_or_equal:today'],
        ]);
        $issue = DB::transaction(function () use ($book, $data) {
            $book = Book::lockForUpdate()->find($book->id);
            abort_if($book->available_copies < 1, 422, 'No copies available.');
            $book->decrement('available_copies');
            return BookIssue::create([
                'book_id'   => $book->id,
                'borrower'  => $data['borrower'],
                'issued_on' => now()->toDateString(),
                'due_on'    => $data['due_on'] ?? now()->addDays(14)->toDateString(),
                'status'    => 'issued',
            ]);
        });
        return response()->json($issue->load('book:id,title'), 201);
    }

    public function returnBook(BookIssue $issue)
    {
        abort_if($issue->status === 'returned', 422, 'Already returned.');
        DB::transaction(function () use ($issue) {
            $issue->update(['status' => 'returned', 'returned_on' => now()->toDateString()]);
            $issue->book?->increment('available_copies');
        });
        return response()->json($issue);
    }
}
