<?php $__env->startSection('title', 'Applicant Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Applicant Dashboard</h4>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <?php if($applicant): ?>
                <h5>Application Status:
                    <span class="badge bg-<?php echo e($applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning')); ?>">
                        <?php echo e(ucfirst($applicant->status)); ?>

                    </span>
                </h5>
                <p>Application Number: <?php echo e($applicant->application_number); ?></p>
                <a href="<?php echo e(route('applicant.application')); ?>" class="btn btn-primary">View Application</a>
                <?php else: ?>
                <p>You haven't submitted an application yet.</p>
                <a href="<?php echo e(route('applicant.apply')); ?>" class="btn btn-primary">Apply Now</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/applicant/dashboard.blade.php ENDPATH**/ ?>