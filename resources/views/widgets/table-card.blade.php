{{--
    Table widget rendered by DashboardResolver.

    Expected `$data` shape:
        title:   string — header text
        icon:    font-awesome class for the header icon
        headers: array of column titles
        rows:    array of arrays, each inner array is a row
        colspan: int — used by the empty-state row
        empty_message: string
--}}
@php
    $title   = $data['title']   ?? '';
    $icon    = $data['icon']    ?? 'fas fa-table';
    $headers = $data['headers'] ?? [];
    $rows    = $data['rows']    ?? [];
    $colspan = $data['colspan'] ?? max(1, count($headers));
    $empty   = $data['empty_message'] ?? 'No data';
@endphp
<div class="col-lg-6 mb-4">
    <div class="card h-100">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="{{ $icon }} me-2"></i>{{ $title }}
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach($headers as $h)
                                <th>{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}" class="text-center text-muted py-4">{{ $empty }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
