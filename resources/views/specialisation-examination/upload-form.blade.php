@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Fachrichtungen</h1>
    <div class="row">
        <div class="col-md-6">
            <h2 class="mb-3">Import Fachrichtungen</h2>
            <form method="POST" action="{{route('specialisation-examination.import', ["label" => $label, "service" => $service])}}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Select file</label>
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file">
                    @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
        <div class="col-md-6">
            <h2 class="mb-3">Export Fachrichtungen</h2>
            <a href="{{route('specialisation-examination.export', ["label" => $label, "service" => $service])}}" class="btn btn-primary">Export</a>
        </div>
    </div>
    <div class="mb-3">
        <label for="specialisation" class="form-label">Fachrichtung</label>
        <select class="form-select" name="specialisation" id="specialisation">
            <option selected value="">--bitte auswählen--</option>
            @foreach ($specialisations as $s)
                <option value="{{$s->api_id}}">{{trans("$label.$service.specialisation.$s->api_id")}}</option>
            @endforeach
        </select>
        <div class="mb-3" id="examination_div" hidden>
            <label for="examination" class="form-label">Untersuchung</label>
            <select class="form-select" name="examination" id="examination">
                <option selected>--bitte auswählen--</option>
            </select>
        </div>
        <script>
            let examination_trans = {!! json_encode($examination_trans) !!}    
            let specialisation_examination = {!! json_encode($specialisation_examination)!!}
            let examination_note = {!! $examination_note !!}
            let specialisation_note = {!! $specialisation_note !!}

            document.querySelector("#specialisation").onchange = (e) => {
                let ops = specialisation_examination[e.target.value]
                document.querySelector("#examination_div").hidden = ops == undefined
                if (ops){
                    // ids of examinations for 'Other', 'Sonstige', 'Sonstiges'
                    let other_ids = Object.keys(examination_trans)
                    .filter(k => ['Other', 'Sonstige', 'Sonstiges']
                    .includes(examination_trans[k]))
                    .map(e => parseInt(e))

                    ops = ops.sort((a, b) => (examination_trans[a] > examination_trans[b]) ? 1 : -1)
                    // move the ids to end of array, so other is displayed as last option
                    other_ids.forEach(i => {
                        ops.push(ops.splice(ops.indexOf(i), 1)[0]);
                    })

                    document.querySelector("#examination").innerHTML = "<option value=''>--bitte auswählen--</option>"
                    ops.forEach(o => {
                        option = document.createElement('option')
                        option.innerText = examination_trans[o]
                        option.value = o
                        document.querySelector("#examination").appendChild(option)
                    });
                }

                // check if we should display a note for specialisation
                if (specialisation_note[e.target.value]) {
                    if (specialisation_note[e.target.value]["popup"]) {
                        alert(specialisation_note[e.target.value]['note'])
                    } else {
                        console.log(specialisation_note[e.target.value]['note'])
                    }
                }
            }

            document.querySelector("#examination").onchange = (e) => {
                // check if we should display a note for specialisation
                if (examination_note[e.target.value]) {
                    if (examination_note[e.target.value]["popup"]) {
                        alert(examination_note[e.target.value]['note'])
                    } else {
                        console.log(examination_note[e.target.value]['note'])
                    }
                }
            }
        </script>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col" colspan="3">
                        Fachrichtung
                    </th>
                </tr>
                <tr>
                    <th scope="col">DE</th>
                    <th scope="col">EN</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody class="">
                @foreach ($specialisations as $s)
                <tr class="">

                    <td scope="row">
                        <input type="text" class="form-control"
                        value="{{trans("$label.$service.specialisation.$s->api_id",[], "de")}}"
                        >
                        @if ($s->has_note)
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        {{trans("$label.$service.specialisation_note.$s->api_id",[], "de")}}
                        @endif
                    </td>
                    <td class="">
                        <input type="text" class="form-control"
                        value="{{trans("$label.$service.specialisation.$s->api_id",[], "en")}}"
                        >
                        @if ($s->has_note)
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        {{trans("$label.$service.specialisation_note.$s->api_id",[], "en")}}
                        @endif
                    </td>
                    <td class="">
                        {{ $s->id }},
                        {{ $s->api_id }}
                    </td>

                </tr>
                @if ($s->examinations->count() > 0)
                <tr>
                    <td colspan="3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col" colspan="3">
                                        Untersuchungen von {{trans("$label.$service.specialisation.$s->api_id")}}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="">
                                @foreach ($s->examinations as $e)
                                <tr class="">
                                    <td scope="row" class="">
                                        <input type="text" class="form-control" 
                                        value="{{trans("$label.$service.examination.$e->api_id",[], "de")}}"
                                        >
                                        @if ($e->has_note)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                        </svg>
                                        {{trans("$label.$service.examination_note.$e->api_id",[], "de")}}
                                        @endif

                                    </td>
                                    <td class="">
                                        <input type="text" class="form-control" 
                                        value="{{trans("$label.$service.examination.$e->api_id",[], "en")}}"
                                        >
                                        @if ($e->has_note)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill" viewBox="0 0 16 16">
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                        </svg>
                                        {{trans("$label.$service.examination_note.$e->api_id",[], "en")}}
                                        @endif
                                    </td>
                                    <td class="">
                                        {{ $e->id }},
                                        {{ $e->api_id }}
                                    </td>
                                </tr>    
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
            @endforeach
    
                <tr class="">
                    <td scope="row"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="">
                    <td scope="row"></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
