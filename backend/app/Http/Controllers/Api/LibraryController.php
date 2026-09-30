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
            $query->where('title', 'like', "%{$search}%")->orWhere('author', 'like', "%{$search}%");
        }
        return $query->orderBy('title')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => ['required', 'string'],
            'author'       => ['nullable', 'string'],
            'category'     => ['nullable', 'string'],
            'isbn'         => ['nullable', 'string'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ]);
        $data['available_copies'] = $data['total_copies'];
        return response()->json(Book::create($data), 201);
    }

    public function show(Book $book)
    {
        return response()->json($book);
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title'        => ['sometimes', 'string'],
            'author'       => ['nullable', 'string'],
            'category'     => ['nullable', 'string'],
            'isbn'         => ['nullable', 'string'],
            'total_copies' => ['sometimes', 'integer', 'min:1'],
            'available_copies' => ['sometimes', 'integer', 'min:0'],
        ]);
        $book->update($data);
        return response()->json($book);
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return response()->noContent();
    }

    public function issue(Request $request, Book $book)
    {
        $data = $request->validate([
            'borrower' => ['required', 'string'],
            'due_on'   => ['nullable', 'date'],
        ]);
        abort_if($book->available_copies < 1, 422, 'No copies available.');
        $issue = DB::transaction(function () use ($book, $data) {
            $book->decrement('available_copies');
            return BookIssue::create([
                'book_id'   => $book->id,
                'borrower'  => $data['borrower'],
                'issued_on' => now()->toDateString(),
                'due_on'    => $data['due_on'] ?? now()->addDays(14)->toDateString(),
                'status'    => 'issued',
            ]);
        });
        return response()->json($issue, 201);
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
