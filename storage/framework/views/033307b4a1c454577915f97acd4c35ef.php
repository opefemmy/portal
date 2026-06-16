<?php $__env->startSection('title', 'Student Dashboard'); ?>

<?php
$scrollingMessage = \App\Models\Setting::get('scrolling_message');
$loginNotification = session('login_notification');
$showPopup = session('show_popup');
$popupMessage = session('popup_message');
?>

<?php $__env->startSection('content'); ?>

<?php if(isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>


<?php if(isset($profileIncomplete) && $profileIncomplete && $student): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>Your profile is incomplete.
    <a href="<?php echo e(route('student.profile.edit')); ?>">Click here to complete it.</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>


<?php if($scrollingMessage): ?>
<div class="alert alert-info mb-3 p-0" style="background: #0dcaf0; color: white;">
    <marquee class="py-2" behavior="scroll" direction="left">
        <?php echo e($scrollingMessage); ?>

    </marquee>
</div>
<?php endif; ?>


<?php if($loginNotification): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo e($loginNotification); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <h4 class="mb-0">Student Dashboard</h4>
    <p class="text-muted mb-0">Welcome, <?php echo e(auth()->user()->name); ?><?php if($student): ?><span class="mx-2">|</span><?php echo e($student->matric_number ?? 'N/A'); ?><?php endif; ?></p>
</div>

<?php if(!$student): ?>
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Profile Not Set Up</h5>
    <p class="mb-0">Your student profile has not been configured. Please contact the registry/administrator for assistance.</p>
</div>
<?php else: ?>

<div class="row">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <h6 class="text-muted">Registered Courses</h6>
                <h2><?php echo e($registeredCourses->count()); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Payments</h6>
                <h2><?php echo e($payments->count()); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <a href="<?php echo e(route('student.courses')); ?>" class="btn btn-outline-primary w-100">
                    <i class="fas fa-book me-2"></i>My Courses
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo e(route('student.results')); ?>" class="btn btn-outline-success w-100">
                    <i class="fas fa-chart-line me-2"></i>My Results
                </a>
            </div>
            <div class="col-md-4">
                <a href="<?php echo e(route('student.payments')); ?>" class="btn btn-outline-warning w-100">
                    <i class="fas fa-dollar-sign me-2"></i>My Payments
                </a>
            </div>
        </div>
    </div>
</div>


<?php if($showPopup && $popupMessage): ?>
<div class="modal fade" id="postLoginPopup" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a237e, #6a1b9a); color: white;">
                <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Important Information</h5>
            </div>
            <div class="modal-body">
                <?php echo nl2br(e($popupMessage)); ?>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var popupModal = new bootstrap.Modal(document.getElementById('postLoginPopup'));
    popupModal.show();
});
</script>
<?php endif; ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/student/dashboard.blade.php ENDPATH**/ ?>