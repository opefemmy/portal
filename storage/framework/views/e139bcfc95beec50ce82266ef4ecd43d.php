<?php $__env->startSection('title', 'My Timetable'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>My Timetable</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Venue</th>
                        <th>Lecturer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $timetables; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $timetable): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e(ucfirst($timetable->day)); ?></td>
                        <td><?php echo e($timetable->start_time); ?> - <?php echo e($timetable->end_time); ?></td>
                        <td><?php echo e($timetable->course->code ?? 'N/A'); ?></td>
                        <td><?php echo e($timetable->venue); ?></td>
                        <td><?php echo e($timetable->lecturer->name ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No timetable available.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/student/timetable.blade.php ENDPATH**/ ?>