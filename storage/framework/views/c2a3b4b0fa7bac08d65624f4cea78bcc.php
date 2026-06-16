<?php $__env->startSection('title', 'Courses'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Courses</h4>
    <a href="<?php echo e(route('admin.courses.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Course
    </a>
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
                        <th>School</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($course->code); ?></td>
                        <td><?php echo e($course->title); ?></td>
                        <td><?php echo e($course->units); ?></td>
                        <td><?php echo e($course->school->code ?? 'N/A'); ?></td>
                        <td><?php echo e($course->department->code ?? 'N/A'); ?></td>
                        <td><?php echo e(\App\Models\Course::getLevelName($course->level)); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.courses.edit', $course)); ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this course">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.courses.destroy', $course)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this course" onclick="return confirm('Delete this course?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No courses found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/courses/index.blade.php ENDPATH**/ ?>