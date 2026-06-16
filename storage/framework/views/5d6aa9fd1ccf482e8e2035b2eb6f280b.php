<?php $__env->startSection('title', 'OnCourses - Course Assignments'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>OnCourses - Course Assignments</h4>
    <a href="<?php echo e(route('admin.course-assignments.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Assign Course to Lecturer
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Department</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($assignment->course->code ?? 'N/A'); ?></td>
                        <td><?php echo e($assignment->course->title ?? 'N/A'); ?></td>
                        <td><?php echo e($assignment->course->department->name ?? 'N/A'); ?></td>
                        <td><?php echo e($assignment->lecturer->name ?? 'N/A'); ?></td>
                        <td><?php echo e($assignment->session->name ?? 'N/A'); ?></td>
                        <td><?php echo e($assignment->semester); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.course-assignments.edit', $assignment)); ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this assignment">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.course-assignments.destroy', $assignment)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Remove this assignment" onclick="return confirm('Remove this course assignment?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No course assignments found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/course-assignments/index.blade.php ENDPATH**/ ?>