<?php $__env->startSection('title', 'My Courses'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Courses</h4>
    <div class="d-flex gap-2">
        <a href="<?php echo e(route('student.courses.print')); ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-print me-2"></i>Print Form
        </a>
        <a href="<?php echo e(route('student.courses.register')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Register Courses
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Units</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($course->course->code); ?></td>
                        <td><?php echo e($course->course->title); ?></td>
                        <td><?php echo e($course->course->units); ?></td>
                        <td><?php echo e(ucfirst($course->semester)); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($course->status === 'registered' ? 'success' : 'danger'); ?>">
                                <?php echo e(ucfirst($course->status)); ?>

                            </span>
                        </td>
                        <td>
                            <?php if($course->status === 'registered'): ?>
                            <form method="POST" action="<?php echo e(route('student.courses.drop', $course)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Drop this course?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">No courses registered yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/student/courses.blade.php ENDPATH**/ ?>