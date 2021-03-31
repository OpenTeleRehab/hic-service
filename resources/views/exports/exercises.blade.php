<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Images</th>
        <th>Set default sets and reps</th>
        <th>Sets</th>
        <th>Reps</th>
        <th>Collect sets and reps</th>
        <th>Collect pain level</th>
        <th>Categories</th>
        <th>Dynamic fields</th>
    </tr>
    </thead>
    <tbody>
    @foreach($exercises as $exercise)
        <tr>
            <td>{{ $exercise->id }}</td>
            <td>{{ $exercise->title }}</td>
            <td>
                @foreach($exercise->files()->get() as $file)
                    {{ env("APP_URL") . '/api/file/' . $file->id }}{{ $loop->remaining ? ',' : '' }}
                @endforeach
            </td>
            <td>{{ $exercise->sets > 0 ? 'yes' : 'no' }}</td>
            <td>{{ $exercise->sets }}</td>
            <td>{{ $exercise->reps }}</td>
            <td>{{ $exercise->include_feedback ? 'yes' : 'no' }}</td>
            <td>{{ $exercise->get_pain_level ? 'yes' : 'no' }}</td>
            <td>
                @foreach($exercise->categories()->get() as $category)
                    @php
                        $treeTitles = [];
                        $treeCategories = \App\Helpers\CategoryHelper::getRootTreeCategories($category);
                        foreach ($treeCategories as $nodeCategory) {
                            $treeTitles[] = $nodeCategory->title;
                        }
                    @endphp
                    {{ implode('->', $treeTitles) }}{{ $loop->remaining ? ',' : '' }}
                @endforeach
            </td>
            <td>
                @php
                    $additionalFields = json_decode($exercise->additional_fields) ?? [];
                @endphp

                @foreach($additionalFields as $additionalField)
                    {{ $additionalField->field }}: {{ $additionalField->value }}{{ $loop->remaining ? ',' : '' }}
                @endforeach
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
