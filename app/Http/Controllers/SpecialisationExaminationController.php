<?php

namespace App\Http\Controllers;

use App\Models\Examination;
use App\Models\ExaminationSpecialisation;
use App\Models\Specialisation;
use App\Models\SpecialisationNote;
use Illuminate\Http\Request;
use Spatie\TranslationLoader\LanguageLine;
use Spatie\TranslationLoader\TranslationServiceProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpecialisationExaminationController extends Controller
{
    public function importForm(String $label, String $service)
    {
        $specialisations = Specialisation::with('examinations')->where([
            'insurence_label' => $label,
            'service' => $service,
            // 'api_id' => 34,
        ])->get();

        $examination_trans = $specialisations->filter(function ($i) {return $i->examinations->count() > 0;})
        ->map(function ($i) {return [$i->examinations->pluck('api_id')];})->collapse()->collapse()->unique()->sort()
        ->mapWithKeys(function ($i) use ($label, $service) {return [$i => trans("$label.$service.examination.$i")];})
        ->toArray();

        $specialisation_examination = $specialisations->filter(function ($i) {return $i->examinations->count() > 0;})
        ->mapWithKeys(function ($i) {return [$i->api_id => $i->examinations->pluck('api_id')];})
        ->toArray();

        return view('specialisation-examination.upload-form', [
            'label' => $label,
            'service' => $service,
            'specialisations' => $specialisations,
            'examination_trans' => $examination_trans,
            'specialisation_examination' => $specialisation_examination,
        ]);
    }

    public function export(String $label, String $service)
    {
        $specialisations = Specialisation::with('examinations')->where([
            'insurence_label' => $label,
            'service' => $service,
            // 'api_id' => 34,
        ])->get();

        $workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $workbook->removeSheetByIndex(0);
        foreach (['de', 'en'] as $lang) {
            $row_index = 1;
            // create new sheet for export data, using language as sheet name
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($workbook, $lang);
            $sheet->fromArray([
                "Fachrichtung_ID","Fachrichtung","Fachrichtung_Hinweis","Fachrichtung_Hinweis_PopUp",
                "Untersuchung_ID","Untersuchung","Fachrichtung_Untersuchung_Hinweis","Fachrichtung_Untersuchung_Hinweis_PopUp"
            ], NULL, "A$row_index");
            $row_index = 2;
            foreach ($specialisations as $s) {
                $row = [
                    $s->api_id,
                    trans("$label.$service.specialisation.{$s->api_id}", [], $lang),
                    $s->has_note ? trans("$label.$service.specialisation_note.{$s->api_id}", [], $lang) : NULL, //note
                    $s->has_note_popup ? 'ja' : NULL //note is popup
                ];

                if ($s->examinations->count() == 0) {
                    $sheet->fromArray($row, NULL, "A$row_index");
                    $row_index = $row_index +1;
                    continue;
                }
                foreach ($s->examinations as $e ) {
                    $sheet->fromArray(array_merge(
                        $row,
                        [
                            $e->api_id,
                            trans("$label.$service.examination.{$e->api_id}", [], $lang),
                            $e->has_note ? trans("$label.$service.examination_note.{$e->api_id}", [], $lang) : NULL, //note
                            $e->has_note_popup ? 'ja' : NULL, //note is popup
                        ]
                    ), NULL, "A$row_index");
                    $row_index = $row_index +1;
                }
            }
            // add sheet to the workbook
            $workbook->addSheet($sheet);
        }
        // $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
        // $writer->save("fachrichtung_{$label}_{$service}_export.xlsx");
        $response_headers = [
            'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;',
            'Content-Disposition' => "attachment; filename=fachrichtung_{$label}_{$service}_export.xlsx",
            'Pragma' => 'no-cache',
            'Expires' => 0,
        ];
        $callback = function () use ($workbook) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook);
            $resource = fopen('php://output', 'w');
            $writer->save($resource);
            fclose($resource);
        };
        return response()->stream($callback, 200, $response_headers);
    }

    public function import(String $label, String $service)
    {
        request()->validate([
            'file' => 'required|file:xlsx'
        ],
        [
            'file' => 'keine gültige XLSX Datei ausgewählt'
        ]
    );
        $file = request()->file('file');


        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setReadDataOnly(true);
        $data = $reader->load($file);

        $groups = [
            'specialisation' => [],
            'examination' => [],
        ];
        $relations = [];
        $notes = [];
        foreach (['de', 'en'] as $lang) {
            if (! in_array($lang, $data->getSheetNames())){ continue; }
            foreach (array_slice($data->getSheetByName($lang)->toArray(), 1) as $row) {
                $specialisation_api_id = $row[0];
                $specialisation_note = $row[2];
                $specialisation_note_popup = strtolower($row[3]);
                $specialisation = $row[1];
                $examination_api_id = $row[4];
                $examination = $row[5];
                $examination_note = $row[6];
                $examination_note_popup = strtolower($row[7]);

                // skip rows without specialisations => invalid data
                if (!$specialisation_api_id) { continue; }
                
                $groups['specialisation'][$specialisation_api_id][$lang] = $specialisation;

                if ($specialisation_note){
                    $notes["specialisation"][$specialisation_api_id][$lang] = $specialisation_note;
                    $notes["specialisation"][$specialisation_api_id]['popup'] = in_array($specialisation_note_popup, ["yes","ja"]);
                }
                
                // if no examination go to next row
                if (!$examination_api_id) { continue; }
                
                $groups['examination'][$examination_api_id][$lang] = $examination;

                if ($examination_note){
                    $notes["examination"][$examination_api_id][$lang] = $examination_note;
                    $notes["examination"][$examination_api_id]['popup'] = in_array($examination_note_popup, ["yes","ja"]);
                }

                if (!isset($relations[$specialisation_api_id])) {
                    $relations[$specialisation_api_id] = [$examination_api_id];
                } elseif (! in_array($examination_api_id, $relations[$specialisation_api_id])){
                    $relations[$specialisation_api_id][] = $examination_api_id;
                }
            }
        }

        // get translations from Database Model
        $translations = [
            "examination" => LanguageLine::where("group", $label)->where("key", "like", "$service.examination.%")->get(),
            "specialisation" => LanguageLine::where("group", $label)->where("key", "like", "$service.specialisation.%")->get(),
            "specialisation_note" => LanguageLine::where("group", $label)->where("key", "like", "$service.specialisation_note.%")->get(),
            "examination_note" => LanguageLine::where("group", $label)->where("key", "like", "$service.examination_note.%")->get(),
        ];

        $examinations = Examination::where([
            "insurence_label" => $label, 
            "service" => $service,
        ])->get();

        foreach ($groups['examination'] as $examination_api_id => $trans) {
            $e = $examinations->firstWhere(
                "api_id", $examination_api_id,
                ) ?: new Examination();
            $e->insurence_label = $label;
            $e->api_id = $examination_api_id;
            $e->service = $service;

            if (array_key_exists($examination_api_id, $notes['examination'])){
                // mark note usage
                $e->has_note = true;
                $e->has_note_popup = $notes["examination"][$examination_api_id]['popup'];
                
                // store translation of note
                $tn = $translations['examination']->firstWhere("key", "$service.examination_note.$examination_api_id") ?: new LanguageLine();
                $tn->group = $label;
                $tn->key = "$service.examination_note.$examination_api_id";
                $tn->text = collect(["de", "en"])
                    ->mapWithKeys(function ($e) use ($notes, $examination_api_id) {
                        return [$e =>  $notes["examination"][$examination_api_id][$e]];
                    });
                $tn->save();
            }
            $e->save();
            
            $t = $translations['examination']->firstWhere("key", "$service.examination.$examination_api_id") ?: new LanguageLine();
            $t->group = $label;
            $t->key = "$service.examination.$examination_api_id";
            $t->text = $trans;
            $t->save();
        }
        
        // reload examinations after inserts, need api_id and id for relation mapping
        $examinations = Examination::where([
            "insurence_label" => $label, 
            "service" => $service,
        ])->get();
            
        $specialisations = Specialisation::where([
            "insurence_label" => $label, 
            "service" => $service,
        ])->with('examinations')->get();

        foreach ($groups['specialisation'] as $specialisation_api_id => $trans) {
            $s = $specialisations->firstWhere(
                "api_id", $specialisation_api_id,
            ) ?: new Specialisation();
            $s->insurence_label = $label;
            $s->api_id = $specialisation_api_id;
            $s->service = $service;

            if (array_key_exists($specialisation_api_id, $notes['specialisation'])){
                // mark note usage
                $s->has_note = true;
                $s->has_note_popup = $notes["specialisation"][$specialisation_api_id]['popup'];
                
                // store translation of note
                $tn = $translations['specialisation']->firstWhere("key", "$service.specialisation_note.$specialisation_api_id") ?: new LanguageLine();
                $tn->group = $label;
                $tn->key = "$service.specialisation_note.$specialisation_api_id";
                $tn->text = collect(["de", "en"])
                    ->mapWithKeys(function ($e) use ($notes, $specialisation_api_id) {
                        return [$e =>  $notes["specialisation"][$specialisation_api_id][$e]];
                    });
                $tn->save();
            }

            $s->save();
            
            if (isset($relations[$specialisation_api_id])){
                // get real ids of examinations, based on service, label and api_id (we got from excel)
                $related_examinations = $examinations->whereIn('api_id', $relations[$specialisation_api_id])->pluck('id')->unique();
                $s->examinations()->sync($related_examinations);
            }

            $t = $translations['specialisation']->firstWhere("key", "$service.specialisation.$specialisation_api_id") ?: new LanguageLine();
            $t->group = $label;
            $t->key = "$service.specialisation.$specialisation_api_id";
            $t->text = $trans;
            $t->save();
        }

        // find entrys in DB which are not contained in Excel (based on id in excel not in api_id DB) 
        Examination::where([
            "insurence_label" => $label, 
            "service" => $service,
        ])->whereNotIn('api_id', array_keys($groups['examination']))->delete();
        Specialisation::where([
            "insurence_label" => $label, 
            "service" => $service,
        ])->whereNotIn('api_id', array_keys($groups['specialisation']))->delete();

        return redirect()->back()->with('success', 'Fachrichtungen importiert');
    }
 
}