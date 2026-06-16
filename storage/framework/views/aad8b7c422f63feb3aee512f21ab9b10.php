<?php $__env->startSection('title', 'Users'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Users</h4>
    <div>
        <a href="<?php echo e(route('admin.users.upload')); ?>" class="btn btn-success">
            <i class="fas fa-upload me-2"></i>Upload Users
        </a>
        <a href="<?php echo e(route('admin.users.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add User
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($user->name); ?></td>
                        <td><?php echo e($user->email); ?></td>
                        <td><?php echo e($user->role->name ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($user->is_active ? 'success' : 'danger'); ?>">
                                <?php echo e($user->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View user details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this user">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this user" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/users/index.blade.php ENDPATH**/ ?>