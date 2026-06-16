<?php $__env->startSection('title', 'Notification Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h4>Notification Settings</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('admin.notifications.update')); ?>">
            <?php echo csrf_field(); ?>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Scrolling Message (Marquee)</h5>
                <div class="mb-3">
                    <label for="scrolling_message" class="form-label">Scrolling Message for Students</label>
                    <textarea class="form-control <?php $__errorArgs = ['scrolling_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                              id="scrolling_message" name="scrolling_message" rows="2"
                              placeholder="Enter message to display as scrolling text on student dashboard"><?php echo e(old('scrolling_message', $scrolling_message)); ?></textarea>
                    <small class="text-muted">This message will appear as a scrolling marquee on student portal</small>
                    <?php $__errorArgs = ['scrolling_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Login Notification</h5>
                <div class="mb-3">
                    <label for="login_notification" class="form-label">Message to Show on Login</label>
                    <textarea class="form-control <?php $__errorArgs = ['login_notification'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                              id="login_notification" name="login_notification" rows="3"
                              placeholder="Enter notification to show when student logs in"><?php echo e(old('login_notification', $login_notification)); ?></textarea>
                    <small class="text-muted">This will be displayed as an alert after successful login</small>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Post-Login Popup</h5>
                <div class="mb-3">
                    <label for="post_login_message" class="form-label">Popup Message</label>
                    <textarea class="form-control <?php $__errorArgs = ['post_login_message'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                              id="post_login_message" name="post_login_message" rows="4"
                              placeholder="Enter information to show in popup after login"><?php echo e(old('post_login_message', $post_login_message)); ?></textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="show_post_login_popup" name="show_post_login_popup" value="1"
                           <?php echo e(old('show_post_login_popup', $show_post_login_popup) ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="show_post_login_popup">Enable Post-Login Popup</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>


<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Preview</h5>
    </div>
    <div class="card-body">
        <h6>Scrolling Message Preview:</h6>
        <?php if($scrolling_message): ?>
            <div class="alert alert-info">
                <marquee><?php echo e($scrolling_message); ?></marquee>
            </div>
        <?php else: ?>
            <p class="text-muted">No scrolling message configured</p>
        <?php endif; ?>

        <h6 class="mt-3">Login Notification Preview:</h6>
        <?php if($login_notification): ?>
            <div class="alert alert-success">
                <?php echo e($login_notification); ?>

            </div>
        <?php else: ?>
            <p class="text-muted">No login notification configured</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\HP\OneDrive\Documents\Portal\resources\views/admin/notifications/index.blade.php ENDPATH**/ ?>