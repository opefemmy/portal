<?php $__env->startSection('title', 'Departments'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Departments</h4>
    <a href="<?php echo e(route('admin.departments.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Department
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>School</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($dept->code); ?></td>
                        <td><?php echo e($dept->name); ?></td>
                        <td><?php echo e($dept->school->name ?? 'N/A'); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.departments.edit', $dept)); ?>" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">No departments found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/departments/index.blade.php ENDPATH**/ ?>