<?php $__env->startSection('title', 'My Application'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>My Application</h4>
</div>

<div class="card">
    <div class="card-body">
        <h5>Application Details</h5>
        <table class="table">
            <tr>
                <th>Application Number:</th>
                <td><?php echo e($applicant->application_number); ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-<?php echo e($applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning')); ?>">
                        <?php echo e(ucfirst($applicant->status)); ?>

                    </span>
                </td>
            </tr>
            <tr>
                <th>School:</th>
                <td><?php echo e($applicant->school->name); ?></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo e($applicant->department->name); ?></td>
            </tr>
            <tr>
                <th>Programme:</th>
                <td><?php echo e($applicant->programme->name); ?></td>
            </tr>
            <tr>
                <th>Session:</th>
                <td><?php echo e($applicant->session->name); ?></td>
            </tr>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/applicant/application.blade.php ENDPATH**/ ?>