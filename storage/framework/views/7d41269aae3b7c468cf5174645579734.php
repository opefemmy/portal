<?php $__env->startSection('title', 'Grades'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Grading System</h4>
    <a href="<?php echo e(route('admin.grades.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Grade
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Score Range</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Remark</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $grades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($grade->min_score); ?> - <?php echo e($grade->max_score); ?></td>
                        <td><?php echo e($grade->grade); ?></td>
                        <td><?php echo e($grade->grade_point); ?></td>
                        <td><?php echo e($grade->remark); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.grades.edit', $grade)); ?>" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No grades configured.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/grades/index.blade.php ENDPATH**/ ?>