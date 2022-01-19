<table>
    <thead>
    <tr>
        <th>Customer</th>
        <th>Error Fields</th>
        <th>Errors</th>
    </tr>
    </thead>
    <tbody>
    @foreach($errors as $customer => $error)
        <tr>
            <td>{{ $customer }}</td>
            <td>{{ join(',',array_keys($error)) }}</td>
            <td>{{ join(',', \Illuminate\Support\Arr::flatten($error) ) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
