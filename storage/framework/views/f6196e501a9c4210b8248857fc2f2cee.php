<?php $__env->startSection('title', 'Fees'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Fees Configuration</h4>
    <a href="<?php echo e(route('admin.fees.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Fee
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Level</th>
                        <th>Session</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $fees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($fee->name); ?></td>
                        <td><?php echo e($fee->payment_type ?? 'N/A'); ?></td>
                        <td>₦<?php echo e(number_format($fee->amount, 2)); ?></td>
                        <td><?php echo e($fee->school->name ?? 'All'); ?></td>
                        <td><?php echo e($fee->department->name ?? 'All'); ?></td>
                        <td><?php echo e($fee->programme->name ?? 'All'); ?></td>
                        <td><?php echo e($fee->levelDisplay); ?></td>
                        <td><?php echo e($fee->session->name ?? 'N/A'); ?></td>
                        <td><?php echo e($fee->due_date?->format('d M Y')); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.fees.edit', $fee)); ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this fee">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="<?php echo e(route('admin.fees.destroy', $fee)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fee?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this fee">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">No fees configured.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/fees/index.blade.php ENDPATH**/ ?>