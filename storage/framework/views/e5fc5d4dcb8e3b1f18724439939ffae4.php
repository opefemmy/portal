<?php $__env->startSection('title', 'Library Books'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Library Books</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Books
        </button>
        <a href="<?php echo e(route('admin.library.books.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Book
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="Search by title, author, ISBN..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Available</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($book->isbn ?? 'N/A'); ?></td>
                    <td><?php echo e($book->title); ?></td>
                    <td><?php echo e($book->author); ?></td>
                    <td><?php echo e($book->category ?? 'N/A'); ?></td>
                    <td><span class="badge bg-<?php echo e($book->available > 0 ? 'success' : 'danger'); ?>"><?php echo e($book->available); ?></span></td>
                    <td><?php echo e($book->quantity); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="text-center py-4">No books found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo e($books->links()); ?>

    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Books (CSV/Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?php echo e(route('admin.library.books.upload')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" class="form-control" name="file" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">Columns: ISBN, Title, Author, Publisher, Year, Category, Quantity, Shelf Location</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/library/books.blade.php ENDPATH**/ ?>