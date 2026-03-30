<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Inbox</h1>
            <span>Received emails</span>
          </div>
          <div>
              <a href="{{ route('mail.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-pencil me-1"></i> Compose
              </a>
              <a href="{{ route('mail.sent') }}" class="btn btn-secondary waves-effect waves-light">
                <i class="fi fi-rr-paper-plane me-1"></i> Sent
              </a>
          </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">From</th>
                    <th class="minw-200px">Subject</th>
                    <th class="minw-100px">Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($emails as $email)
                  <tr class="{{ !$email->read_at ? 'fw-bold' : '' }}">
                    <td>{{ $email->from->name }}</td>
                    <td>{{ $email->subject }}</td>
                    <td>{{ $email->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                            <ul class="dropdown-menu" style="">
                                <li><a class="dropdown-item" href="{{ route('mail.show', $email->id) }}">View</a></li>
                            </ul>
                        </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
              <div class="mt-3">
                  {{ $emails->links() }}
              </div>
            </div>
        </div>
    </div>
</x-app-layout>
