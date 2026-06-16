<?php $__env->startSection('title', 'Add School'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Add New School</h4>
</div>

<form action="<?php echo e(route('admin.schools.store')); ?>" method="POST">
    <?php echo csrf_field(); ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">School Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">School Code *</label>
                <input type="text" name="code" class="form-control" placeholder="e.g., SOC" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Create School
    </button>
    <a href="<?php echo e(route('admin.schools.index')); ?>" class="btn btn-secondary">Cancel</a>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/schools/create.blade.php ENDPATH**/ ?>