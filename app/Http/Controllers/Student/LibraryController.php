<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        // Check if library fee is required
        $libraryFeeRequired = SystemSetting::get('library_fee_required', 'false') === 'true';
        $libraryFeeAmount = SystemSetting::get('library_fee_amount', 0);

        // Get available books
        $books = Book::where('is_active', true)
            ->where('available', '>', 0)
            ->orderBy('title')
            ->paginate(20);

        // Get my loans with late fee calculation
        $myLoans = BookLoan::where('student_id', $student->id)
            ->whereIn('status', ['issued', 'overdue'])
            ->with('book')
            ->get();

        // Calculate late fees for each loan
        foreach ($myLoans as $loan) {
            if ($loan->book) {
                $lateFeePerDay = $loan->book->late_fee_per_day ?? SystemSetting::get('library_late_fee_per_day', 100);
                $maxBorrowDays = $loan->book->max_borrow_days ?? SystemSetting::get('library_max_borrow_days', 14);

                $dueDate = \Carbon\Carbon::parse($loan->due_date);
                $today = \Carbon\Carbon::now();

                if ($today->greaterThan($dueDate)) {
                    $penaltyDays = $today->diffInDays($dueDate);
                    $lateFee = $penaltyDays * $lateFeePerDay;

                    // Update the loan with calculated penalty
                    $loan->penalty_days = $penaltyDays;
                    $loan->late_fee = $lateFee;
                }
            }
        }

        // Get loan history
        $loanHistory = BookLoan::where('student_id', $student->id)
            ->where('status', 'returned')
            ->with('book')
            ->latest()
            ->limit(10)
            ->get();

        return view('student.library.index', compact('books', 'myLoans', 'loanHistory', 'libraryFeeRequired', 'libraryFeeAmount', 'student'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $books = Book::where('is_active', true)
            ->where('available', '>', 0)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('author', 'like', "%{$query}%")
                  ->orWhere('isbn', 'like', "%{$query}%")
                  ->orWhere('category', 'like', "%{$query}%");
            })
            ->orderBy('title')
            ->paginate(20);

        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $libraryFeeRequired = SystemSetting::get('library_fee_required', 'false') === 'true';
        $libraryFeeAmount = SystemSetting::get('library_fee_amount', 0);

        return view('student.library.index', compact('books', 'query', 'libraryFeeRequired', 'libraryFeeAmount', 'student'));
    }

    public function payLibraryFee()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $libraryFeeAmount = SystemSetting::get('library_fee_amount', 0);

        // Mark library fee as paid
        $student->update([
            'library_fee_paid' => true,
            'library_fee_paid_at' => now(),
        ]);

        return back()->with('success', 'Library fee of ₦' . number_format($libraryFeeAmount, 2) . ' paid successfully. You can now borrow books.');
    }

    public function borrowBook(Request $request, Book $book)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        // Check if library fee is required
        $libraryFeeRequired = SystemSetting::get('library_fee_required', 'false') === 'true';

        if ($libraryFeeRequired && !$student->library_fee_paid) {
            return back()->with('error', 'Please pay the library fee before borrowing books.');
        }

        // Check if book is available
        if ($book->available < 1) {
            return back()->with('error', 'This book is not available for borrowing.');
        }

        // Get max borrow days from settings
        $maxBorrowDays = $book->max_borrow_days ?? SystemSetting::get('library_max_borrow_days', 14);

        // Create loan
        BookLoan::create([
            'book_id' => $book->id,
            'student_id' => $student->id,
            'user_id' => auth()->id(),
            'issue_date' => now(),
            'due_date' => now()->addDays($maxBorrowDays),
            'status' => 'issued',
        ]);

        // Decrease available quantity
        $book->decrement('available');

        return back()->with('success', 'Book borrowed successfully. Due date: ' . now()->addDays($maxBorrowDays)->format('d M Y'));
    }
}