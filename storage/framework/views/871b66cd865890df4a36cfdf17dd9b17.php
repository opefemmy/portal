<?php $__env->startSection('title', 'Sessions'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Sessions</h4>
    <a href="<?php echo e(route('admin.sessions.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Session
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Semester</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($session->name); ?></td>
                        <td><?php echo e($session->semester ?? 'N/A'); ?></td>
                        <td><?php echo e($session->start_date?->format('d M Y')); ?></td>
                        <td><?php echo e($session->end_date?->format('d M Y')); ?></td>
                        <td>
                            <?php if($session->is_current): ?>
                            <span class="badge bg-success">Current</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!$session->is_current): ?>
                            <form method="POST" action="<?php echo e(route('admin.sessions.set_current', $session)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Set this as the current session">Set Current</button>
                            </form>
                            <?php endif; ?>
                            <a href="<?php echo e(route('admin.sessions.edit', $session)); ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this session">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">No sessions found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/sessions/index.blade.php ENDPATH**/ ?>