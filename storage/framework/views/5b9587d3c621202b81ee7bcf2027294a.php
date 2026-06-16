<?php $__env->startSection('title', 'Course Registration Report'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Course Registration Report</h4>
    <div>
        <a href="<?php echo e(route('admin.course-registrations.export', request()->query())); ?>" class="btn btn-success">
            <i class="fas fa-file-export me-2"></i>Export CSV
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="session_id" class="form-label">Session</label>
                <select class="form-select" id="session_id" name="session_id">
                    <option value="">All Sessions</option>
                    <?php $__currentLoopData = $sessions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($session->id); ?>" <?php echo e(request('session_id') == $session->id ? 'selected' : ''); ?>>
                            <?php echo e($session->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <option value="First" <?php echo e(request('semester') == 'First' ? 'selected' : ''); ?>>First</option>
                    <option value="Second" <?php echo e(request('semester') == 'Second' ? 'selected' : ''); ?>>Second</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="registered" <?php echo e(request('status') == 'registered' ? 'selected' : ''); ?>>Registered</option>
                    <option value="unsubmitted" <?php echo e(request('status') == 'unsubmitted' ? 'selected' : ''); ?>>Unsubmitted</option>
                    <option value="dropped" <?php echo e(request('status') == 'dropped' ? 'selected' : ''); ?>>Dropped</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Units</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $registrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($reg->student->matric_number ?? 'N/A'); ?></td>
                        <td><?php echo e($reg->student->user->name ?? 'N/A'); ?></td>
                        <td><?php echo e($reg->course->code ?? 'N/A'); ?></td>
                        <td><?php echo e($reg->course->title ?? 'N/A'); ?></td>
                        <td><?php echo e($reg->course->units ?? 0); ?></td>
                        <td><?php echo e($reg->session->name ?? 'N/A'); ?></td>
                        <td><?php echo e($reg->semester); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($reg->status === 'registered' ? 'success' : ($reg->status === 'unsubmitted' ? 'warning' : 'danger')); ?>">
                                <?php echo e(ucfirst($reg->status)); ?>

                            </span>
                        </td>
                        <td>
                            <?php if($reg->status === 'registered'): ?>
                            <form method="POST" action="<?php echo e(route('admin.course-registrations.unsubmit', $reg)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Unsubmit this course" onclick="return confirm('Unsubmit this course registration?')">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            <?php elseif($reg->status === 'unsubmitted'): ?>
                            <form method="POST" action="<?php echo e(route('admin.course-registrations.resubmit', $reg)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Resubmit this course">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4">No course registrations found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/course-registrations/index.blade.php ENDPATH**/ ?>