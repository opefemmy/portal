<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBooks = Book::count();
        $availableBooks = Book::where('status', 'available')->count();
        $borrowedBooks = BookLoan::where('status', 'borrowed')->count();
        $overdueLoans = BookLoan::where('status', 'borrowed')
            ->where('due_date', '<', now())->count();

        return view('librarian.dashboard', compact('totalBooks', 'availableBooks', 'borrowedBooks', 'overdueLoans'));
    }

    public function books()
    {
        $books = Book::latest()->paginate(20);
        return view('librarian.books', compact('books'));
    }

    public function createBook()
    {
        return view('librarian.book-create');
    }

    public function storeBook(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'edition' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:1',
            'category' => 'nullable|string|max:100',
            'shelf_location' => 'nullable|string|max:50',
        ]);

        Book::create($validated);

        return redirect()->route('librarian.books')->with('success', 'Book added successfully');
    }

    public function loans()
    {
        $loans = BookLoan::with(['book', 'user'])->latest()->paginate(20);
        return view('librarian.loans', compact('loans'));
    }

    public function issueBook(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_id' => 'required|exists:books,id',
            'due_date' => 'required|date|after:today',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        if ($book->status !== 'available') {
            return back()->with('error', 'Book is not available');
        }

        $loan = BookLoan::create([
            'user_id' => $validated['user_id'],
            'book_id' => $validated['book_id'],
            'loan_date' => now(),
            'due_date' => $validated['due_date'],
            'status' => 'borrowed',
        ]);

        $book->update(['status' => 'borrowed']);

        return back()->with('success', 'Book issued successfully');
    }

    public function returnBook(BookLoan $loan)
    {
        $loan->update([
            'return_date' => now(),
            'status' => 'returned',
        ]);

        $loan->book->update(['status' => 'available']);

        return back()->with('success', 'Book returned successfully');
    }
}