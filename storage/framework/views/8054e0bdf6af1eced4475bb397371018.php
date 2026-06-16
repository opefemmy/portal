<?php $__env->startSection('title', 'Register Courses'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Register Courses</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('student.courses.register')); ?>">
            <?php echo csrf_field(); ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $availableCourses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="courses[]" value="<?php echo e($course->id); ?>">
                            </td>
                            <td><?php echo e($course->code); ?></td>
                            <td><?php echo e($course->title); ?></td>
                            <td><?php echo e($course->units); ?></td>
                            <td><?php echo e(ucfirst($course->semester)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="text-center">No courses available for registration.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Register Selected Courses
            </button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/student/courses-register.blade.php ENDPATH**/ ?>