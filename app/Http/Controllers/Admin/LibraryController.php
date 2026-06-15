<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\Student;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function books(Request $request)
    {
        $query = Book::query();
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('author', 'like', "%{$request->search}%")
                  ->orWhere('isbn', 'like', "%{$request->search}%");
            });
        }
        $books = $query->latest()->paginate(20);
        return view('admin.library.books', compact('books'));
    }

    public function createBook()
    {
        return view('admin.library.book-create');
    }

    public function storeBook(Request $request)
    {
        $validated = $request->validate([
            'isbn' => 'nullable|string|max:20',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:2100',
            'category' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'shelf_location' => 'nullable|string|max:50',
        ]);
        $validated['available'] = $validated['quantity'];
        Book::create($validated);
        return redirect()->route('admin.library.books')->with('success', 'Book added successfully');
    }

    public function loans(Request $request)
    {
        $query = BookLoan::with(['book', 'student.user', 'issuedBy']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $loans = $query->latest()->paginate(20);
        $students = Student::with('user')->get();
        $books = Book::where('available', '>', 0)->get();
        return view('admin.library.loans', compact('loans', 'students', 'books'));
    }

    public function issueBook(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:students,id',
            'due_date' => 'required|date|after:today',
        ]);

        $book = Book::find($validated['book_id']);
        if ($book->available < 1) {
            return back()->with('error', 'Book not available');
        }

        BookLoan::create([
            'book_id' => $validated['book_id'],
            'student_id' => $validated['student_id'],
            'user_id' => auth()->id(),
            'issue_date' => now()->toDateString(),
            'due_date' => $validated['due_date'],
            'status' => 'issued',
        ]);

        $book->decrement('available');

        return back()->with('success', 'Book issued successfully');
    }

    public function returnBook(BookLoan $loan)
    {
        if ($loan->status === 'returned') {
            return back()->with('error', 'Book already returned');
        }

        $loan->update([
            'return_date' => now()->toDateString(),
            'status' => 'returned',
        ]);

        $loan->book->increment('available');

        return back()->with('success', 'Book returned successfully');
    }

    public function uploadBooks(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file));
        } else {
            // For Excel files, simple import
            $data = [];
        }

        $count = 0;
        foreach ($data as $row) {
            if (empty($row[0]) || $row[0] === 'Title') continue; // Skip header or empty

            Book::create([
                'isbn' => $row[0] ?? null,
                'title' => $row[1] ?? 'Unknown Title',
                'author' => $row[2] ?? 'Unknown Author',
                'publisher' => $row[3] ?? null,
                'year' => $row[4] ?? null,
                'category' => $row[5] ?? null,
                'quantity' => $row[6] ?? 1,
                'available' => $row[6] ?? 1,
                'shelf_location' => $row[7] ?? null,
            ]);
            $count++;
        }

        return redirect()->route('admin.library.books')->with('success', "$count books uploaded successfully");
    }
}