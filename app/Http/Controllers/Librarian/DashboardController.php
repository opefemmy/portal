<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\User;
use App\Models\Role;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('librarian.dashboard.view');

        $totalBooks = Book::count();
        // The `books` table has no `status` column — it has
        // `available` (int) + `is_active` (bool). The earlier
        // `where('status', 'available')` query was a latent 500
        // that surfaced only when the registration flow added
        // a librarian widget. Use `available > 0` so the dashboard
        // actually loads.
        $availableBooks = Book::where('available', '>', 0)->count();
        $borrowedBooks = BookLoan::where('status', 'borrowed')->count();
        $overdueLoans = BookLoan::where('status', 'borrowed')
            ->where('due_date', '<', now())->count();

        // Widget grid (4 stat tiles) — read from the registry via
        // DashboardResolver. Quick Actions card and Overdue Books
        // callout stay in the view's chrome.
        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('librarian.dashboard', compact(
            'widgets', 'totalBooks', 'availableBooks', 'borrowedBooks', 'overdueLoans'
        ));
    }

    public function books()
    {
        $this->requirePermission('librarian.books.view');

        $books = Book::latest()->paginate(20);
        return view('librarian.books', compact('books'));
    }

    public function createBook()
    {
        $this->requirePermission('librarian.books.create');

        return view('librarian.book-create');
    }

    public function storeBook(Request $request)
    {
        $this->requirePermission('librarian.books.create');

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
        $this->requirePermission('librarian.borrowing.view');

        $loans = BookLoan::with(['book', 'user'])->latest()->paginate(20);
        return view('librarian.loans', compact('loans'));
    }

    public function issueBook(Request $request)
    {
        $this->requirePermission('librarian.borrowing.issue');

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
        $this->requirePermission('librarian.borrowing.return');

        $loan->update([
            'return_date' => now(),
            'status' => 'returned',
        ]);

        $loan->book->update(['status' => 'available']);

        return back()->with('success', 'Book returned successfully');
    }
}