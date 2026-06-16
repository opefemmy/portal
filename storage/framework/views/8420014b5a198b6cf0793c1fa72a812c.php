<?php $__env->startSection('title', 'Schools'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Schools</h4>
    <a href="<?php echo e(route('admin.schools.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add School
    </a>
</div>

<div class="row">
    <?php $__empty_1 = true; $__currentLoopData = $schools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $school): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><?php echo e($school->name); ?></h5>
                <p class="text-muted"><?php echo e($school->code); ?></p>
                <p><?php echo e($school->departments->count()); ?> Departments</p>
                <a href="<?php echo e(route('admin.schools.edit', $school)); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit this school">Edit</a>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-12">
        <p class="text-center">No schools found.</p>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/schools/index.blade.php ENDPATH**/ ?>