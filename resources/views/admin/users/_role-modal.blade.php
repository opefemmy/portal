{{--
    Reusable modal for editing a single user's role memberships.

    Variables:
      $user  \App\Models\User
      $roles \Illuminate\Support\Collection<\App\Models\Role>

    Submits PUT admin.users.roles.update with:
      role_ids[]       = all selected role IDs
      primary_role_id  = the role that becomes the user's primary

    The primary role's checkbox must remain ticked — the controller
    rejects a primary that's not in role_ids[]. The radio lets the
    admin promote a different role to primary.
--}}
@php
    $currentRoleIds = $user->roles->pluck('id')->all();
    $primaryRoleId  = $user->role_id;
@endphp

<div class="modal fade" id="roleModal{{ $user->id }}" tabindex="-1" aria-labelledby="roleModalLabel{{ $user->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.roles.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="roleModalLabel{{ $user->id }}">
                        <i class="fas fa-user-shield me-2"></i>
                        Manage Roles — {{ $user->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Tick every role <strong>{{ $user->name }}</strong>
                        should hold. Use the radio to choose which one is the
                        <strong>primary role</strong> — that's the role used
                        for the post-login redirect and role middleware.
                    </div>

                    @php
                        $errorBag = $errors ?? (session()->has('errors') ? session('errors') : null);
                    @endphp
                    @if($errorBag && method_exists($errorBag, 'any') && $errorBag->any())
                        <div class="alert alert-danger small">
                            {{ $errorBag->first() }}
                        </div>
                    @endif

                    <div class="row">
                        @forelse($roles as $role)
                            @php
                                $isCurrent = in_array($role->id, $currentRoleIds, true);
                                $isPrimary = $role->id === $primaryRoleId;
                            @endphp
                            <div class="col-md-6 mb-2">
                                <div class="border rounded p-2 h-100 {{ $isPrimary ? 'bg-light border-primary' : '' }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input mt-0 role-toggle"
                                               type="checkbox"
                                               name="role_ids[]"
                                               value="{{ $role->id }}"
                                               id="role-{{ $user->id }}-{{ $role->id }}"
                                               data-role-id="{{ $role->id }}"
                                               {{ $isCurrent ? 'checked' : '' }}>
                                        <label class="form-check-label flex-grow-1" for="role-{{ $user->id }}-{{ $role->id }}">
                                            <strong class="d-block">{{ $role->name }}</strong>
                                            <code class="small text-muted">{{ $role->slug }}</code>
                                        </label>
                                        <div class="form-check">
                                            <input class="form-check-input primary-radio"
                                                   type="radio"
                                                   name="primary_role_id"
                                                   value="{{ $role->id }}"
                                                   id="primary-{{ $user->id }}-{{ $role->id }}"
                                                   {{ $isPrimary ? 'checked' : '' }}
                                                   {{ $isCurrent ? '' : 'disabled' }}
                                                   title="Make primary">
                                            <label class="form-check-label small text-muted" for="primary-{{ $user->id }}-{{ $role->id }}">
                                                Primary
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted text-center py-3">
                                No roles available.
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    // Inside each role modal, keep the "Primary" radio in sync with
    // the role's checkbox: ticking a checkbox enables its radio,
    // un-ticking disables it (so a role that's not selected can't be
    // picked as primary). This is purely a UX nicety — the server
    // also enforces the rule.
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('role-toggle')) return;
        const roleId = e.target.dataset.roleId;
        const modal = e.target.closest('.modal');
        if (!modal) return;
        const radio = modal.querySelector('.primary-radio[value="' + roleId + '"]');
        if (!radio) return;
        radio.disabled = !e.target.checked;
        if (!e.target.checked && radio.checked) {
            // Unchecking the primary role without picking a new one
            // would leave the form with no primary selected. Auto-
            // promote the first still-checked role so the server's
            // "primary must be in role_ids" check passes.
            const stillChecked = modal.querySelector('.role-toggle:checked');
            if (stillChecked) {
                const newPrimary = modal.querySelector('.primary-radio[value="' + stillChecked.dataset.roleId + '"]');
                if (newPrimary) newPrimary.checked = true;
            }
        }
        if (e.target.checked && !modal.querySelector('.primary-radio:checked')) {
            radio.checked = true;
        }
    });
</script>
@endpush
@endonce
