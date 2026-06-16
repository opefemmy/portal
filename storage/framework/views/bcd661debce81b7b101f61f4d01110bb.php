<?php $__env->startSection('title', 'Reports'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Reports Dashboard</h4>
</div>

<div class="row">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h5>Student Report</h5>
                <p class="text-muted">View all students by department, level, status</p>
                <a href="<?php echo e(route('admin.reports.students')); ?>" class="btn btn-primary">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                <h5>Results Report</h5>
                <p class="text-muted">View student results and grades</p>
                <a href="<?php echo e(route('admin.reports.results')); ?>" class="btn btn-success">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-dollar-sign fa-3x text-warning mb-3"></i>
                <h5>Payment Report</h5>
                <p class="text-muted">View payment records and status</p>
                <a href="<?php echo e(route('admin.reports.payments')); ?>" class="btn btn-warning">View Report</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-book fa-3x text-info mb-3"></i>
                <h5>Course Report</h5>
                <p class="text-muted">View courses and assignments</p>
                <a href="<?php echo e(route('admin.course-assignments.index')); ?>" class="btn btn-info">View Report</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/reports/index.blade.php ENDPATH**/ ?>