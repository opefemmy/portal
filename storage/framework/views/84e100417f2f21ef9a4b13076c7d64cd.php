<?php $__env->startSection('title', 'Create Course'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Create Course</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('admin.courses.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Units</label>
                    <input type="number" name="units" class="form-control" min="1" max="10" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control" required>
                        <option value="first">First</option>
                        <option value="second">Second</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-control" required>
                        <?php $__currentLoopData = $schools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $school): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($school->id); ?>"><?php echo e($school->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control" required>
                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($dept->id); ?>"><?php echo e($dept->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme</label>
                    <select name="programme_id" class="form-control" required>
                        <?php $__currentLoopData = $programmes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($prog->id); ?>"><?php echo e($prog->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Level</label>
                    <input type="number" name="level" class="form-control" min="1" max="6" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Course</button>
            <a href="<?php echo e(route('admin.courses.index')); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/courses/create.blade.php ENDPATH**/ ?>